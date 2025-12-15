# LiveDG Plugin Structure

```
live-dg/
│
├── 📄 live-dg.php                     ← Main plugin file (bootstrap)
├── 📄 uninstall.php                   ← Uninstall cleanup script
│
├── 📋 composer.json                   ← Composer dependencies
├── 📋 phpunit.xml.dist               ← PHPUnit configuration
├── 📋 .gitignore                     ← Git ignore rules
│
├── 📖 PROJECT-SUMMARY.md              ← This file - complete overview
├── 📖 README.md                       ← Main documentation
├── 📖 readme.txt                      ← WordPress.org readme
├── 📖 INSTALL.md                      ← Installation guide
├── 📖 TESTING.md                      ← Testing procedures
├── 📖 DEVELOPER.md                    ← Developer notes
│
├── 🎨 assets/
│   ├── css/
│   │   └── admin.css                 ← Admin interface styles
│   └── js/
│       └── admin.js                  ← Admin interactive scripts
│
├── ⚙️ includes/
│   │
│   ├── 🔧 Core Classes
│   ├── class-ldg-plugin.php          ← Main singleton plugin class
│   ├── class-ldg-loader.php          ← Hook registration system
│   ├── class-ldg-admin.php           ← Admin menu & pages
│   ├── class-ldg-ajax.php            ← AJAX request handlers
│   │
│   ├── 🔌 Integration
│   ├── class-ldg-discogs-client.php  ← Discogs API client
│   ├── class-ldg-importer.php        ← Product import logic
│   │
│   ├── 🛠️ Utilities
│   ├── class-ldg-settings.php        ← Settings API wrapper
│   ├── class-ldg-logger.php          ← Logging system
│   ├── class-ldg-cache.php           ← Cache helper
│   ├── class-ldg-uninstall.php       ← Cleanup utilities
│   │
│   └── 📄 templates/
│       ├── admin-dashboard.php       ← Dashboard page
│       ├── admin-search.php          ← Search interface
│       ├── admin-settings.php        ← Settings page
│       └── admin-logs.php            ← Log viewer
│
└── 🧪 tests/
    ├── bootstrap.php                 ← Test environment setup
    ├── test-plugin.php              ← Plugin initialization tests
    ├── test-cache.php               ← Cache tests
    └── test-logger.php              ← Logger tests
```

## Component Relationships

```
┌─────────────────────────────────────────────────────────────┐
│                      live-dg.php                            │
│                    (Entry Point)                            │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────┐
│                   LdgPlugin (Singleton)                     │
│                  Main Orchestrator                          │
└─┬───────────────────────────────────────────────────────────┘
  │
  ├─→ LdgLoader          (Hook Registration)
  │
  ├─→ LdgAdmin           (Admin Interface)
  │   └─→ Templates      (Dashboard, Search, Settings, Logs)
  │
  ├─→ LdgAjax            (AJAX Handlers)
  │
  ├─→ LdgSettings        (Settings API)
  │
  ├─→ LdgDiscogsClient   (API Integration)
  │   └─→ LdgCache       (Response Caching)
  │
  ├─→ LdgImporter        (Product Import)
  │   └─→ WooCommerce    (Product Creation)
  │
  └─→ LdgLogger          (Activity Logging)
```

## Data Flow: Product Import

```
User Action (Admin)
        ↓
Search Interface (admin-search.php)
        ↓
AJAX Request (admin.js)
        ↓
LdgAjax::handleImportRelease()
        ↓
LdgImporter::importRelease()
        ↓
LdgDiscogsClient::getRelease()
        ↓
[Cache Check] → [API Request] → [Cache Store]
        ↓
Release Data Retrieved
        ↓
LdgImporter::createProduct()
        ↓
WC_Product_Simple (created)
        ↓
Meta Data Added
        ↓
Image Import
        ↓
Categories/Tags Created
        ↓
Product Saved
        ↓
Success Response
        ↓
UI Update (show success)
```

## Plugin Architecture Layers

```
┌─────────────────────────────────────────────────┐
│           Presentation Layer                    │
│  (Templates, CSS, JavaScript)                   │
├─────────────────────────────────────────────────┤
│           Application Layer                     │
│  (Admin, AJAX, Settings)                        │
├─────────────────────────────────────────────────┤
│           Business Logic Layer                  │
│  (Importer, Plugin Core)                        │
├─────────────────────────────────────────────────┤
│           Integration Layer                     │
│  (Discogs Client, WooCommerce)                  │
├─────────────────────────────────────────────────┤
│           Infrastructure Layer                  │
│  (Cache, Logger, Loader)                        │
└─────────────────────────────────────────────────┘
```

