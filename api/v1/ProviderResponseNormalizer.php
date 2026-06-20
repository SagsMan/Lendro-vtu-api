<?php
/**
 * ProviderResponseNormalizer
 *
 * Maps each provider's raw purchase response to one of three outcome states:
 *   - "success"  → transaction delivered, mark complete
 *   - "pending"  → provider still processing, schedule reconciliation
 *   - "failed"   → provider rejected the request, try next provider or refund
 *
 * Returns a structured array (not just a string) so callers can log the
 * provider reference number alongside the status.
 *
 * Called by the background worker:
 *   $norm = ProviderResponseNormalizer::normalize($providerSlug, $rawResponse);
 *   if ($norm['status'] === 'success') { ... }
 */
class ProviderResponseNormalizer
{
    /**
     * Normalise a provider's raw response.
     *
     * @param  string $providerSlug  matches providers.slug — allows per-provider logic
     * @param  array  $response      raw decoded JSON from the provider
     * @return array  ['status' => 'success|pending|failed', 'provider_ref' => '...', 'message' => '...']
     */
    public static function normalize(string $providerSlug, array $response): array
    {
        // Extract the provider-specific reference number (token, transaction ID, etc.)
        // Each provider uses a different key — check the most common ones
        $providerRef = $response['transaction_id']
            ?? $response['request_id']
            ?? $response['ref']
            ?? $response['data']['transaction_id']
            ?? $response['data']['ref']
            ?? null;

        // Extract a human-readable status string from the response
        // Providers use wildly different field names and values — check them all
        $rawStatus = strtolower(trim((string) (
            $response['status']
            ?? $response['data']['status']
            ?? $response['Status']
            ?? ''
        )));

        // ── Map to our three canonical outcomes ─────────────────────────────

        // Success signals
        $successTokens = ['success', 'successful', 'completed', 'delivered', 'true', '1', 'done', 'approved', 'fulfilled'];
        // Pending signals (provider received but not yet delivered)
        $pendingTokens = ['pending', 'processing', 'queued', 'initiated', 'in_progress', 'inprogress'];

        if (in_array($rawStatus, $successTokens, true)) {
            $status = 'success';
        } elseif (in_array($rawStatus, $pendingTokens, true)) {
            $status = 'pending';
        } else {
            // Anything else (failed, error, null, empty) is treated as failed
            // so we can try the next provider or trigger an automatic refund
            $status = 'failed';
        }

        // ── Per-provider overrides ───────────────────────────────────────────
        // ConnectBridge wraps its status inside a `data` object
        if ($providerSlug === 'connectbridge') {
            $cbStatus = strtolower(trim((string) ($response['data']['status'] ?? $rawStatus)));
            if (in_array($cbStatus, $successTokens, true)) {
                $status = 'success';
            } elseif (in_array($cbStatus, $pendingTokens, true)) {
                $status = 'pending';
            }
            // Prefer ConnectBridge's inner reference
            $providerRef = $response['data']['reference']
                ?? $response['data']['transaction_id']
                ?? $providerRef;
        }

        // ── CheapDataHub uses a numeric HTTP-style status code ───────────────
        if ($providerSlug === 'cheapdatahub') {
            $httpLike = (int) ($response['status_code'] ?? $response['code'] ?? 0);
            if ($httpLike === 200 || $httpLike === 201) {
                $status = 'success';
            }
        }

        return [
            'status'       => $status,
            'provider_ref' => $providerRef,
            'raw_status'   => $rawStatus,
            'message'      => $response['message'] ?? $response['data']['message'] ?? '',
        ];
    }
}
