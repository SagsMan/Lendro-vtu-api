<?php
/**
 * Cronjob: Sync & Populate Services
 *
 * Pulls the product catalogue from every active provider, normalises each item
 * into our standard service format, applies our markup, and upserts the result
 * into the `services` and `provider_services` tables.
 *
 * Run this once a day (or every few hours) to keep your catalogue fresh.
 *
 * Cron schedule example (runs every 6 hours):
 *   0 */6 * * * php /var/www/html/api/v1/cronjob/populate-services.php >> /var/log/lendro-sync.log 2>&1
 *
 * You can also trigger it manually:
 *   php api/v1/cronjob/populate-services.php
 */

// This script runs from CLI — no JSON headers needed
define('RUNNING_FROM_CLI', php_sapi_name() === 'cli');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../ProviderFactory.php';

$startTime = microtime(true);
$stats     = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

log_line("=== Lendro Service Sync Started ===");
log_line("Providers to sync: " . implode(', ', PROVIDER_SLUGS));

foreach (PROVIDER_SLUGS as $providerSlug) {

    log_line("\n--- Syncing provider: {$providerSlug} ---");

    try {
        // Build the provider instance from its DB config
        $provider = ProviderFactory::make($providerSlug, $db);

        // 1. Fetch raw products from the provider
        log_line("  Fetching product catalogue...");
        $rawProducts = $provider->getServices();

        if (empty($rawProducts)) {
            log_line("  WARNING: No products returned from {$providerSlug}. Skipping.");
            continue;
        }

        log_line("  Got " . count($rawProducts) . " raw products.");

        // 2. Normalise into our standard format
        $services = $provider->normalizeServices($rawProducts);
        log_line("  Normalised to " . count($services) . " services.");

        // 3. Get this provider's database ID once (not inside the loop)
        $providerId = ProviderFactory::getIdBySlug($providerSlug, $db);

        if (!$providerId) {
            log_line("  ERROR: Provider ID not found for slug '{$providerSlug}'. Skipping.");
            $stats['errors']++;
            continue;
        }

        // 4. Upsert each service into the database
        foreach ($services as $srv) {

            // Skip items with no service key (bad data)
            if (empty($srv['service_key'])) {
                $stats['skipped']++;
                continue;
            }

            $serviceKey  = $srv['service_key'];
            $serviceType = strtolower($srv['type']);

            // Airtime has no fixed price — provider sends whatever amount the user requests
            if ($serviceType === 'airtime') {
                $sellingPrice = null;
                $costPrice    = null;
            } else {
                $costPrice    = (float) ($srv['price'] ?? 0);
                // Apply our markup on top of the provider's cost price
                $sellingPrice = $costPrice > 0 ? round($costPrice * (1 + MARKUP), 2) : null;
            }

            // Does this service already exist in our catalogue?
            $stmt = $db->prepare('SELECT id, price FROM services WHERE service_key = ? LIMIT 1');
            $stmt->execute([$serviceKey]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $serviceId = (int) $existing['id'];

                // Only update the price if our new selling price is higher
                // (we always store the most expensive option so the markup covers all providers)
                if ($sellingPrice !== null && $sellingPrice > (float) $existing['price']) {
                    $stmt = $db->prepare('UPDATE services SET price = ?, updated_at = NOW() WHERE id = ?');
                    $stmt->execute([$sellingPrice, $serviceId]);
                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }

            } else {
                // Brand-new service — insert it
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
                $stats['inserted']++;
            }

            // 5. Upsert the provider→service mapping
            //    This records which provider_code (SKU) to use when purchasing through this provider
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

        log_line("  Done with {$providerSlug}.");

    } catch (Exception $e) {
        $stats['errors']++;
        log_line("  ERROR syncing {$providerSlug}: " . $e->getMessage());
        error_log("[ServiceSync] {$providerSlug}: " . $e->getMessage());
    }
}

$elapsed = round(microtime(true) - $startTime, 2);
log_line("\n=== Sync Complete in {$elapsed}s ===");
log_line("  Inserted : {$stats['inserted']}");
log_line("  Updated  : {$stats['updated']}");
log_line("  Skipped  : {$stats['skipped']}");
log_line("  Errors   : {$stats['errors']}");

// ── Helper ────────────────────────────────────────────────────────────────────
function log_line(string $msg): void
{
    $ts = date('[Y-m-d H:i:s]');
    echo "{$ts} {$msg}\n";
}
