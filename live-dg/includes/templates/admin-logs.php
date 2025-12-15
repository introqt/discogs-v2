<?php
/**
 * Admin Logs Template
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

$filterLevel = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : '';
$filters = [];

if (!empty($filterLevel)) {
    $filters['level'] = $filterLevel;
}

$logs = ldg()->logger->getLogs($filters);
?>

<div class="wrap ldg-logs">
    <h1><?php echo esc_html__('Activity Logs', 'livedg'); ?></h1>

    <div class="ldg-logs-filters">
        <form method="get">
            <input type="hidden" name="page" value="livedg-logs" />
            
            <label for="log-level-filter"><?php echo esc_html__('Filter by Level:', 'livedg'); ?></label>
            <select name="level" id="log-level-filter">
                <option value=""><?php echo esc_html__('All Levels', 'livedg'); ?></option>
                <option value="error" <?php selected($filterLevel, 'error'); ?>><?php echo esc_html__('Error', 'livedg'); ?></option>
                <option value="warning" <?php selected($filterLevel, 'warning'); ?>><?php echo esc_html__('Warning', 'livedg'); ?></option>
                <option value="info" <?php selected($filterLevel, 'info'); ?>><?php echo esc_html__('Info', 'livedg'); ?></option>
                <option value="success" <?php selected($filterLevel, 'success'); ?>><?php echo esc_html__('Success', 'livedg'); ?></option>
                <option value="debug" <?php selected($filterLevel, 'debug'); ?>><?php echo esc_html__('Debug', 'livedg'); ?></option>
            </select>
            
            <button type="submit" class="button"><?php echo esc_html__('Filter', 'livedg'); ?></button>
            
            <?php if (!empty($filterLevel)) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=livedg-logs')); ?>" class="button">
                    <?php echo esc_html__('Clear Filter', 'livedg'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($logs)) : ?>
        <p class="ldg-logs-count">
            <?php printf(esc_html__('Showing %d log entries', 'livedg'), count($logs)); ?>
        </p>

        <table class="wp-list-table widefat fixed striped ldg-logs-table">
            <thead>
                <tr>
                    <th class="ldg-log-timestamp"><?php echo esc_html__('Timestamp', 'livedg'); ?></th>
                    <th class="ldg-log-level"><?php echo esc_html__('Level', 'livedg'); ?></th>
                    <th class="ldg-log-message"><?php echo esc_html__('Message', 'livedg'); ?></th>
                    <th class="ldg-log-user"><?php echo esc_html__('User', 'livedg'); ?></th>
                    <th class="ldg-log-actions"><?php echo esc_html__('Actions', 'livedg'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $index => $log) : ?>
                    <tr data-log-index="<?php echo esc_attr($index); ?>">
                        <td class="ldg-log-timestamp">
                            <?php echo esc_html($log['timestamp']); ?>
                        </td>
                        <td class="ldg-log-level">
                            <span class="ldg-log-badge ldg-log-<?php echo esc_attr($log['level']); ?>">
                                <?php echo esc_html(ucfirst($log['level'])); ?>
                            </span>
                        </td>
                        <td class="ldg-log-message">
                            <?php echo esc_html($log['message']); ?>
                        </td>
                        <td class="ldg-log-user">
                            <?php
                            if (!empty($log['user_id'])) {
                                $user = get_userdata($log['user_id']);
                                echo esc_html($user ? $user->display_name : __('Unknown', 'livedg'));
                            } else {
                                echo esc_html__('System', 'livedg');
                            }
                            ?>
                        </td>
                        <td class="ldg-log-actions">
                            <?php if (!empty($log['context'])) : ?>
                                <button type="button" 
                                        class="button button-small ldg-view-context" 
                                        data-context="<?php echo esc_attr(wp_json_encode($log['context'])); ?>">
                                    <?php echo esc_html__('View Details', 'livedg'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <div class="notice notice-info">
            <p><?php echo esc_html__('No log entries found.', 'livedg'); ?></p>
        </div>
    <?php endif; ?>

    <div id="ldg-log-context-modal" class="ldg-modal" style="display: none;">
        <div class="ldg-modal-content">
            <span class="ldg-modal-close">&times;</span>
            <h2><?php echo esc_html__('Log Details', 'livedg'); ?></h2>
            <pre id="ldg-log-context-content"></pre>
        </div>
    </div>
</div>
