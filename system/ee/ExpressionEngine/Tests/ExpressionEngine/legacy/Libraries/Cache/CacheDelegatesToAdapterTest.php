<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\Cache;

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

class CacheDelegatesToAdapterTest extends CacheTestBase
{
    /**
     * Test that Cache::get() delegates to adapter
     */
    public function testGetDelegatesToAdapter()
    {
        $cache = $this->makeCacheWithAdapter('dummy');

        // Dummy driver always returns false for get
        $value = $cache->get('any_key');
        $this->assertFalse($value);
    }

    /**
     * Test that Cache::save() delegates to adapter
     */
    public function testSaveDelegatesToAdapter()
    {
        $cache = $this->makeCacheWithAdapter('dummy');

        // Dummy driver always returns true for save
        $result = $cache->save('save_test', 'saved_value', 60);
        $this->assertTrue($result);

        // Dummy driver always returns false for get
        $value = $cache->get('save_test');
        $this->assertFalse($value);
    }

    /**
     * Test that Cache::delete() delegates to adapter
     */
    public function testDeleteDelegatesToAdapter()
    {
        $cache = $this->makeCacheWithAdapter('dummy');

        // Dummy always returns true for save
        $cache->save('delete_test', 'to_be_deleted', 60);

        // Dummy always returns false for get
        $this->assertFalse($cache->get('delete_test'));

        // Delete (dummy always returns true)
        $result = $cache->delete('delete_test');
        $this->assertTrue($result);
    }

    /**
     * Test that Cache::clean() delegates to adapter
     */
    public function testCleanDelegatesToAdapter()
    {
        $cache = $this->makeCacheWithAdapter('dummy');

        // Save multiple items (dummy always returns true)
        $cache->save('clean_test1', 'value1', 60);
        $cache->save('clean_test2', 'value2', 60);

        // Dummy always returns false for get
        $this->assertFalse($cache->get('clean_test1'));
        $this->assertFalse($cache->get('clean_test2'));

        // Clean (dummy always returns true)
        $result = $cache->clean();
        $this->assertTrue($result);
    }

    /**
     * Test that Cache::cache_info() delegates to adapter
     */
    public function testCacheInfoDelegatesToAdapter()
    {
        $cache = $this->makeCacheWithAdapter('dummy');

        $info = $cache->cache_info();

        // Dummy driver returns false
        $this->assertFalse($info);
    }

    /**
     * Test that Cache::get_metadata() delegates to adapter
     */
    public function testGetMetadataDelegatesToAdapter()
    {
        $cache = $this->makeCacheWithAdapter('dummy');

        // Save an item first
        $cache->save('metadata_test', 'metadata_value', 300); // 5 minutes TTL

        // Get metadata
        $metadata = $cache->get_metadata('metadata_test');

        // Dummy driver returns false
        $this->assertFalse($metadata);
    }

    /**
     * Test delegation with complex data types
     */
    public function testDelegationWithComplexData()
    {
        $cache = $this->makeCacheWithAdapter('dummy');

        $complexData = [
            'array' => ['nested' => 'data'],
            'object' => (object)['prop' => 'value'],
            'string' => 'simple string',
            'number' => 42
        ];

        // Save complex data (dummy always returns true)
        $result = $cache->save('complex_test', $complexData, 60);
        $this->assertTrue($result);

        // Retrieve (dummy always returns false)
        $retrieved = $cache->get('complex_test');
        $this->assertFalse($retrieved);
    }

    /**
     * Test delegation with global scope
     */
    public function testDelegationWithGlobalScope()
    {
        $cache = $this->makeCacheWithAdapter('dummy');

        // Save with global scope (dummy always returns true)
        $result = $cache->save('global_test', 'global_value', 60, \Cache::GLOBAL_SCOPE);
        $this->assertTrue($result);

        // Retrieve with global scope (dummy always returns false)
        $value = $cache->get('global_test', \Cache::GLOBAL_SCOPE);
        $this->assertFalse($value);
    }
}
