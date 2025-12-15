<?php
/**
 * Settings Class
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle plugin settings using WordPress Settings API
 */
class LdgSettings
{
    /**
     * Settings group name
     */
    private const SETTINGS_GROUP = 'ldg_settings_group';

    /**
     * Settings page slug
     */
    private const SETTINGS_PAGE = 'livedg-settings';

    /**
     * Register all settings
     *
     * @return void
     */
    public function registerSettings(): void
    {
        register_setting(self::SETTINGS_GROUP, 'ldg_discogs_consumer_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);

        register_setting(self::SETTINGS_GROUP, 'ldg_discogs_consumer_secret', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);

        register_setting(self::SETTINGS_GROUP, 'ldg_discogs_access_token', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);

        register_setting(self::SETTINGS_GROUP, 'ldg_discogs_user_agent', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'LiveDG/1.0 +' . get_site_url(),
        ]);

        register_setting(self::SETTINGS_GROUP, 'ldg_sku_prefix', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'LDG',
        ]);

        register_setting(self::SETTINGS_GROUP, 'ldg_default_product_status', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'draft',
        ]);

        register_setting(self::SETTINGS_GROUP, 'ldg_import_images', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true,
        ]);

        register_setting(self::SETTINGS_GROUP, 'ldg_auto_categorize', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true,
        ]);

        register_setting(self::SETTINGS_GROUP, 'ldg_enable_logging', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true,
        ]);

        register_setting(self::SETTINGS_GROUP, 'ldg_cache_duration', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 3600,
        ]);

        add_settings_section(
            'ldg_api_section',
            __('Discogs API Settings', 'livedg'),
            [$this, 'renderApiSectionDescription'],
            self::SETTINGS_PAGE
        );

        add_settings_field(
            'ldg_discogs_consumer_key',
            __('Consumer Key', 'livedg'),
            [$this, 'renderTextField'],
            self::SETTINGS_PAGE,
            'ldg_api_section',
            ['label_for' => 'ldg_discogs_consumer_key', 'option_name' => 'ldg_discogs_consumer_key']
        );

        add_settings_field(
            'ldg_discogs_consumer_secret',
            __('Consumer Secret', 'livedg'),
            [$this, 'renderTextField'],
            self::SETTINGS_PAGE,
            'ldg_api_section',
            ['label_for' => 'ldg_discogs_consumer_secret', 'option_name' => 'ldg_discogs_consumer_secret']
        );

        add_settings_field(
            'ldg_discogs_access_token',
            __('Personal Access Token', 'livedg'),
            [$this, 'renderTextField'],
            self::SETTINGS_PAGE,
            'ldg_api_section',
            ['label_for' => 'ldg_discogs_access_token', 'option_name' => 'ldg_discogs_access_token']
        );

        add_settings_field(
            'ldg_discogs_user_agent',
            __('User Agent', 'livedg'),
            [$this, 'renderTextField'],
            self::SETTINGS_PAGE,
            'ldg_api_section',
            ['label_for' => 'ldg_discogs_user_agent', 'option_name' => 'ldg_discogs_user_agent']
        );

        add_settings_section(
            'ldg_import_section',
            __('Import Settings', 'livedg'),
            [$this, 'renderImportSectionDescription'],
            self::SETTINGS_PAGE
        );

        add_settings_field(
            'ldg_sku_prefix',
            __('SKU Prefix', 'livedg'),
            [$this, 'renderTextField'],
            self::SETTINGS_PAGE,
            'ldg_import_section',
            ['label_for' => 'ldg_sku_prefix', 'option_name' => 'ldg_sku_prefix']
        );

        add_settings_field(
            'ldg_default_product_status',
            __('Default Product Status', 'livedg'),
            [$this, 'renderSelectField'],
            self::SETTINGS_PAGE,
            'ldg_import_section',
            [
                'label_for' => 'ldg_default_product_status',
                'option_name' => 'ldg_default_product_status',
                'options' => [
                    'publish' => __('Published', 'livedg'),
                    'draft' => __('Draft', 'livedg'),
                    'pending' => __('Pending Review', 'livedg'),
                ],
            ]
        );

        add_settings_field(
            'ldg_import_images',
            __('Import Images', 'livedg'),
            [$this, 'renderCheckboxField'],
            self::SETTINGS_PAGE,
            'ldg_import_section',
            ['label_for' => 'ldg_import_images', 'option_name' => 'ldg_import_images']
        );

        add_settings_field(
            'ldg_auto_categorize',
            __('Auto Categorize', 'livedg'),
            [$this, 'renderCheckboxField'],
            self::SETTINGS_PAGE,
            'ldg_import_section',
            ['label_for' => 'ldg_auto_categorize', 'option_name' => 'ldg_auto_categorize']
        );

        add_settings_section(
            'ldg_advanced_section',
            __('Advanced Settings', 'livedg'),
            [$this, 'renderAdvancedSectionDescription'],
            self::SETTINGS_PAGE
        );

        add_settings_field(
            'ldg_enable_logging',
            __('Enable Logging', 'livedg'),
            [$this, 'renderCheckboxField'],
            self::SETTINGS_PAGE,
            'ldg_advanced_section',
            ['label_for' => 'ldg_enable_logging', 'option_name' => 'ldg_enable_logging']
        );

        add_settings_field(
            'ldg_cache_duration',
            __('Cache Duration (seconds)', 'livedg'),
            [$this, 'renderNumberField'],
            self::SETTINGS_PAGE,
            'ldg_advanced_section',
            ['label_for' => 'ldg_cache_duration', 'option_name' => 'ldg_cache_duration']
        );
    }

    /**
     * Render API section description
     *
     * @return void
     */
    public function renderApiSectionDescription(): void
    {
        echo '<p>' . esc_html__(
            'Configure your Discogs API credentials. You can get these from your Discogs developer settings.',
            'livedg'
        ) . '</p>';
        echo '<p><a href="https://www.discogs.com/settings/developers" target="_blank">' .
            esc_html__('Get Discogs API Credentials', 'livedg') . '</a></p>';
    }

    /**
     * Render import section description
     *
     * @return void
     */
    public function renderImportSectionDescription(): void
    {
        echo '<p>' . esc_html__('Configure how products are imported from Discogs.', 'livedg') . '</p>';
    }

    /**
     * Render advanced section description
     *
     * @return void
     */
    public function renderAdvancedSectionDescription(): void
    {
        echo '<p>' . esc_html__('Advanced configuration options.', 'livedg') . '</p>';
    }

    /**
     * Render text field
     *
     * @param array $args Field arguments
     * @return void
     */
    public function renderTextField(array $args): void
    {
        $optionName = $args['option_name'];
        $value = get_option($optionName, '');

        printf(
            '<input type="text" id="%s" name="%s" value="%s" class="regular-text" />',
            esc_attr($args['label_for']),
            esc_attr($optionName),
            esc_attr($value)
        );
    }

    /**
     * Render select field
     *
     * @param array $args Field arguments
     * @return void
     */
    public function renderSelectField(array $args): void
    {
        $optionName = $args['option_name'];
        $value = get_option($optionName, '');
        $options = $args['options'] ?? [];

        printf('<select id="%s" name="%s">', esc_attr($args['label_for']), esc_attr($optionName));

        foreach ($options as $optionValue => $optionLabel) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($optionValue),
                selected($value, $optionValue, false),
                esc_html($optionLabel)
            );
        }

        echo '</select>';
    }

    /**
     * Render checkbox field
     *
     * @param array $args Field arguments
     * @return void
     */
    public function renderCheckboxField(array $args): void
    {
        $optionName = $args['option_name'];
        $value = get_option($optionName, false);

        printf(
            '<input type="checkbox" id="%s" name="%s" value="1" %s />',
            esc_attr($args['label_for']),
            esc_attr($optionName),
            checked($value, true, false)
        );
    }

    /**
     * Render number field
     *
     * @param array $args Field arguments
     * @return void
     */
    public function renderNumberField(array $args): void
    {
        $optionName = $args['option_name'];
        $value = get_option($optionName, 0);

        printf(
            '<input type="number" id="%s" name="%s" value="%s" class="small-text" min="0" />',
            esc_attr($args['label_for']),
            esc_attr($optionName),
            esc_attr($value)
        );
    }

    /**
     * Get setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return get_option($key, $default);
    }

    /**
     * Set setting value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool
     */
    public function set(string $key, mixed $value): bool
    {
        return update_option($key, $value);
    }
}
