<?php
/**
 * Uninstall Script
 *
 * @package LiveDG
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-ldg-uninstall.php';

LiveDG\LdgUninstall::uninstall();
