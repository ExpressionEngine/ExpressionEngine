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

class CacheDelegatesToAdapterTest extends CacheTestBase
{
    /**
     * Test that Cache::get() delegates to adapter
     */
    public function testGetDelegatesToAdapter()
    {
        $cache = $this->makeCacheWithAdapter('file');

        // Save a value first
        $result = $cache->save('test_key', 'test_value', 60);
        $this->assertTrue($result);

        // Now get it back
        $value = $cache->get('test_key');
        $this->assertEquals('test_value', $value);
    }

    /**
     * Test that Cache::save() delegates to adapter
     */
    public function testSaveDelegatesToAdapter()
    {
        $cache = $this->makeCacheWithAdapter('file');

        $result = $cache->save('save_test', 'saved_value', 60);
        $this->assertTrue($result);

        // Verify it was actually saved
        $value = $cache->get('save_test');
        $this->assertEquals('saved_value', $value);
    }

    /**
     * Test that Cache::delete() delegates to adapter
     */
    public function testDeleteDelegatesToAdapter()
    {
        $cache = $this->makeCacheWithAdapter('file');

        // Save and verify
        $cache->save('delete_test', 'to_be_deleted', 60);
        $this->assertEquals('to_be_deleted', $cache->get('delete_test'));

        // Delete
        $result = $cache->delete('delete_test');
        $this->assertTrue($result);

        // Verify it's gone
        $this->assertFalse($cache->get('delete_test'));
    }

    /**
     * Test that Cache::clean() delegates to adapter
     */
    public function testCleanDelegatesToAdapter()
    {
        $cache = $this->makeCacheWithAdapter('file');

        // Save multiple items
        $cache->save('clean_test1', 'value1', 60);
        $cache->save('clean_test2', 'value2', 60);

        // Verify they exist
        $this->assertEquals('value1', $cache->get('clean_test1'));
        $this->assertEquals('value2', $cache->get('clean_test2'));

        // Clean
        $result = $cache->clean();
        $this->assertTrue($result);

        // Verify they're gone (clean should clear local scope)
        $this->assertFalse($cache->get('clean_test1'));
        $this->assertFalse($cache->get('clean_test2'));
    }

    /**
     * Test that Cache::cache_info() delegates to adapter
     */
    public function testCacheInfoDelegatesToAdapter()
    {
        $cache = $this->makeCacheWithAdapter('file');

        $info = $cache->cache_info();

        // File driver returns directory info array
        $this->assertIsArray($info);
    }

    /**
     * Test that Cache::get_metadata() delegates to adapter
     */
    public function testGetMetadataDelegatesToAdapter()
    {
        $cache = $this->makeCacheWithAdapter('file');

        // Save an item first
        $cache->save('metadata_test', 'metadata_value', 300); // 5 minutes TTL

        // Get metadata
        $metadata = $cache->get_metadata('metadata_test');

        // File driver returns array with expire, mtime, data
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('expire', $metadata);
        $this->assertArrayHasKey('mtime', $metadata);
        $this->assertArrayHasKey('data', $metadata);
        $this->assertEquals('metadata_value', $metadata['data']);
    }

    /**
     * Test delegation with complex data types
     */
    public function testDelegationWithComplexData()
    {
        $cache = $this->makeCacheWithAdapter('file');

        $complexData = [
            'array' => ['nested' => 'data'],
            'object' => (object)['prop' => 'value'],
            'string' => 'simple string',
            'number' => 42
        ];

        // Save complex data
        $result = $cache->save('complex_test', $complexData, 60);
        $this->assertTrue($result);

        // Retrieve and verify
        $retrieved = $cache->get('complex_test');
        $this->assertEquals($complexData, $retrieved);
    }

    /**
     * Test delegation with global scope
     */
    public function testDelegationWithGlobalScope()
    {
        $cache = $this->makeCacheWithAdapter('file');

        // Save with global scope
        $result = $cache->save('global_test', 'global_value', 60, \Cache::GLOBAL_SCOPE);
        $this->assertTrue($result);

        // Retrieve with global scope
        $value = $cache->get('global_test', \Cache::GLOBAL_SCOPE);
        $this->assertEquals('global_value', $value);
    }
}
