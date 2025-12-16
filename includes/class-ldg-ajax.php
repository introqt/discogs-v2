<?php
/**
 * AJAX Handler Class
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle AJAX requests
 */
class LdgAjax
{
    private const SAVED_SEARCHES_META_KEY = 'ldg_saved_searches';
    private const SAVED_SEARCHES_LIMIT = 50;

    /**
     * Importer instance
     *
     * @var LdgImporter
     */
    private LdgImporter $importer;

    /**
     * Cache instance
     *
     * @var LdgCache
     */
    private LdgCache $cache;

    /**
     * Logger instance
     *
     * @var LdgLogger
     */
    private LdgLogger $logger;

    /**
     * Constructor
     *
     * @param LdgImporter $importer Importer instance
     * @param LdgCache $cache Cache instance
     * @param LdgLogger $logger Logger instance
     */
    public function __construct(LdgImporter $importer, LdgCache $cache, LdgLogger $logger)
    {
        $this->importer = $importer;
        $this->cache = $cache;
        $this->logger = $logger;

        $this->registerAjaxHandlers();
    }

    /**
     * Register AJAX action handlers
     *
     * @return void
     */
    private function registerAjaxHandlers(): void
    {
        add_action('wp_ajax_ldg_search_releases', [$this, 'handleSearchReleases']);
        add_action('wp_ajax_ldg_get_saved_searches', [$this, 'handleGetSavedSearches']);
        add_action('wp_ajax_ldg_save_search', [$this, 'handleSaveSearch']);
        add_action('wp_ajax_ldg_delete_saved_search', [$this, 'handleDeleteSavedSearch']);
        add_action('wp_ajax_ldg_import_release', [$this, 'handleImportRelease']);
        add_action('wp_ajax_ldg_clear_cache', [$this, 'handleClearCache']);
        add_action('wp_ajax_ldg_clear_logs', [$this, 'handleClearLogs']);
        add_action('wp_ajax_ldg_export_logs', [$this, 'handleExportLogs']);
    }

