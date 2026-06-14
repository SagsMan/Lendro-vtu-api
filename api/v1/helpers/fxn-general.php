<?php
/**
 * fxn-general.php — API response cache helpers
 *
 * Provider product lists don't change every second, so we cache them in the
 * `apicache` table for a configurable number of hours. This reduces external
 * API calls and keeps the service-sync fast even if a provider's endpoint is slow.
 */

/**
 * Fetch a cached value by its cache key.
 * Returns the decoded value, or null if it's not in cache or has expired.
 */
function getCache(PDO $db, string $key): mixed
{
    $stmt = $db->prepare(
        'SELECT payload, expires_at FROM apicache WHERE cachekey = ? LIMIT 1'
    );
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    // Check expiry
    if (!empty($row['expires_at']) && time() > strtotime($row['expires_at'])) {
        return null; // stale — let the caller refresh it
    }

    return json_decode($row['payload'], true);
}

/**
 * Store a value in the cache with an expiry time.
 *
 * @param mixed $payload  anything JSON-serialisable
 * @return string         the cache version string (expiry date)
 */
function setCache(PDO $db, string $key, string $group, mixed $payload): string
{
    global $CacheExpiryinHrs;

    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$CacheExpiryinHrs} hours"));
    $version   = date('Y-m-d');

    if (!is_string($payload)) {
        $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    $stmt = $db->prepare(
        'INSERT INTO apicache (cachekey, cachegroup, payload, version, expires_at)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           payload    = VALUES(payload),
           version    = VALUES(version),
           expires_at = VALUES(expires_at),
           updated_at = NOW()'
    );
    $stmt->execute([$key, $group, $payload, $version, $expiresAt]);

    return $version;
}

/**
 * Fetch all cached entries belonging to a named group.
 * Returns an associative array keyed by cachekey.
 */
function getCacheGroup(PDO $db, string $group): array
{
    $stmt = $db->prepare(
        'SELECT cachekey, payload FROM apicache WHERE cachegroup = ?'
    );
    $stmt->execute([$group]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $row) {
        $data[$row['cachekey']] = json_decode($row['payload'], true);
    }

    return $data;
}

/**
 * Wipe all entries from the cache.
 * Useful after a full service sync so stale product data doesn't linger.
 */
function clearCache(PDO $db): void
{
    $db->prepare('DELETE FROM apicache')->execute();
}
