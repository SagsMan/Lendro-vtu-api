<?php
  require __DIR__."/../db.php";
  requireAuth();

  $type    = $_POST["type"]    ?? $_GET["type"]    ?? null;
  $network = $_POST["network"] ?? $_GET["network"] ?? null;

  if (!$type) { toJSON(["status"=>"failed","message"=>"Service type required."]); exit; }

  $dbNetwork = $network;
  if ($network && str_ends_with($network, '-data')) $dbNetwork = substr($network, 0, -5);

  $sql    = "SELECT * FROM services WHERE status = 1 AND type = ?";
  $params = [$type];
  if ($dbNetwork) { $sql .= " AND LOWER(network) = ?"; $params[] = strtolower($dbNetwork); }
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
          'group_name'     => strtoupper($row['network'] ?? '').' Data',
          'service_key'    => $row['service_key'] ?? '',
      ];
  }

  $billerCode = $network ?? $type;
  toJSON(["status"=>"success","data"=>["dataitems"=>[$billerCode=>$items]]]);
  