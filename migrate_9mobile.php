<?php
  /**
   * One-time migration: insert 9mobile data plans if missing.
   * Call once: https://lendro.trackd.live/migrate_9mobile.php
   * Deletes itself after running.
   */
  require_once __DIR__ . '/api/v1/db.php';

  $stmt = $db->query("SELECT COUNT(*) FROM services WHERE type='data' AND LOWER(network)='9mobile'");
  $exists = (int)$stmt->fetchColumn();

  if ($exists > 0) {
      echo json_encode(['status'=>'skipped','message'=>"9mobile data already exists ($exists rows)"]);
      @unlink(__FILE__); exit;
  }

  $plans = [
    ['9mobile_data_200mb_1day','9MOBILE DATA 200MB (1 Day)','9mobile','data','data',150.00,1,'day'],
    ['9mobile_data_500mb_30day','9MOBILE DATA 500MB (30 Days)','9mobile','data','data',230.00,30,'day'],
    ['9mobile_data_1gb_1day','9MOBILE DATA 1GB (1 Day)','9mobile','data','data',300.00,1,'day'],
    ['9mobile_data_1gb_30day','9MOBILE DATA 1GB (30 Days)','9mobile','data','data',500.00,30,'day'],
    ['9mobile_data_1_5gb_30day','9MOBILE DATA 1.5GB (30 Days)','9mobile','data','data',1000.00,30,'day'],
    ['9mobile_data_2gb_30day','9MOBILE DATA 2GB (30 Days)','9mobile','data','data',1200.00,30,'day'],
    ['9mobile_data_3gb_30day','9MOBILE DATA 3GB (30 Days)','9mobile','data','data',1500.00,30,'day'],
    ['9mobile_data_5gb_30day','9MOBILE DATA 5GB (30 Days)','9mobile','data','data',2500.00,30,'day'],
    ['9mobile_data_10gb_30day','9MOBILE DATA 10GB (30 Days)','9mobile','data','data',3500.00,30,'day'],
    ['9mobile_data_500mb_7day','9MOBILE DATA 500MB (7 Days)','9mobile','data','data',500.00,7,'day'],
    ['9mobile_data_1gb_7day','9MOBILE DATA 1GB (7 Days)','9mobile','data','data',1000.00,7,'day'],
    ['9mobile_data_2gb_7day','9MOBILE DATA 2GB (7 Days)','9mobile','data','data',1500.00,7,'day'],
  ];

  $stmt = $db->prepare("INSERT INTO services (service_key,name,network,type,category,price,duration,validity_unit,status) VALUES (?,?,?,?,?,?,?,?,1)");
  $inserted = 0;
  foreach ($plans as $p) {
    try { $stmt->execute($p); $inserted++; } catch(Exception $e) { /* skip dup */ }
  }
  echo json_encode(['status'=>'success','inserted'=>$inserted,'message'=>"$inserted 9mobile data plans added"]);
  @unlink(__FILE__);
  