<?php
/**
 * ProviderProductsA — static product catalogue for CheapDataHub
 *
 * CheapDataHub doesn't always have a clean JSON products endpoint, so we
 * scrape their public plan-ID table and supplement it with our own static lists
 * for airtime and electricity (which don't change often).
 */
class ProviderProductsA
{
    /**
     * Parse a data-plan name like "MTN 1GB SME (7 Days)" into its component parts.
     */
    public static function parseDataPlan(string $text): array
    {
        $text = trim($text);

        // Volume — e.g. "500MB", "1.5GB"
        preg_match('/([\d.]+\s*(MB|GB))/i', $text, $volumeMatch);
        $volume = strtoupper(trim($volumeMatch[1] ?? ''));

        // Duration + unit — e.g. "(7 Days)", "(1 day)"
        preg_match('/\((\d+)\s*(day|days|week|weeks|month|months)\)/i', $text, $durationMatch);
        $duration     = isset($durationMatch[1]) ? (int) $durationMatch[1] : null;
        $validityType = strtolower($durationMatch[2] ?? 'day');
        // normalise to singular
        $validityType = rtrim($validityType, 's'); // days→day, weeks→week, months→month

        // Plan type — SME, gifting, or generic bundle
        $lower = strtolower($text);
        if (strpos($lower, 'sme') !== false) {
            $type = 'sme';
        } elseif (
            strpos($lower, 'gifting') !== false ||
            strpos($lower, 'corporate') !== false ||
            strpos($lower, 'social bundle') !== false
        ) {
            $type = 'gifting';
        } else {
            $type = 'bundle';
        }

        return [
            'volume'        => $volume,
            'type'          => $type,
            'duration'      => $duration,
            'validity_type' => $validityType,
        ];
    }

    // ─── Static product lists ────────────────────────────────────────────────

    /** The four Nigerian mobile networks for airtime top-up */
    public static function getAirtimeProducts(): array
    {
        return [
            ['plan_id' => 1,  'plan_name' => 'MTN Airtime',     'network' => 'mtn',     'service' => 'airtime', 'type' => 'airtime', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
            ['plan_id' => 2,  'plan_name' => 'GLO Airtime',     'network' => 'glo',     'service' => 'airtime', 'type' => 'airtime', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
            ['plan_id' => 3,  'plan_name' => 'Airtel Airtime',  'network' => 'airtel',  'service' => 'airtime', 'type' => 'airtime', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
            ['plan_id' => 4,  'plan_name' => '9mobile Airtime', 'network' => '9mobile', 'service' => 'airtime', 'type' => 'airtime', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
        ];
    }

    /** Nigeria's electricity distribution companies (DISCOs) */
    public static function getElectricityProducts(): array
    {
        return [
            ['plan_id' => 1,  'plan_name' => 'Abuja Electricity (AEDC)',          'network' => 'AEDC',  'service' => 'bill', 'type' => 'electricity', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
            ['plan_id' => 2,  'plan_name' => 'Eko Electricity (EKEDC)',           'network' => 'EKEDC', 'service' => 'bill', 'type' => 'electricity', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
            ['plan_id' => 3,  'plan_name' => 'Ibadan Electricity (IBEDC)',        'network' => 'IBEDC', 'service' => 'bill', 'type' => 'electricity', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
            ['plan_id' => 4,  'plan_name' => 'Ikeja Electricity (IKEDC)',         'network' => 'IKEDC', 'service' => 'bill', 'type' => 'electricity', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
            ['plan_id' => 5,  'plan_name' => 'Kaduna Electricity (KEDCO)',        'network' => 'KEDCO', 'service' => 'bill', 'type' => 'electricity', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
            ['plan_id' => 6,  'plan_name' => 'Port Harcourt Electricity (PHED)', 'network' => 'PHED',  'service' => 'bill', 'type' => 'electricity', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
            ['plan_id' => 7,  'plan_name' => 'Jos Electricity (JED)',            'network' => 'JED',   'service' => 'bill', 'type' => 'electricity', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
            ['plan_id' => 8,  'plan_name' => 'Enugu Electricity (EEDC)',         'network' => 'EEDC',  'service' => 'bill', 'type' => 'electricity', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
            ['plan_id' => 9,  'plan_name' => 'Yola Electricity (YEDC)',          'network' => 'YEDC',  'service' => 'bill', 'type' => 'electricity', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
            ['plan_id' => 10, 'plan_name' => 'Benin Electricity (BEDC)',         'network' => 'BEDC',  'service' => 'bill', 'type' => 'electricity', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
        ];
    }

    /**
     * Scrape CheapDataHub's plan-ID table and merge with our electricity list.
     *
     * Returns a plain PHP array (NOT a JSON string) so ProviderA::getServices()
     * can work with it directly.
     */
    public static function getCheapDataProducts(): array
    {
        $url  = 'https://www.cheapdatahub.ng/api/plan-ids/';
        $html = @file_get_contents($url);

        $plans = [];

        if ($html) {
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            $rows  = $xpath->query('//table//tr');

            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue; // skip header
                }

                $cells = $row->getElementsByTagName('td');
                if ($cells->length < 5) {
                    continue;
                }

                $planName  = trim($cells->item(2)->textContent);
                $planNorms = self::parseDataPlan($planName);

                $plans[] = [
                    'network'       => trim($cells->item(0)->textContent),
                    'service'       => trim($cells->item(1)->textContent),
                    'plan_name'     => $planName,
                    'volume'        => $planNorms['volume'],
                    'type'          => $planNorms['type'],
                    'duration'      => $planNorms['duration'],
                    'validity_type' => $planNorms['validity_type'],
                    'plan_id'       => trim($cells->item(3)->textContent),
                    'price'         => (float) trim($cells->item(4)->textContent),
                ];
            }
        }

        // Merge scraped data plans with our static electricity list
        $electricity = self::getElectricityProducts();
        return array_merge($plans, $electricity);
    }
}
