<?php
/**
 * ProviderA — CheapDataHub integration
 */
require_once __DIR__ . '/BaseProvider.php';
require_once __DIR__ . '/../ProviderInterface.php';
require_once __DIR__ . '/ProviderProductsA.php';

class ProviderA extends BaseProvider implements ProviderInterface
{
    public function getServices(): array
    {
        return ProviderProductsA::getCheapDataProducts();
    }

    public function normalizeServices(array $raw): array
    {
        $services = [];

        foreach ($raw as $item) {
            $planId       = (string) ($item['plan_id']     ?? '');
            $network      = strtolower(trim($item['network']      ?? ''));
            $serviceType  = strtolower(trim($item['service']      ?? '')); // "airtime","data","bill","education"
            $subtype      = strtolower(trim($item['type']         ?? '')); // "sme","gifting","bundle","electricity","education","cable"
            $volume       = strtolower(trim($item['volume']       ?? ''));
            $duration     = $item['duration']      ?? null;
            $validityType = $item['validity_type'] ?? '';
            $price        = isset($item['price']) ? (float) $item['price'] : null;

            // Determine category and normalised type
            if ($serviceType === 'airtime') {
                $category = 'airtime';
                // serviceType stays 'airtime'
            } elseif ($serviceType === 'data') {
                $category = 'data';
                // serviceType stays 'data'
            } elseif ($serviceType === 'education') {
                $category    = 'education';
                $serviceType = 'education';
            } else {
                // bill, cable, electricity, etc.
                $category    = $subtype ?: $serviceType;
                $serviceType = 'bill';
            }

            // Build a clean service key
            $keyParts = array_filter([
                $network,
                $serviceType,
                $volume,
                $duration ? $duration . $validityType : '',
                // for airtime don't add the redundant 'airtime' subtype suffix
                ($subtype && !in_array($subtype, ['airtime', 'data', 'bundle'])) ? $subtype : '',
            ]);
            $serviceKey = implode('_', $keyParts);
            $serviceKey = strtolower(preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '.'], '_', $serviceKey)));

            // Human-readable name
            $name = strtoupper(trim("{$network} {$serviceType}"));
            if ($volume) {
                $name .= ' ' . strtoupper($volume);
            }
            if ($duration) {
                $name .= " ({$duration} " . ucfirst($validityType) . ($duration > 1 ? 's' : '') . ')';
            }
            if ($subtype && !in_array($subtype, ['airtime', 'data', 'bill', 'bundle', 'education'])) {
                $name .= ' ' . strtoupper($subtype);
            }
            // Use the original plan_name for education (it's already clean)
            if ($serviceType === 'education' && !empty($item['plan_name'])) {
                $name = $item['plan_name'];
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

    public function queryTransaction(string $reference): array
    {
        return $this->request('/transactions/' . urlencode($reference) . '/', [
            'apikey' => $this->apiKey,
        ], 'GET');
    }
}
