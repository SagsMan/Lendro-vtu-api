<?php
/**
 * Cronjob: Sync Provider Services
 *
 * Fetches the current product lists from all active providers and upserts
 * them into the `services` and `provider_services` tables.
 *
 * Run via cron (e.g. daily at 2 AM):
 *   0 2 * * * php /home/tracsmda/lendro/api/v1/cronjob/sync_provider_services.php
 *
 * Also safe to run manually from shell for a fresh sync.
 */

require __DIR__ . '/../db.php';
require __DIR__ . '/../ProviderFactory.php';
require __DIR__ . '/../helpers/helpers.php';

$syncStart = microtime(true);
echo "=== Provider Service Sync (" . date('Y-m-d H:i:s') . ") ===\n\n";

// PROVIDER_SLUGS is defined in configs.php as ['cheapdatahub', 'connectbridge']
// We load provider details from the DB so we have the numeric ID for foreign keys.
foreach (PROVIDER_SLUGS as $slug) {

    echo "── Provider: {$slug}\n";

    // Load the provider row (we need the numeric id for provider_services)
    $stmt = $db->prepare('SELECT id, name, active FROM providers WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $providerRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$providerRow) {
        echo "   SKIP: Provider '{$slug}' not found in DB.\n\n";
        continue;
    }
    if (!$providerRow['active']) {
        echo "   SKIP: Provider '{$slug}' is inactive.\n\n";
        continue;
    }

    $providerId   = (int) $providerRow['id'];
    $providerName = $providerRow['name'];

    try {
        // Instantiate the provider class (ProviderFactory uses the slug)
        $provider = ProviderFactory::make($slug, $db);

        // getServices() returns a raw array from the provider's API
        $rawServices = $provider->getServices();

        if (empty($rawServices)) {
            echo "   WARNING: No services returned from {$providerName}.\n\n";
            continue;
        }

        // normalizeServices() maps the raw API response to our standard structure:
        // [ service_key, name, type, network, category, duration,
        //   validity_unit, provider_code, price ]
        $services = method_exists($provider, 'normalizeServices')
            ? $provider->normalizeServices($rawServices)
            : $rawServices;

        $inserted = 0;
        $updated  = 0;
        $mapped   = 0;

        foreach ($services as $service) {
            // ── 1. Upsert into services table ────────────────────────────────
            $stmt = $db->prepare('SELECT id, price FROM services WHERE service_key = ? LIMIT 1');
            $stmt->execute([$service['service_key']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                // New service — insert
                $stmt = $db->prepare(
                    'INSERT INTO services
                        (name, type, network, category, service_key,
                         price, duration, validity_unit, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
                );
                $stmt->execute([
                    $service['name'],
                    $service['type'],
                    $service['network'] ?? '',
                    $service['category'] ?? $service['type'],
                    $service['service_key'],
                    $service['price'] ?? null,
                    $service['duration'] ?? null,
                    $service['validity_unit'] ?? 'day',
                ]);
                $serviceId = (int) $db->lastInsertId();
                $inserted++;
            } else {
                $serviceId = (int) $existing['id'];
                // Update the price if the provider changed it
                if ((float) ($existing['price'] ?? 0) !== (float) ($service['price'] ?? 0)) {
                    $stmt = $db->prepare('UPDATE services SET price = ?, updated_at = NOW() WHERE id = ?');
                    $stmt->execute([$service['price'] ?? null, $serviceId]);
                    $updated++;
                }
            }

            // ── 2. Upsert into provider_services table ───────────────────────
            $stmt = $db->prepare(
                'SELECT id FROM provider_services WHERE service_id = ? AND provider_id = ? LIMIT 1'
            );
            $stmt->execute([$serviceId, $providerId]);
            $mapping = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$mapping) {
                $stmt = $db->prepare(
                    'INSERT INTO provider_services
                        (service_id, provider_id, provider_code, cost_price, status)
                     VALUES (?, ?, ?, ?, 1)'
                );
                $stmt->execute([
                    $serviceId,
                    $providerId,
                    (string) ($service['provider_code'] ?? ''),
                    $service['price'] ?? null,
                ]);
                $mapped++;
            } else {
                // Keep provider_code and cost_price in sync
                $stmt = $db->prepare(
                    'UPDATE provider_services
                        SET provider_code = ?, cost_price = ?, updated_at = NOW()
                      WHERE service_id = ? AND provider_id = ?'
                );
                $stmt->execute([
                    (string) ($service['provider_code'] ?? ''),
                    $service['price'] ?? null,
                    $serviceId,
                    $providerId,
                ]);
            }
        }

        echo "   Done: {$inserted} new services, {$updated} price updates, {$mapped} new mappings.\n\n";

    } catch (Exception $e) {
        echo "   ERROR: " . $e->getMessage() . "\n\n";
        error_log("[sync_provider_services] {$slug}: " . $e->getMessage());
    }
}

$elapsed = round(microtime(true) - $syncStart, 2);
echo "=== Sync complete in {$elapsed}s ===\n";
