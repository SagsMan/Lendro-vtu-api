<?php
/**
 * TransactionService — queue-based purchase flow
 *
 * This is the recommended (async) purchase path:
 *
 *   1. Validate the request + idempotency key.
 *   2. Lock and debit the user's wallet inside a DB transaction.
 *   3. Create a PENDING transaction row.
 *   4. Push the transaction ID into the `transaction_queue` table.
 *   5. Commit and return immediately — the background worker handles the rest.
 *
 * The background worker (workers/process_transactions.php) picks up pending
 * queue items, calls providers, and updates transaction status.
 * If all providers fail it refunds the wallet.
 * If a provider is pending, the reconciliation worker follows up later.
 */
require_once __DIR__ . '/ServiceManager.php';
require_once __DIR__ . '/IdempotencyService.php';
require_once __DIR__ . '/helpers/helpers.php';

class TransactionService
{
    /**
     * Initiate a purchase — debit wallet, create transaction, push to queue.
     *
     * @param  int    $userid         authenticated user's ID
     * @param  int    $serviceId      internal service ID from the services table
     * @param  string $phone          recipient's phone number
     * @param  string $idempotencyKey client-generated unique key to prevent duplicates
     * @param  PDO    $db
     * @return array  JSON-ready response array
     */
    public static function process(int $userid, int $serviceId, string $phone, string $idempotencyKey, PDO $db): array
    {
        // Generate a human-friendly reference number we'll use throughout the system
        $reference = generateRefNo('LDR');

        try {
            $db->beginTransaction();

            // ── Step 1: Idempotency check ────────────────────────────────────
            // If this key was used before, return the stored result immediately
            $requestHash = hash('sha256', json_encode([$userid, $serviceId, $phone]));
            $existing    = IdempotencyService::validate($idempotencyKey, $requestHash, $db);

            if ($existing) {
                $db->commit();
                return [
                    'status'             => 'already_processed',
                    'idempotent'         => true,
                    'reference'          => $existing['reference'],
                    'transaction_status' => $existing['status'],
                    'message'            => 'This request was already submitted. Here is the original result.',
                ];
            }

            // ── Step 2: Load service ─────────────────────────────────────────
            $service = ServiceManager::getService($serviceId, $db);
            $amount  = (float) $service['price'];

            // Airtime is flexible-amount — the user sends the amount in the request
            // For now we use the stored price; extend here if you support custom amounts
            if ($amount <= 0) {
                throw new Exception('This service does not have a fixed price. Please contact support.');
            }

            // ── Step 3: Lock and debit wallet ────────────────────────────────
            // FOR UPDATE locks the row so concurrent requests can't race
            $stmt = $db->prepare('SELECT * FROM wallets WHERE userid = ? FOR UPDATE');
            $stmt->execute([$userid]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$wallet) {
                throw new Exception('Wallet not found. Please contact support.');
            }

            $balanceBefore = (float) $wallet['balance'];

            if ($balanceBefore < $amount) {
                throw new Exception(
                    "Insufficient wallet balance. You need ₦" . number_format($amount, 2) .
                    " but your balance is ₦" . number_format($balanceBefore, 2) . "."
                );
            }

            $balanceAfter = $balanceBefore - $amount;

            $stmt = $db->prepare('UPDATE wallets SET balance = ?, updated_at = NOW() WHERE userid = ?');
            $stmt->execute([$balanceAfter, $userid]);

            // ── Step 4: Log the wallet debit ─────────────────────────────────
            logWalletEvent($db, $userid, 'debit', $amount, $balanceBefore, $balanceAfter, $reference, "Purchase: {$service['name']}");

            // ── Step 5: Create a PENDING transaction record ──────────────────
            $stmt = $db->prepare(
                'INSERT INTO transactions
                    (userid, service_id, amount, phone, transtype, refno,
                     idempotency_key, request_hash, transtitle, transdesc, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $userid, $serviceId, $amount, $phone,
                'debit', $reference,
                $idempotencyKey, $requestHash,
                $service['name'], 'VTU Purchase',
                'pending',
            ]);

            $transactionId = (int) $db->lastInsertId();

            // ── Step 6: Push to the processing queue ─────────────────────────
            $stmt = $db->prepare(
                "INSERT INTO transaction_queue (transaction_id, status, created_at)
                 VALUES (?, 'pending', NOW())"
            );
            $stmt->execute([$transactionId]);

            // Commit everything — wallet is debited, tx is logged, job is queued
            $db->commit();

            return [
                'status'    => 'processing',
                'reference' => $reference,
                'amount'    => $amount,
                'message'   => 'Your request has been received and is being processed.',
            ];

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack(); // wallet debit is rolled back — user keeps their money
            }
            error_log('[TransactionService] ' . $e->getMessage());

            return [
                'status'  => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Look up the current status of a transaction by its reference number.
     * Safe to call repeatedly — read-only, no side effects.
     */
    public static function getStatus(string $reference, int $userid, PDO $db): array
    {
        $stmt = $db->prepare(
            'SELECT t.refno, t.status, t.amount, t.phone, t.transtitle,
                    t.created_at, t.updated_at, t.provider_id,
                    p.name AS provider_name
               FROM transactions t
               LEFT JOIN providers p ON t.provider_id = p.id
              WHERE t.refno = ? AND t.userid = ?
              LIMIT 1'
        );
        $stmt->execute([$reference, $userid]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tx) {
            return ['status' => 'failed', 'message' => 'Transaction not found.'];
        }

        return [
            'status'        => 'success',
            'reference'     => $tx['refno'],
            'tx_status'     => $tx['status'],
            'amount'        => (float) $tx['amount'],
            'phone'         => $tx['phone'],
            'service'       => $tx['transtitle'],
            'provider'      => $tx['provider_name'],
            'created_at'    => $tx['created_at'],
            'updated_at'    => $tx['updated_at'],
        ];
    }
}
