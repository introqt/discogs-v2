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
        $this->enforceRateLimit();

        $url = self::API_BASE_URL . $endpoint;

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

        if ($statusCode === 429) {
            $this->logger->log('warning', 'Rate limit exceeded, waiting before retry');

            if ($retryCount < 3) {
                sleep(60);
                return $this->makeRequest($endpoint, $params, $method, $retryCount + 1);
            }

            return null;
        }

        if ($statusCode >= 200 && $statusCode < 300) {
            $data = json_decode($body, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->updateRateLimitTracking();
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
        } elseif (!empty($this->consumerKey) && !empty($this->consumerSecret)) {
            $headers['Authorization'] = "Discogs key={$this->consumerKey}, secret={$this->consumerSecret}";
        }

        /**
         * Filter API request headers
         *
         * @param array $headers HTTP headers
         */
        return apply_filters('ldg_api_headers', $headers);
    }

    /**
     * Enforce rate limiting
     *
     * @return void
     */
    private function enforceRateLimit(): void
    {
        $requestCount = get_transient('ldg_api_request_count');

        if ($requestCount === false) {
            set_transient('ldg_api_request_count', 1, 60);
            return;
        }

        if ((int)$requestCount >= self::RATE_LIMIT) {
            $this->logger->log('warning', 'Rate limit reached, waiting 60 seconds');
            sleep(60);
            delete_transient('ldg_api_request_count');
            set_transient('ldg_api_request_count', 1, 60);
            return;
        }
    }

    /**
     * Update rate limit tracking counter
     *
     * @return void
     */
    private function updateRateLimitTracking(): void
    {
        $requestCount = get_transient('ldg_api_request_count');

        if ($requestCount === false) {
            set_transient('ldg_api_request_count', 1, 60);
        } else {
            set_transient('ldg_api_request_count', (int)$requestCount + 1, 60);
        }
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
