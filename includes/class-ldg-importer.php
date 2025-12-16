<?php
/**
 * Product Importer Class
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Import Discogs releases as WooCommerce products
 */
class LdgImporter
{
    /**
     * Discogs client instance
     *
     * @var LdgDiscogsClient
     */
    private LdgDiscogsClient $discogsClient;

    /**
     * Logger instance
     *
     * @var LdgLogger
     */
    private LdgLogger $logger;

    /**
     * Constructor
     *
     * @param LdgDiscogsClient $discogsClient Discogs client instance
     * @param LdgLogger $logger Logger instance
     */
    public function __construct(LdgDiscogsClient $discogsClient, LdgLogger $logger)
    {
        $this->discogsClient = $discogsClient;
        $this->logger = $logger;
    }

    /**
     * Import a Discogs release as a WooCommerce product
     *
     * @param int $releaseId Discogs release ID
     * @param array $options Import options
     * @return int|false Product ID or false on failure
     */
    public function importRelease(int $releaseId, array $options = []): int|false
    {
        $options = $this->applyDefaultOptions($options);

        if (!class_exists('WooCommerce')) {
            $this->logger->log('error', 'WooCommerce is not active');
            return false;
        }

        $release = $this->discogsClient->getRelease($releaseId);

        if (!$release) {
            $this->logger->log('error', "Failed to fetch release {$releaseId}");
            return false;
        }

        $existingProductId = $this->getProductByDiscogsId($releaseId);

        if ($existingProductId && empty($options['force_update'])) {
            $this->logger->log('info', "Product already exists for release {$releaseId}");
            return $existingProductId;
        }

        if ($existingProductId) {
            return $this->updateProduct($existingProductId, $release, $options);
        }

        return $this->createProduct($release, $options);
    }

    /**
     * Create a new WooCommerce product from Discogs release
     *
     * @param array $release Release data from Discogs
     * @param array $options Import options
     * @return int|false Product ID or false on failure
     */
    private function createProduct(array $release, array $options): int|false
    {
        try {
            $product = new \WC_Product_Simple();

            $product->set_name($this->getProductTitle($release));
            $product->set_slug(sanitize_title($this->getProductTitle($release)));
            $product->set_description($this->getProductDescription($release));
            $product->set_short_description($this->getProductShortDescription($release));
            $product->set_sku($this->generateSku($release));
            $product->set_regular_price($options['price'] ?? '0');
            $product->set_manage_stock($options['manage_stock'] ?? false);

            if (!empty($options['stock_quantity'])) {
                $product->set_stock_quantity((int)$options['stock_quantity']);
            }

            $product->set_status($options['status'] ?? 'draft');

            $productId = $product->save();

            if (!$productId) {
                $this->logger->log('error', 'Failed to save product', ['release' => $release['id']]);
                return false;
            }

            update_post_meta($productId, '_ldg_discogs_id', $release['id']);
            update_post_meta($productId, '_ldg_discogs_url', $release['uri'] ?? '');
            update_post_meta($productId, '_ldg_import_date', current_time('mysql'));
            update_post_meta($productId, '_ldg_release_data', wp_json_encode($release));

            if (!empty($options['import_images'])) {
                $this->importProductImage($productId, $release);
            }

            if (!empty($options['auto_categorize'])) {
                $this->importProductCategories($productId, $release);
                $this->importProductTags($productId, $release);
            }

            $this->importProductAttributes($productId, $release);

            /**
             * Action hook after product creation from Discogs
             *
             * @param int $productId WooCommerce product ID
             * @param array $release Discogs release data
             * @param array $options Import options
             */
            do_action('ldg_product_created', $productId, $release, $options);

            $this->logger->log('success', "Product created successfully", [
                'product_id' => $productId,
                'release_id' => $release['id'],
            ]);

            return $productId;
        } catch (\Exception $e) {
            $this->logger->log('error', 'Exception during product creation', [
                'message' => $e->getMessage(),
                'release_id' => $release['id'] ?? 'unknown',
            ]);

            return false;
        }
    }

    /**
     * Update existing WooCommerce product from Discogs release
     *
     * @param int $productId Product ID
     * @param array $release Release data from Discogs
     * @param array $options Import options
     * @return int|false Product ID or false on failure
     */
    private function updateProduct(int $productId, array $release, array $options): int|false
    {
        try {
            $product = wc_get_product($productId);

            if (!$product) {
                return false;
            }

            $product->set_name($this->getProductTitle($release));
            $product->set_description($this->getProductDescription($release));
            $product->set_short_description($this->getProductShortDescription($release));

            if (isset($options['price'])) {
                $product->set_regular_price($options['price']);
            }

            $product->save();

            update_post_meta($productId, '_ldg_last_sync', current_time('mysql'));
            update_post_meta($productId, '_ldg_release_data', wp_json_encode($release));

            if (!empty($options['import_images'])) {
                $this->importProductImage($productId, $release);
            }

            if (!empty($options['auto_categorize'])) {
                $this->importProductCategories($productId, $release);
                $this->importProductTags($productId, $release);
            }

            $this->importProductAttributes($productId, $release);

            /**
             * Action hook after product update from Discogs
             *
             * @param int $productId WooCommerce product ID
             * @param array $release Discogs release data
             * @param array $options Import options
             */
            do_action('ldg_product_updated', $productId, $release, $options);

            $this->logger->log('success', "Product updated successfully", [
                'product_id' => $productId,
                'release_id' => $release['id'],
            ]);

            return $productId;
        } catch (\Exception $e) {
            $this->logger->log('error', 'Exception during product update', [
                'message' => $e->getMessage(),
                'product_id' => $productId,
            ]);

            return false;
        }
    }

