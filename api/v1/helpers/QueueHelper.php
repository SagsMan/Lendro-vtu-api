<?php
/**
 * QueueHelper — utility functions for the transaction_queue table
 *
 * Used by the background worker (workers/process_transactions.php) to update
 * queue items and transaction rows after a provider responds.
 */

// ── Simple status updaters ──────────────────────────────────────────────────

/**
 * Update the queue status for a job linked to a transaction reference.
 * Safe to call at any stage: pending → processing → completed/failed.
 */
function updateQueueStatus(PDO $db, string $reference, string $status): void
{
    $stmt = $db->prepare(
        'UPDATE transaction_queue tq
           JOIN transactions t ON tq.transaction_id = t.id
            SET tq.status = ?, tq.updated_at = NOW()
          WHERE t.refno = ?'
    );
    $stmt->execute([$status, $reference]);
}

/**
 * Mark a queue item as failed and increment its attempt counter.
 * Pass $retryInMinutes > 0 to schedule an automatic retry instead of a hard fail.
 */
function failQueueItem(PDO $db, int $queueId, int $retryInMinutes = 0): void
{
    if ($retryInMinutes > 0) {
        $stmt = $db->prepare(
            'UPDATE transaction_queue
                SET status = ?, attempts = attempts + 1,
                    next_retry_at = NOW() + INTERVAL ? MINUTE,
                    updated_at = NOW()
              WHERE id = ?'
        );
        $stmt->execute(['pending', $retryInMinutes, $queueId]);
    } else {
        $stmt = $db->prepare(
            'UPDATE transaction_queue
                SET status = ?, attempts = attempts + 1, updated_at = NOW()
              WHERE id = ?'
        );
        $stmt->execute(['failed', $queueId]);
    }
}

// ── Worker result handlers (called as static methods from the worker) ────────

class QueueHelper
{
    /**
     * Called when a provider confirms success.
     * Marks the queue item and the transaction as completed.
     *
     * @param int    $queueId  transaction_queue.id
     * @param int    $txId     transactions.id
     * @param array  $norm     normalised response from ProviderResponseNormalizer::normalize()
     * @param PDO    $db
     */
    public static function markSuccess(int $queueId, int $txId, array $norm, PDO $db): void
    {
        // Mark the underlying transaction as success
        $stmt = $db->prepare(
            "UPDATE transactions
                SET status = 'success',
                    provider_ref = ?,
                    updated_at   = NOW()
              WHERE id = ?"
        );
        $stmt->execute([$norm['provider_ref'] ?? null, $txId]);

        // Mark the queue job as completed
        $stmt = $db->prepare(
            "UPDATE transaction_queue
                SET status = 'completed', updated_at = NOW()
              WHERE id = ?"
        );
        $stmt->execute([$queueId]);

        echo "  → Success. Queue job #{$queueId} closed.\n";
    }

    /**
     * Called when a provider says the request is still pending (e.g. queued on their side).
     * We don't refund — we schedule a reconciliation check instead.
     *
     * @param int    $queueId
     * @param int    $txId
     * @param array  $norm
     * @param PDO    $db
     */
    public static function markAwaitingReconciliation(int $queueId, int $txId, array $norm, PDO $db): void
    {
        // Update transaction to show it is still in-flight
        $stmt = $db->prepare(
            "UPDATE transactions
                SET status     = 'processing',
                    provider_ref = ?,
                    updated_at   = NOW()
              WHERE id = ?"
        );
        $stmt->execute([$norm['provider_ref'] ?? null, $txId]);

        // Put the queue item into reconciliation state so the reconcile cron picks it up
        $stmt = $db->prepare(
            "UPDATE transaction_queue
                SET status = 'awaiting_reconciliation',
                    next_retry_at = NOW() + INTERVAL 5 MINUTE,
                    updated_at = NOW()
              WHERE id = ?"
        );
        $stmt->execute([$queueId]);

        echo "  → Pending on provider side. Scheduled for reconciliation.\n";
    }

    /**
     * Called when ALL providers have failed for this transaction.
     * Refunds the wallet and marks everything as reversed/failed.
     *
     * @param int    $queueId
     * @param array  $tx        Full transaction row from the DB
     * @param PDO    $db
     * @param string $reason    Human-readable failure reason for logs
     */
    public static function refundAndFail(int $queueId, array $tx, PDO $db, string $reason = 'All providers failed'): void
    {
        // Call the shared refund helper (defined in helpers/helpers.php)
        // It handles wallet locking + credit + transaction status update atomically
        $refunded = refundTransaction($tx, $db);

        if ($refunded) {
            echo "  → Refunded ₦{$tx['amount']} to user #{$tx['userid']} wallet.\n";
        } else {
            // Refund failed — log for manual intervention
            error_log("[QueueHelper] Refund failed for tx #{$tx['id']} ref:{$tx['refno']} reason: {$reason}");
            echo "  → WARNING: Refund failed for tx #{$tx['id']}. Manual intervention required.\n";
        }

        // Mark queue job as failed regardless
        $stmt = $db->prepare(
            "UPDATE transaction_queue
                SET status = 'failed',
                    attempts = attempts + 1,
                    updated_at = NOW()
              WHERE id = ?"
        );
        $stmt->execute([$queueId]);

        echo "  → Queue job #{$queueId} marked as failed. Reason: {$reason}\n";
    }
}
