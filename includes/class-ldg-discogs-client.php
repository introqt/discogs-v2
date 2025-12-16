<?php
/**
 * Discogs API Client Class
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle all Discogs API communications
 */
class LdgDiscogsClient
{
    /**
     * Discogs API base URL
     */
    private const API_BASE_URL = 'https://api.discogs.com';

    /**
     * Rate limit: requests per minute
     */
    private const RATE_LIMIT = 60;

    /**
     * Logger instance
     *
     * @var LdgLogger
     */
    private LdgLogger $logger;

    /**
     * Cache instance
     *
     * @var LdgCache
     */
    private LdgCache $cache;

    /**
     * Consumer key
     *
     * @var string
     */
    private string $consumerKey;

    /**
     * Consumer secret
     *
     * @var string
     */
    private string $consumerSecret;

    /**
     * Access token
     *
     * @var string
     */
    private string $accessToken;

    /**
     * User agent
     *
     * @var string
     */
    private string $userAgent;

    /**
     * Constructor
     *
     * @param LdgLogger $logger Logger instance
     * @param LdgCache $cache Cache instance
     */
    public function __construct(LdgLogger $logger, LdgCache $cache)
    {
        $this->logger = $logger;
        $this->cache = $cache;

        $this->consumerKey = get_option('ldg_discogs_consumer_key', '');
        $this->consumerSecret = get_option('ldg_discogs_consumer_secret', '');
        $this->accessToken = get_option('ldg_discogs_access_token', '');
        $this->userAgent = get_option('ldg_discogs_user_agent', 'LiveDG/1.0 +' . get_site_url());
    }

