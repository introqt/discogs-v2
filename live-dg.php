<?php
/**
 * Plugin Name: LiveDG
 * Plugin URI: https://example.com/livedg
 * Description: Integrates WooCommerce with Discogs.com REST API for music product management
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: livedg
 * Domain Path: /languages
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

define('LDG_VERSION', '1.0.0');
define('LDG_PLUGIN_FILE', __FILE__);
define('LDG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LDG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LDG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Autoload plugin classes
 *
 * @param string $class The class name to load
 * @return void
 */
function ldgAutoloader(string $class): void
{
    if (strpos($class, 'LiveDG\\') !== 0) {
        return;
    }

    $className = str_replace('LiveDG\\', '', $class);
    $className = preg_replace('/([a-z])([A-Z])/', '$1-$2', $className);
    $className = strtolower($className);
    $file = LDG_PLUGIN_DIR . 'includes/class-' . $className . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
}

spl_autoload_register(__NAMESPACE__ . '\\ldgAutoloader');

require_once LDG_PLUGIN_DIR . 'includes/class-ldg-plugin.php';

/**
 * Main instance of the plugin
 *
 * @return LdgPlugin
 */
function ldg(): LdgPlugin
{
    return LdgPlugin::getInstance();
}

/**
 * Activation hook callback
 *
 * @return void
 */
function ldgActivate(): void
{
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(LDG_PLUGIN_BASENAME);
        wp_die(
            esc_html__('LiveDG requires WooCommerce to be installed and active.', 'livedg'),
            esc_html__('Plugin Activation Error', 'livedg'),
            ['back_link' => true]
        );
    }

    require_once LDG_PLUGIN_DIR . 'includes/class-ldg-plugin.php';
    LdgPlugin::activate();
}

/**
 * Deactivation hook callback
 *
 * @return void
 */
function ldgDeactivate(): void
{
    require_once LDG_PLUGIN_DIR . 'includes/class-ldg-plugin.php';
    LdgPlugin::deactivate();
}

register_activation_hook(__FILE__, __NAMESPACE__ . '\\ldgActivate');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\\ldgDeactivate');

ldg()->run();
