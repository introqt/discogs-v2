<?php
/**
 * Cache Helper Class
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle caching using WordPress transients
 */
class LdgCache
{
    /**
     * Cache key prefix
     */
    private const PREFIX = 'ldg_cache_';

    /**
     * Default cache duration in seconds
     */
    private int $defaultDuration;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->defaultDuration = (int)get_option('ldg_cache_duration', 3600);
    }

    /**
     * Get cached value
     *
     * @param string $key Cache key
     * @return mixed Cached value or false if not found
     */
    public function get(string $key): mixed
    {
        $cacheKey = $this->buildKey($key);
        return get_transient($cacheKey);
    }

    /**
     * Set cached value
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $duration Cache duration in seconds
     * @return bool
     */
    public function set(string $key, mixed $value, ?int $duration = null): bool
    {
        $cacheKey = $this->buildKey($key);
        $duration = $duration ?? $this->defaultDuration;

        return set_transient($cacheKey, $value, $duration);
    }

    /**
     * Delete cached value
     *
     * @param string $key Cache key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $cacheKey = $this->buildKey($key);
        return delete_transient($cacheKey);
    }

    /**
     * Check if cache key exists
     *
     * @param string $key Cache key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== false;
    }

    /**
     * Clear all plugin caches
     *
     * @return int Number of cache entries deleted
     */
    public function flush(): int
    {
        global $wpdb;

        $pattern = self::PREFIX . '%';

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $pattern
            )
        );

        $deleted += $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . $pattern
            )
        );

        /**
         * Action hook after cache flush
         *
         * @param int $deleted Number of entries deleted
         */
        do_action('ldg_cache_flushed', $deleted);

        return (int)$deleted;
    }

    /**
     * Build cache key with prefix
     *
     * @param string $key Raw cache key
     * @return string Prefixed cache key
     */
    private function buildKey(string $key): string
    {
        return self::PREFIX . sanitize_key($key);
    }

    /**
     * Remember value with callback
     *
     * @param string $key Cache key
     * @param callable $callback Callback to generate value if not cached
     * @param int|null $duration Cache duration in seconds
     * @return mixed Cached or newly generated value
     */
    public function remember(string $key, callable $callback, ?int $duration = null): mixed
    {
        $value = $this->get($key);

        if ($value !== false) {
            return $value;
        }

        $value = $callback();

        if ($value !== null && $value !== false) {
            $this->set($key, $value, $duration);
        }

        return $value;
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function getStats(): array
    {
        global $wpdb;

        $pattern = self::PREFIX . '%';

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $pattern
            )
        );

        $size = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $pattern
            )
        );

        return [
            'count' => (int)$count,
            'size_bytes' => (int)$size,
            'size_formatted' => size_format((int)$size),
        ];
    }
}
