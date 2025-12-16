# LiveDG - Discogs Integration for WooCommerce

A professional WordPress plugin that seamlessly integrates WooCommerce with the Discogs.com REST API, enabling music retailers to import and manage vinyl records, CDs, and other music products.

## Features

- **Discogs API Integration**: Full integration with Discogs REST API v2
- **Product Import**: Import releases from Discogs as WooCommerce products
- **Smart Search**: Advanced search functionality with filtering
- **Automatic Categorization**: Auto-categorize products by genre and style
- **Image Import**: Automatically import cover images
- **Metadata Mapping**: Map Discogs metadata to WooCommerce attributes
- **Rate Limiting**: Built-in API rate limiting and retry logic
- **Caching**: Intelligent caching to reduce API calls
- **Logging**: Comprehensive activity logging for debugging
- **Extensible**: Action hooks and filters for developers

## Requirements

- WordPress 6.0 or higher
- PHP 8.4 or higher
- WooCommerce 8.0 or higher
- Discogs API credentials (Consumer Key/Secret or Personal Access Token)

## Installation

1. Upload the `live-dg` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **LiveDG > Settings**
4. Configure your Discogs API credentials
5. Start importing products!

## Getting API Credentials

1. Visit [Discogs Developer Settings](https://www.discogs.com/settings/developers)
2. Create a new application
3. Copy your Consumer Key and Consumer Secret
4. Alternatively, generate a Personal Access Token for simpler authentication

## Architecture

### Core Classes

- **LdgPlugin**: Main plugin singleton managing lifecycle and dependencies
- **LdgLoader**: Registers all WordPress hooks and filters
- **LdgAdmin**: Handles admin interface and menu pages
- **LdgSettings**: WordPress Settings API wrapper
- **LdgDiscogsClient**: Discogs API client with rate limiting and caching
- **LdgImporter**: Handles product import and WooCommerce integration
- **LdgLogger**: Activity logging system
- **LdgCache**: Caching helper using WordPress transients
- **LdgUninstall**: Clean uninstall procedures

### File Structure

```
live-dg/
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── includes/
│   ├── class-ldg-plugin.php
│   ├── class-ldg-loader.php
│   ├── class-ldg-admin.php
│   ├── class-ldg-settings.php
│   ├── class-ldg-discogs-client.php
│   ├── class-ldg-importer.php
│   ├── class-ldg-logger.php
│   ├── class-ldg-cache.php
│   ├── class-ldg-uninstall.php
│   └── templates/
│       ├── admin-dashboard.php
│       ├── admin-search.php
│       ├── admin-settings.php
│       └── admin-logs.php
├── live-dg.php
├── uninstall.php
├── README.md
├── readme.txt
├── composer.json
└── phpunit.xml.dist
```

## Usage

### Basic Import Workflow

1. **Search**: Navigate to **LiveDG > Search Discogs**
2. **Find**: Enter artist name, album title, or catalog number
3. **Import**: Click "Import" on desired releases
4. **Configure**: Set price, stock status, and other options
5. **Publish**: Products are created in WooCommerce

### Programmatic Usage

```php
// Get plugin instance
$livedg = ldg();

// Import a release
$productId = $livedg->importer->importRelease(123456, [
    'price' => 19.99,
    'status' => 'publish',
    'stock_quantity' => 10
]);

// Search Discogs
$results = $livedg->discogsClient->searchReleases('Pink Floyd Dark Side');

// Get release details
$release = $livedg->discogsClient->getRelease(123456);
```

## Hooks & Filters

### Actions

```php
// After product created from Discogs
do_action('ldg_product_created', $productId, $release, $options);

// After product updated from Discogs
do_action('ldg_product_updated', $productId, $release, $options);

// Plugin activation
do_action('ldg_activated');

// Plugin deactivation
do_action('ldg_deactivated');

// Cache flushed
do_action('ldg_cache_flushed', $deletedCount);

// Log entry created
do_action('ldg_log_entry', $entry);
```

### Filters

```php
// Modify API request headers
$headers = apply_filters('ldg_api_headers', $headers);

// Add custom public hooks
apply_filters('ldg_public_hooks', $loader, $plugin);
```

## Configuration

### Settings

- **Consumer Key**: Discogs OAuth Consumer Key
- **Consumer Secret**: Discogs OAuth Consumer Secret
- **Personal Access Token**: Alternative to OAuth (recommended for simplicity)
- **User Agent**: Custom user agent for API requests
- **SKU Prefix**: Prefix for generated product SKUs
- **Default Product Status**: Default status for imported products (draft/publish)
- **Import Images**: Enable/disable automatic image import
- **Auto Categorize**: Automatically create categories from genres
- **Enable Logging**: Activity logging on/off
- **Cache Duration**: Cache lifetime in seconds

## Troubleshooting

### Common Issues

**API Connection Failed**
- Verify API credentials in settings
- Check that your server can make outbound HTTPS requests
- Review logs at **LiveDG > Logs**

**Rate Limit Exceeded**
- Reduce import frequency
- Increase cache duration in settings
- Discogs free tier: 60 requests/minute

**Images Not Importing**
- Ensure `wp-content/uploads` is writable
- Check PHP memory limit (256MB recommended)
- Enable error logging: `define('WP_DEBUG', true);`

**Products Import as Draft**
- Check default product status in settings
- Verify WooCommerce product permissions

### Debug Mode

Enable WordPress debug mode in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

View logs at `wp-content/debug.log` and **LiveDG > Logs**

## Testing

### Running Unit Tests

```bash
# Install dependencies
composer install

# Run PHPUnit tests
vendor/bin/phpunit

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

### Test Structure

```
tests/
├── bootstrap.php
├── test-plugin.php
├── test-discogs-client.php
├── test-importer.php
└── test-cache.php
```

## Security

- All user inputs are sanitized and validated
- Nonce verification on all forms
- Capability checks on admin functions
- SQL queries use prepared statements
- Output is properly escaped
- API credentials stored securely

## Performance

- Intelligent caching reduces API calls
- Rate limiting prevents throttling
- Background processing for bulk imports (optional)
- Optimized database queries
- Lazy loading of admin assets

## Extending the Plugin

### Custom Import Logic

```php
add_action('ldg_product_created', function($productId, $release, $options) {
    // Custom logic after product import
    update_post_meta($productId, '_custom_field', $release['custom_data']);
}, 10, 3);
```

### Modify API Headers

```php
add_filter('ldg_api_headers', function($headers) {
    $headers['X-Custom-Header'] = 'value';
    return $headers;
});
```

### Custom Product Mapping

Create a custom importer class extending `LdgImporter`:

```php
namespace MyPlugin;

class CustomImporter extends \LiveDG\LdgImporter {
    protected function createProduct(array $release, array $options): int|false {
        // Custom product creation logic
        return parent::createProduct($release, $options);
    }
}
```

## Roadmap

- [ ] Bulk import functionality
- [ ] Inventory synchronization
- [ ] Marketplace integration
- [ ] Advanced pricing rules
- [ ] Custom field mapping UI
- [ ] Export functionality
- [ ] REST API endpoints

## Support

- Documentation: [Plugin Website](#)
- Issues: [GitHub Issues](#)
- Community: [WordPress Forums](#)

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Follow WordPress Coding Standards
4. Write PHPUnit tests for new features
5. Submit a pull request

## License

GPL v2 or later. See LICENSE for details.

## Credits

- Developed by [Your Name/Company]
- Discogs API: [Discogs.com](https://www.discogs.com)
- WooCommerce: [WooCommerce.com](https://woocommerce.com)

## Changelog

### 1.0.0 (2025-12-15)
- Initial release
- Discogs API integration
- Product import functionality
- Search interface
- Settings management
- Logging system
- Caching layer
- Full WooCommerce integration

---

Made with ♥ for music retailers worldwide
