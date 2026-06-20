<?php
/**
 * ProviderB — ConnectBridge integration
 *
 * Auth:  Authorization: Token <api_key>   (set auth_mode='token' in providers table)
 * Base:  https://connectbridge.com.ng
 *
 * Confirmed working endpoints (from live API validation):
 *   POST /api/airtime  — fields: network, phone/mobile_number, amount
 *   POST /api/data     — fields: data_plan/plan, phone/mobile_number, bypass/ported_number
 *   POST /api/cable    — fields: cable_name/provider, smartcard_number/iuc_number, plan_id, phone
 *   POST /api/electricity — fields: disco_name/provider, meter_number, meter_type, amount, phone
 *   POST /api/education  — fields: exam_body, quantity, phone
 */
require_once __DIR__ . '/BaseProvider.php';
require_once __DIR__ . '/../ProviderInterface.php';

class ProviderB extends BaseProvider implements ProviderInterface
{
    /**
     * ConnectBridge does not have a dedicated service-listing endpoint.
     * We return an empty array — services are managed manually in the DB
     * via the providers table and the populate-services cronjob.
     */
    public function getServices(): array
    {
        return [];
    }

    /**
     * ConnectBridge does not require normalization (no live product sync).
     */
    public function normalizeServices(array $raw): array
    {
        return [];
    }

    /**
     * Purchase a service from ConnectBridge.
     *
     * $payload keys (set by TransactionService / workers):
     *   service_type     — "airtime" | "data" | "cable" | "electricity" | "education"
     *   provider_code    — ConnectBridge plan/service code
     *   phone            — recipient phone (11 digits)
     *   amount           — transaction amount (for airtime / electricity)
     *   reference        — our unique order reference
     *   network          — network slug (for airtime/data)
     *   smartcard_number — IUC number (cable)
     *   meter_number     — meter number (electricity)
     *   meter_type       — "prepaid" | "postpaid" (electricity)
     *   quantity         — number of exam pins (education)
     */
    public function purchase(array $payload): array
    {
        $serviceType = strtolower($payload['service_type'] ?? 'data');
        $phone       = $payload['phone']    ?? '';
        $reference   = $payload['reference'] ?? '';

        switch ($serviceType) {
            case 'airtime':
                return $this->request('/api/airtime', [
                    'network'        => $payload['network'] ?? '',
                    'mobile_number'  => $phone,
                    'amount'         => $payload['amount'],
                    'request_id'     => $reference,
                ], 'POST');

            case 'data':
                return $this->request('/api/data', [
                    'network'        => $payload['network'] ?? '',
                    'plan'           => $payload['provider_code'],
                    'mobile_number'  => $phone,
                    'bypass'         => 0,
                    'request_id'     => $reference,
                ], 'POST');

            case 'cable':
                return $this->request('/api/cable', [
                    'cable_name'         => $payload['cable_name']       ?? $payload['provider_code'] ?? '',
                    'smartcard_number'   => $payload['smartcard_number'] ?? '',
                    'plan_id'            => $payload['provider_code'],
                    'phone'              => $phone,
                    'request_id'         => $reference,
                ], 'POST');

            case 'electricity':
                return $this->request('/api/electricity', [
                    'disco_name'     => $payload['disco_name']    ?? $payload['provider_code'] ?? '',
                    'meter_number'   => $payload['meter_number']  ?? '',
                    'meter_type'     => $payload['meter_type']    ?? 'prepaid',
                    'amount'         => $payload['amount'],
                    'phone'          => $phone,
                    'request_id'     => $reference,
                ], 'POST');

            case 'education':
                return $this->request('/api/education', [
                    'exam_body'      => $payload['provider_code'],
                    'quantity'       => $payload['quantity'] ?? 1,
                    'phone'          => $phone,
                    'request_id'     => $reference,
                ], 'POST');

            default:
                return $this->request('/api/data', [
                    'network'        => $payload['network'] ?? '',
                    'plan'           => $payload['provider_code'],
                    'mobile_number'  => $phone,
                    'bypass'         => 0,
                    'request_id'     => $reference,
                ], 'POST');
        }
    }

    /**
     * Query a past transaction's status from ConnectBridge.
     */
    public function queryTransaction(string $reference): array
    {
        return $this->request('/api/query', [
            'request_id' => $reference,
        ], 'POST');
    }
}
