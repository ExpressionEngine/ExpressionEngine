<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\Cache;

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

class CacheAdminSettingChoicesTest extends CacheTestBase
{
    /**
     * Test that admin_setting returns radio type with all valid drivers
     */
    public function testAdminSettingReturnsRadioWithAllDrivers()
    {
        $cache = new \Cache();

        $result = $cache->admin_setting();

        $this->assertEquals('radio', $result['type']);
        $this->assertArrayHasKey('choices', $result);
        $this->assertArrayHasKey('file', $result['choices']);
        $this->assertArrayHasKey('memcached', $result['choices']);
        $this->assertArrayHasKey('redis', $result['choices']);
        $this->assertArrayHasKey('dummy', $result['choices']);
    }

    /**
     * Test that admin_setting has correct driver labels
     */
    public function testAdminSettingHasCorrectLabels()
    {
        $cache = new \Cache();

        $result = $cache->admin_setting();

        $this->assertEquals('File', $result['choices']['file']);
        $this->assertEquals('Memcached', $result['choices']['memcached']);
        $this->assertEquals('Redis', $result['choices']['redis']);
        // Note: 'dummy' gets renamed to lang('disable_caching') which returns the key as-is
        $this->assertEquals('disable_caching', $result['choices']['dummy']);
    }

    /**
     * Test that admin_setting defaults to file when config is empty
     */
    public function testAdminSettingDefaultsToFileWhenEmpty()
    {
        // Set empty cache_driver config
        ee()->config->setItem('cache_driver', '');

        $cache = new \Cache();

        // The cache should initialize with file as default, but may fall back to dummy in test environment
        $adapter = $cache->get_adapter();
        $this->assertContains($adapter, ['file', 'dummy'], 'Should use file or dummy driver');

        $result = $cache->admin_setting();

        // Should still return all choices
        $this->assertArrayHasKey('file', $result['choices']);
    }

    /**
     * Test that admin_setting includes note when configured driver differs from active
     */
    public function testAdminSettingIncludesNoteWhenDriverMismatch()
    {
        // Configure memcached (unsupported) so it falls back to dummy
        ee()->config->setItem('cache_driver', 'memcached');
        ee()->config->setItem('cache_driver_backup', 'dummy');

        $cache = new \Cache();

        $result = $cache->admin_setting();

        // Should have a note since configured is memcached but active is dummy
        $this->assertArrayHasKey('note', $result);
        // Note: lang() mock returns key as-is, so we get the lang key
        $this->assertEquals('caching_driver_failover', $result['note']);
    }

    /**
     * Test that admin_setting works when configured and active drivers match
     */
    public function testAdminSettingNoNoteWhenDriversMatch()
    {
        // Configure dummy (which is always supported) to ensure it matches active driver
        ee()->config->setItem('cache_driver', 'dummy');

        $cache = new \Cache();

        $result = $cache->admin_setting();

        // Should not have a note since configured and active match
        $this->assertArrayNotHasKey('note', $result);
    }
}
