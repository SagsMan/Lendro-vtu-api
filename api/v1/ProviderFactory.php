<?php
/**
 * ProviderFactory — builds provider instances from the database
 *
 * The factory reads the provider's config (base_url, api_key, etc.) from the
 * `providers` table and hands it to the right provider class.
 *
 * All methods accept a PDO instance so there is no reliance on a global $db.
 */
require_once __DIR__ . '/providers/ProviderA.php';
require_once __DIR__ . '/providers/ProviderB.php';

class ProviderFactory
{
    /**
     * Build a fully initialised provider instance by slug.
     *
     * @param  string $providerSlug  matches the `slug` column in the providers table
     * @param  PDO    $db
     * @return ProviderInterface
     * @throws Exception if the provider is unknown, inactive, or misconfigured
     */
    public static function make(string $providerSlug, PDO $db): ProviderInterface
    {
        if (empty($providerSlug)) {
            throw new Exception('Provider slug is required');
        }

        $stmt = $db->prepare('SELECT * FROM providers WHERE slug = ? AND status = 1 LIMIT 1');
        $stmt->execute([$providerSlug]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            throw new Exception("Provider not found or inactive: {$providerSlug}");
        }

        if (empty($config['base_url']) || empty($config['api_key'])) {
            throw new Exception("Provider config is incomplete for: {$providerSlug}");
        }

        switch ($providerSlug) {
            case 'cheapdatahub':
                return new ProviderA($config);

            case 'connectbridge':
                return new ProviderB($config);

            default:
                throw new Exception("No provider class registered for: {$providerSlug}");
        }
    }

    // ─── Convenience lookup helpers ──────────────────────────────────────────

    /** Get the provider config row by slug (without instantiating a class) */
    public static function getConfig(string $slug, PDO $db): ?array
    {
        $stmt = $db->prepare('SELECT * FROM providers WHERE slug = ? AND status = 1 LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Resolve a provider's database ID from its slug */
    public static function getIdBySlug(string $slug, PDO $db): ?int
    {
        $stmt = $db->prepare('SELECT id FROM providers WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['id'] : null;
    }

    /** Resolve a provider's slug from its database ID */
    public static function getSlugById(int $id, PDO $db): ?string
    {
        $stmt = $db->prepare('SELECT slug FROM providers WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['slug'] ?? null;
    }
}
