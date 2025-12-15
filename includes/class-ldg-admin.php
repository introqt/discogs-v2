<?php
/**
 * Admin Area Class
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define admin-specific functionality
 */
class LdgAdmin
{
    /**
     * Settings instance
     *
     * @var LdgSettings
     */
    private LdgSettings $settings;

    /**
     * Discogs client instance
     *
     * @var LdgDiscogsClient
     */
    private LdgDiscogsClient $discogsClient;

    /**
     * Importer instance
     *
     * @var LdgImporter
     */
    private LdgImporter $importer;

    /**
     * Constructor
     *
     * @param LdgSettings $settings Settings instance
     * @param LdgDiscogsClient $discogsClient Discogs client instance
     * @param LdgImporter $importer Importer instance
     */
    public function __construct(LdgSettings $settings, LdgDiscogsClient $discogsClient, LdgImporter $importer)
    {
        $this->settings = $settings;
        $this->discogsClient = $discogsClient;
        $this->importer = $importer;
    }

    /**
     * Register admin styles
     *
     * @param string $hook The current admin page
     * @return void
     */
    public function enqueueStyles(string $hook): void
    {
        if (!$this->isLdgAdminPage($hook)) {
            return;
        }

        wp_enqueue_style(
            'ldg-admin',
            LDG_PLUGIN_URL . 'assets/css/admin.css',
            [],
            LDG_VERSION,
            'all'
        );
    }

    /**
     * Register admin scripts
     *
     * @param string $hook The current admin page
     * @return void
     */
    public function enqueueScripts(string $hook): void
    {
        if (!$this->isLdgAdminPage($hook)) {
            return;
        }

        wp_enqueue_script(
            'ldg-admin',
            LDG_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            LDG_VERSION,
            true
        );

        wp_localize_script('ldg-admin', 'ldgAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ldg_admin_nonce'),
            'i18n' => [
                'confirmImport' => __('Are you sure you want to import this product?', 'livedg'),
                'importSuccess' => __('Product imported successfully!', 'livedg'),
                'importError' => __('Error importing product. Please try again.', 'livedg'),
            ],
        ]);
    }

    /**
     * Check if current page is a LiveDG admin page
     *
     * @param string $hook Current admin page hook
     * @return bool
     */
    private function isLdgAdminPage(string $hook): bool
    {
        return strpos($hook, 'livedg') !== false;
    }

    /**
     * Add plugin admin menu
     *
     * @return void
     */
    public function addAdminMenu(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        add_menu_page(
            __('LiveDG', 'livedg'),
            __('LiveDG', 'livedg'),
            'manage_woocommerce',
            'livedg',
            [$this, 'displayDashboardPage'],
            'dashicons-album',
            56
        );

        add_submenu_page(
            'livedg',
            __('Dashboard', 'livedg'),
            __('Dashboard', 'livedg'),
            'manage_woocommerce',
            'livedg',
            [$this, 'displayDashboardPage']
        );

        add_submenu_page(
            'livedg',
            __('Search Discogs', 'livedg'),
            __('Search Discogs', 'livedg'),
            'manage_woocommerce',
            'livedg-search',
            [$this, 'displaySearchPage']
        );

        add_submenu_page(
            'livedg',
            __('Settings', 'livedg'),
            __('Settings', 'livedg'),
            'manage_options',
            'livedg-settings',
            [$this, 'displaySettingsPage']
        );

        add_submenu_page(
            'livedg',
            __('Logs', 'livedg'),
            __('Logs', 'livedg'),
            'manage_options',
            'livedg-logs',
            [$this, 'displayLogsPage']
        );
    }

    /**
     * Display dashboard page
     *
     * @return void
     */
    public function displayDashboardPage(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'livedg'));
        }

        include LDG_PLUGIN_DIR . 'includes/templates/admin-dashboard.php';
    }

    /**
     * Display search page
     *
     * @return void
     */
    public function displaySearchPage(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'livedg'));
        }

        include LDG_PLUGIN_DIR . 'includes/templates/admin-search.php';
    }

    /**
     * Display settings page
     *
     * @return void
     */
    public function displaySettingsPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'livedg'));
        }

        include LDG_PLUGIN_DIR . 'includes/templates/admin-settings.php';
    }

    /**
     * Display logs page
     *
     * @return void
     */
    public function displayLogsPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'livedg'));
        }

        include LDG_PLUGIN_DIR . 'includes/templates/admin-logs.php';
    }
}
