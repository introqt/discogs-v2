<?php
/**
 * Admin Search Template
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ldg-search">
    <h1><?php echo esc_html__('Search Discogs', 'livedg'); ?></h1>

    <div class="ldg-search-form">
        <form id="ldg-search-form">
            <?php wp_nonce_field('ldg_search_nonce', 'ldg_search_nonce'); ?>

            <div class="ldg-saved-searches">
                <label for="ldg-saved-search-select" class="screen-reader-text">
                    <?php echo esc_html__('Saved searches', 'livedg'); ?>
                </label>
                <select id="ldg-saved-search-select" class="regular-text" aria-label="<?php echo esc_attr__('Saved searches', 'livedg'); ?>">
                    <option value=""><?php echo esc_html__('Saved searches…', 'livedg'); ?></option>
                </select>
                <button type="button" class="button" id="ldg-load-saved-search">
                    <?php echo esc_html__('Load', 'livedg'); ?>
                </button>
                <button type="button" class="button" id="ldg-save-current-search">
                    <?php echo esc_html__('Save', 'livedg'); ?>
                </button>
                <button type="button" class="button" id="ldg-delete-saved-search">
                    <?php echo esc_html__('Delete', 'livedg'); ?>
                </button>
            </div>
            
            <p class="search-box">
                <input type="search" 
                       name="s" 
                       id="ldg-search-input" 
                       placeholder="<?php echo esc_attr__('Keywords (optional)', 'livedg'); ?>" 
                       class="regular-text" />
                <button type="submit" class="button button-primary">
                    <?php echo esc_html__('Search', 'livedg'); ?>
                </button>
                <button type="button" class="button" id="ldg-reset-filters">
                    <?php echo esc_html__('Reset', 'livedg'); ?>
                </button>
            </p>

            <details class="ldg-advanced-search" open>
                <summary><?php echo esc_html__('Advanced filters', 'livedg'); ?></summary>

                <div class="ldg-filters-grid">
                    <div class="ldg-field">
                        <label for="ldg-filter-artist"><?php echo esc_html__('Artist', 'livedg'); ?></label>
                        <input type="text" id="ldg-filter-artist" class="regular-text" />
                    </div>

                    <div class="ldg-field">
                        <label for="ldg-filter-release-title"><?php echo esc_html__('Release title', 'livedg'); ?></label>
                        <input type="text" id="ldg-filter-release-title" class="regular-text" />
                    </div>

                    <div class="ldg-field">
                        <label for="ldg-filter-label"><?php echo esc_html__('Label', 'livedg'); ?></label>
                        <input type="text" id="ldg-filter-label" class="regular-text" />
                    </div>

                    <div class="ldg-field">
                        <label for="ldg-filter-catno"><?php echo esc_html__('Catalog number', 'livedg'); ?></label>
                        <input type="text" id="ldg-filter-catno" class="regular-text" />
                    </div>

                    <div class="ldg-field">
                        <label for="ldg-filter-year"><?php echo esc_html__('Year', 'livedg'); ?></label>
                        <input type="number" id="ldg-filter-year" min="0" step="1" class="small-text" />
                    </div>

                    <div class="ldg-field">
                        <label for="ldg-filter-format"><?php echo esc_html__('Format', 'livedg'); ?></label>
                        <input type="text" id="ldg-filter-format" class="regular-text" placeholder="<?php echo esc_attr__('e.g. Vinyl, CD', 'livedg'); ?>" />
                    </div>

                    <div class="ldg-field">
                        <label for="ldg-filter-country"><?php echo esc_html__('Country', 'livedg'); ?></label>
                        <input type="text" id="ldg-filter-country" class="regular-text" placeholder="<?php echo esc_attr__('e.g. US, UK, Japan', 'livedg'); ?>" />
                    </div>

                    <div class="ldg-field">
                        <label for="ldg-filter-genre"><?php echo esc_html__('Genre', 'livedg'); ?></label>
                        <input type="text" id="ldg-filter-genre" class="regular-text" placeholder="<?php echo esc_attr__('Exact Discogs genre', 'livedg'); ?>" />
                    </div>

                    <div class="ldg-field">
                        <label for="ldg-filter-style"><?php echo esc_html__('Style', 'livedg'); ?></label>
                        <input type="text" id="ldg-filter-style" class="regular-text" placeholder="<?php echo esc_attr__('Exact Discogs style', 'livedg'); ?>" />
                    </div>

                    <div class="ldg-field">
                        <label for="ldg-sort"><?php echo esc_html__('Sort', 'livedg'); ?></label>
                        <select id="ldg-sort">
                            <option value=""><?php echo esc_html__('Relevance', 'livedg'); ?></option>
                            <option value="artist"><?php echo esc_html__('Artist', 'livedg'); ?></option>
                            <option value="title"><?php echo esc_html__('Title', 'livedg'); ?></option>
                            <option value="label"><?php echo esc_html__('Label', 'livedg'); ?></option>
                            <option value="catno"><?php echo esc_html__('Catalog number', 'livedg'); ?></option>
                            <option value="year"><?php echo esc_html__('Year', 'livedg'); ?></option>
                            <option value="format"><?php echo esc_html__('Format', 'livedg'); ?></option>
                            <option value="country"><?php echo esc_html__('Country', 'livedg'); ?></option>
                        </select>
                    </div>

                    <div class="ldg-field">
                        <label for="ldg-sort-order"><?php echo esc_html__('Order', 'livedg'); ?></label>
                        <select id="ldg-sort-order">
                            <option value="asc"><?php echo esc_html__('Ascending', 'livedg'); ?></option>
                            <option value="desc"><?php echo esc_html__('Descending', 'livedg'); ?></option>
                        </select>
                    </div>
                </div>
            </details>
        </form>
    </div>

    <div id="ldg-search-loading" style="display: none;">
        <p><?php echo esc_html__('Searching...', 'livedg'); ?></p>
    </div>

    <div id="ldg-search-results"></div>

    <div id="ldg-import-modal" class="ldg-modal" style="display: none;">
        <div class="ldg-modal-content">
            <span class="ldg-modal-close">&times;</span>
            <h2><?php echo esc_html__('Import Release', 'livedg'); ?></h2>
            <form id="ldg-import-form">
                <?php wp_nonce_field('ldg_import_nonce', 'ldg_import_nonce'); ?>
                <input type="hidden" name="release_id" id="ldg-import-release-id" />
                
                <p>
                    <label for="ldg-import-price">
                        <?php echo esc_html__('Price', 'livedg'); ?>
                    </label>
                    <input type="number" 
                           name="price" 
                           id="ldg-import-price" 
                           step="0.01" 
                           min="0" 
                           class="regular-text" />
                </p>

                <p>
                    <label for="ldg-import-status">
                        <?php echo esc_html__('Product Status', 'livedg'); ?>
                    </label>
                    <select name="status" id="ldg-import-status">
                        <option value="draft"><?php echo esc_html__('Draft', 'livedg'); ?></option>
                        <option value="publish"><?php echo esc_html__('Published', 'livedg'); ?></option>
                        <option value="pending"><?php echo esc_html__('Pending Review', 'livedg'); ?></option>
                    </select>
                </p>

                <p>
                    <label>
                        <input type="checkbox" name="manage_stock" id="ldg-import-manage-stock" value="1" />
                        <?php echo esc_html__('Manage Stock', 'livedg'); ?>
                    </label>
                </p>

                <p id="ldg-stock-quantity-field" style="display: none;">
                    <label for="ldg-import-stock-quantity">
                        <?php echo esc_html__('Stock Quantity', 'livedg'); ?>
                    </label>
                    <input type="number" 
                           name="stock_quantity" 
                           id="ldg-import-stock-quantity" 
                           min="0" 
                           class="small-text" />
                </p>

                <p>
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__('Import Product', 'livedg'); ?>
                    </button>
                    <button type="button" class="button ldg-modal-close">
                        <?php echo esc_html__('Cancel', 'livedg'); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>
</div>
