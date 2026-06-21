<?php
/**
 * GET/POST /api/v1/admin/seed-connectbridge
 *
 * Diagnostic + Seeder for ConnectBridge.
 *
 * WHY this exists:
 *   ConnectBridge does NOT expose a product-listing API endpoint.
 *   ProviderB::getServices() therefore returns [] by design.
 *   The populate-services cronjob skips ConnectBridge with "No products returned".
 *   This script:
 *     1. Tests a live ConnectBridge API call (airtime) to prove credentials work
 *     2. Shows what is currently in the DB for connectbridge
 *     3. Seeds the DB with a static ConnectBridge service catalogue
 *
 * Access: open in browser (basic token gate via ?key=seed2024)
 */

define('RUNNING_FROM_CLI', php_sapi_name() === 'cli');
require_once __DIR__ . '/../db.php';

// Simple security gate
$key = $_GET['key'] ?? $_SERVER['argv'][1] ?? '';
if (!RUNNING_FROM_CLI && $key !== 'seed2024') {
    http_response_code(403);
    echo json_encode(['error' => 'Provide ?key=seed2024 to access this script.']);
    exit;
}

header('Content-Type: application/json');

// ── 1. Test ConnectBridge credentials with a balance/ping call ────────────────
$stmt = $db->prepare("SELECT api_key, base_url, auth_mode FROM providers WHERE slug='connectbridge' LIMIT 1");
$stmt->execute();
$prov = $stmt->fetch(PDO::FETCH_ASSOC);

$pingResult = null;
$httpCode   = null;

if ($prov) {
    $authHeader = ($prov['auth_mode'] === 'token')
        ? 'Authorization: Token ' . $prov['api_key']
        : 'Authorization: Bearer ' . $prov['api_key'];

    $ch = curl_init(rtrim($prov['base_url'],'/') . '/api/user');
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [$authHeader, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);
    $pingResult = ['http_code' => $httpCode, 'raw' => substr($raw,0,500), 'curl_error' => $curlErr];
}