    /**
     * Get product title from release data
     *
     * @param array $release Release data
     * @return string
     */
    private function getProductTitle(array $release): string
    {
        $artists = $release['artists_sort'] ?? $release['artists'][0]['name'] ?? 'Unknown Artist';
        $title = $release['title'] ?? 'Untitled';

        return "{$artists} - {$title}";
    }

    /**
     * Get product description from release data
     *
     * @param array $release Release data
     * @return string
     */
    private function getProductDescription(array $release): string
    {
        $parts = [];

        if (!empty($release['notes'])) {
            $parts[] = wp_kses_post($release['notes']);
        }

        if (!empty($release['tracklist'])) {
            $parts[] = "<h3>Tracklist</h3>";
            $parts[] = "<ol>";

            foreach ($release['tracklist'] as $track) {
                $duration = !empty($track['duration']) ? " ({$track['duration']})" : '';
                $parts[] = "<li>{$track['position']} - {$track['title']}{$duration}</li>";
            }

            $parts[] = "</ol>";
        }

        return implode("\n", $parts);
    }

    /**
     * Get product short description from release data
     *
     * @param array $release Release data
     * @return string
     */
    private function getProductShortDescription(array $release): string
    {
        $parts = [];

        if (!empty($release['year'])) {
            $parts[] = "Released: {$release['year']}";
        }

        if (!empty($release['genres'])) {
            $parts[] = "Genre: " . implode(', ', $release['genres']);
        }

        if (!empty($release['labels'][0]['name'])) {
            $parts[] = "Label: {$release['labels'][0]['name']}";
        }

        if (!empty($release['formats'][0]['name'])) {
            $parts[] = "Format: {$release['formats'][0]['name']}";
        }

        return implode(' | ', $parts);
    }

    /**
     * Generate SKU for product
     *
     * @param array $release Release data
     * @return string
     */
    private function generateSku(array $release): string
    {
        $prefix = get_option('ldg_sku_prefix', 'LDG');
        return $prefix ? "{$prefix}-{$release['id']}" : (string) $release['id'];
    }

    /**
     * Merge saved defaults into import options
     *
     * @param array $options Import options
     * @return array
     */
    private function applyDefaultOptions(array $options): array
    {
        if (!array_key_exists('status', $options)) {
            $options['status'] = get_option('ldg_default_product_status', 'draft');
        }

        if (!array_key_exists('import_images', $options)) {
            $options['import_images'] = rest_sanitize_boolean(get_option('ldg_import_images', true));
        }

        if (!array_key_exists('auto_categorize', $options)) {
            $options['auto_categorize'] = rest_sanitize_boolean(get_option('ldg_auto_categorize', true));
        }

        return $options;
    }

    /**
     * Import product image from Discogs
     *
     * @param int $productId Product ID
     * @param array $release Release data
     * @return void
     */
    private function importProductImage(int $productId, array $release): void
    {
        if (empty($release['images'][0]['uri'])) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $imageUrl = $release['images'][0]['uri'];
        $attachmentId = media_sideload_image($imageUrl, $productId, null, 'id');

        if (!is_wp_error($attachmentId)) {
            set_post_thumbnail($productId, $attachmentId);
        }
    }

    /**
     * Import product categories
     *
     * @param int $productId Product ID
     * @param array $release Release data
     * @return void
     */
    private function importProductCategories(int $productId, array $release): void
    {
        if (empty($release['genres'])) {
            return;
        }

        $categoryIds = [];

        foreach ($release['genres'] as $genre) {
            $term = term_exists($genre, 'product_cat');

            if (!$term) {
                $term = wp_insert_term($genre, 'product_cat');
            }

            if (!is_wp_error($term)) {
                $categoryIds[] = $term['term_id'];
            }
        }

        if (!empty($categoryIds)) {
            wp_set_object_terms($productId, $categoryIds, 'product_cat');
        }
    }

    /**
     * Import product tags
     *
     * @param int $productId Product ID
     * @param array $release Release data
     * @return void
     */
    private function importProductTags(int $productId, array $release): void
    {
        $tags = [];

        if (!empty($release['styles'])) {
            $tags = array_merge($tags, $release['styles']);
        }

        if (!empty($release['formats'])) {
            foreach ($release['formats'] as $format) {
                $tags[] = $format['name'];
            }
        }

        if (!empty($tags)) {
            wp_set_object_terms($productId, $tags, 'product_tag');
        }
    }

    /**
     * Import product attributes
     *
     * @param int $productId Product ID
     * @param array $release Release data
     * @return void
     */
    private function importProductAttributes(int $productId, array $release): void
    {
        $attributes = [];

        if (!empty($release['year'])) {
            $attributes['year'] = [
                'name' => 'Year',
                'value' => $release['year'],
                'is_visible' => 1,
                'is_taxonomy' => 0,
            ];
        }

        if (!empty($release['country'])) {
            $attributes['country'] = [
                'name' => 'Country',
                'value' => $release['country'],
                'is_visible' => 1,
                'is_taxonomy' => 0,
            ];
        }

        if (!empty($release['labels'][0]['catno'])) {
            $attributes['catalog_number'] = [
                'name' => 'Catalog Number',
                'value' => $release['labels'][0]['catno'],
                'is_visible' => 1,
                'is_taxonomy' => 0,
            ];
        }

        if (!empty($attributes)) {
            update_post_meta($productId, '_product_attributes', $attributes);
        }
    }

    /**
     * Get product ID by Discogs release ID
     *
     * @param int $releaseId Discogs release ID
     * @return int|false Product ID or false if not found
     */
    private function getProductByDiscogsId(int $releaseId): int|false
    {
        global $wpdb;

        $productId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ldg_discogs_id' AND meta_value = %d",
                $releaseId
            )
        );

        return $productId ? (int)$productId : false;
    }
}
