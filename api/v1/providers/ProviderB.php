<?php
/**
 * ProviderB — ConnectBridge integration
 *
 * ConnectBridge (connectbridge.com.ng) is our secondary/fallback provider.
 * All services come directly from their live API — no static lists.
 */
require_once __DIR__ . '/BaseProvider.php';
require_once __DIR__ . '/../ProviderInterface.php';

class ProviderB extends BaseProvider implements ProviderInterface
{
    /**
     * Fetch all available services from ConnectBridge's live API.
     */
    public function getServices(): array
    {
        $response = $this->request('/services', ['apikey' => $this->apiKey], 'GET');
        return $response['data'] ?? [];
    }

    /**
     * Normalise ConnectBridge's product list into our standard service structure.
     */
    public function normalizeServices(array $raw): array
    {
        $services = [];

        foreach ($raw as $item) {
            $network  = strtolower(trim($item['network'] ?? ''));
            $rawType  = strtolower(trim($item['type']    ?? 'data'));
            $duration = isset($item['duration']) ? (int) $item['duration'] : null;
            $price    = isset($item['price'])    ? (float) $item['price']  : null;
            $name     = trim($item['plan']  ?? $item['name']  ?? '');
            $code     = (string) ($item['code'] ?? $item['id'] ?? '');
            $size     = trim($item['size']  ?? '');

            // Map raw type → canonical category + normalised service type
            $category = match ($rawType) {
                'electricity' => 'electricity',
                'cable'       => 'cable',
                'education'   => 'education',
                'airtime'     => 'airtime',
                default       => 'data',
            };

            $normType = match ($rawType) {
                'electricity', 'cable' => 'bill',
                'education'            => 'education',
                'airtime'              => 'airtime',
                default                => 'data',
            };

            // Build a consistent service key:  {network}_{type}_{size}_{duration}
            $keyParts = [$network, $rawType];
            if (!empty($size)) {
                $keyParts[] = strtolower(preg_replace('/\s+/', '', $size));
            }
            if ($duration && $rawType === 'data') {
                $keyParts[] = $duration . 'day';
            }
            $serviceKey = preg_replace('/[^a-z0-9_]/', '', implode('_', $keyParts));

            if (!$name) {
                $name = strtoupper("{$network} {$rawType}" . ($size ? " {$size}" : ''));
            }

            $services[] = [
                'service_key'   => $serviceKey,
                'name'          => $name,
                'type'          => $normType,
                'network'       => $network,
                'category'      => $category,
                'duration'      => $duration,
                'validity_unit' => 'day',
                'provider_code' => $code,
                'price'         => $price,
            ];
        }

        return $services;
    }

    /**
     * Purchase a service from ConnectBridge.
     */
    public function purchase(array $payload): array
    {
        return $this->request('/purchase', [
            'apikey'     => $this->apiKey,
            'service'    => $payload['provider_code'],
            'phone'      => $payload['phone'],
            'amount'     => $payload['amount'],
            'request_id' => $payload['reference'],
        ], 'POST');
    }

    /**
     * Query a past transaction's status from ConnectBridge.
     */
    public function queryTransaction(string $reference): array
    {
        return $this->request('/transaction-status', [
            'apikey'    => $this->apiKey,
            'reference' => $reference,
        ], 'GET');
    }
}
