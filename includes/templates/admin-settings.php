<?php
/**
 * Admin Settings Template
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

if (isset($_POST['ldg_test_connection']) && check_admin_referer('ldg_test_connection_nonce')) {
    $testResult = ldg()->discogsClient->testConnection();
    
    if ($testResult) {
        add_settings_error('ldg_messages', 'ldg_message', __('API connection successful!', 'livedg'), 'success');
    } else {
        add_settings_error('ldg_messages', 'ldg_message', __('API connection failed. Please check your credentials.', 'livedg'), 'error');
    }
}

if (isset($_POST['ldg_clear_cache']) && check_admin_referer('ldg_clear_cache_nonce')) {
    $deleted = ldg()->cache->flush();
    add_settings_error('ldg_messages', 'ldg_message', sprintf(__('Cache cleared. %d entries deleted.', 'livedg'), $deleted), 'success');
}

settings_errors('ldg_messages');
?>

<div class="wrap ldg-settings">
    <h1><?php echo esc_html__('LiveDG Settings', 'livedg'); ?></h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('ldg_settings_group');
        do_settings_sections('livedg-settings');
        submit_button(__('Save Settings', 'livedg'));
        ?>
    </form>

    <hr />

    <h2><?php echo esc_html__('Tools', 'livedg'); ?></h2>

    <div class="ldg-tools">
        <div class="ldg-tool-card">
            <h3><?php echo esc_html__('Test API Connection', 'livedg'); ?></h3>
            <p><?php echo esc_html__('Test your Discogs API credentials.', 'livedg'); ?></p>
            <form method="post">
                <?php wp_nonce_field('ldg_test_connection_nonce'); ?>
                <button type="submit" name="ldg_test_connection" class="button">
                    <?php echo esc_html__('Test Connection', 'livedg'); ?>
                </button>
            </form>
        </div>

        <div class="ldg-tool-card">
            <h3><?php echo esc_html__('Clear Cache', 'livedg'); ?></h3>
            <p><?php echo esc_html__('Clear all cached API responses.', 'livedg'); ?></p>
            <?php
            $cacheStats = ldg()->cache->getStats();
            ?>
            <p>
                <strong><?php echo esc_html__('Current Cache Size:', 'livedg'); ?></strong>
                <?php echo esc_html($cacheStats['size_formatted']); ?>
                (<?php echo esc_html($cacheStats['count']); ?> <?php echo esc_html__('items', 'livedg'); ?>)
            </p>
            <form method="post">
                <?php wp_nonce_field('ldg_clear_cache_nonce'); ?>
                <button type="submit" name="ldg_clear_cache" class="button" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to clear the cache?', 'livedg')); ?>');">
                    <?php echo esc_html__('Clear Cache', 'livedg'); ?>
                </button>
            </form>
        </div>

        <div class="ldg-tool-card">
            <h3><?php echo esc_html__('Export Logs', 'livedg'); ?></h3>
            <p><?php echo esc_html__('Export all log entries to a JSON file.', 'livedg'); ?></p>
            <button type="button" class="button" id="ldg-export-logs">
                <?php echo esc_html__('Export Logs', 'livedg'); ?>
            </button>
        </div>

        <div class="ldg-tool-card">
            <h3><?php echo esc_html__('Clear Logs', 'livedg'); ?></h3>
            <p><?php echo esc_html__('Delete all log entries.', 'livedg'); ?></p>
            <button type="button" class="button" id="ldg-clear-logs" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to clear all logs?', 'livedg')); ?>');">
                <?php echo esc_html__('Clear Logs', 'livedg'); ?>
            </button>
        </div>
    </div>

    <hr />

    <h2><?php echo esc_html__('System Information', 'livedg'); ?></h2>

    <table class="widefat">
        <tbody>
            <tr>
                <td><strong><?php echo esc_html__('Plugin Version', 'livedg'); ?></strong></td>
                <td><?php echo esc_html(LDG_VERSION); ?></td>
            </tr>
            <tr>
                <td><strong><?php echo esc_html__('WordPress Version', 'livedg'); ?></strong></td>
                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
            </tr>
            <tr>
                <td><strong><?php echo esc_html__('PHP Version', 'livedg'); ?></strong></td>
                <td><?php echo esc_html(phpversion()); ?></td>
            </tr>
            <tr>
                <td><strong><?php echo esc_html__('WooCommerce Version', 'livedg'); ?></strong></td>
                <td><?php echo esc_html(defined('WC_VERSION') ? WC_VERSION : __('Not Installed', 'livedg')); ?></td>
            </tr>
            <tr>
                <td><strong><?php echo esc_html__('WP Memory Limit', 'livedg'); ?></strong></td>
                <td><?php echo esc_html(WP_MEMORY_LIMIT); ?></td>
            </tr>
        </tbody>
    </table>
</div>