## File Size Estimate

```
Source Code:        ~50 KB
Documentation:      ~120 KB
Assets (CSS/JS):    ~15 KB
Tests:              ~10 KB
Config Files:       ~5 KB
─────────────────────────
Total:              ~200 KB (uncompressed)
```

## Class Dependencies

```
LdgPlugin
├── requires: LdgLoader
├── requires: LdgAdmin
│   ├── requires: LdgSettings
│   ├── requires: LdgDiscogsClient
│   └── requires: LdgImporter
├── requires: LdgAjax
│   ├── requires: LdgImporter
│   ├── requires: LdgCache
│   └── requires: LdgLogger
├── requires: LdgSettings
├── requires: LdgDiscogsClient
│   ├── requires: LdgLogger
│   └── requires: LdgCache
├── requires: LdgImporter
│   ├── requires: LdgDiscogsClient
│   └── requires: LdgLogger
├── requires: LdgLogger
└── requires: LdgCache
```

## Admin Menu Structure

```
WordPress Admin
└── LiveDG 🎵
    ├── Dashboard        (Overview & Quick Actions)
    ├── Search Discogs   (Search & Import Interface)
    ├── Settings         (API & Configuration)
    └── Logs             (Activity Monitoring)
```

## Database Schema

### Options Table (`wp_options`)
```
ldg_version                      ← Plugin version
ldg_activation_date              ← Installation date
ldg_discogs_consumer_key         ← API consumer key
ldg_discogs_consumer_secret      ← API consumer secret
ldg_discogs_access_token         ← Personal access token
ldg_discogs_user_agent           ← Custom user agent
ldg_sku_prefix                   ← SKU prefix (default: LDG)
ldg_default_product_status       ← Default status (draft/publish)
ldg_import_images                ← Import images toggle
ldg_auto_categorize              ← Auto categorization toggle
ldg_enable_logging               ← Logging enabled
ldg_cache_duration               ← Cache duration (seconds)
ldg_logs                         ← Log entries (serialized)
```

### Post Meta Table (`wp_postmeta`)
```
_ldg_discogs_id                  ← Discogs release ID
_ldg_discogs_url                 ← Discogs release URL
_ldg_import_date                 ← Import timestamp
_ldg_last_sync                   ← Last sync timestamp
_ldg_release_data                ← Full JSON release data
```

### Transients (`wp_options`)
```
_transient_ldg_cache_*           ← Cached API responses
_transient_ldg_api_request_count ← Rate limit counter
```

## Security Measures

```
✅ Nonce Verification
   ├── All forms protected
   └── All AJAX requests verified

✅ Capability Checks
   ├── manage_woocommerce (for imports)
   └── manage_options (for settings)

✅ Input Sanitization
   ├── sanitize_text_field()
   ├── absint()
   ├── floatval()
   └── wp_kses_post()

✅ Output Escaping
   ├── esc_html()
   ├── esc_attr()
   ├── esc_url()
   └── esc_js()

✅ Database Security
   ├── Prepared statements
   └── $wpdb->prepare()

✅ File Access
   └── ABSPATH checks in all files
```

## Performance Features

```
⚡ Caching
   ├── Transient-based
   ├── Configurable duration
   └── Automatic invalidation

⚡ Rate Limiting
   ├── 60 requests/minute
   ├── Automatic throttling
   └── Exponential backoff

⚡ Lazy Loading
   ├── Admin assets only on plugin pages
   └── Conditional script loading

⚡ Optimized Queries
   ├── Indexed meta queries
   └── Efficient database access
```

## Extensibility

```
🔌 Actions
   ├── ldg_product_created
   ├── ldg_product_updated
   ├── ldg_activated
   ├── ldg_deactivated
   ├── ldg_cache_flushed
   └── ldg_log_entry

🔌 Filters
   ├── ldg_api_headers
   └── ldg_public_hooks
```

---

**Status: ✅ Production Ready**  
**Version: 1.0.0**  
**Last Updated: 2025-12-15**