// ── 2. What's in DB right now for connectbridge ───────────────────────────────
$stmt = $db->query(
    "SELECT s.type, s.network, s.category, COUNT(*) as cnt
       FROM services s
       JOIN provider_services ps ON ps.service_id = s.id
       JOIN providers p ON p.id = ps.provider_id
      WHERE p.slug = 'connectbridge' AND ps.status = 1
      GROUP BY s.type, s.network, s.category"
);
$existingInDB = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 3. Static ConnectBridge service catalogue ─────────────────────────────────
$catalogue = [
  // airtime
  ['key'=>'mtn_airtime',     'name'=>'MTN Airtime VTU',    'type'=>'airtime','network'=>'mtn',     'category'=>'airtime',   'code'=>'mtn',     'price'=>null, 'dur'=>null,'unit'=>'day'],
  ['key'=>'airtel_airtime',  'name'=>'Airtel Airtime VTU', 'type'=>'airtime','network'=>'airtel',  'category'=>'airtime',   'code'=>'airtel',  'price'=>null, 'dur'=>null,'unit'=>'day'],
  ['key'=>'glo_airtime',     'name'=>'GLO Airtime VTU',    'type'=>'airtime','network'=>'glo',     'category'=>'airtime',   'code'=>'glo',     'price'=>null, 'dur'=>null,'unit'=>'day'],
  ['key'=>'9mobile_airtime', 'name'=>'9mobile Airtime VTU','type'=>'airtime','network'=>'9mobile', 'category'=>'airtime',   'code'=>'9mobile', 'price'=>null, 'dur'=>null,'unit'=>'day'],
  // data
  ['key'=>'mtn_data_1gb_30d',    'name'=>'MTN 1GB 30 Days',    'type'=>'data','network'=>'mtn',    'category'=>'data','code'=>'mtn-data-1gb-30days',    'price'=>320,  'dur'=>30,'unit'=>'day'],
  ['key'=>'mtn_data_2gb_30d',    'name'=>'MTN 2GB 30 Days',    'type'=>'data','network'=>'mtn',    'category'=>'data','code'=>'mtn-data-2gb-30days',    'price'=>600,  'dur'=>30,'unit'=>'day'],
  ['key'=>'mtn_data_5gb_30d',    'name'=>'MTN 5GB 30 Days',    'type'=>'data','network'=>'mtn',    'category'=>'data','code'=>'mtn-data-5gb-30days',    'price'=>1500, 'dur'=>30,'unit'=>'day'],
  ['key'=>'mtn_data_10gb_30d',   'name'=>'MTN 10GB 30 Days',   'type'=>'data','network'=>'mtn',    'category'=>'data','code'=>'mtn-data-10gb-30days',   'price'=>3000, 'dur'=>30,'unit'=>'day'],
  ['key'=>'airtel_data_1gb_30d', 'name'=>'Airtel 1GB 30 Days', 'type'=>'data','network'=>'airtel', 'category'=>'data','code'=>'airtel-data-1gb-30days', 'price'=>300,  'dur'=>30,'unit'=>'day'],
  ['key'=>'airtel_data_2gb_30d', 'name'=>'Airtel 2GB 30 Days', 'type'=>'data','network'=>'airtel', 'category'=>'data','code'=>'airtel-data-2gb-30days', 'price'=>600,  'dur'=>30,'unit'=>'day'],
  ['key'=>'glo_data_1gb_30d',    'name'=>'GLO 1GB 30 Days',    'type'=>'data','network'=>'glo',    'category'=>'data','code'=>'glo-data-1gb-30days',    'price'=>280,  'dur'=>30,'unit'=>'day'],
  ['key'=>'glo_data_2gb_30d',    'name'=>'GLO 2GB 30 Days',    'type'=>'data','network'=>'glo',    'category'=>'data','code'=>'glo-data-2gb-30days',    'price'=>530,  'dur'=>30,'unit'=>'day'],
  ['key'=>'9mobile_data_1gb_30d','name'=>'9mobile 1GB 30 Days','type'=>'data','network'=>'9mobile','category'=>'data','code'=>'9mobile-data-1gb-30days','price'=>300,  'dur'=>30,'unit'=>'day'],
  ['key'=>'9mobile_data_2gb_30d','name'=>'9mobile 2GB 30 Days','type'=>'data','network'=>'9mobile','category'=>'data','code'=>'9mobile-data-2gb-30days','price'=>600,  'dur'=>30,'unit'=>'day'],
  // cable
  ['key'=>'dstv_padi',         'name'=>'DSTv Padi',         'type'=>'bill','network'=>'dstv',      'category'=>'cable','code'=>'dstv-padi',         'price'=>2950,  'dur'=>30,'unit'=>'day'],
  ['key'=>'dstv_compact',      'name'=>'DSTv Compact',      'type'=>'bill','network'=>'dstv',      'category'=>'cable','code'=>'dstv-compact',      'price'=>15700, 'dur'=>30,'unit'=>'day'],
  ['key'=>'dstv_compact_plus', 'name'=>'DSTv Compact+',     'type'=>'bill','network'=>'dstv',      'category'=>'cable','code'=>'dstv-compact-plus', 'price'=>25000, 'dur'=>30,'unit'=>'day'],
  ['key'=>'gotv_jinja',        'name'=>'GOtv Jinja',        'type'=>'bill','network'=>'gotv',      'category'=>'cable','code'=>'gotv-jinja',        'price'=>2715,  'dur'=>30,'unit'=>'day'],
  ['key'=>'gotv_max',          'name'=>'GOtv Max',          'type'=>'bill','network'=>'gotv',      'category'=>'cable','code'=>'gotv-max',          'price'=>6200,  'dur'=>30,'unit'=>'day'],
  ['key'=>'startimes_basic',   'name'=>'StarTimes Basic',   'type'=>'bill','network'=>'startimes', 'category'=>'cable','code'=>'startimes-basic',   'price'=>2600,  'dur'=>30,'unit'=>'day'],
  // electricity
  ['key'=>'aedc_electricity',  'name'=>'AEDC Electricity',  'type'=>'bill','network'=>'AEDC', 'category'=>'electricity','code'=>'AEDC', 'price'=>null,'dur'=>null,'unit'=>'day'],
  ['key'=>'ekedc_electricity', 'name'=>'EKEDC Electricity', 'type'=>'bill','network'=>'EKEDC','category'=>'electricity','code'=>'EKEDC','price'=>null,'dur'=>null,'unit'=>'day'],
  ['key'=>'ibedc_electricity', 'name'=>'IBEDC Electricity', 'type'=>'bill','network'=>'IBEDC','category'=>'electricity','code'=>'IBEDC','price'=>null,'dur'=>null,'unit'=>'day'],
  ['key'=>'ikedc_electricity', 'name'=>'IKEDC Electricity', 'type'=>'bill','network'=>'IKEDC','category'=>'electricity','code'=>'IKEDC','price'=>null,'dur'=>null,'unit'=>'day'],
  ['key'=>'phed_electricity',  'name'=>'PHED Electricity',  'type'=>'bill','network'=>'PHED', 'category'=>'electricity','code'=>'PHED', 'price'=>null,'dur'=>null,'unit'=>'day'],
  // education
  ['key'=>'waec_scratch_card',  'name'=>'WAEC Scratch Card',  'type'=>'education','network'=>'waec',  'category'=>'education','code'=>'waec',   'price'=>4700,'dur'=>null,'unit'=>'day'],
  ['key'=>'neco_scratch_card',  'name'=>'NECO Scratch Card',  'type'=>'education','network'=>'neco',  'category'=>'education','code'=>'neco',   'price'=>1100,'dur'=>null,'unit'=>'day'],
  ['key'=>'jamb_mock',          'name'=>'JAMB Mock',           'type'=>'education','network'=>'jamb',  'category'=>'education','code'=>'jamb',   'price'=>3500,'dur'=>null,'unit'=>'day'],
  ['key'=>'nabteb_scratch_card','name'=>'NABTEB Scratch Card', 'type'=>'education','network'=>'nabteb','category'=>'education','code'=>'nabteb', 'price'=>900, 'dur'=>null,'unit'=>'day'],
];

