# Import configuration options are ignored

## Type
Bug

## Problem
Several importer behaviors ignore the settings exposed in `LdgSettings`:
- Products default to `draft` instead of honoring the **Default Product Status** option.
- Images are always sideloaded even when **Import Images** is disabled.
- Categories/tags are always created even when **Auto Categorize** is disabled.

These mismatches make the UI misleading and can surprise merchants who expect the toggles to control importer behavior.

## Where
- `includes/class-ldg-settings.php` registers `ldg_default_product_status`, `ldg_import_images`, and `ldg_auto_categorize` fields, but the importer and AJAX handlers never read those options.
- `includes/class-ldg-importer.php` uses hardcoded defaults for status (`draft`) and always calls image/category/tag imports.

## Suggested Fix
Read the stored options when building import defaults (e.g., in the AJAX handler) and conditionally skip image/category/tag imports based on those toggles. Honor the saved default product status when a status is not provided explicitly.
