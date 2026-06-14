<?php
/**
 * Normalizer — maps provider-specific data shapes onto our internal formats
 *
 * Each provider uses different field names and status strings.
 * This class is the single place where we translate all of that into
 * the consistent vocabulary the rest of the system speaks.
 */
class Normalizer
{
    /**
     * Normalise a webhook/callback payload from any known provider.
     *
     * Returns:
     *   - reference          our internal reference (LDR-xxx)
     *   - provider_reference the provider's own transaction ID
     *   - status             one of: success | failed | processing | pending
     *   - raw                the original payload for audit logging
     */
    public static function normalizeProviderWebhook(string $provider, array $data): array
    {
        switch (strtolower($provider)) {

            // ── CheapDataHub ────────────────────────────────────────────────
            case 'cheapdatahub':
                $rawStatus = strtolower(trim($data['status'] ?? ''));
                return [
                    'reference'          => $data['reference'] ?? $data['ref'] ?? null,
                    'provider_reference' => $data['transaction_id'] ?? $data['id'] ?? null,
                    'status'             => self::normalizeTransactionStatus($rawStatus),
                    'raw'                => $data,
                ];

            // ── ConnectBridge ───────────────────────────────────────────────
            case 'connectbridge':
                $rawStatus = strtolower(trim($data['message'] ?? $data['status'] ?? ''));
                return [
                    'reference'          => $data['ref'] ?? $data['reference'] ?? null,
                    'provider_reference' => $data['trans_id'] ?? $data['transaction_id'] ?? null,
                    'status'             => self::normalizeTransactionStatus($rawStatus),
                    'raw'                => $data,
                ];

            // ── Fallback for unknown providers ──────────────────────────────
            default:
                return [
                    'reference'          => $data['reference'] ?? null,
                    'provider_reference' => $data['transaction_id'] ?? $data['id'] ?? null,
                    'status'             => self::normalizeTransactionStatus($data['status'] ?? ''),
                    'raw'                => $data,
                ];
        }
    }

    /**
     * Map any provider status string → one of our four canonical statuses:
     *   success | failed | processing | pending
     */
    public static function normalizeTransactionStatus(string $status): string
    {
        $s = strtolower(trim($status));

        if (in_array($s, ['success', 'successful', 'completed', 'delivered', 'done', 'approved', 'true', '1'])) {
            return 'success';
        }

        if (in_array($s, ['failed', 'error', 'cancelled', 'reversed', 'unsuccessful', 'false', '0'])) {
            return 'failed';
        }

        if (in_array($s, ['pending', 'processing', 'queued', 'initiated', 'timeout', 'request_timeout', 'no_response'])) {
            return 'processing';
        }

        // Anything we don't recognise lands here and will be retried by reconciliation
        return 'pending';
    }
}
