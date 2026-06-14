<?php
/**
 * ProviderResponseNormalizer
 *
 * Used by the transaction worker and TransactionService to interpret
 * a raw provider purchase response and map it to one of three outcomes:
 *   - "success"    → tx succeeded, mark complete
 *   - "pending"    → provider is still processing, schedule reconciliation
 *   - "failed"     → provider rejected the request, try next provider or refund
 */
class ProviderResponseNormalizer
{
    public static function normalize(array $response): string
    {
        // Some providers return a boolean "true"/"false" string
        $status = strtolower(trim((string) ($response['status'] ?? '')));

        if (in_array($status, ['success', 'successful', 'completed', 'delivered', 'true', '1', 'done', 'approved'])) {
            return 'success';
        }

        if (in_array($status, ['pending', 'processing', 'queued', 'initiated'])) {
            return 'pending';
        }

        // Anything else — failure, error, bad response — treat as failed
        // so we can try the next provider or refund
        return 'failed';
    }
}
