<?php
/**
 * Plugin Tests
 *
 * @package LiveDG
 */

use PHPUnit\Framework\TestCase;

/**
 * Test plugin initialization
 */
class LdgPluginTest extends TestCase
{
    /**
     * Test plugin instance creation
     */
    public function testPluginInstance(): void
    {
        $this->assertInstanceOf(
            \LiveDG\LdgPlugin::class,
            ldg()
        );
    }

    /**
     * Test plugin version constant
     */
    public function testPluginVersion(): void
    {
        $this->assertTrue(defined('LDG_VERSION'));
        $this->assertEquals('1.0.0', LDG_VERSION);
    }

    /**
     * Test plugin components initialization
     */
    public function testPluginComponents(): void
    {
        $plugin = ldg();

        $this->assertInstanceOf(\LiveDG\LdgLoader::class, $plugin->loader);
        $this->assertInstanceOf(\LiveDG\LdgAdmin::class, $plugin->admin);
        $this->assertInstanceOf(\LiveDG\LdgSettings::class, $plugin->settings);
        $this->assertInstanceOf(\LiveDG\LdgDiscogsClient::class, $plugin->discogsClient);
        $this->assertInstanceOf(\LiveDG\LdgImporter::class, $plugin->importer);
        $this->assertInstanceOf(\LiveDG\LdgLogger::class, $plugin->logger);
        $this->assertInstanceOf(\LiveDG\LdgCache::class, $plugin->cache);
    }
}
