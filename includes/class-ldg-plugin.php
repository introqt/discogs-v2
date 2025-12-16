<?php
/**
 * Main Plugin Class
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin singleton class
 */
class LdgPlugin
{
    /**
     * Plugin instance
     *
     * @var LdgPlugin|null
     */
    private static ?LdgPlugin $instance = null;

    /**
     * Loader instance
     *
     * @var LdgLoader
     */
    public LdgLoader $loader;

    /**
     * Admin instance
     *
     * @var LdgAdmin
     */
    public LdgAdmin $admin;

    /**
     * Settings instance
     *
     * @var LdgSettings
     */
    public LdgSettings $settings;

    /**
     * Discogs client instance
     *
     * @var LdgDiscogsClient
     */
    public LdgDiscogsClient $discogsClient;

    /**
     * Importer instance
     *
     * @var LdgImporter
     */
    public LdgImporter $importer;

    /**
     * Logger instance
     *
     * @var LdgLogger
     */
    public LdgLogger $logger;

    /**
     * Cache instance
     *
     * @var LdgCache
     */
    public LdgCache $cache;

    /**
     * AJAX handler instance
     *
     * @var LdgAjax
     */
    public LdgAjax $ajax;

    /**
     * Public handler instance
     *
     * @var LdgPublic
     */
    public LdgPublic $public;

    /**
     * Get singleton instance
     *
     * @return LdgPlugin
     */
    public static function getInstance(): LdgPlugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->loadDependencies();
        $this->setLocale();
        $this->defineAdminHooks();
        $this->definePublicHooks();
    }

    /**
     * Load required dependencies
     *
     * @return void
     */
    private function loadDependencies(): void
    {
        $this->loader = new LdgLoader();
        $this->logger = new LdgLogger();
        $this->cache = new LdgCache();
        $this->settings = new LdgSettings();
        $this->discogsClient = new LdgDiscogsClient($this->logger, $this->cache);
        $this->importer = new LdgImporter($this->discogsClient, $this->logger);
        $this->admin = new LdgAdmin($this->settings, $this->discogsClient, $this->importer);
        $this->ajax = new LdgAjax($this->importer, $this->cache, $this->logger);
        $this->public = new LdgPublic();
    }

    /**
     * Define the locale for internationalization
     *
     * @return void
     */
    private function setLocale(): void
    {
        $this->loader->addAction('plugins_loaded', $this, 'loadPluginTextdomain');
    }

    /**
     * Load plugin text domain
     *
     * @return void
     */
    public function loadPluginTextdomain(): void
    {
        load_plugin_textdomain(
            'livedg',
            false,
            dirname(LDG_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Register admin hooks
     *
     * @return void
     */
    private function defineAdminHooks(): void
    {
        $this->loader->addAction('admin_enqueue_scripts', $this->admin, 'enqueueStyles');
        $this->loader->addAction('admin_enqueue_scripts', $this->admin, 'enqueueScripts');
        $this->loader->addAction('admin_menu', $this->admin, 'addAdminMenu');
        $this->loader->addAction('admin_init', $this->settings, 'registerSettings');
    }

    /**
     * Register public hooks
     *
     * @return void
     */
    private function definePublicHooks(): void
    {
        $this->loader->addFilter('woocommerce_product_tabs', $this->public, 'addProductTabs', 25, 1);

        /**
         * Filter to allow developers to add custom public hooks
         *
         * @param LdgLoader $loader The loader instance
         * @param LdgPlugin $plugin The plugin instance
         */
        apply_filters('ldg_public_hooks', $this->loader, $this);
    }

    /**
     * Run the loader to execute all hooks
     *
     * @return void
     */
    public function run(): void
    {
        $this->loader->run();
    }

    /**
     * Plugin activation callback
     *
     * @return void
     */
    public static function activate(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        add_option('ldg_version', LDG_VERSION);
        add_option('ldg_activation_date', current_time('mysql'));

        flush_rewrite_rules();

        /**
         * Action hook after plugin activation
         */
        do_action('ldg_activated');
    }

    /**
     * Plugin deactivation callback
     *
     * @return void
     */
    public static function deactivate(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        flush_rewrite_rules();

        /**
         * Action hook after plugin deactivation
         */
        do_action('ldg_deactivated');
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return LDG_VERSION;
    }
}
