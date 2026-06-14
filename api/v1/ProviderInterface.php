<?php
/**
 * ProviderInterface
 *
 * Every VTU provider class MUST implement this contract.
 * That way, the rest of the system can treat all providers the same way
 * without knowing anything about the individual provider's API quirks.
 */
interface ProviderInterface
{
    /**
     * Fetch the raw product/service list from this provider.
     * Returns an array that can be passed straight into normalizeServices().
     */
    public function getServices(): array;

    /**
     * Convert the provider's raw product list into our standard service structure.
     *
     * Each item in the returned array should have:
     *   - service_key   (string)  e.g. "mtn_data_1gb_7day_sme"
     *   - name          (string)  human-readable label
     *   - type          (string)  "airtime" | "data" | "bill"
     *   - network       (string)  e.g. "mtn", "glo", "airtel", "9mobile"
     *   - category      (string)  e.g. "airtime", "data", "electricity", "cable"
     *   - duration      (int|null) number of days validity
     *   - validity_unit (string)  "day" | "week" | "month"
     *   - provider_code (string)  the provider's internal plan ID / SKU
     *   - price         (float)   provider's cost price (before our markup)
     */
    public function normalizeServices(array $raw): array;

    /**
     * Send a purchase request to this provider.
     *
     * Expected $payload keys:
     *   - provider_code  the provider's internal SKU
     *   - phone          recipient's phone number
     *   - amount         transaction amount in naira
     *   - reference      our unique reference (LDR-xxxx-xxxx)
     *
     * Returns the raw provider response array.
     */
    public function purchase(array $payload): array;

    /**
     * Ask the provider for the current status of a past transaction.
     * Used by the reconciliation worker.
     */
    public function queryTransaction(string $reference): array;
}
