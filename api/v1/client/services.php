<?php
/**
 * GET /api/v1/client/services
 *
 * Return all active, normalised services grouped by type and network.
 * This is what the frontend reads to build the service selection UI.
 *
 * Optional query params:
 *   ?type=data            filter to a single type  (airtime | data | bill)
 *   ?network=mtn          further filter by network
 *
 * Response shape:
 * {
 *   "status": "success",
 *   "data": {
 *     "airtime": { "mtn": [...], "glo": [...] },
 *     "data":    { "mtn": [...], "airtel": [...] },
 *     "bill":    { "electricity": [...], "cable": [...] }
 *   }
 * }
 */
require_once __DIR__ . '/../db.php';

$userid = requireAuth();

$filterType    = strtolower(trim($_GET['type']    ?? ''));
$filterNetwork = strtolower(trim($_GET['network'] ?? ''));

// Build query based on optional filters
$sql    = 'SELECT * FROM services WHERE status = 1';
$params = [];

if (!empty($filterType)) {
    $sql    .= ' AND type = ?';
    $params[] = $filterType;
}
if (!empty($filterNetwork)) {
    $sql    .= ' AND LOWER(network) = ?';
    $params[] = $filterNetwork;
}

$sql .= ' ORDER BY type ASC, network ASC, price ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group results into a nested structure the frontend can render directly
$grouped = [];

foreach ($rows as $row) {
    $type    = $row['type']                ?: 'other';
    $network = strtolower($row['network']) ?: 'general';

    $grouped[$type][$network][] = [
        'id'       => (int) $row['id'],
        'key'      => $row['service_key'],
        'name'     => $row['name'],
        'price'    => $row['price'] !== null ? (float) $row['price'] : null,
        'category' => $row['category'],
        'duration' => $row['duration'],
        'unit'     => $row['validity_unit'],
    ];
}

sendJson(['status' => 'success', 'data' => $grouped]);
