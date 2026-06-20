<?php
/**
 * ProviderB — ConnectBridge integration
 */
require_once __DIR__ . '/BaseProvider.php';
require_once __DIR__ . '/../ProviderInterface.php';

class ProviderB extends BaseProvider implements ProviderInterface
{
    public function getServices(): array
    {
        $response = $this->request('/services', ['apikey' => $this->apiKey], 'GET');
        return $response['data'] ?? [];
    }

    public function normalizeServices(array $raw): array
    {
        $services = [];

        foreach ($raw as $item) {
            $network  = strtolower(trim($item['network'] ?? ''));
            $type     = strtolower(trim($item['type']    ?? 'data'));
            $duration = (int) ($item['duration'] ?? 30);
            $price    = isset($item['price']) ? (float) $item['price'] : null;
            $name     = trim($item['plan'] ?? $item['name'] ?? '');
            $code     = $item['code'] ?? $item['id'] ?? '';

            // Determine category
            $category = match ($type) {
                'airtime'     => 'airtime',
                'data'        => 'data',
                'electricity' => 'electricity',
                'cable'       => 'cable',
                'education'   => 'education',
                default       => $type,
            };

            // Normalise to bill type for non-airtime/data
            $normType = in_array($type, ['airtime', 'data', 'education']) ? $type : 'bill';

            // Build service key
            $serviceKey = strtolower("{$network}_{$type}");
            if (!empty($item['size'])) {
                $serviceKey .= '_' . strtolower(preg_replace('/\s+/', '', $item['size']));
            }
            $serviceKey = preg_replace('/[^a-z0-9_]/', '', $serviceKey);

            $services[] = [
                'service_key'   => $serviceKey,
                'name'          => $name ?: strtoupper("{$network} {$type}"),
                'type'          => $normType,
                'network'       => $network,
                'category'      => $category,
                'duration'      => $duration,
                'validity_unit' => 'day',
                'provider_code' => (string) $code,
                'price'         => $price,
            ];
        }

        return $services;
    }

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

    public function queryTransaction(string $reference): array
    {
        return $this->request('/transaction-status', [
            'apikey'    => $this->apiKey,
            'reference' => $reference,
        ], 'GET');
    }
}
