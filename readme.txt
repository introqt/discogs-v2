=== LiveDG - Discogs Integration for WooCommerce ===
Contributors: yourname
Tags: discogs, woocommerce, music, vinyl, products
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import and manage music products from Discogs.com directly into your WooCommerce store.

== Description ==

LiveDG seamlessly integrates your WooCommerce store with Discogs.com, the world's largest music database. Import vinyl records, CDs, cassettes, and other music products with complete metadata, images, and categorization.

= Features =

* Search Discogs database directly from WordPress admin
* One-click product import
* Automatic image import
* Smart categorization by genre and style
* Comprehensive metadata mapping
* Rate limiting and caching for optimal performance
* Activity logging for debugging
* Extensible with hooks and filters

= Perfect For =

* Record stores
* Music retailers
* Online vinyl shops
* Collectible music sellers
* Music industry professionals

= Requirements =

* WordPress 6.0+
* WooCommerce 8.0+
* PHP 8.4+
* Discogs API credentials (free)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/live-dg`
2. Activate the plugin through the 'Plugins' screen
3. Navigate to LiveDG > Settings
4. Enter your Discogs API credentials
5. Start importing products!

= Getting API Credentials =

1. Visit https://www.discogs.com/settings/developers
2. Create a new application
3. Copy your Consumer Key and Consumer Secret OR generate a Personal Access Token
4. Enter credentials in LiveDG Settings

== Frequently Asked Questions ==

= Do I need a Discogs account? =

Yes, you need a free Discogs account to get API credentials.

= How many products can I import? =

There's no hard limit, but Discogs API has rate limits (60 requests/minute for free accounts).

= Can I bulk import products? =

Currently, imports are done individually. Bulk import is planned for future releases.

= Will it update existing products? =

Yes, you can re-import products to update information.

= Does it work with variable products? =

Currently supports simple products. Variable product support is planned.

== Screenshots ==

1. Dashboard overview with statistics
2. Search interface for finding Discogs releases
3. Product import modal with options
4. Settings page with API configuration
5. Activity logs for debugging

== Changelog ==

= 1.0.0 =
* Initial release
* Discogs API integration
* Product search and import
* Settings management
* Logging system
* Caching layer

== Upgrade Notice ==

= 1.0.0 =
Initial release of LiveDG.

== Developer Notes ==

= Hooks =

**Actions:**
* `ldg_product_created` - After product import
* `ldg_product_updated` - After product update
* `ldg_activated` - Plugin activation
* `ldg_deactivated` - Plugin deactivation

**Filters:**
* `ldg_api_headers` - Modify API request headers
* `ldg_public_hooks` - Add custom public hooks

= Examples =

`
// Hook into product creation
add_action('ldg_product_created', function($productId, $release, $options) {
    // Your custom logic
}, 10, 3);

// Modify API headers
add_filter('ldg_api_headers', function($headers) {
    $headers['X-Custom'] = 'value';
    return $headers;
});
`

== Privacy Policy ==

LiveDG connects to the Discogs API to retrieve product information. API requests include:
* Search queries
* Release IDs
* API authentication credentials

No personal data is sent to Discogs except what's required for API authentication.

== Support ==

For support, please visit our support forum or GitHub repository.
