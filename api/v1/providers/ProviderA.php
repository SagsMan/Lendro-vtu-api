<?php
/**
 * ProviderA — CheapDataHub integration
 *
 * CheapDataHub (cheapdatahub.ng) is our primary data/airtime/electricity
 * provider. Their API uses an API-key as a query/body parameter.
 *
 * Docs: https://www.cheapdatahub.ng/api/docs/ (check their reseller portal)
 */
require_once __DIR__ . '/BaseProvider.php';
require_once __DIR__ . '/../ProviderInterface.php';
require_once __DIR__ . '/ProviderProductsA.php';

class ProviderA extends BaseProvider implements ProviderInterface
{
    /**
     * Pull the complete product catalogue from CheapDataHub.
     * We delegate to ProviderProductsA which scrapes their HTML plan table
     * and supplements it with static lists for airtime and electricity.
     */
    public function getServices(): array
    {
        // getCheapDataProducts() returns a plain array — no JSON decoding needed
        return ProviderProductsA::getCheapDataProducts();
    }

    /**
     * Convert CheapDataHub's raw product list into our standard service format.
     *
     * The $raw array is what getServices() returned: a flat list of product rows.
     */
    public function normalizeServices(array $raw): array
    {
        $services = [];

        foreach ($raw as $item) {
            $planId       = (string) ($item['plan_id']     ?? '');
            $network      = strtolower(trim($item['network']      ?? ''));
            $serviceType  = strtolower(trim($item['service']      ?? '')); // "airtime", "data", "bill"
            $subtype      = strtolower(trim($item['type']         ?? '')); // "sme", "gifting", "bundle", "electricity"
            $volume       = strtolower(trim($item['volume']       ?? '')); // "1GB", "500MB"
            $duration     = $item['duration']      ?? null;
            $validityType = $item['validity_type'] ?? '';
            $price        = isset($item['price']) ? (float) $item['price'] : null;

            // Bills (electricity, cable) go under type=bill; keep the real category
            if (!in_array($serviceType, ['airtime', 'data'])) {
                $category    = $subtype;   // e.g. "electricity"
                $serviceType = 'bill';
            } else {
                $category = $serviceType;  // "airtime" or "data"
            }

            // Build a clean, human-readable service key
            // e.g. mtn_data_1gb_7day_sme  or  aedc_bill_electricity
            $parts = array_filter([$network, $serviceType, $volume, $duration ? $duration . $validityType : '', $subtype]);
            $serviceKey = implode('_', $parts);
            $serviceKey = strtolower(preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '.'], '_', $serviceKey)));

            // Human name shown to users
            $name = strtoupper(trim("{$network} {$serviceType} {$volume}"));
            if ($duration) {
                $name .= " ({$duration} " . ucfirst($validityType) . ($duration > 1 ? 's' : '') . ')';
            }
            if ($subtype && !in_array($subtype, ['airtime', 'data', 'bill'])) {
                $name .= ' ' . strtoupper($subtype);
            }

            $services[] = [
                'service_key'   => $serviceKey,
                'name'          => trim($name),
                'type'          => $serviceType,
                'network'       => $network,
                'category'      => $category,
                'duration'      => $duration,
                'validity_unit' => $validityType ?: 'day',
                'provider_code' => $planId,
                'price'         => $price,
            ];
        }

        return $services;
    }

    /**
     * Purchase a service from CheapDataHub.
     *
     * The endpoint differs depending on service type (airtime vs data vs electricity).
     * We detect the type from the payload and route accordingly.
     */
    public function purchase(array $payload): array
    {
        $serviceType = strtolower($payload['service_type'] ?? 'data');

        switch ($serviceType) {
            case 'airtime':
                return $this->request('/airtime/purchase/', [
                    'apikey'       => $this->apiKey,
                    'provider_id'  => $payload['provider_code'],
                    'phone_number' => $payload['phone'],
                    'amount'       => $payload['amount'],
                    'request_id'   => $payload['reference'],
                ], 'POST');

            case 'electricity':
                return $this->request('/electricity/purchase/', [
                    'apikey'       => $this->apiKey,
                    'disco_id'     => $payload['provider_code'],
                    'meter_number' => $payload['meter_number'] ?? '',
                    'amount'       => $payload['amount'],
                    'meter_type'   => $payload['meter_type'] ?? 'prepaid',
                    'phone_number' => $payload['phone'],
                    'request_id'   => $payload['reference'],
                ], 'POST');

            case 'cable':
                return $this->request('/cable/purchase/', [
                    'apikey'      => $this->apiKey,
                    'plan_id'     => $payload['provider_code'],
                    'cardnumber'  => $payload['smartcard_number'] ?? '',
                    'phone'       => $payload['phone'],
                    'request_id'  => $payload['reference'],
                ], 'POST');

            case 'education':
                return $this->request('/exam-pin/purchase/', [
                    'apikey'      => $this->apiKey,
                    'product_id'  => $payload['provider_code'],
                    'quantity'    => $payload['quantity'] ?? 1,
                    'request_id'  => $payload['reference'],
                ], 'POST');

            case 'data':
            default:
                return $this->request('/data/purchase/', [
                    'apikey'       => $this->apiKey,
                    'bundle_id'    => $payload['provider_code'],
                    'phone_number' => $payload['phone'],
                    'request_id'   => $payload['reference'],
                ], 'POST');
        }
    }

    /**
     * Check the status of a past transaction by our own reference.
     */
    public function queryTransaction(string $reference): array
    {
        return $this->request('/transactions/' . urlencode($reference) . '/', [
            'apikey' => $this->apiKey,
        ], 'GET');
    }
}