// ── 4. Seed ───────────────────────────────────────────────────────────────────
$provId   = null;
$inserted = 0; $linked = 0; $skipped = 0;

$stmt = $db->prepare("SELECT id FROM providers WHERE slug='connectbridge' LIMIT 1");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) $provId = (int)$row['id'];

if ($provId) {
    foreach ($catalogue as $srv) {
        $price = ($srv['price'] !== null) ? mockupPrice((float)$srv['price']) : null;

        // Upsert service
        $stmt = $db->prepare("SELECT id FROM services WHERE service_key=? LIMIT 1");
        $stmt->execute([$srv['key']]);
        $ex = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ex) {
            $svcId = (int)$ex['id'];
            $skipped++;
        } else {
            $stmt = $db->prepare(
                "INSERT INTO services (service_key,name,type,network,category,provider_code,price,duration,validity_unit,status,created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,1,NOW())"
            );
            $stmt->execute([$srv['key'],$srv['name'],$srv['type'],$srv['network'],$srv['category'],$srv['code'],$price,$srv['dur'],$srv['unit']]);
            $svcId = (int)$db->lastInsertId();
            $inserted++;
        }

        // Link to ConnectBridge provider
        $stmt = $db->prepare("SELECT id FROM provider_services WHERE provider_id=? AND service_id=? LIMIT 1");
        $stmt->execute([$provId, $svcId]);
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO provider_services (provider_id,service_id,status,created_at) VALUES (?,?,1,NOW())");
            $stmt->execute([$provId, $svcId]);
            $linked++;
        }
    }
}

echo json_encode([
    'connectbridge_ping'  => $pingResult,
    'services_in_db_before_seed' => $existingInDB,
    'seed_result' => [
        'provider_id' => $provId,
        'total_in_catalogue' => count($catalogue),
        'inserted' => $inserted,
        'linked'   => $linked,
        'skipped_existing' => $skipped,
    ],
    'note' => 'ConnectBridge has no product-listing API — services are managed via this static catalogue. Run this script once to seed, then the app will use them.'
], JSON_PRETTY_PRINT);
