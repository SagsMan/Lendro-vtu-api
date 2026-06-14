<?php
/**
 * BaseProvider — shared HTTP request logic for all providers
 *
 * Each concrete provider (ProviderA, ProviderB, …) extends this class and gets
 * the request() helper for free. The subclass only needs to implement
 * the four methods defined in ProviderInterface.
 */
abstract class BaseProvider
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->apiKey  = $config['api_key'];
    }

    /**
     * Generic HTTP helper — supports GET and POST.
     *
     * Returns the decoded JSON response as an array.
     * On any error (network, bad JSON, non-2xx) it returns a normalised
     * failure array so callers never have to handle raw curl errors.
     */
    protected function request(string $endpoint, array $payload = [], string $method = 'GET'): array
    {
        $url = $this->baseUrl . $endpoint;

        // For GET requests, append params as query string
        if (strtoupper($method) === 'GET' && !empty($payload)) {
            $url .= '?' . http_build_query($payload);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 40,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,   // always verify in production
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_errno($ch);
        $curlMsg  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log("[Provider:{$endpoint}] cURL error {$curlErr}: {$curlMsg}");
            return ['status' => 'failed', 'message' => 'Network error: ' . $curlMsg, 'http_code' => $httpCode];
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            error_log("[Provider:{$endpoint}] Invalid JSON response (HTTP {$httpCode}): " . substr($response, 0, 300));
            return ['status' => 'failed', 'message' => 'Invalid response from provider', 'http_code' => $httpCode];
        }

        return $decoded;
    }

    /**
     * Map a free-text service type string onto one of our four canonical types.
     * Used by subclass normalizers to avoid repeating this logic.
     */
    protected function normServiceType(string $value): string
    {
        $v = strtolower($value);

        if (preg_match('/cable|satellite|dstv|gotv|startimes|television|\btv\b/i', $v)) {
            return 'cable';
        }
        if (preg_match('/electric|electricity|power|disco|utility|energy/i', $v)) {
            return 'electricity';
        }
        if (preg_match('/airtime|recharge|top\s?up/i', $v)) {
            return 'airtime';
        }
        if (preg_match('/data|internet|bundle/i', $v)) {
            return 'data';
        }
        if (preg_match('/exam|waec|jamb|education|pin/i', $v)) {
            return 'education';
        }

        return 'other';
    }
}
