<?php
/**
 * Admin Dashboard Template
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

$stats = [
    'imported_products' => wp_count_posts('product')->publish ?? 0,
    'cache_size' => ldg()->cache->getStats()['size_formatted'] ?? '0 B',
    'log_count' => count(ldg()->logger->getLogs(['limit' => 100])),
];

$recentLogs = ldg()->logger->getLogs(['limit' => 5]);
?>

<div class="wrap ldg-dashboard">
    <h1><?php echo esc_html__('LiveDG Dashboard', 'livedg'); ?></h1>

    <div class="ldg-dashboard-grid">
        <div class="ldg-card">
            <h2><?php echo esc_html__('Quick Stats', 'livedg'); ?></h2>
            <div class="ldg-stats">
                <div class="ldg-stat-item">
                    <span class="ldg-stat-label"><?php echo esc_html__('Imported Products', 'livedg'); ?></span>
                    <span class="ldg-stat-value"><?php echo esc_html($stats['imported_products']); ?></span>
                </div>
                <div class="ldg-stat-item">
                    <span class="ldg-stat-label"><?php echo esc_html__('Cache Size', 'livedg'); ?></span>
                    <span class="ldg-stat-value"><?php echo esc_html($stats['cache_size']); ?></span>
                </div>
                <div class="ldg-stat-item">
                    <span class="ldg-stat-label"><?php echo esc_html__('Recent Logs', 'livedg'); ?></span>
                    <span class="ldg-stat-value"><?php echo esc_html($stats['log_count']); ?></span>
                </div>
            </div>
        </div>

        <div class="ldg-card">
            <h2><?php echo esc_html__('Quick Actions', 'livedg'); ?></h2>
            <div class="ldg-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=livedg-search')); ?>" class="button button-primary button-large">
                    <?php echo esc_html__('Search Discogs', 'livedg'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=livedg-settings')); ?>" class="button button-large">
                    <?php echo esc_html__('Settings', 'livedg'); ?>
                </a>
                <button type="button" class="button button-large" id="ldg-clear-cache">
                    <?php echo esc_html__('Clear Cache', 'livedg'); ?>
                </button>
            </div>
        </div>

        <div class="ldg-card ldg-full-width">
            <h2><?php echo esc_html__('Recent Activity', 'livedg'); ?></h2>
            <?php if (!empty($recentLogs)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Time', 'livedg'); ?></th>
                            <th><?php echo esc_html__('Level', 'livedg'); ?></th>
                            <th><?php echo esc_html__('Message', 'livedg'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $log) : ?>
                            <tr>
                                <td><?php echo esc_html($log['timestamp']); ?></td>
                                <td><span class="ldg-log-level ldg-log-<?php echo esc_attr($log['level']); ?>">
                                    <?php echo esc_html(ucfirst($log['level'])); ?>
                                </span></td>
                                <td><?php echo esc_html($log['message']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=livedg-logs')); ?>">
                        <?php echo esc_html__('View All Logs', 'livedg'); ?>
                    </a>
                </p>
            <?php else : ?>
                <p><?php echo esc_html__('No recent activity.', 'livedg'); ?></p>
            <?php endif; ?>
        </div>

        <div class="ldg-card ldg-full-width">
            <h2><?php echo esc_html__('Getting Started', 'livedg'); ?></h2>
            <ol class="ldg-getting-started">
                <li>
                    <strong><?php echo esc_html__('Configure API Credentials', 'livedg'); ?></strong>
                    <p><?php echo esc_html__('Add your Discogs API credentials in the settings page.', 'livedg'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=livedg-settings')); ?>" class="button">
                        <?php echo esc_html__('Go to Settings', 'livedg'); ?>
                    </a>
                </li>
                <li>
                    <strong><?php echo esc_html__('Search Discogs', 'livedg'); ?></strong>
                    <p><?php echo esc_html__('Use the search page to find releases on Discogs.', 'livedg'); ?></p>
                </li>
                <li>
                    <strong><?php echo esc_html__('Import Products', 'livedg'); ?></strong>
                    <p><?php echo esc_html__('Import releases as WooCommerce products with one click.', 'livedg'); ?></p>
                </li>
            </ol>
        </div>
    </div>
</div>
