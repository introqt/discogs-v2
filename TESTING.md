# LiveDG Testing Guide

## Overview

This guide explains how to test the LiveDG plugin both manually and with automated tests.

## Prerequisites

- WordPress test environment
- WooCommerce installed and activated
- Discogs API credentials
- Composer installed (for automated tests)
- PHPUnit (installed via Composer)

## Manual Testing

### Initial Setup

1. **Install Plugin**
   - Upload plugin to `/wp-content/plugins/live-dg/`
   - Activate via WordPress admin
   - Verify WooCommerce dependency check

2. **Configure API Credentials**
   - Navigate to LiveDG > Settings
   - Enter Discogs Consumer Key/Secret or Personal Access Token
   - User Agent should be auto-populated
   - Click "Test Connection" to verify credentials

### Feature Testing

#### Search Functionality

1. Navigate to LiveDG > Search Discogs
2. Enter search term (e.g., "Pink Floyd Dark Side")
3. Verify search results display correctly
4. Check that images, titles, and metadata appear
5. Test pagination if more than one page of results

#### Product Import

1. From search results, click "Import" on a release
2. Fill import form:
   - Set price
   - Choose product status
   - Enable/disable stock management
   - Set stock quantity if enabled
3. Click "Import Product"
4. Verify success message appears
5. Navigate to WooCommerce > Products
6. Verify new product exists with:
   - Correct title
   - Description with tracklist
   - Product image
   - Categories (genres)
   - Tags (styles)
   - Attributes (year, country, catalog number)
   - Correct SKU format

#### Settings Management

1. Navigate to LiveDG > Settings
2. Test each setting:
   - API credentials
   - SKU prefix
   - Default product status
   - Import images toggle
   - Auto categorize toggle
   - Enable logging toggle
   - Cache duration

3. Test Tools:
   - Test API Connection
   - Clear Cache
   - Export Logs
   - Clear Logs

#### Logging System

1. Navigate to LiveDG > Logs
2. Verify logs display correctly
3. Test filtering by level
4. Click "View Details" on logs with context
5. Test log export functionality
6. Test log clearing

#### Dashboard

1. Navigate to LiveDG > Dashboard
2. Verify statistics display:
   - Imported products count
   - Cache size
   - Log count
3. Test quick actions
4. Review recent activity

## Automated Testing

### Setup

```bash
# Install dependencies
composer install

# Set WordPress test library path
export WP_TESTS_DIR=/path/to/wordpress-tests-lib

# Or install WordPress tests
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### Running Tests

```bash
# Run all tests
composer test

# Or use PHPUnit directly
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/test-cache.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/

# Run with verbose output
vendor/bin/phpunit --verbose
```

### Test Structure

```
tests/
├── bootstrap.php          # Test environment setup
├── test-plugin.php        # Plugin initialization tests
├── test-cache.php         # Cache functionality tests
├── test-logger.php        # Logger tests
└── test-importer.php      # Import functionality tests
```

### Writing New Tests

```php
<?php
use PHPUnit\Framework\TestCase;

class MyNewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Setup test environment
    }

    public function testSomething(): void
    {
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        // Cleanup
        parent::tearDown();
    }
}
```

## Code Quality

### PHP Code Sniffer

```bash
# Check code standards
composer phpcs

# Auto-fix code standards
composer phpcbf

# Check specific file
vendor/bin/phpcs includes/class-ldg-plugin.php
```

### Standards

- WordPress Coding Standards
- PHP 8.4 compatibility
- PHPDoc comments for all functions
- Proper escaping and sanitization

## API Testing

### Manual API Tests

1. **Search Endpoint**
   ```php
   $client = ldg()->discogsClient;
   $results = $client->searchReleases('test query');
   var_dump($results);
   ```

2. **Get Release**
   ```php
   $release = $client->getRelease(123456);
   var_dump($release);
   ```

3. **Rate Limiting**
   - Make 60+ requests quickly
   - Verify rate limit enforcement
   - Check logs for rate limit messages

4. **Caching**
   - Search for same term twice
   - Verify second search is faster
   - Check cache stats in settings

### Error Scenarios

Test error handling:

1. **Invalid API Credentials**
   - Set wrong credentials
   - Attempt API call
   - Verify error message

2. **Network Errors**
   - Simulate network failure
   - Verify retry logic
   - Check error logging

3. **Invalid Release ID**
   - Try importing non-existent release
   - Verify error handling

4. **WooCommerce Inactive**
   - Deactivate WooCommerce
   - Try plugin operations
   - Verify dependency checks

## Performance Testing

### Cache Performance

```php
// Test without cache
$start = microtime(true);
$release = $client->getRelease(123456);
$time1 = microtime(true) - $start;

// Test with cache
$start = microtime(true);
$release = $client->getRelease(123456);
$time2 = microtime(true) - $start;

echo "Without cache: {$time1}s\n";
echo "With cache: {$time2}s\n";
```

### Import Performance

Test importing:
- 1 product
- 10 products
- 100 products

Monitor:
- Memory usage
- Execution time
- Database queries

## Security Testing

### Input Validation

Test all forms with:
- SQL injection attempts
- XSS attempts
- CSRF token manipulation
- Invalid data types
- Boundary values

### Permission Checks

Test as different user roles:
- Administrator
- Shop Manager
- Editor
- Subscriber
- Logged out user

Verify proper capability checks.

### Data Sanitization

Review all user input handling:
- Search queries
- Form submissions
- AJAX requests
- URL parameters

## Browser Testing

Test admin interface in:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

Test responsive design at:
- Desktop (1920x1080)
- Tablet (768x1024)
- Mobile (375x667)

## Troubleshooting Tests

### Enable Debug Mode

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Logs

- WordPress debug log: `wp-content/debug.log`
- Plugin logs: LiveDG > Logs
- PHP error log: Check server logs
- Browser console: Check for JS errors

### Common Issues

1. **Tests fail to run**
   - Check WP_TESTS_DIR environment variable
   - Verify WordPress test suite installed
   - Check PHP version (8.4+)

2. **Import fails**
   - Verify API credentials
   - Check network connectivity
   - Review logs for errors
   - Verify WooCommerce active

3. **Cache issues**
   - Clear cache manually
   - Check transient storage
   - Verify write permissions

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        
    - name: Install dependencies
      run: composer install
      
    - name: Run tests
      run: composer test
```

## Test Checklist

- [ ] Plugin activates successfully
- [ ] WooCommerce dependency enforced
- [ ] API credentials configurable
- [ ] API connection test works
- [ ] Search returns results
- [ ] Products import correctly
- [ ] Images import properly
- [ ] Categories created automatically
- [ ] Product attributes set correctly
- [ ] Cache functions properly
- [ ] Logs record activities
- [ ] Settings save correctly
- [ ] AJAX handlers work
- [ ] Nonce verification passes
- [ ] Capability checks enforced
- [ ] Data sanitized properly
- [ ] All unit tests pass
- [ ] Code passes PHPCS
- [ ] No PHP errors/warnings
- [ ] No JavaScript errors
- [ ] Responsive design works
- [ ] All browsers supported

## Reporting Issues

When reporting issues, include:
- WordPress version
- PHP version
- WooCommerce version
- Plugin version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Error messages
- Relevant log entries

## Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Discogs API Documentation](https://www.discogs.com/developers)
- [WooCommerce Documentation](https://woocommerce.com/documentation/)
