<?php
/**
 * BaseProvider — shared HTTP request logic for all providers.
 *
 * Supports two auth modes:
 *   "bearer" (default) — Authorization: Bearer <key>   (ProviderA / CheapDataHub)
 *   "token"            — Authorization: Token  <key>   (ProviderB / ConnectBridge)
 */
abstract class BaseProvider
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $authMode; // "bearer" | "token"

    public function __construct(array $config)
    {
        $this->baseUrl  = rtrim($config['base_url'], '/');
        $this->apiKey   = $config['api_key'];
        // providers table may have an auth_mode column; default is "bearer"
        $this->authMode = strtolower(trim($config['auth_mode'] ?? 'bearer'));
    }

    /**
     * Generic HTTP helper — supports GET and POST.
     * Returns decoded JSON or a normalised failure array.
     */
    protected function request(string $endpoint, array $payload = [], string $method = 'GET'): array
    {
        $url = $this->baseUrl . $endpoint;

        if (strtoupper($method) === 'GET' && !empty($payload)) {
            $url .= '?' . http_build_query($payload);
        }

        $authHeader = ($this->authMode === 'token')
            ? 'Authorization: Token '  . $this->apiKey
            : 'Authorization: Bearer ' . $this->apiKey;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 40,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                $authHeader,
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
            error_log("[Provider:{$endpoint}] Invalid JSON (HTTP {$httpCode}): " . substr($response, 0, 300));
            return ['status' => 'failed', 'message' => 'Invalid response from provider', 'http_code' => $httpCode];
        }

        return $decoded;
    }

    /**
     * Map a free-text service type onto one of our four canonical types.
     */
    protected function normServiceType(string $value): string
    {
        $v = strtolower($value);
        if (preg_match('/cable|satellite|dstv|gotv|startimes|television|\btv\b/i', $v)) return 'cable';
        if (preg_match('/electric|electricity|power|disco|utility|energy/i', $v))        return 'electricity';
        if (preg_match('/airtime|recharge|top\s?up/i', $v))                              return 'airtime';
        if (preg_match('/data|internet|bundle/i', $v))                                   return 'data';
        if (preg_match('/exam|waec|jamb|education|pin/i', $v))                           return 'education';
        return 'other';
    }
}
