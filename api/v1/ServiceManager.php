<?php
/**
 * ServiceManager
 *
 * Handles all lookups related to our internal service catalogue.
 * Services are what our users see and buy — they are provider-agnostic.
 * The mapping to a specific provider lives in `provider_services`.
 */
class ServiceManager
{
    /**
     * Fetch a single service by its database ID.
     *
     * @throws Exception if the service does not exist or is inactive
     */
    public static function getService(int $serviceId, PDO $db): array
    {
        $stmt = $db->prepare('SELECT * FROM services WHERE id = ? AND status = 1 LIMIT 1');
        $stmt->execute([$serviceId]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$service) {
            throw new Exception("Service #{$serviceId} not found or is currently unavailable.");
        }

        return $service;
    }

    /**
     * Get all active providers that support a specific service,
     * ordered by priority then by cheapest cost price.
     * The worker loops through this list and tries each one in order.
     */
    public static function getAllProviders(int $serviceId, PDO $db): array
    {
        $stmt = $db->prepare(
            'SELECT ps.*, p.slug AS provider_slug, p.name AS provider_name
               FROM provider_services ps
               JOIN providers p ON ps.provider_id = p.id
              WHERE ps.service_id = ?
                AND p.status = 1
                AND ps.status = 1
              ORDER BY ps.priority ASC, ps.cost_price ASC'
        );
        $stmt->execute([$serviceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return all active services grouped by type and network.
     * This is what the /client/services endpoint returns to the frontend.
     *
     * Structure:
     * {
     *   "airtime": { "mtn": [...], "glo": [...] },
     *   "data":    { "mtn": [...], "airtel": [...] },
     *   "bill":    { "electricity": [...], "cable": [...] }
     * }
     */
    public static function getAllGrouped(PDO $db): array
    {
        $stmt = $db->prepare(
            'SELECT * FROM services WHERE status = 1
              ORDER BY type ASC, network ASC, price ASC'
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = ['airtime' => [], 'data' => [], 'bill' => []];

        foreach ($rows as $row) {
            $type    = $row['type']                ?: 'other';
            $network = strtolower($row['network']) ?: 'general';

            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            if (!isset($grouped[$type][$network])) {
                $grouped[$type][$network] = [];
            }

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

        return $grouped;
    }
}
