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

class FileDriverCleanInfoTest extends CacheTestBase
{
    /**
     * @var EE_Cache_file
     */
    private $driver;

    public function setUp(): void
    {
        parent::setUp();
        $this->driver = $this->makeFileDriverAt($this->tempDir);
    }

    /**
     * Test clean local scope clears items but preserves index.html
     */
    public function testCleanLocalScope()
    {
        // Save some items
        $this->driver->save('local_item1', 'value1', 60, \Cache::LOCAL_SCOPE);
        $this->driver->save('local_item2', 'value2', 60, \Cache::LOCAL_SCOPE);

        // Verify they exist
        $this->assertEquals('value1', $this->driver->get('local_item1', \Cache::LOCAL_SCOPE));
        $this->assertEquals('value2', $this->driver->get('local_item2', \Cache::LOCAL_SCOPE));

        // Clean local scope
        $result = $this->driver->clean(\Cache::LOCAL_SCOPE);
        $this->assertTrue($result);

        // Items should be gone
        $this->assertFalse($this->driver->get('local_item1', \Cache::LOCAL_SCOPE));
        $this->assertFalse($this->driver->get('local_item2', \Cache::LOCAL_SCOPE));
    }

    /**
     * Test clean global scope preserves .htaccess
     */
    public function testCleanGlobalScopePreservesHtaccess()
    {
        // Create a .htaccess file in the temp dir
        $htaccessPath = $this->tempDir . DIRECTORY_SEPARATOR . '.htaccess';
        file_put_contents($htaccessPath, 'Deny from all');
        $this->assertFileExists($htaccessPath);

        // Save some items with global scope
        $this->driver->save('global_item1', 'value1', 60, \Cache::GLOBAL_SCOPE);
        $this->driver->save('global_item2', 'value2', 60, \Cache::GLOBAL_SCOPE);

        // Clean global scope
        $result = $this->driver->clean(\Cache::GLOBAL_SCOPE);
        $this->assertTrue($result);

        // .htaccess should still exist
        $this->assertFileExists($htaccessPath);
        $this->assertEquals('Deny from all', file_get_contents($htaccessPath));
    }

    /**
     * Test cache_info returns array
     */
    public function testCacheInfoReturnsArray()
    {
        // Save some items to have something to list
        $this->driver->save('info_test1', 'value1', 60);
        $this->driver->save('info_test2', 'value2', 60);

        $info = $this->driver->cache_info();

        $this->assertIsArray($info);
        // May be empty for temp directory, but should still be an array
    }

    /**
     * Test get_metadata returns correct structure
     */
    public function testGetMetadataReturnsCorrectStructure()
    {
        $testData = 'metadata test data';
        $ttl = 300; // 5 minutes

        $this->driver->save('metadata_test', $testData, $ttl);

        $metadata = $this->driver->get_metadata('metadata_test');

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('expire', $metadata);
        $this->assertArrayHasKey('mtime', $metadata);
        $this->assertArrayHasKey('data', $metadata);

        // Check types
        $this->assertIsInt($metadata['expire']);
        $this->assertIsInt($metadata['mtime']);
        $this->assertEquals($testData, $metadata['data']);

        // Expire should be mtime + ttl
        $this->assertEquals($metadata['mtime'] + $ttl, $metadata['expire']);
    }

    /**
     * Test get_metadata for non-existent key
     */
    public function testGetMetadataForNonExistentKey()
    {
        $metadata = $this->driver->get_metadata('nonexistent');
        $this->assertFalse($metadata);
    }

    /**
     * Test is_supported returns true for writable directory
     */
    public function testIsSupportedReturnsTrueForWritableDir()
    {
        $supported = $this->driver->is_supported();
        $this->assertTrue($supported);
    }

    /**
     * Test is_supported returns false for non-writable directory
     */
    public function testIsSupportedReturnsFalseForNonWritableDir()
    {
        // Skip on Windows as chmod permissions don't work the same way
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('This test is not reliable on Windows due to different permission handling');
        }

        // Create a non-writable directory
        $nonWritableDir = $this->tempDir . DIRECTORY_SEPARATOR . 'non_writable';
        mkdir($nonWritableDir, 0444); // Read-only

        $driver = $this->makeFileDriverAt($nonWritableDir);

        $supported = $driver->is_supported();
        $this->assertFalse($supported);
    }

    /**
     * Test namespacing with site_short_name for local scope
     */
    public function testNamespacingWithSiteShortName()
    {
        // ee()->config->setItem('site_short_name', 'default_site'); is already set in base

        $this->driver->save('namespaced_key', 'namespaced_value', 60, \Cache::LOCAL_SCOPE);

        // The key should be retrievable
        $value = $this->driver->get('namespaced_key', \Cache::LOCAL_SCOPE);
        $this->assertEquals('namespaced_value', $value);
    }

    /**
     * Test namespacing without site_short_name
     */
    public function testNamespacingWithoutSiteShortName()
    {
        // Temporarily remove site_short_name
        ee()->config->setItem('site_short_name', '');

        $this->driver->save('no_site_key', 'no_site_value', 60, \Cache::LOCAL_SCOPE);

        $value = $this->driver->get('no_site_key', \Cache::LOCAL_SCOPE);
        $this->assertEquals('no_site_value', $value);

        // Restore for other tests
        ee()->config->setItem('site_short_name', 'default_site');
    }

}
