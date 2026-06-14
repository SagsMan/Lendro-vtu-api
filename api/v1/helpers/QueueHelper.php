<?php
/**
 * QueueHelper — utility functions for the transaction_queue table
 */

/**
 * Update the status of a queue item by the transaction's reference number.
 * Used by the worker, reconciler, and webhook handler after they resolve a job.
 *
 * @param string $reference  our internal refno (LDR-xxx)
 * @param string $status     new queue status: pending|processing|awaiting_reconciliation|completed|failed
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
 * Optionally set a next_retry_at time so the worker waits before retrying.
 *
 * @param int    $queueId         primary key of the transaction_queue row
 * @param int    $retryInMinutes  how many minutes until the next retry (0 = no retry delay)
 */
function failQueueItem(PDO $db, int $queueId, int $retryInMinutes = 0): void
{
    if ($retryInMinutes > 0) {
        $stmt = $db->prepare(
            'UPDATE transaction_queue
                SET status = ?, attempts = attempts + 1, next_retry_at = NOW() + INTERVAL ? MINUTE, updated_at = NOW()
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
