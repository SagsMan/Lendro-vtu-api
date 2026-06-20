<?php
/**
 * Background Worker: Process Transaction Queue
 *
 * Run via cron every 1-2 minutes on shared hosting:
 *   * * * * * php /home/tracsmda/lendro/api/v1/workers/process_transactions.php >> /dev/null 2>&1
 *
 * How it works:
 *  1. Claims one PENDING queue job at a time (SKIP LOCKED prevents double-processing)
 *  2. Loads the transaction + service + provider list for that job
 *  3. Builds a clean payload and tries each active provider in priority order
 *  4. On success  → marks the transaction and queue item as completed
 *  5. On pending  → schedules reconciliation (provider is still processing)
 *  6. On all fail → refunds the user's wallet and marks as failed
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../ServiceManager.php';
require_once __DIR__ . '/../ProviderFactory.php';
require_once __DIR__ . '/../ProviderResponseNormalizer.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/QueueHelper.php';

$workerToken = 'wkr_' . uniqid();
$processed   = 0;
$maxBatch    = 20; // max jobs per cron run — prevents runaway

echo "[{$workerToken}] Transaction worker started.\n";

while ($processed < $maxBatch) {
    try {
        // ── Claim one pending job atomically ──────────────────────────────
        $db->beginTransaction();
        $stmt = $db->prepare(
            "SELECT * FROM transaction_queue
              WHERE status = 'pending'
                AND (next_retry_at IS NULL OR next_retry_at <= NOW())
              ORDER BY id ASC
              LIMIT 1
              FOR UPDATE SKIP LOCKED"
        );
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            $db->commit();
            break; // no more work — exit cleanly
        }

        // Lock the job so no other worker touches it while we process
        $stmt = $db->prepare(
            "UPDATE transaction_queue
                SET status = 'processing',
                    locked_at    = NOW(),
                    worker_token = ?,
                    attempts     = attempts + 1
              WHERE id = ?"
        );
        $stmt->execute([$workerToken, $job['id']]);
        $db->commit();

        // ── Load the transaction row ───────────────────────────────────────
        $stmt = $db->prepare('SELECT * FROM transactions WHERE id = ? LIMIT 1');
        $stmt->execute([$job['transaction_id']]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tx) {
            echo "Job #{$job['id']}: transaction #{$job['transaction_id']} missing — skipping.\n";
            $processed++;
            continue;
        }

        echo "Processing job #{$job['id']} | ref: {$tx['refno']} | amount: ₦{$tx['amount']}\n";

        // ── Load service details (we need service type for the provider payload) ──
        $stmt = $db->prepare('SELECT * FROM services WHERE id = ? LIMIT 1');
        $stmt->execute([$tx['service_id']]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$service) {
            QueueHelper::refundAndFail($job['id'], $tx, $db, "Service #{$tx['service_id']} not found");
            $processed++;
            continue;
        }

        // ── Get all providers that can handle this service (priority order) ──
        $providers = ServiceManager::getAllProviders((int) $tx['service_id'], $db);

        if (empty($providers)) {
            QueueHelper::refundAndFail($job['id'], $tx, $db, 'No active providers for this service');
            $processed++;
            continue;
        }

        // ── Try each provider in order until one succeeds ─────────────────
        $success = false;

        foreach ($providers as $providerRow) {
            echo "  Trying provider: {$providerRow['provider_slug']}\n";

            try {
                // Build the structured payload the provider classes expect
                // (they do NOT receive the raw transaction row)
                $payload = [
                    'service_type'      => $service['type'],     // "airtime" | "data" | "bill"
                    'category'          => $service['category'],  // "electricity", "cable", etc.
                    'provider_code'     => $providerRow['provider_code'], // provider's own plan ID
                    'phone'             => $tx['phone'],
                    'amount'            => (float) $tx['amount'],
                    'reference'         => $tx['refno'],
                    // Optional extras for electricity/cable
                    'meter_number'      => $tx['meter_number']   ?? null,
                    'smartcard_number'  => $tx['smartcard_number'] ?? null,
                    'meter_type'        => $tx['meter_type']     ?? 'prepaid',
                    'quantity'          => $tx['quantity']       ?? 1,
                ];

                $provider = ProviderFactory::make($providerRow['provider_slug'], $db);
                $rawResult = $provider->purchase($payload);

                // Normalise to our three outcomes: success | pending | failed
                $norm = ProviderResponseNormalizer::normalize($providerRow['provider_slug'], $rawResult);

                echo "  Provider response: status={$norm['status']} ref={$norm['provider_ref']}\n";

                if ($norm['status'] === 'success') {
                    // Save which provider delivered, then mark everything done
                    $stmt = $db->prepare(
                        'UPDATE transactions SET provider_id = ? WHERE id = ?'
                    );
                    $stmt->execute([$providerRow['provider_id'], $tx['id']]);

                    QueueHelper::markSuccess($job['id'], $tx['id'], $norm, $db);
                    $success = true;
                    break;

                } elseif ($norm['status'] === 'pending') {
                    // Save which provider accepted it, await their callback/reconciliation
                    $stmt = $db->prepare(
                        'UPDATE transactions SET provider_id = ? WHERE id = ?'
                    );
                    $stmt->execute([$providerRow['provider_id'], $tx['id']]);

                    QueueHelper::markAwaitingReconciliation($job['id'], $tx['id'], $norm, $db);
                    $success = true; // don't try another provider — this one claimed it
                    break;
                }

                // status === "failed" → try the next provider in the loop

            } catch (Exception $e) {
                echo "  Provider {$providerRow['provider_slug']} threw: " . $e->getMessage() . "\n";
                error_log("[worker] Provider {$providerRow['provider_slug']} error for job #{$job['id']}: " . $e->getMessage());
                // Continue to next provider
            }
        }

        // ── If every provider failed, refund the wallet ───────────────────
        if (!$success) {
            QueueHelper::refundAndFail($job['id'], $tx, $db, 'All providers failed or returned error');
        }

        $processed++;

    } catch (PDOException $e) {
        echo "DB error: " . $e->getMessage() . "\n";
        error_log("[worker] PDOException: " . $e->getMessage());
        try { $db->rollBack(); } catch (Exception $ex) {}
        break; // stop this cron run on DB errors
    }
}

echo "[{$workerToken}] Done. Processed {$processed} job(s).\n";
