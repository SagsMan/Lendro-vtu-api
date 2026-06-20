<?php
/**
 * GET /api/v1/admin/sync-services
 *
 * Manually trigger a full service-catalogue sync from the browser.
 * Runs the same logic as cronjob/populate-services.php but returns
 * a JSON report so you can see exactly what changed.
 *
 * Authentication — include your admin token in one of these ways:
 *   Header:  Authorization: Bearer <ADMIN_TOKEN>
 *   Query:   ?token=<ADMIN_TOKEN>
 *
 * The token is set via the ADMIN_TOKEN environment variable in cPanel.
 * If ADMIN_TOKEN is not set, it defaults to a value derived from your
 * database credentials (unique per installation).
 *
 * Optional query params:
 *   ?dry_run=1   — preview the sync without writing anything to the DB
 *   ?provider=cheapdatahub  — sync a single provider only
 */

// Suppress HTML errors — this endpoint always returns JSON
ini_set('display_errors', 0);
@ini_set('max_execution_time', 120);

// ── Override CORS for admin (restrict to same origin in production) ───────────
header('Content-Type: application/json');

// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../ProviderFactory.php';

// ── Admin token auth ──────────────────────────────────────────────────────────
$adminToken = getenv('ADMIN_TOKEN') ?: '';

// Derive a fallback token from DB credentials if env var not set
if (empty($adminToken)) {
    $adminToken = substr(hash('sha256', $password . $username . 'admin-sync'), 0, 32);
}

// Accept token from Authorization header or query string
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (str_starts_with($authHeader, 'Bearer ')) {
    $providedToken = trim(substr($authHeader, 7));
} else {
    $providedToken = trim($_GET['token'] ?? '');
}

if (!hash_equals($adminToken, $providedToken)) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'failed',
        'message' => 'Unauthorized. Provide a valid admin token via ?token= or Authorization: Bearer header.',
    ]);
    exit;
}

// ── Options ───────────────────────────────────────────────────────────────────
$dryRun          = !empty($_GET['dry_run']);
$onlyProvider    = strtolower(trim($_GET['provider'] ?? ''));
$providersToSync = !empty($onlyProvider) ? [$onlyProvider] : PROVIDER_SLUGS;

// ── Run sync ──────────────────────────────────────────────────────────────────
$startTime = microtime(true);

$report = [
    'dry_run'   => $dryRun,
    'started'   => date('Y-m-d H:i:s'),
    'providers' => [],
];

$totalInserted = 0;
$totalUpdated  = 0;
$totalSkipped  = 0;
$totalErrors   = 0;

foreach ($providersToSync as $providerSlug) {

    $provReport = [
        'slug'      => $providerSlug,
        'status'    => 'ok',
        'raw_count' => 0,
        'normalized'=> 0,
        'inserted'  => 0,
        'updated'   => 0,
        'skipped'   => 0,
        'errors'    => 0,
        'messages'  => [],
    ];

    try {
        $provider = ProviderFactory::make($providerSlug, $db);

        $rawProducts = $provider->getServices();
        if (empty($rawProducts)) {
            $provReport['status']   = 'warn';
            $provReport['messages'][] = 'No products returned from provider.';
            $report['providers'][]  = $provReport;
            continue;
        }
        $provReport['raw_count'] = count($rawProducts);

        $services = $provider->normalizeServices($rawProducts);
        $provReport['normalized'] = count($services);

        $providerId = ProviderFactory::getIdBySlug($providerSlug, $db);
        if (!$providerId) {
            throw new Exception("Provider ID not found for slug '{$providerSlug}'.");
        }

        foreach ($services as $srv) {

            if (empty($srv['service_key'])) {
                $provReport['skipped']++;
                continue;
            }

            $serviceKey   = $srv['service_key'];
            $serviceType  = strtolower($srv['type']);

            if ($serviceType === 'airtime') {
                $sellingPrice = null;
                $costPrice    = null;
            } else {
                $costPrice    = (float) ($srv['price'] ?? 0);
                $sellingPrice = $costPrice > 0 ? round($costPrice * (1 + MARKUP), 2) : null;
            }

            // Check if service already exists
            $stmt = $db->prepare('SELECT id, price FROM services WHERE service_key = ? LIMIT 1');
            $stmt->execute([$serviceKey]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $serviceId = (int) $existing['id'];

                $shouldUpdate = $sellingPrice !== null && $sellingPrice > (float) $existing['price'];

                if ($shouldUpdate) {
                    if (!$dryRun) {
                        $stmt = $db->prepare('UPDATE services SET price = ?, updated_at = NOW() WHERE id = ?');
                        $stmt->execute([$sellingPrice, $serviceId]);
                    }
                    $provReport['updated']++;
                    $provReport['messages'][] = ($dryRun ? '[DRY] ' : '') . "Updated price for {$serviceKey}: ₦{$existing['price']} → ₦{$sellingPrice}";
                } else {
                    $provReport['skipped']++;
                }

            } else {
                // New service
                if (!$dryRun) {
                    $stmt = $db->prepare(
                        'INSERT INTO services
                            (service_key, name, network, type, category, price, duration, validity_unit, status, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())'
                    );
                    $stmt->execute([
                        $serviceKey,
                        $srv['name'],
                        $srv['network']       ?? '',
                        $srv['type']          ?? 'other',
                        $srv['category']      ?? $srv['type'],
                        $sellingPrice,
                        $srv['duration']      ?? null,
                        $srv['validity_unit'] ?? 'day',
                    ]);
                    $serviceId = (int) $db->lastInsertId();
                } else {
                    $serviceId = 0; // placeholder for dry run
                }

                $provReport['inserted']++;
                $provReport['messages'][] = ($dryRun ? '[DRY] ' : '') . "Inserted new service: {$serviceKey} (₦" . ($sellingPrice ?? 'variable') . ')';
            }

            // Upsert provider_services mapping
            if (!$dryRun && $serviceId > 0) {
                $stmt = $db->prepare(
                    'INSERT INTO provider_services
                        (provider_id, service_id, provider_code, cost_price, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE
                       provider_code = VALUES(provider_code),
                       cost_price    = VALUES(cost_price),
                       updated_at    = NOW()'
                );
                $stmt->execute([
                    $providerId,
                    $serviceId,
                    $srv['provider_code'] ?? '',
                    $costPrice,
                ]);
            }
        }

    } catch (Exception $e) {
        $provReport['status']     = 'error';
        $provReport['errors']++;
        $provReport['messages'][] = 'ERROR: ' . $e->getMessage();
        error_log("[AdminSync] {$providerSlug}: " . $e->getMessage());
    }

    $totalInserted += $provReport['inserted'];
    $totalUpdated  += $provReport['updated'];
    $totalSkipped  += $provReport['skipped'];
    $totalErrors   += $provReport['errors'];

    $report['providers'][] = $provReport;
}

$elapsed = round(microtime(true) - $startTime, 3);

$report['summary'] = [
    'duration_seconds' => $elapsed,
    'inserted'         => $totalInserted,
    'updated'          => $totalUpdated,
    'skipped'          => $totalSkipped,
    'errors'           => $totalErrors,
    'note'             => $dryRun ? 'DRY RUN — nothing was written to the database.' : 'Sync complete.',
];

$report['status'] = $totalErrors > 0 ? 'partial' : 'success';

http_response_code(200);
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
