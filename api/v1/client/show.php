<?php
/**
 * POST /api/v1/client/show
 *
 * Return service items filtered by type, network, or category.
 * Called by the frontend when the user opens a data/bill sub-category.
 *
 * The response includes the internal service `id` so the frontend can pass
 * it directly to the order endpoint without a second lookup.
 *
 * Params (POST or GET):
 *   type      string  required  — "data" | "airtime" | "bill"
 *   network   string  optional  — "mtn" | "mtn-data" | "airtel" | "glo" | "9mobile"
 *   category  string  optional  — "electricity-bill" | "tv-subscription" | "education" | ...
 */
require __DIR__ . '/../db.php';
requireAuth();

$type     = $_POST['type']     ?? $_GET['type']     ?? null;
$network  = $_POST['network']  ?? $_GET['network']  ?? null;
$category = $_POST['category'] ?? $_GET['category'] ?? null;

if (!$type) {
    toJSON(['status' => 'failed', 'message' => 'Service type required.']);
    exit;
}

// Map frontend identifier aliases to actual DB category values
// (the frontend uses nice slugs that the DB doesn't store)
$catAliasMap = [
    'tv-subscription'  => ['bundle', 'cable', 'cabletv'],
    'electricity-bill' => ['electricity'],
    'education'        => ['education'],
    'insurance'        => ['insurance'],
    'TRANSLOG'         => ['transport'],
    'DEALPAY'          => ['betting'],
    'RELINST'          => ['religion'],
    'SCHPB'            => ['school'],
];

// Strip the "-data" suffix that the frontend appends to network codes (e.g. "mtn-data" → "mtn")
$dbNetwork = $network;
if ($network && substr($network, -5) === '-data') {
    $dbNetwork = substr($network, 0, -5);
}

// Build query — only show services that have at least one active provider mapping
$sql    = 'SELECT s.*
             FROM services s
            WHERE s.status = 1
              AND s.type = ?
              AND s.id IN (
                    SELECT DISTINCT ps.service_id
                      FROM provider_services ps
                     WHERE ps.status = 1
                  )';
$params = [$type];

if ($dbNetwork) {
    $sql    .= ' AND LOWER(s.network) = ?';
    $params[] = strtolower($dbNetwork);
}

if ($category) {
    $dbCats       = $catAliasMap[$category] ?? [$category];
    $placeholders = implode(',', array_fill(0, count($dbCats), '?'));
    $sql         .= " AND s.category IN ({$placeholders})";
    foreach ($dbCats as $c) {
        $params[] = $c;
    }
}

$sql .= ' ORDER BY s.price ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$items = [];
foreach ($rows as $row) {
    // Convert validity to days regardless of stored unit
    $days = (int) ($row['duration'] ?? 0);
    $unit = $row['validity_unit'] ?? 'day';
    if ($unit === 'week')  $days *= 7;
    if ($unit === 'month') $days *= 30;

    $items[] = [
        'id'              => (int) $row['id'],   // ← DB primary key for /client/order
        'biller_name'     => $row['name'],
        'amount'          => $row['price'] !== null ? (float) $row['price'] : null,
        'validity_period' => $days,
        'group_name'      => $row['name'],
        'service_key'     => $row['service_key'] ?? '',
        'network'         => $row['network'] ?? '',
        'category'        => $row['category'] ?? '',
    ];
}

$billerCode = $network ?? $category ?? $type;
toJSON(['status' => 'success', 'data' => ['dataitems' => [$billerCode => $items]]]);
