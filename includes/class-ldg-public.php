<?php
/**
 * Public Hooks Class
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle public-facing WooCommerce integrations
 */
class LdgPublic
{
    private const META_KEY_DISCOGS_TRACKLIST_HTML = '_ldg_discogs_tracklist_html';
    private const META_KEY_DISCOGS_CREDITS_HTML = '_ldg_discogs_credits_html';

    /**
     * Add Tracklist/Credits product tab when imported data exists
     *
     * @param array $tabs Existing WooCommerce product tabs
     * @return array
     */
    public function addProductTabs(array $tabs): array
    {
        if (!function_exists('wc_get_product')) {
            return $tabs;
        }

        global $product;

        if (!$product instanceof \WC_Product) {
            return $tabs;
        }

        $productId = $product->get_id();
        $tracklistHtml = (string) get_post_meta($productId, self::META_KEY_DISCOGS_TRACKLIST_HTML, true);
        $creditsHtml = (string) get_post_meta($productId, self::META_KEY_DISCOGS_CREDITS_HTML, true);

        if ($tracklistHtml === '' && $creditsHtml === '') {
            return $tabs;
        }

        $tabs['ldg_tracklist_credits'] = [
            'title' => __('Tracklist & Credits', 'livedg'),
            'priority' => 25,
            'callback' => [$this, 'renderTracklistCreditsTab'],
        ];

        return $tabs;
    }

    /**
     * Render Tracklist & Credits product tab content
     *
     * @param string $key Tab key
     * @param array $tab Tab config
     * @return void
     */
    public function renderTracklistCreditsTab(string $key, array $tab): void
    {
        if (!function_exists('wc_get_product')) {
            return;
        }

        global $product;

        if (!$product instanceof \WC_Product) {
            return;
        }

        $productId = $product->get_id();
        $tracklistHtml = (string) get_post_meta($productId, self::META_KEY_DISCOGS_TRACKLIST_HTML, true);
        $creditsHtml = (string) get_post_meta($productId, self::META_KEY_DISCOGS_CREDITS_HTML, true);

        if ($tracklistHtml !== '') {
            echo '<h2>' . esc_html__('Tracklist', 'livedg') . '</h2>';
            echo wp_kses_post($tracklistHtml);
        }

        if ($creditsHtml !== '') {
            echo '<h2>' . esc_html__('Credits', 'livedg') . '</h2>';
            echo wp_kses_post($creditsHtml);
        }
    }
}

