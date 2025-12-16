<?php
/**
 * Uninstall Helper Class
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle plugin uninstallation cleanup
 */
class LdgUninstall
{
    /**
     * Run uninstall cleanup
     *
     * @return void
     */
    public static function uninstall(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        check_admin_referer('bulk-plugins');

        if (__FILE__ != WP_UNINSTALL_PLUGIN) {
            return;
        }

        $deleteData = get_option('ldg_delete_data_on_uninstall', false);

        if (!$deleteData) {
            return;
        }

        self::deleteOptions();
        self::deletePostMeta();
        self::deleteTransients();
        self::deleteLogs();

        /**
         * Action hook after plugin data cleanup
         */
        do_action('ldg_uninstall_cleanup');
    }

    /**
     * Delete plugin options
     *
     * @return void
     */
    private static function deleteOptions(): void
    {
        global $wpdb;

        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ldg_%'");
    }

    /**
     * Delete plugin post meta
     *
     * @return void
     */
    private static function deletePostMeta(): void
    {
        global $wpdb;

        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_ldg_%'");
    }

    /**
     * Delete plugin transients
     *
     * @return void
     */
    private static function deleteTransients(): void
    {
        global $wpdb;

        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ldg_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ldg_%'");
    }

    /**
     * Delete log files
     *
     * @return void
     */
    private static function deleteLogs(): void
    {
        $uploadDir = wp_upload_dir();
        $logDir = $uploadDir['basedir'] . '/livedg-logs';

        if (file_exists($logDir)) {
            self::deleteDirectory($logDir);
        }
    }

    /**
     * Recursively delete directory
     *
     * @param string $dir Directory path
     * @return bool
     */
    private static function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!self::deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
