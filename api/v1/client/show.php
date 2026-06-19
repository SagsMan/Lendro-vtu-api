<?php
    require __DIR__."/../db.php";
    requireAuth();

    $type     = $_POST["type"]     ?? $_GET["type"]     ?? null;
    $network  = $_POST["network"]  ?? $_GET["network"]  ?? null;
    $category = $_POST["category"] ?? $_GET["category"] ?? null;

    if (!$type) { toJSON(["status"=>"failed","message"=>"Service type required."]); exit; }

    // identifier → actual DB category values (handles tv-subscription, electricity-bill etc.)
    $catAliasMap = [
      'tv-subscription'  => ['bundle','cable','cabletv'],
      'electricity-bill' => ['electricity'],
      'education'        => ['education'],
      'insurance'        => ['insurance'],
      'TRANSLOG'         => ['transport'],
      'DEALPAY'          => ['betting'],
      'RELINST'          => ['religion'],
      'SCHPB'            => ['school'],
    ];

    // strip -data suffix from network (e.g. mtn-data → mtn)
    $dbNetwork = $network;
    if ($network && str_ends_with($network, '-data')) $dbNetwork = substr($network, 0, -5);

    $sql    = "SELECT * FROM services WHERE status = 1 AND type = ? AND id IN (SELECT DISTINCT service_id FROM provider_services WHERE status = 1)";
    $params = [$type];

    if ($dbNetwork) {
      $sql .= " AND LOWER(network) = ?";
      $params[] = strtolower($dbNetwork);
    }

    if ($category) {
      $dbCats = $catAliasMap[$category] ?? [$category];
      $placeholders = implode(',', array_fill(0, count($dbCats), '?'));
      $sql .= " AND category IN ($placeholders)";
      foreach ($dbCats as $c) $params[] = $c;
    }

    $sql .= " ORDER BY price ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rows as $row) {
        $days = (int)($row['duration'] ?? 0);
        $unit = $row['validity_unit'] ?? 'day';
        if ($unit === 'week')  $days *= 7;
        if ($unit === 'month') $days *= 30;
        $items[] = [
            'biller_name'    => $row['name'],
            'amount'         => $row['price'] !== null ? (float)$row['price'] : null,
            'validity_period'=> $days,
            'group_name'     => $row['name'],
            'service_key'    => $row['service_key'] ?? '',
            'network'        => $row['network'] ?? '',
            'category'       => $row['category'] ?? '',
        ];
    }

    $billerCode = $network ?? $category ?? $type;
    toJSON(["status"=>"success","data"=>["dataitems"=>[$billerCode=>$items]]]);
  