    /**
     * Search for releases on Discogs
     *
     * @param string $query Search query
     * @param array $params Additional search parameters
     * @return array|null
     */
    public function searchReleases(string $query, array $params = []): ?array
    {
        $cacheKey = 'ldg_search_' . md5($query . serialize($params));
        $cached = $this->cache->get($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $defaultParams = [
            'q' => $query,
            'type' => 'release',
            'per_page' => 50,
            'page' => 1,
        ];

        $params = array_merge($defaultParams, $params);

        $response = $this->makeRequest('/database/search', $params);

        if ($response && isset($response['results'])) {
            $this->cache->set($cacheKey, $response, 3600);
            return $response;
        }

        return null;
    }

    /**
     * Get release details by ID
     *
     * @param int $releaseId Release ID
     * @return array|null
     */
    public function getRelease(int $releaseId): ?array
    {
        $cacheKey = 'ldg_release_' . $releaseId;
        $cached = $this->cache->get($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->makeRequest("/releases/{$releaseId}");

        if ($response) {
            $this->cache->set($cacheKey, $response, 86400);
            return $response;
        }

        return null;
    }

    /**
     * Get artist details by ID
     *
     * @param int $artistId Artist ID
     * @return array|null
     */
    public function getArtist(int $artistId): ?array
    {
        $cacheKey = 'ldg_artist_' . $artistId;
        $cached = $this->cache->get($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->makeRequest("/artists/{$artistId}");

        if ($response) {
            $this->cache->set($cacheKey, $response, 86400);
            return $response;
        }

        return null;
    }

    /**
     * Get label details by ID
     *
     * @param int $labelId Label ID
     * @return array|null
     */
    public function getLabel(int $labelId): ?array
    {
        $cacheKey = 'ldg_label_' . $labelId;
        $cached = $this->cache->get($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->makeRequest("/labels/{$labelId}");

        if ($response) {
            $this->cache->set($cacheKey, $response, 86400);
            return $response;
        }

        return null;
    }

    /**
     * Make HTTP request to Discogs API
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @param string $method HTTP method
     * @param int $retryCount Current retry attempt
     * @return array|null
     */
    private function makeRequest(
        string $endpoint,
        array $params = [],
        string $method = 'GET',
        int $retryCount = 0
    ): ?array {
        $rateLimitBlock = $this->enforceRateLimit();

        if (is_wp_error($rateLimitBlock)) {
            $this->logger->log('warning', $rateLimitBlock->get_error_message());
            return null;
        }

        $url = self::API_BASE_URL . $endpoint;

        if (empty($this->accessToken) && !empty($this->consumerKey) && !empty($this->consumerSecret)) {
            $params['key'] = $this->consumerKey;
            $params['secret'] = $this->consumerSecret;
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $args = [
            'method' => $method,
            'headers' => $this->getHeaders(),
            'timeout' => 30,
        ];

        $this->logger->log('info', "Making {$method} request to {$endpoint}", ['params' => $params]);

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $this->logger->log('error', 'API request failed', [
                'endpoint' => $endpoint,
                'error' => $response->get_error_message(),
            ]);

            if ($retryCount < 3) {
                sleep(pow(2, $retryCount));
                return $this->makeRequest($endpoint, $params, $method, $retryCount + 1);
            }

            return null;
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);

        if ($statusCode === 429) {
            $retryAfter = $this->getRetryAfterSeconds($headers);
            $this->updateRateLimitTracking($headers, $statusCode);

            $message = $retryAfter > 0
                ? sprintf('Rate limit exceeded. Try again in %d seconds.', $retryAfter)
                : 'Rate limit exceeded. Please retry shortly.';

            $this->logger->log('warning', $message, ['retry_after' => $retryAfter]);

            if ($retryCount < 1 && $retryAfter > 0 && $retryAfter <= 5) {
                sleep($retryAfter);
                return $this->makeRequest($endpoint, $params, $method, $retryCount + 1);
            }

            return null;
        }

        if ($statusCode >= 200 && $statusCode < 300) {
            $data = json_decode($body, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->updateRateLimitTracking($headers, $statusCode);
                return $data;
            }

            $this->logger->log('error', 'Failed to decode JSON response', ['body' => $body]);
            return null;
        }

        $this->logger->log('error', "API returned status code {$statusCode}", [
            'endpoint' => $endpoint,
            'body' => $body,
        ]);

        return null;
    }

    /**
     * Get HTTP headers for API request
     *
     * @return array
     */
    private function getHeaders(): array
    {
        $headers = [
            'User-Agent' => $this->userAgent,
            'Accept' => 'application/json',
        ];

        if (!empty($this->accessToken)) {
            $headers['Authorization'] = 'Discogs token=' . $this->accessToken;
        }

        /**
         * Filter API request headers
         *
         * @param array $headers HTTP headers
         */
        return apply_filters('ldg_api_headers', $headers);
    }

    /**
     * Enforce rate limiting based on cached Discogs guidance
     *
     * @return \WP_Error|null
     */
    private function enforceRateLimit(): ?\WP_Error
    {
        $state = get_transient('ldg_api_rate_limit_state');

        if (!$state || empty($state['reset'])) {
            return null;
        }

        $secondsUntilReset = (int)$state['reset'] - time();

        if (!isset($state['remaining']) || $secondsUntilReset <= 0) {
            delete_transient('ldg_api_rate_limit_state');
            return null;
        }

        if ((int)$state['remaining'] <= 0) {
            $message = sprintf(
                /* translators: %d: seconds until Discogs rate limit resets */
                __('Discogs rate limit reached. Try again in %d seconds.', 'livedg'),
                max($secondsUntilReset, 1)
            );

            return new \WP_Error('ldg_rate_limit', $message, [
                'retry_after' => $secondsUntilReset,
            ]);
        }

        return null;
    }

    /**
     * Update rate limit tracking counter
     *
     * @param array $headers Response headers
     * @param int $statusCode Response status code
     * @return void
     */
    private function updateRateLimitTracking(array $headers, int $statusCode): void
    {
        $remaining = $this->getHeaderValue($headers, 'x-discogs-ratelimit-remaining');
        $reset = $this->getHeaderValue($headers, 'x-discogs-ratelimit-reset');
        $retryAfter = $this->getRetryAfterSeconds($headers);

        if ($remaining === null && $reset === null && $retryAfter === 0) {
            return;
        }

        $resetTime = time();

        if ($reset !== null) {
            $resetTime += (int)$reset;
        } elseif ($retryAfter > 0) {
            $resetTime += $retryAfter;
        }

        $state = [
            'remaining' => $remaining !== null ? (int)$remaining : 0,
            'reset' => $resetTime,
            'last_status' => $statusCode,
        ];

        set_transient('ldg_api_rate_limit_state', $state, max($resetTime - time(), 60));
    }

    /**
     * Extract a header value regardless of case
     *
     * @param array|\WP_HTTP_Headers $headers Response headers
     * @param string $name Header name
     * @return string|null
     */
    private function getHeaderValue($headers, string $name): ?string
    {
        if (empty($headers)) {
            return null;
        }

        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (strtolower((string)$key) === strtolower($name)) {
                    return is_array($value) ? (string)reset($value) : (string)$value;
                }
            }

            return null;
        }

        $value = $headers->get($name);

        if ($value === null) {
            $value = $headers->get(strtolower($name));
        }

        return $value !== null ? (string)$value : null;
    }

    /**
     * Parse retry-after value from response headers
     *
     * @param array|\WP_HTTP_Headers $headers Response headers
     * @return int
     */
    private function getRetryAfterSeconds($headers): int
    {
        $retryAfter = $this->getHeaderValue($headers, 'retry-after');

        if ($retryAfter === null) {
            $retryAfter = $this->getHeaderValue($headers, 'x-discogs-ratelimit-reset');
        }

        return $retryAfter !== null ? max(0, (int)$retryAfter) : 0;
    }

    /**
     * Test API connection
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        $response = $this->makeRequest('/');

        return $response !== null;
    }
}
