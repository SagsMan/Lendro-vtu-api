<?php
/**
 * Cronjob / Long-running Worker: Transaction Reconciliation
 *
 * Picks up transactions stuck in "awaiting_reconciliation" status,
 * queries each provider for the current result, and either:
 *   - Marks the transaction as SUCCESS
 *   - Refunds the user if it FAILED
 *   - Reschedules another check if still PROCESSING
 *
 * Run this every 5 minutes via cron:
 *   */5 * * * * php /var/www/html/api/v1/cronjob/reconcile_transactions.php >> /var/log/lendro-recon.log 2>&1
 *
 * Or run it as a long-lived process (it loops internally with sleep):
 *   php api/v1/cronjob/reconcile_transactions.php &
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../ProviderFactory.php';
require_once __DIR__ . '/../ProviderResponseNormalizer.php';
require_once __DIR__ . '/../helpers/QueueHelper.php';

// Each worker run gets a unique token so we can trace which worker touched a job
$workerToken = 'rcn_' . uniqid();
$maxAttempts = 20;  // give up after 20 tries (~100 minutes at 5-min intervals)

echo "[{$workerToken}] Reconciliation worker started.\n";

while (true) {
    try {
        // ── Pick the next job that needs reconciliation ────────────────────
        $db->beginTransaction();

        $stmt = $db->prepare(
            "SELECT * FROM transaction_queue
              WHERE status = 'awaiting_reconciliation'
                AND (next_retry_at IS NULL OR next_retry_at <= NOW())
              ORDER BY id ASC
              LIMIT 1
              FOR UPDATE"
        );
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            $db->commit();
            sleep(10); // nothing to do — wait before checking again
            continue;
        }

        // Lock this job so no other worker picks it up at the same time
        $stmt = $db->prepare(
            'UPDATE transaction_queue
                SET locked_at = NOW(), worker_token = ?
              WHERE id = ?'
        );
        $stmt->execute([$workerToken, $job['id']]);
        $db->commit();

        // ── Load the transaction ───────────────────────────────────────────
        $stmt = $db->prepare('SELECT * FROM transactions WHERE id = ? LIMIT 1');
        $stmt->execute([$job['transaction_id']]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tx) {
            echo "Job #{$job['id']}: transaction not found — skipping.\n";
            continue;
        }

        // Already resolved? Just clean up the queue
        if (in_array($tx['status'], ['success', 'reversed', 'failed'])) {
            updateQueueStatus($db, $tx['refno'], 'completed');
            echo "Job #{$job['id']}: already resolved ({$tx['status']}) — marked complete.\n";
            continue;
        }

        // Can't reconcile without knowing which provider handled it
        if (!$tx['provider_id']) {
            echo "Job #{$job['id']}: no provider assigned — marking failed.\n";
            updateQueueStatus($db, $tx['refno'], 'failed');
            continue;
        }

        // ── Load the provider and query the status ─────────────────────────
        $stmt = $db->prepare('SELECT * FROM providers WHERE id = ? LIMIT 1');
        $stmt->execute([$tx['provider_id']]);
        $providerRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$providerRow) {
            echo "Job #{$job['id']}: provider row missing — skipping.\n";
            continue;
        }

        $provider = ProviderFactory::make($providerRow['slug'], $db);
        $response = $provider->queryTransaction($tx['refno']);
        $outcome  = ProviderResponseNormalizer::normalize($response);

        echo "Job #{$job['id']} (ref: {$tx['refno']}): provider says '{$outcome}'.\n";

        // ── Handle each possible outcome ───────────────────────────────────

        if ($outcome === 'success') {

            $db->beginTransaction();

            // Re-lock the transaction to prevent a simultaneous webhook from interfering
            $stmt = $db->prepare('SELECT * FROM transactions WHERE id = ? FOR UPDATE');
            $stmt->execute([$tx['id']]);
            $lockedTx = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($lockedTx['status'] !== 'success') {
                $stmt = $db->prepare(
                    "UPDATE transactions
                        SET status = 'success',
                            provider_status   = ?,
                            provider_response = ?,
                            reconciled        = 1,
                            completed_at      = NOW(),
                            updated_at        = NOW()
                      WHERE id = ?"
                );
                $stmt->execute([$response['status'] ?? 'success', json_encode($response), $tx['id']]);
                updateQueueStatus($db, $tx['refno'], 'completed');

                // Notify the user
                $stmt = $db->prepare("INSERT INTO notifications (userid, message, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$tx['userid'], "Your {$tx['transtitle']} transaction was successful!"]);
            }

            $db->commit();
            continue;
        }

        if ($outcome === 'failed') {

            $db->beginTransaction();

            $stmt = $db->prepare('SELECT * FROM transactions WHERE id = ? FOR UPDATE');
            $stmt->execute([$tx['id']]);
            $lockedTx = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!in_array($lockedTx['status'], ['reversed', 'success'])) {

                // Refund: lock wallet and credit back
                $stmt = $db->prepare('SELECT * FROM wallets WHERE userid = ? FOR UPDATE');
                $stmt->execute([$tx['userid']]);
                $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($wallet) {
                    $before = (float) $wallet['balance'];
                    $after  = $before + (float) $tx['amount'];

                    $stmt = $db->prepare('UPDATE wallets SET balance = ?, updated_at = NOW() WHERE userid = ?');
                    $stmt->execute([$after, $tx['userid']]);

                    $stmt = $db->prepare(
                        'INSERT INTO wallet_logs (userid, type, amount, balance_before, balance_after, reference, description, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
                    );
                    $stmt->execute([$tx['userid'], 'credit', $tx['amount'], $before, $after, $tx['refno'], 'Refund: reconciliation failed']);
                }

                $stmt = $db->prepare(
                    "UPDATE transactions
                        SET status = 'reversed',
                            provider_status   = ?,
                            provider_response = ?,
                            reconciled        = 1,
                            updated_at        = NOW()
                      WHERE id = ?"
                );
                $stmt->execute([$response['status'] ?? 'failed', json_encode($response), $tx['id']]);
                updateQueueStatus($db, $tx['refno'], 'failed');

                $stmt = $db->prepare("INSERT INTO notifications (userid, message, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$tx['userid'], "Transaction {$tx['refno']} failed. Your wallet has been refunded."]);
            }

            $db->commit();
            continue;
        }

        // ── Still processing — schedule another check ──────────────────────
        $newAttempts = (int) $job['attempts'] + 1;

        if ($newAttempts >= $maxAttempts) {
            // We've tried too many times — give up and refund
            echo "Job #{$job['id']}: max attempts reached — reversing and refunding.\n";
            refundTransaction($tx, $db);
            $stmt = $db->prepare("UPDATE transaction_queue SET status = 'failed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$job['id']]);
        } else {
            $stmt = $db->prepare(
                'UPDATE transaction_queue
                    SET attempts = ?, next_retry_at = NOW() + INTERVAL 5 MINUTE, updated_at = NOW()
                  WHERE id = ?'
            );
            $stmt->execute([$newAttempts, $job['id']]);
            echo "Job #{$job['id']}: still processing — attempt {$newAttempts}/{$maxAttempts}. Retry in 5 min.\n";
        }

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('[Reconciliation] ' . $e->getMessage());
        echo "ERROR: " . $e->getMessage() . "\n";
        sleep(5);
    }
}
