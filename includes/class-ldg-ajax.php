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
        add_action('wp_ajax_ldg_import_release', [$this, 'handleImportRelease']);
        add_action('wp_ajax_ldg_clear_cache', [$this, 'handleClearCache']);
        add_action('wp_ajax_ldg_clear_logs', [$this, 'handleClearLogs']);
        add_action('wp_ajax_ldg_export_logs', [$this, 'handleExportLogs']);
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
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'draft',
            'manage_stock' => isset($_POST['manage_stock']) && $_POST['manage_stock'] === 'true',
            'stock_quantity' => isset($_POST['stock_quantity']) ? absint($_POST['stock_quantity']) : 0,
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
