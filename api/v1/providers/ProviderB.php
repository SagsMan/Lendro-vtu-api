<?php
/**
 * ProviderB — ConnectBridge integration
 *
 * ConnectBridge (connectbridge.com.ng) is our secondary/fallback provider.
 * Their API structure is slightly different from CheapDataHub but our
 * normalizer maps everything to the same internal format.
 */
require_once __DIR__ . '/BaseProvider.php';
require_once __DIR__ . '/../ProviderInterface.php';

class ProviderB extends BaseProvider implements ProviderInterface
{
    /**
     * Fetch all available services from ConnectBridge.
     * Their /services endpoint returns a paginated or flat JSON list.
     */
    public function getServices(): array
    {
        $response = $this->request('/services', ['apikey' => $this->apiKey], 'GET');

        // Return the data array, or empty if the call failed
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
            $type     = strtolower(trim($item['type']    ?? 'data')); // "airtime" | "data"
            $duration = (int) ($item['duration'] ?? 30);
            $price    = isset($item['price']) ? (float) $item['price'] : null;
            $name     = trim($item['plan'] ?? $item['name'] ?? '');
            $code     = $item['code'] ?? $item['id'] ?? '';

            // Derive service key the same way ProviderA does
            $serviceKey = strtolower("{$network}_{$type}");
            if (!empty($item['size'])) {
                $serviceKey .= '_' . strtolower(preg_replace('/\s+/', '', $item['size']));
            }
            $serviceKey = preg_replace('/[^a-z0-9_]/', '', $serviceKey);

            $services[] = [
                'service_key'   => $serviceKey,
                'name'          => $name ?: strtoupper("{$network} {$type}"),
                'type'          => $type,
                'network'       => $network,
                'category'      => $type,   // for data/airtime, category equals type
                'duration'      => $duration,
                'validity_unit' => 'day',
                'provider_code' => (string) $code,
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
