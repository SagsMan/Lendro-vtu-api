<?php
  /**
   * ProviderProductsA — static product catalogue for CheapDataHub
   */
  class ProviderProductsA
  {
      public static function parseDataPlan(string $text): array
      {
          $text = trim($text);
          preg_match('/([\.\d]+\s*(MB|GB))/i', $text, $volumeMatch);
          $volume = strtoupper(trim($volumeMatch[1] ?? ''));
          preg_match('/\((\d+)\s*(day|days|week|weeks|month|months)\)/i', $text, $durationMatch);
          $duration     = isset($durationMatch[1]) ? (int) $durationMatch[1] : null;
          $validityType = strtolower(rtrim($durationMatch[2] ?? 'day', 's'));
          $lower = strtolower($text);
          if (strpos($lower, 'sme') !== false) $type = 'sme';
          elseif (strpos($lower, 'gifting') !== false || strpos($lower, 'corporate') !== false || strpos($lower, 'social bundle') !== false) $type = 'gifting';
          else $type = 'bundle';
          return ['volume' => $volume, 'type' => $type, 'duration' => $duration, 'validity_type' => $validityType];
      }

      public static function getAirtimeProducts(): array
      {
          return [
              ['plan_id' => 1,  'plan_name' => 'MTN Airtime',     'network' => 'mtn',     'service' => 'airtime', 'type' => 'airtime', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
              ['plan_id' => 2,  'plan_name' => 'GLO Airtime',     'network' => 'glo',     'service' => 'airtime', 'type' => 'airtime', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
              ['plan_id' => 3,  'plan_name' => 'Airtel Airtime',  'network' => 'airtel',  'service' => 'airtime', 'type' => 'airtime', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
              ['plan_id' => 4,  'plan_name' => '9mobile Airtime', 'network' => '9mobile', 'service' => 'airtime', 'type' => 'airtime', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => null],
          ];
      }

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

      /** Education / Exam services — WAEC, JAMB, NECO, NABTEB */
      public static function getEducationProducts(): array
      {
          return [
              ['plan_id' => 'waec-result',   'plan_name' => 'WAEC Result Checker',      'network' => 'waec',   'service' => 'bill', 'type' => 'education', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => 3550.00],
              ['plan_id' => 'waec-gce',      'plan_name' => 'WAEC GCE Registration',    'network' => 'waec',   'service' => 'bill', 'type' => 'education', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => 22500.00],
              ['plan_id' => 'jamb-utme',     'plan_name' => 'JAMB UTME e-PIN',          'network' => 'jamb',   'service' => 'bill', 'type' => 'education', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => 6200.00],
              ['plan_id' => 'jamb-de',       'plan_name' => 'JAMB Direct Entry e-PIN',  'network' => 'jamb',   'service' => 'bill', 'type' => 'education', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => 4700.00],
              ['plan_id' => 'neco-result',   'plan_name' => 'NECO Result Checker',      'network' => 'neco',   'service' => 'bill', 'type' => 'education', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => 1500.00],
              ['plan_id' => 'neco-gce',      'plan_name' => 'NECO GCE Registration',    'network' => 'neco',   'service' => 'bill', 'type' => 'education', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => 16800.00],
              ['plan_id' => 'nabteb-result', 'plan_name' => 'NABTEB Result Checker',    'network' => 'nabteb', 'service' => 'bill', 'type' => 'education', 'volume' => '', 'duration' => null, 'validity_type' => '', 'price' => 1000.00],
          ];
      }

      /** Cable TV subscriptions — DSTV, GOtv, Startimes */
      public static function getCableProducts(): array
      {
          return [
              ['plan_id' => 'dstv-padi',      'plan_name' => 'DSTV Padi',        'network' => 'dstv',      'service' => 'bill', 'type' => 'cable', 'volume' => '', 'duration' => 30, 'validity_type' => 'day', 'price' => 2950.00],
              ['plan_id' => 'dstv-yanga',     'plan_name' => 'DSTV Yanga',       'network' => 'dstv',      'service' => 'bill', 'type' => 'cable', 'volume' => '', 'duration' => 30, 'validity_type' => 'day', 'price' => 4615.00],
              ['plan_id' => 'dstv-confam',    'plan_name' => 'DSTV Confam',      'network' => 'dstv',      'service' => 'bill', 'type' => 'cable', 'volume' => '', 'duration' => 30, 'validity_type' => 'day', 'price' => 9315.00],
              ['plan_id' => 'dstv-compact',   'plan_name' => 'DSTV Compact',     'network' => 'dstv',      'service' => 'bill', 'type' => 'cable', 'volume' => '', 'duration' => 30, 'validity_type' => 'day', 'price' => 15700.00],
              ['plan_id' => 'dstv-premium',   'plan_name' => 'DSTV Premium',     'network' => 'dstv',      'service' => 'bill', 'type' => 'cable', 'volume' => '', 'duration' => 30, 'validity_type' => 'day', 'price' => 37000.00],
              ['plan_id' => 'gotv-supa',      'plan_name' => 'GOtv Supa',        'network' => 'gotv',      'service' => 'bill', 'type' => 'cable', 'volume' => '', 'duration' => 30, 'validity_type' => 'day', 'price' => 6400.00],
              ['plan_id' => 'gotv-max',       'plan_name' => 'GOtv Max',         'network' => 'gotv',      'service' => 'bill', 'type' => 'cable', 'volume' => '', 'duration' => 30, 'validity_type' => 'day', 'price' => 4850.00],
              ['plan_id' => 'gotv-jolli',     'plan_name' => 'GOtv Jolli',       'network' => 'gotv',      'service' => 'bill', 'type' => 'cable', 'volume' => '', 'duration' => 30, 'validity_type' => 'day', 'price' => 3300.00],
              ['plan_id' => 'gotv-jinja',     'plan_name' => 'GOtv Jinja',       'network' => 'gotv',      'service' => 'bill', 'type' => 'cable', 'volume' => '', 'duration' => 30, 'validity_type' => 'day', 'price' => 2460.00],
              ['plan_id' => 'startimes-nova', 'plan_name' => 'Startimes Nova',   'network' => 'startimes', 'service' => 'bill', 'type' => 'cable', 'volume' => '', 'duration' => 30, 'validity_type' => 'day', 'price' => 1200.00],
              ['plan_id' => 'startimes-basic','plan_name' => 'Startimes Basic',  'network' => 'startimes', 'service' => 'bill', 'type' => 'cable', 'volume' => '', 'duration' => 30, 'validity_type' => 'day', 'price' => 2100.00],
              ['plan_id' => 'startimes-smart','plan_name' => 'Startimes Smart',  'network' => 'startimes', 'service' => 'bill', 'type' => 'cable', 'volume' => '', 'duration' => 30, 'validity_type' => 'day', 'price' => 2750.00],
          ];
      }

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
                  if ($index === 0) continue;
                  $cells = $row->getElementsByTagName('td');
                  if ($cells->length < 5) continue;
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

          // Merge ALL service types — data plans + airtime + electricity + education + cable
          return array_merge(
              $plans,
              self::getAirtimeProducts(),
              self::getElectricityProducts(),
              self::getEducationProducts(),
              self::getCableProducts()
          );
      }
  }
  