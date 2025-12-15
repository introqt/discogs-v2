<?php
/**
 * Logger Class
 *
 * @package LiveDG
 */

namespace LiveDG;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle plugin logging
 */
class LdgLogger
{
    /**
     * Log levels
     */
    private const LEVEL_ERROR = 'error';
    private const LEVEL_WARNING = 'warning';
    private const LEVEL_INFO = 'info';
    private const LEVEL_SUCCESS = 'success';
    private const LEVEL_DEBUG = 'debug';

    /**
     * Maximum log entries to keep
     */
    private const MAX_LOG_ENTRIES = 1000;

    /**
     * Check if logging is enabled
     *
     * @return bool
     */
    private function isEnabled(): bool
    {
        return (bool)get_option('ldg_enable_logging', true);
    }

    /**
     * Log a message
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $entry = [
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
        ];

        $logs = get_option('ldg_logs', []);

        if (!is_array($logs)) {
            $logs = [];
        }

        array_unshift($logs, $entry);

        if (count($logs) > self::MAX_LOG_ENTRIES) {
            $logs = array_slice($logs, 0, self::MAX_LOG_ENTRIES);
        }

        update_option('ldg_logs', $logs);

        /**
         * Action hook when log entry is created
         *
         * @param array $entry Log entry data
         */
        do_action('ldg_log_entry', $entry);

        if ($level === self::LEVEL_ERROR) {
            error_log("LiveDG [{$level}]: {$message} " . wp_json_encode($context));
        }
    }

    /**
     * Log error message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log info message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log success message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function success(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_SUCCESS, $message, $context);
    }

    /**
     * Log debug message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        if (!WP_DEBUG) {
            return;
        }

        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Get all log entries
     *
     * @param array $filters Optional filters
     * @return array
     */
    public function getLogs(array $filters = []): array
    {
        $logs = get_option('ldg_logs', []);

        if (!is_array($logs)) {
            return [];
        }

        if (!empty($filters['level'])) {
            $logs = array_filter($logs, function ($log) use ($filters) {
                return $log['level'] === $filters['level'];
            });
        }

        if (!empty($filters['limit'])) {
            $logs = array_slice($logs, 0, (int)$filters['limit']);
        }

        return $logs;
    }

    /**
     * Clear all log entries
     *
     * @return bool
     */
    public function clearLogs(): bool
    {
        return delete_option('ldg_logs');
    }

    /**
     * Export logs to file
     *
     * @return string|false File path or false on failure
     */
    public function exportLogs(): string|false
    {
        $logs = $this->getLogs();

        if (empty($logs)) {
            return false;
        }

        $uploadDir = wp_upload_dir();
        $logDir = $uploadDir['basedir'] . '/livedg-logs';

        if (!file_exists($logDir)) {
            wp_mkdir_p($logDir);
        }

        $filename = 'livedg-log-' . date('Y-m-d-H-i-s') . '.json';
        $filepath = $logDir . '/' . $filename;

        $result = file_put_contents($filepath, wp_json_encode($logs, JSON_PRETTY_PRINT));

        return $result !== false ? $filepath : false;
    }
}
