<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\Cache;

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

class DummyDriverBehaviorTest extends CacheTestBase
{
    /**
     * @var \EE_Cache_dummy
     */
    private $driver;

    public function setUp(): void
    {
        parent::setUp();
        $this->driver = new \EE_Cache_dummy();
    }

    /**
     * Test that get always returns false
     */
    public function testGetAlwaysReturnsFalse()
    {
        $result = $this->driver->get('any_key');
        $this->assertFalse($result);

        $result = $this->driver->get('another_key', \Cache::GLOBAL_SCOPE);
        $this->assertFalse($result);

        $result = $this->driver->get('');
        $this->assertFalse($result);
    }

    /**
     * Test that save always returns true
     */
    public function testSaveAlwaysReturnsTrue()
    {
        $result = $this->driver->save('any_key', 'any_value');
        $this->assertTrue($result);

        $result = $this->driver->save('another_key', 'another_value', 300, \Cache::GLOBAL_SCOPE);
        $this->assertTrue($result);

        $result = $this->driver->save('', '');
        $this->assertTrue($result);

        $result = $this->driver->save('complex', ['array' => 'data']);
        $this->assertTrue($result);
    }

    /**
     * Test that delete always returns true
     */
    public function testDeleteAlwaysReturnsTrue()
    {
        $result = $this->driver->delete('any_key');
        $this->assertTrue($result);

        $result = $this->driver->delete('another_key', \Cache::GLOBAL_SCOPE);
        $this->assertTrue($result);

        $result = $this->driver->delete('');
        $this->assertTrue($result);

        $result = $this->driver->delete('/namespace/');
        $this->assertTrue($result);
    }

    /**
     * Test that clean always returns true
     */
    public function testCleanAlwaysReturnsTrue()
    {
        $result = $this->driver->clean();
        $this->assertTrue($result);

        $result = $this->driver->clean(\Cache::GLOBAL_SCOPE);
        $this->assertTrue($result);
    }

    /**
     * Test that cache_info always returns false
     */
    public function testCacheInfoAlwaysReturnsFalse()
    {
        $result = $this->driver->cache_info();
        $this->assertFalse($result);
    }

    /**
     * Test that get_metadata always returns false
     */
    public function testGetMetadataAlwaysReturnsFalse()
    {
        $result = $this->driver->get_metadata('any_key');
        $this->assertFalse($result);

        $result = $this->driver->get_metadata('another_key', \Cache::GLOBAL_SCOPE);
        $this->assertFalse($result);

        $result = $this->driver->get_metadata('');
        $this->assertFalse($result);
    }

    /**
     * Test that is_supported always returns true
     */
    public function testIsSupportedAlwaysReturnsTrue()
    {
        $result = $this->driver->is_supported();
        $this->assertTrue($result);
    }

    /**
     * Test that dummy driver can be configured directly
     */
    public function testDummyDriverCanBeConfigured()
    {
        // Configure dummy driver directly
        ee()->config->setItem('cache_driver', 'dummy');

        $cache = new \Cache();

        // Should use dummy
        $this->assertEquals('dummy', $cache->get_adapter());
    }
}
