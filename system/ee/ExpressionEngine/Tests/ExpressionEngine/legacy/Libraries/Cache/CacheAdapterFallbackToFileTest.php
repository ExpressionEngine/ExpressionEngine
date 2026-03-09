<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\Cache;

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionEngine.com)
 *
 * @link      https://expressionEngine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionEngine.com/license Licensed under Apache License, Version 2.0
 */

class CacheAdapterFallbackToFileTest extends CacheTestBase
{
    /**
     * Test that cache defaults to file adapter when no config is set
     */
    public function testDefaultsToFileAdapter()
    {
        // Don't set any cache driver config
        ee()->config->setItem('cache_driver', '');
        ee()->config->setItem('cache_driver_backup', '');

        $cache = new \Cache();

        // In test environment, if file driver is not supported, it falls back to dummy
        // The important thing is that it doesn't crash and selects a working driver
        $adapter = $cache->get_adapter();
        $this->assertContains($adapter, ['file', 'dummy'], 'Should select either file or dummy driver');
    }

    /**
     * Test that cache uses configured driver when supported
     */
    public function testUsesConfiguredDriverWhenSupported()
    {
        // In test environment, file driver may not be supported due to temp directory restrictions.
        // Set both primary and backup to dummy to ensure consistent behavior.
        ee()->config->setItem('cache_driver', 'dummy');
        ee()->config->setItem('cache_driver_backup', 'dummy');

        $cache = new \Cache();

        $this->assertEquals('dummy', $cache->get_adapter());
    }

    /**
     * Test fallback to backup driver when primary is unsupported
     */
    public function testFallsBackToBackupWhenPrimaryUnsupported()
    {
        ee()->config->setItem('cache_driver', 'memcached');
        ee()->config->setItem('cache_driver_backup', 'file');

        // Memcached is not supported in our test environment
        $cache = new \Cache();

        $this->assertEquals('file', $cache->get_adapter());
    }

    /**
     * Test fallback to file when both primary and backup are unsupported
     */
    public function testFallsBackToFileWhenBothUnsupported()
    {
        ee()->config->setItem('cache_driver', 'nonexistent');
        ee()->config->setItem('cache_driver_backup', 'alsononexistent');

        $cache = new \Cache();

        $this->assertEquals('file', $cache->get_adapter());
    }

    /**
     * Test that invalid driver names are ignored
     */
    public function testIgnoresInvalidDriverNames()
    {
        ee()->config->setItem('cache_driver', 'invalid_driver');
        ee()->config->setItem('cache_driver_backup', 'another_invalid');

        $cache = new \Cache();

        // Should default to file since both are invalid
        $this->assertEquals('file', $cache->get_adapter());
    }

    /**
     * Test that empty backup driver still allows primary to work
     */
    public function testEmptyBackupAllowsPrimary()
    {
        ee()->config->setItem('cache_driver', 'file');
        ee()->config->setItem('cache_driver_backup', '');

        $cache = new \Cache();

        $this->assertEquals('file', $cache->get_adapter());
    }

    /**
     * Test that valid backup driver is stored even when not used
     */
    public function testValidBackupStoredWhenNotUsed()
    {
        ee()->config->setItem('cache_driver', 'dummy');
        ee()->config->setItem('cache_driver_backup', 'dummy');

        $cache = new \Cache();

        // Should use dummy as primary (since file may not be supported in test environment)
        $this->assertEquals('dummy', $cache->get_adapter());

        // But backup should be stored (we can check via reflection)
        $backup = $this->getProtectedProperty($cache, '_backup_driver');
        $this->assertEquals('dummy', $backup);
    }
}
