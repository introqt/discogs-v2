<?php
/**
 * Cache Tests
 *
 * @package LiveDG
 */

use PHPUnit\Framework\TestCase;

/**
 * Test cache functionality
 */
class LdgCacheTest extends TestCase
{
    /**
     * Cache instance
     *
     * @var \LiveDG\LdgCache
     */
    private $cache;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new \LiveDG\LdgCache();
    }

    /**
     * Test cache set and get
     */
    public function testCacheSetGet(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->cache->set($key, $value);
        $result = $this->cache->get($key);

        $this->assertEquals($value, $result);
    }

    /**
     * Test cache has method
     */
    public function testCacheHas(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->assertFalse($this->cache->has($key));

        $this->cache->set($key, $value);

        $this->assertTrue($this->cache->has($key));
    }

    /**
     * Test cache delete
     */
    public function testCacheDelete(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->cache->set($key, $value);
        $this->assertTrue($this->cache->has($key));

        $this->cache->delete($key);
        $this->assertFalse($this->cache->has($key));
    }

    /**
     * Test cache remember
     */
    public function testCacheRemember(): void
    {
        $key = 'test_remember';
        $callCount = 0;

        $callback = function () use (&$callCount) {
            $callCount++;
            return 'computed_value';
        };

        $result1 = $this->cache->remember($key, $callback);
        $result2 = $this->cache->remember($key, $callback);

        $this->assertEquals('computed_value', $result1);
        $this->assertEquals('computed_value', $result2);
        $this->assertEquals(1, $callCount);
    }
}
