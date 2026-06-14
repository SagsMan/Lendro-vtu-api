<?php
/**
 * IdempotencyService
 *
 * Prevents duplicate transactions when a client retries the same request
 * (e.g. network timeout, double-tap on the buy button).
 *
 * How it works:
 *   1. The client sends a unique idempotency_key with every purchase request.
 *   2. We hash the request payload (user + service + phone) into a request_hash.
 *   3. On the first call we store both in the transaction row.
 *   4. On a retry, we find the existing row by idempotency_key and:
 *        a. Verify the request_hash matches — rejects payload-tampered retries.
 *        b. Return the stored result so the client sees the same response.
 */
class IdempotencyService
{
    /**
     * Check whether this idempotency key has been seen before.
     *
     * @param  string $key   the client-generated unique key
     * @param  string $hash  SHA-256 of the request payload
     * @param  PDO    $db
     * @return array|null    existing transaction row, or null if this is a new request
     * @throws Exception     if the key exists but the payload hash doesn't match (tampered request)
     */
    public static function validate(string $key, string $hash, PDO $db): ?array
    {
        $stmt = $db->prepare('SELECT * FROM transactions WHERE idempotency_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            return null; // brand-new request — proceed normally
        }

        // Key found — make sure the payload hasn't changed
        if ($existing['request_hash'] !== $hash) {
            throw new Exception('Idempotency key reused with a different payload. This looks like a tampered request.');
        }

        return [
            'status'      => $existing['status'],
            'reference'   => $existing['refno'],
            'transaction' => $existing,
        ];
    }
}
