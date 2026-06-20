<?php
/**
 * GET /api/v1/client/services
 *
 * Return all active services grouped by type and network.
 * Each service includes a `providers` array listing every provider
 * (CheapDataHub, ConnectBridge, etc.) that offers it — including
 * duplicates so the frontend can display both options and choose the
 * best one at purchase time.
 *
 * Optional query params:
 *   ?type=data            filter to a single type  (airtime | data | bill | education)
 *   ?network=mtn          further filter by network
 *   ?provider=cheapdatahub  filter to a specific provider slug
 */
require_once __DIR__ . '/../db.php';

$userid = requireAuth();

$filterType     = strtolower(trim($_GET['type']     ?? ''));
$filterNetwork  = strtolower(trim($_GET['network']  ?? ''));
$filterProvider = strtolower(trim($_GET['provider'] ?? ''));

// ── Build the services query ──────────────────────────────────────────────────
$sql    = 'SELECT s.* FROM services s WHERE s.status = 1';
$params = [];

if (!empty($filterType)) {
    $sql     .= ' AND s.type = ?';
    $params[] = $filterType;
}
if (!empty($filterNetwork)) {
    $sql     .= ' AND LOWER(s.network) = ?';
    $params[] = $filterNetwork;
}

$sql .= ' ORDER BY s.type ASC, s.network ASC, s.price ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($services)) {
    sendJson(['status' => 'success', 'data' => []]);
}

// ── Fetch provider mappings for all returned services ─────────────────────────
// Join provider_services with providers so we know which provider offers what.
$serviceIds = array_column($services, 'id');
$placeholders = implode(',', array_fill(0, count($serviceIds), '?'));

$provSql = "
    SELECT
        ps.service_id,
        ps.provider_code,
        ps.cost_price,
        ps.status AS ps_status,
        p.slug    AS provider_slug,
        p.name    AS provider_name
    FROM provider_services ps
    JOIN providers p ON ps.provider_id = p.id
    WHERE ps.service_id IN ({$placeholders})
      AND ps.status = 1
      AND p.status  = 1
    ORDER BY ps.priority ASC, ps.cost_price ASC
";

$provParams = $serviceIds;

// Apply optional provider filter
if (!empty($filterProvider)) {
    $provSql     .= ' AND p.slug = ?';    // appended after ORDER BY — restructure
    // Rebuild correctly
    $provSql = "
        SELECT
            ps.service_id,
            ps.provider_code,
            ps.cost_price,
            ps.status AS ps_status,
            p.slug    AS provider_slug,
            p.name    AS provider_name
        FROM provider_services ps
        JOIN providers p ON ps.provider_id = p.id
        WHERE ps.service_id IN ({$placeholders})
          AND ps.status = 1
          AND p.status  = 1
          AND p.slug    = ?
        ORDER BY ps.priority ASC, ps.cost_price ASC
    ";
    $provParams[] = $filterProvider;
}

$stmt = $db->prepare($provSql);
$stmt->execute($provParams);
$mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Index mappings by service_id for quick lookup
$providersByService = [];
foreach ($mappings as $m) {
    $providersByService[$m['service_id']][] = [
        'slug'          => $m['provider_slug'],
        'name'          => $m['provider_name'],
        'provider_code' => $m['provider_code'],
        'cost_price'    => $m['cost_price'] !== null ? (float) $m['cost_price'] : null,
    ];
}

// ── Build the response ────────────────────────────────────────────────────────
// Group services by type → network. Each service includes its full providers list.
$grouped = [];

foreach ($services as $row) {
    $type    = $row['type']                ?: 'other';
    $network = strtolower($row['network']) ?: 'general';
    $svcId   = (int) $row['id'];

    $providers = $providersByService[$svcId] ?? [];

    // Skip services with no active provider mapping (nothing to purchase from)
    if (empty($providers)) {
        continue;
    }

    $grouped[$type][$network][] = [
        'id'        => $svcId,
        'key'       => $row['service_key'],
        'name'      => $row['name'],
        'price'     => $row['price'] !== null ? (float) $row['price'] : null,
        'category'  => $row['category'],
        'duration'  => $row['duration'],
        'unit'      => $row['validity_unit'],
        'providers' => $providers,   // ALL providers offering this service (incl. duplicates)
    ];
}

sendJson(['status' => 'success', 'data' => $grouped]);
