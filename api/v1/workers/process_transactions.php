<?php
/**
 * Background Worker: Process Transaction Queue
 *
 * This long-running process continuously polls the `transaction_queue` table
 * for pending jobs. For each job it:
 *   1. Picks up the next pending item (locks it so no other worker touches it)
 *   2. Looks up which providers support the service
 *   3. Tries each provider in priority order until one succeeds
 *   4. On SUCCESS → marks transaction + queue as complete
 *   5. On PENDING → puts the queue item into "awaiting_reconciliation"
 *      (the reconciliation cronjob will follow up)
 *   6. On ALL FAILED → refunds the user's wallet
 *
 * Run as a supervised process (systemd, supervisor, screen, etc.):
 *   php api/v1/workers/process_transactions.php
 *
 * Supervisor config example: see workers/lendro-worker.conf
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../ServiceManager.php';
require_once __DIR__ . '/../ProviderFactory.php';
require_once __DIR__ . '/../ProviderResponseNormalizer.php';
require_once __DIR__ . '/../helpers/helpers.php';
require_once __DIR__ . '/../helpers/QueueHelper.php';

$workerToken = 'wkr_' . uniqid();
$maxRetries  = 3;   // retries before giving up and refunding

echo "[{$workerToken}] Transaction worker started.\n";

while (true) {
    try {

        // ── 1. Grab the next pending job (atomic lock) ─────────────────────
        $db->beginTransaction();

        $stmt = $db->prepare(
            "SELECT * FROM transaction_queue
              WHERE status = 'pending'
                AND (next_retry_at IS NULL OR next_retry_at <= NOW())
              ORDER BY id ASC
              LIMIT 1
              FOR UPDATE SKIP LOCKED"  // SKIP LOCKED lets multiple workers run in parallel
        );
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            $db->commit();
            sleep(2); // nothing to do — take a quick nap
            continue;
        }

        // Claim this job
        $stmt = $db->prepare(
            "UPDATE transaction_queue
                SET status = 'processing', locked_at = NOW(), worker_token = ?
              WHERE id = ?"
        );
        $stmt->execute([$workerToken, $job['id']]);
        $db->commit();

        // ── 2. Load the transaction ────────────────────────────────────────
        $stmt = $db->prepare('SELECT * FROM transactions WHERE id = ? LIMIT 1');
        $stmt->execute([$job['transaction_id']]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tx) {
            echo "Job #{$job['id']}: transaction missing — skipping.\n";
            continue;
        }

        echo "Processing job #{$job['id']} | ref: {$tx['refno']} | service: {$tx['transtitle']}\n";

        // ── 3. Get available providers for this service ────────────────────
        $providers = ServiceManager::getAllProviders((int) $tx['service_id'], $db);

        if (empty($providers)) {
            echo "Job #{$job['id']}: no providers available — refunding.\n";
            refundTransaction($tx, $db);
            $stmt = $db->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$tx['id']]);
            failQueueItem($db, $job['id']);
            continue;
        }

        $resolved = false;

        // ── 4. Try each provider in order ─────────────────────────────────
        foreach ($providers as $providerData) {
            try {
                $provider = ProviderFactory::make($providerData['provider_slug'], $db);

                // Build the purchase payload; add extra fields for services that need them
                $purchasePayload = [
                    'provider_code' => $providerData['provider_code'],
                    'phone'         => $tx['phone'],
                    'amount'        => (float) $tx['amount'],
                    'reference'     => $tx['refno'],
                    'service_type'  => $tx['service_type'] ?? 'data',  // set if you store it on the tx
                ];

                $response = $provider->purchase($purchasePayload);
                $outcome  = ProviderResponseNormalizer::normalize($response);

                echo "  Provider {$providerData['provider_slug']}: {$outcome}\n";

                if ($outcome === 'success') {
                    // ✅ Transaction delivered — mark everything done
                    $stmt = $db->prepare(
                        "UPDATE transactions
                            SET provider_id       = ?,
                                provider_status   = ?,
                                provider_response = ?,
                                status            = 'success',
                                completed_at      = NOW(),
                                updated_at        = NOW()
                          WHERE id = ?"
                    );
                    $stmt->execute([
                        $providerData['provider_id'],
                        $response['status'] ?? 'success',
                        json_encode($response),
                        $tx['id'],
                    ]);

                    $stmt = $db->prepare("UPDATE transaction_queue SET status = 'completed', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$job['id']]);

                    // Notify the user
                    $stmt = $db->prepare("INSERT INTO notifications (userid, message, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$tx['userid'], "Your {$tx['transtitle']} for {$tx['phone']} was successful!"]);

                    $resolved = true;
                    break;
                }

                if ($outcome === 'pending') {
                    // ⏳ Provider is processing — hand off to the reconciler
                    $stmt = $db->prepare(
                        "UPDATE transactions
                            SET provider_id       = ?,
                                provider_status   = ?,
                                provider_response = ?,
                                status            = 'processing',
                                updated_at        = NOW()
                          WHERE id = ?"
                    );
                    $stmt->execute([
                        $providerData['provider_id'],
                        $response['status'] ?? 'pending',
                        json_encode($response),
                        $tx['id'],
                    ]);

                    $stmt = $db->prepare(
                        "UPDATE transaction_queue
                            SET status = 'awaiting_reconciliation',
                                next_retry_at = NOW() + INTERVAL 5 MINUTE,
                                updated_at    = NOW()
                          WHERE id = ?"
                    );
                    $stmt->execute([$job['id']]);

                    $resolved = true;
                    break;
                }

                // FAILED — try the next provider
                echo "  Provider {$providerData['provider_slug']} failed. Trying next...\n";

            } catch (Exception $providerError) {
                // Provider threw an exception (network error, bad config, etc.)
                error_log("[Worker] Provider {$providerData['provider_slug']}: " . $providerError->getMessage());
                echo "  Provider {$providerData['provider_slug']} threw: " . $providerError->getMessage() . "\n";
                continue; // try the next provider
            }
        }

        // ── 5. All providers failed ────────────────────────────────────────
        if (!$resolved) {
            $attempts = (int) $job['attempts'] + 1;

            if ($attempts >= $maxRetries) {
                echo "Job #{$job['id']}: all providers failed after {$attempts} attempts — refunding.\n";
                refundTransaction($tx, $db);

                $stmt = $db->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$tx['id']]);

                failQueueItem($db, $job['id']); // no retry delay = final failure

                // Notify user of refund
                $stmt = $db->prepare("INSERT INTO notifications (userid, message, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$tx['userid'], "Your transaction failed. ₦" . number_format($tx['amount'], 2) . " has been refunded to your wallet."]);

            } else {
                // Retry after 2 minutes
                echo "Job #{$job['id']}: failed (attempt {$attempts}/{$maxRetries}) — retrying in 2 min.\n";
                failQueueItem($db, $job['id'], 2);
            }
        }

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('[Worker] Unhandled: ' . $e->getMessage());
        echo "UNHANDLED ERROR: " . $e->getMessage() . "\n";
        sleep(3); // back off before next iteration
    }
}
