<?php
$key = $_GET['key'] ?? '';
if ($key !== 'seed2024') { echo json_encode(['error'=>'forbidden']); exit; }
header('Content-Type: application/json');
try {
    $pdo = new PDO('mysql:host=localhost;dbname=tracsmda_lendro;charset=utf8mb4',
                   'tracsmda_lendrou','Lendro@Secure2024',
                   [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

    $stmt = $pdo->query("SELECT id,api_key,base_url,auth_mode FROM providers WHERE slug='connectbridge' LIMIT 1");
    $prov = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ping ConnectBridge
    $pingResult = null;
    if ($prov) {
        $authH = ($prov['auth_mode']==='token')
            ? 'Authorization: Token '.$prov['api_key']
            : 'Authorization: Bearer '.$prov['api_key'];
        $ch = curl_init(rtrim($prov['base_url'],'/').'/api/user');
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>[$authH,'Content-Type: application/json'],CURLOPT_TIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>false]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);
        $pingResult = ['http_code'=>$httpCode,'response'=>substr($raw,0,500),'curl_error'=>$curlErr,
                       'services_returned'=>0,'note'=>'ConnectBridge has NO product-listing API endpoint. getServices() returns [] by design.'];
    }

    $stmt2 = $pdo->query("SELECT COUNT(*) FROM provider_services ps JOIN providers p ON p.id=ps.provider_id WHERE p.slug='connectbridge' AND ps.status=1");
    $before = $stmt2->fetchColumn();

    // Catalogue: [service_key, name, type, network, category, provider_code, cost_price, dur, unit]
    $cat = [
        ['mtn_airtime',     'MTN Airtime VTU',     'airtime','mtn',    'airtime',   'mtn',    null,null,'day'],
        ['airtel_airtime',  'Airtel Airtime VTU',  'airtime','airtel', 'airtime',   'airtel', null,null,'day'],
        ['glo_airtime',     'GLO Airtime VTU',     'airtime','glo',    'airtime',   'glo',    null,null,'day'],
        ['9mobile_airtime', '9mobile Airtime VTU', 'airtime','9mobile','airtime',   '9mobile',null,null,'day'],
        ['mtn_data_1gb_30d','MTN 1GB 30 Days',     'data','mtn',      'data','mtn-data-1gb-30days',    320, 30,'day'],
        ['mtn_data_2gb_30d','MTN 2GB 30 Days',     'data','mtn',      'data','mtn-data-2gb-30days',    600, 30,'day'],
        ['mtn_data_5gb_30d','MTN 5GB 30 Days',     'data','mtn',      'data','mtn-data-5gb-30days',   1500, 30,'day'],
        ['airtel_1gb_30d',  'Airtel 1GB 30 Days',  'data','airtel',   'data','airtel-data-1gb-30days', 300, 30,'day'],
        ['airtel_2gb_30d',  'Airtel 2GB 30 Days',  'data','airtel',   'data','airtel-data-2gb-30days', 600, 30,'day'],
        ['glo_1gb_30d',     'GLO 1GB 30 Days',     'data','glo',      'data','glo-data-1gb-30days',    280, 30,'day'],
        ['glo_2gb_30d',     'GLO 2GB 30 Days',     'data','glo',      'data','glo-data-2gb-30days',    530, 30,'day'],
        ['9mob_1gb_30d',    '9mobile 1GB 30 Days', 'data','9mobile',  'data','9mobile-1gb-30days',     300, 30,'day'],
        ['9mob_2gb_30d',    '9mobile 2GB 30 Days', 'data','9mobile',  'data','9mobile-2gb-30days',     600, 30,'day'],
        ['dstv_padi',       'DSTv Padi',           'bill','dstv',     'cable','dstv-padi',    2950,30,'day'],
        ['dstv_compact',    'DSTv Compact',        'bill','dstv',     'cable','dstv-compact', 15700,30,'day'],
        ['gotv_jinja',      'GOtv Jinja',          'bill','gotv',     'cable','gotv-jinja',    2715,30,'day'],
        ['gotv_max',        'GOtv Max',            'bill','gotv',     'cable','gotv-max',      6200,30,'day'],
        ['startimes_basic', 'StarTimes Basic',     'bill','startimes','cable','startimes-basic',2600,30,'day'],
        ['aedc_elec',  'AEDC Electricity', 'bill','AEDC', 'electricity','AEDC', null,null,'day'],
        ['ekedc_elec', 'EKEDC Electricity','bill','EKEDC','electricity','EKEDC',null,null,'day'],
        ['ibedc_elec', 'IBEDC Electricity','bill','IBEDC','electricity','IBEDC',null,null,'day'],
        ['ikedc_elec', 'IKEDC Electricity','bill','IKEDC','electricity','IKEDC',null,null,'day'],
        ['phed_elec',  'PHED Electricity', 'bill','PHED', 'electricity','PHED', null,null,'day'],
        ['waec_card',  'WAEC Scratch Card', 'education','waec',  'education','waec',  4700,null,'day'],
        ['neco_card',  'NECO Scratch Card', 'education','neco',  'education','neco',  1100,null,'day'],
        ['jamb_mock',  'JAMB Mock',          'education','jamb',  'education','jamb',  3500,null,'day'],
        ['nabteb_card','NABTEB Scratch Card','education','nabteb','education','nabteb', 900,null,'day'],
    ];

    $ins=0; $lnk=0; $skp=0;
    $provId = $prov ? (int)$prov['id'] : null;

    if ($provId) foreach ($cat as [$sk,$nm,$tp,$nw,$ctg,$pcode,$cost,$dur,$unit]) {
        // Markup
        $sell = null;
        if ($cost !== null) {
            $rnd = ($cost>=1000)?50:10;
            $mkp = ($cost<=1000)?20:(($cost<=2500)?50:100);
            $sell = ceil(($cost+$mkp)/$rnd)*$rnd;
        }
        // Upsert service
        $s = $pdo->prepare("SELECT id FROM services WHERE service_key=? LIMIT 1");
        $s->execute([$sk]);
        $ex = $s->fetch(PDO::FETCH_ASSOC);
        if ($ex) { $sid=(int)$ex['id']; $skp++; }
        else {
            $s=$pdo->prepare("INSERT INTO services (service_key,name,type,network,category,price,duration,validity_unit,status,created_at) VALUES(?,?,?,?,?,?,?,?,1,NOW())");
            $s->execute([$sk,$nm,$tp,$nw,$ctg,$sell,$dur,$unit]);
            $sid=(int)$pdo->lastInsertId(); $ins++;
        }
        // Link to connectbridge
        $s=$pdo->prepare("SELECT id FROM provider_services WHERE provider_id=? AND service_id=? LIMIT 1");
        $s->execute([$provId,$sid]);
        if (!$s->fetch()) {
            $pdo->prepare("INSERT INTO provider_services(provider_id,service_id,provider_code,cost_price,status,created_at,updated_at) VALUES(?,?,?,?,1,NOW(),NOW())")->execute([$provId,$sid,$pcode,$cost]);
            $lnk++;
        }
    }

    $stmt3 = $pdo->query("SELECT COUNT(*) FROM provider_services ps JOIN providers p ON p.id=ps.provider_id WHERE p.slug='connectbridge' AND ps.status=1");
    $after = $stmt3->fetchColumn();

    echo json_encode(['connectbridge_ping'=>$pingResult,'services_in_db_before'=>(int)$before,'seed'=>['inserted'=>$ins,'linked'=>$lnk,'skipped_existing'=>$skp],'services_in_db_after'=>(int)$after],JSON_PRETTY_PRINT);
} catch(Exception $e){ echo json_encode(['error'=>$e->getMessage()]); }
