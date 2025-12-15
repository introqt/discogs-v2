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

$searchQuery = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$searchResults = null;

if (!empty($searchQuery) && check_admin_referer('ldg_search_nonce')) {
    $searchResults = ldg()->discogsClient->searchReleases($searchQuery);
}
?>

<div class="wrap ldg-search">
    <h1><?php echo esc_html__('Search Discogs', 'livedg'); ?></h1>

    <div class="ldg-search-form">
        <form method="get" action="">
            <input type="hidden" name="page" value="livedg-search" />
            <?php wp_nonce_field('ldg_search_nonce'); ?>
            
            <p class="search-box">
                <input type="search" 
                       name="s" 
                       id="ldg-search-input" 
                       value="<?php echo esc_attr($searchQuery); ?>" 
                       placeholder="<?php echo esc_attr__('Search for artist, album, or catalog number...', 'livedg'); ?>" 
                       class="regular-text" />
                <button type="submit" class="button button-primary">
                    <?php echo esc_html__('Search', 'livedg'); ?>
                </button>
            </p>
        </form>
    </div>

    <?php if ($searchResults !== null) : ?>
        <div class="ldg-search-results">
            <?php if (!empty($searchResults['results'])) : ?>
                <p class="ldg-results-count">
                    <?php
                    printf(
                        esc_html__('Found %d results', 'livedg'),
                        (int)($searchResults['pagination']['items'] ?? count($searchResults['results']))
                    );
                    ?>
                </p>

                <div class="ldg-results-grid">
                    <?php foreach ($searchResults['results'] as $result) : ?>
                        <div class="ldg-result-card" data-release-id="<?php echo esc_attr($result['id']); ?>">
                            <?php if (!empty($result['cover_image'])) : ?>
                                <div class="ldg-result-image">
                                    <img src="<?php echo esc_url($result['cover_image']); ?>" 
                                         alt="<?php echo esc_attr($result['title']); ?>" />
                                </div>
                            <?php endif; ?>

                            <div class="ldg-result-content">
                                <h3 class="ldg-result-title">
                                    <?php echo esc_html($result['title']); ?>
                                </h3>

                                <div class="ldg-result-meta">
                                    <?php if (!empty($result['year'])) : ?>
                                        <span class="ldg-result-year">
                                            <?php echo esc_html($result['year']); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($result['format'])) : ?>
                                        <span class="ldg-result-format">
                                            <?php echo esc_html(implode(', ', $result['format'])); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($result['label'])) : ?>
                                        <span class="ldg-result-label">
                                            <?php echo esc_html(implode(', ', $result['label'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="ldg-result-actions">
                                    <button type="button" 
                                            class="button button-primary ldg-import-btn" 
                                            data-release-id="<?php echo esc_attr($result['id']); ?>">
                                        <?php echo esc_html__('Import', 'livedg'); ?>
                                    </button>
                                    <a href="<?php echo esc_url($result['uri']); ?>" 
                                       target="_blank" 
                                       class="button">
                                        <?php echo esc_html__('View on Discogs', 'livedg'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($searchResults['pagination']['pages']) && $searchResults['pagination']['pages'] > 1) : ?>
                    <div class="ldg-pagination">
                        <?php
                        $currentPage = (int)($searchResults['pagination']['page'] ?? 1);
                        $totalPages = (int)$searchResults['pagination']['pages'];

                        for ($i = 1; $i <= min($totalPages, 10); $i++) :
                            $pageUrl = add_query_arg([
                                'page' => 'livedg-search',
                                's' => $searchQuery,
                                'paged' => $i,
                            ], admin_url('admin.php'));
                            ?>
                            <a href="<?php echo esc_url($pageUrl); ?>" 
                               class="button <?php echo $i === $currentPage ? 'button-primary' : ''; ?>">
                                <?php echo esc_html($i); ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

            <?php else : ?>
                <div class="notice notice-warning">
                    <p><?php echo esc_html__('No results found. Try a different search term.', 'livedg'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

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
