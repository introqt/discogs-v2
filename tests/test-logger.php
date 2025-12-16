<?php
/**
 * Logger Tests
 *
 * @package LiveDG
 */

use PHPUnit\Framework\TestCase;

/**
 * Test logger functionality
 */
class LdgLoggerTest extends TestCase
{
    /**
     * Logger instance
     *
     * @var \LiveDG\LdgLogger
     */
    private $logger;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new \LiveDG\LdgLogger();
        $this->logger->clearLogs();
    }

    /**
     * Test error logging
     */
    public function testErrorLog(): void
    {
        $this->logger->error('Test error', ['context' => 'test']);

        $logs = $this->logger->getLogs(['level' => 'error']);

        $this->assertCount(1, $logs);
        $this->assertEquals('error', $logs[0]['level']);
        $this->assertEquals('Test error', $logs[0]['message']);
    }

    /**
     * Test info logging
     */
    public function testInfoLog(): void
    {
        $this->logger->info('Test info');

        $logs = $this->logger->getLogs(['level' => 'info']);

        $this->assertCount(1, $logs);
        $this->assertEquals('info', $logs[0]['level']);
    }

    /**
     * Test log filtering
     */
    public function testLogFiltering(): void
    {
        $this->logger->error('Error message');
        $this->logger->info('Info message');
        $this->logger->warning('Warning message');

        $allLogs = $this->logger->getLogs();
        $errorLogs = $this->logger->getLogs(['level' => 'error']);

        $this->assertCount(3, $allLogs);
        $this->assertCount(1, $errorLogs);
    }

    /**
     * Test log limit
     */
    public function testLogLimit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->logger->info("Message $i");
        }

        $logs = $this->logger->getLogs(['limit' => 3]);

        $this->assertCount(3, $logs);
    }

    /**
     * Test clear logs
     */
    public function testClearLogs(): void
    {
        $this->logger->info('Test message');

        $logs = $this->logger->getLogs();
        $this->assertNotEmpty($logs);

        $this->logger->clearLogs();

        $logs = $this->logger->getLogs();
        $this->assertEmpty($logs);
    }
}