    /**
     * Handle search AJAX request
     *
     * @return void
     */
    public function handleSearchReleases(): void
    {
        check_ajax_referer('ldg_search_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions', 'livedg'),
            ], 403);
        }

        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;

        $advancedParams = $this->getAdvancedSearchParamsFromRequest();
        $criteriaParams = $advancedParams;
        unset($criteriaParams['sort'], $criteriaParams['sort_order']);

        if ($query === '' && empty($criteriaParams)) {
            wp_send_json_error([
                'message' => __('Please enter a keyword or at least one filter.', 'livedg'),
            ], 400);
        }

        $discogsClient = ldg()->discogsClient;
        $params = $advancedParams;
        $params['page'] = max(1, $page);
        $results = $discogsClient->searchReleases($query, $params);

        if ($results !== null) {
            wp_send_json_success([
                'results' => $results['results'] ?? [],
                'pagination' => $results['pagination'] ?? [],
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Search failed. Please check your API credentials.', 'livedg'),
            ], 500);
        }
    }

    /**
     * Get advanced Discogs search params from request.
     *
     * @return array<string, string|int>
     */
    private function getAdvancedSearchParamsFromRequest(): array
    {
        $params = [];

        $map = [
            'artist' => 'artist',
            'release_title' => 'release_title',
            'label' => 'label',
            'catno' => 'catno',
            'format' => 'format',
            'country' => 'country',
            'genre' => 'genre',
            'style' => 'style',
        ];

        foreach ($map as $postKey => $apiKey) {
            if (!isset($_POST[$postKey])) {
                continue;
            }

            $value = sanitize_text_field((string)$_POST[$postKey]);

            if ($value !== '') {
                $params[$apiKey] = $value;
            }
        }

        if (isset($_POST['year'])) {
            $year = absint($_POST['year']);
            if ($year > 0) {
                $params['year'] = $year;
            }
        }

        $sort = isset($_POST['sort']) ? sanitize_text_field((string)$_POST['sort']) : '';
        $sortOrder = isset($_POST['sort_order']) ? sanitize_text_field((string)$_POST['sort_order']) : '';

        $allowedSort = [
            'artist',
            'title',
            'label',
            'catno',
            'year',
            'format',
            'country',
        ];

        if (in_array($sort, $allowedSort, true)) {
            $params['sort'] = $sort;
            if (in_array($sortOrder, ['asc', 'desc'], true)) {
                $params['sort_order'] = $sortOrder;
            }
        }

        return $params;
    }

    /**
     * Return saved searches for current user.
     *
     * @return void
     */
    public function handleGetSavedSearches(): void
    {
        check_ajax_referer('ldg_search_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions', 'livedg'),
            ], 403);
        }

        $searches = get_user_meta(get_current_user_id(), self::SAVED_SEARCHES_META_KEY, true);

        if (!is_array($searches)) {
            $searches = [];
        }

        wp_send_json_success([
            'searches' => array_values($searches),
        ]);
    }

    /**
     * Save a search for current user.
     *
     * @return void
     */
    public function handleSaveSearch(): void
    {
        check_ajax_referer('ldg_search_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions', 'livedg'),
            ], 403);
        }

        $name = isset($_POST['name']) ? sanitize_text_field((string)$_POST['name']) : '';
        $query = isset($_POST['query']) ? sanitize_text_field((string)$_POST['query']) : '';

        if ($name === '') {
            wp_send_json_error([
                'message' => __('Search name is required.', 'livedg'),
            ], 400);
        }

        $params = $this->getAdvancedSearchParamsFromRequest();
        $criteriaParams = $params;
        unset($criteriaParams['sort'], $criteriaParams['sort_order']);

        if ($query === '' && empty($criteriaParams)) {
            wp_send_json_error([
                'message' => __('Cannot save an empty search.', 'livedg'),
            ], 400);
        }

        $searches = get_user_meta(get_current_user_id(), self::SAVED_SEARCHES_META_KEY, true);

        if (!is_array($searches)) {
            $searches = [];
        }

        if (count($searches) >= self::SAVED_SEARCHES_LIMIT) {
            wp_send_json_error([
                'message' => sprintf(
                    __('You can only save up to %d searches.', 'livedg'),
                    self::SAVED_SEARCHES_LIMIT
                ),
            ], 400);
        }

        $id = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('ldg_', true);

        $search = [
            'id' => $id,
            'name' => $name,
            'query' => $query,
            'params' => $params,
        ];

        $searches[$id] = $search;
        update_user_meta(get_current_user_id(), self::SAVED_SEARCHES_META_KEY, $searches);

        wp_send_json_success([
            'search' => $search,
            'searches' => array_values($searches),
        ]);
    }

    /**
     * Delete a saved search for current user.
     *
     * @return void
     */
    public function handleDeleteSavedSearch(): void
    {
        check_ajax_referer('ldg_search_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions', 'livedg'),
            ], 403);
        }

        $id = isset($_POST['id']) ? sanitize_text_field((string)$_POST['id']) : '';

        if ($id === '') {
            wp_send_json_error([
                'message' => __('Invalid saved search.', 'livedg'),
            ], 400);
        }

        $searches = get_user_meta(get_current_user_id(), self::SAVED_SEARCHES_META_KEY, true);

        if (!is_array($searches) || !isset($searches[$id])) {
            wp_send_json_error([
                'message' => __('Saved search not found.', 'livedg'),
            ], 404);
        }

        unset($searches[$id]);
        update_user_meta(get_current_user_id(), self::SAVED_SEARCHES_META_KEY, $searches);

        wp_send_json_success([
            'searches' => array_values($searches),
        ]);
    }

    /**
     * Handle product import AJAX request
     *
     * @return void
     */
    public function handleImportRelease(): void
    {
        check_ajax_referer('ldg_import_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions', 'livedg'),
            ], 403);
        }

        $releaseId = isset($_POST['release_id']) ? absint($_POST['release_id']) : 0;

        if (!$releaseId) {
            wp_send_json_error([
                'message' => __('Invalid release ID', 'livedg'),
            ], 400);
        }

        $options = [
            'price' => isset($_POST['price']) ? floatval($_POST['price']) : 0,
            'status' => isset($_POST['status'])
                ? sanitize_text_field($_POST['status'])
                : get_option('ldg_default_product_status', 'draft'),
            'manage_stock' => isset($_POST['manage_stock']) && $_POST['manage_stock'] === 'true',
            'stock_quantity' => isset($_POST['stock_quantity']) ? absint($_POST['stock_quantity']) : 0,
            'import_images' => rest_sanitize_boolean(get_option('ldg_import_images', true)),
            'auto_categorize' => rest_sanitize_boolean(get_option('ldg_auto_categorize', true)),
        ];

        $productId = $this->importer->importRelease($releaseId, $options);

        if ($productId) {
            $editUrl = admin_url("post.php?post={$productId}&action=edit");

            wp_send_json_success([
                'product_id' => $productId,
                'edit_url' => $editUrl,
                'message' => sprintf(
                    __('Product imported successfully! <a href="%s">Edit product</a>', 'livedg'),
                    $editUrl
                ),
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to import product. Check logs for details.', 'livedg'),
            ], 500);
        }
    }

    /**
     * Handle clear cache AJAX request
     *
     * @return void
     */
    public function handleClearCache(): void
    {
        check_ajax_referer('ldg_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions', 'livedg'),
            ], 403);
        }

        $deleted = $this->cache->flush();

        wp_send_json_success([
            'deleted' => $deleted,
            'message' => sprintf(__('Cache cleared. %d entries deleted.', 'livedg'), $deleted),
        ]);
    }

    /**
     * Handle clear logs AJAX request
     *
     * @return void
     */
    public function handleClearLogs(): void
    {
        check_ajax_referer('ldg_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions', 'livedg'),
            ], 403);
        }

        $result = $this->logger->clearLogs();

        if ($result) {
            wp_send_json_success([
                'message' => __('Logs cleared successfully.', 'livedg'),
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to clear logs.', 'livedg'),
            ], 500);
        }
    }

    /**
     * Handle export logs AJAX request
     *
     * @return void
     */
    public function handleExportLogs(): void
    {
        check_ajax_referer('ldg_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions', 'livedg'),
            ], 403);
        }

        $filepath = $this->logger->exportLogs();

        if ($filepath) {
            $uploadDir = wp_upload_dir();
            $fileUrl = str_replace($uploadDir['basedir'], $uploadDir['baseurl'], $filepath);

            wp_send_json_success([
                'url' => $fileUrl,
                'path' => $filepath,
                'message' => __('Logs exported successfully.', 'livedg'),
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to export logs. No logs available.', 'livedg'),
            ], 500);
        }
    }
}
