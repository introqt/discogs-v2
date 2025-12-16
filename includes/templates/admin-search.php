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
            
            <p class="search-box">
                <input type="search" 
                       name="s" 
                       id="ldg-search-input" 
                       placeholder="<?php echo esc_attr__('Search for artist, album, or catalog number...', 'livedg'); ?>" 
                       class="regular-text" />
                <button type="submit" class="button button-primary">
                    <?php echo esc_html__('Search', 'livedg'); ?>
                </button>
            </p>
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
