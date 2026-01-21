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

class CacheDatabaseGetTest extends CacheDatabaseTestBase
{
    /**
     * Test that get returns data when key exists
     */
    public function testGetReturnsDataWhenKeyExists()
    {
        $testData = ['key' => 'value'];
        $row = $this->makeCacheRow(serialize($testData), 60);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->get('test_key');

        $this->assertEquals($testData, $result);
    }

    /**
     * Test that get returns false when key does not exist
     */
    public function testGetReturnsFalseWhenKeyDoesNotExist()
    {
        $this->setQueryResult([]);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->get('missing_key');

        $this->assertFalse($result);
    }

    /**
     * Test that get returns false when cache has expired
     */
    public function testGetReturnsFalseWhenCacheExpired()
    {
        // Set current time
        ee()->localize->now = 1000000000;
        
        // Create expired cache (created 1 hour ago with 30 min TTL)
        $createdAt = 1000000000 - 3600; // 1 hour ago
        $ttl = 1800; // 30 minutes
        $row = $this->makeCacheRow(serialize('expired'), $ttl, $createdAt);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->get('expired_key');

        $this->assertFalse($result);
    }

    /**
     * Test that get returns valid data when not expired
     */
    public function testGetReturnsValidDataWhenNotExpired()
    {
        // Set current time
        ee()->localize->now = 1000000000;
        
        // Create valid cache (created 10 seconds ago with 1 hour TTL)
        $createdAt = 1000000000 - 10;
        $ttl = 3600; // 1 hour
        $testData = 'valid data';
        $row = $this->makeCacheRow(serialize($testData), $ttl, $createdAt);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->get('valid_key');

        $this->assertEquals($testData, $result);
    }

    /**
     * Test that get uses local cache on second call
     */
    public function testGetUsesLocalCacheOnSecondCall()
    {
        $testData = 'cached data';
        $row = $this->makeCacheRow(serialize($testData), 60);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        
        // First call should hit the database
        $result1 = $driver->get('test_key');
        $this->assertEquals($testData, $result1);
        
        // Reset query tracking
        $queryCallCount = ee()->db->getCalled ? 1 : 0;
        
        // Reset the mock to track second call
        $this->setQueryResult([]); // Empty result to prove it uses cache
        
        // Second call should use local cache (not hit database)
        $result2 = $driver->get('test_key');
        $this->assertEquals($testData, $result2);
    }

    /**
     * Test that get with local scope uses correct namespacing
     */
    public function testGetWithLocalScope()
    {
        ee()->config->setItem('site_short_name', 'test_site');

        $testData = 'local data';
        $row = $this->makeCacheRow(serialize($testData), 60);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->get('local_key', \Cache::LOCAL_SCOPE);

        $this->assertEquals($testData, $result);
        $this->assertTrue(ee()->db->whereCalled);
    }

    /**
     * Test that get with global scope uses correct namespacing
     */
    public function testGetWithGlobalScope()
    {
        $testData = 'global data';
        $row = $this->makeCacheRow(serialize($testData), 60);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->get('global_key', \Cache::GLOBAL_SCOPE);

        $this->assertEquals($testData, $result);
        $this->assertTrue(ee()->db->whereCalled);
    }

    /**
     * Test that get unserializes data correctly with various data types
     */
    public function testGetUnserializesDataCorrectly()
    {
        $testCases = [
            'string' => 'simple string',
            'array' => ['nested' => ['data' => 'value']],
            'object' => (object)['prop' => 'value'],
            'number' => 42,
            'boolean' => true,
        ];

        foreach ($testCases as $type => $testData) {
            $this->resetDatabaseMocks();
            
            $row = $this->makeCacheRow(serialize($testData), 60);
            $this->setQueryResult([$row]);

            $driver = $this->makeDatabaseDriver();
            $result = $driver->get("key_$type");

            $this->assertEquals($testData, $result, "Failed to deserialize $type");
        }
    }

    /**
     * Test that get stores false in local cache for missing key
     */
    public function testGetStoresFalseInLocalCacheForMissingKey()
    {
        $this->setQueryResult([]);

        $driver = $this->makeDatabaseDriver();
        
        // First call
        $result1 = $driver->get('missing_key');
        $this->assertFalse($result1);
        
        // Second call should also return false without hitting DB
        $result2 = $driver->get('missing_key');
        $this->assertFalse($result2);
    }

    /**
     * Test that get deletes expired cache item
     */
    public function testGetDeletesExpiredCacheItem()
    {
        // Set current time
        ee()->localize->now = 1000000000;
        
        // Create expired cache
        $createdAt = 1000000000 - 7200; // 2 hours ago
        $ttl = 3600; // 1 hour TTL (expired 1 hour ago)
        $row = $this->makeCacheRow(serialize('expired'), $ttl, $createdAt);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->get('expired_key');

        $this->assertFalse($result);
        // The delete method should have been called
        $this->assertTrue(ee()->db->deleteCalled);
    }

    /**
     * Test that get handles zero TTL (never expires)
     */
    public function testGetHandlesZeroTTL()
    {
        // Set current time far in the future
        ee()->localize->now = 2000000000;
        
        // Create cache with zero TTL (never expires)
        $createdAt = 1000000000; // Long time ago
        $ttl = 0; // Never expires
        $testData = 'never expires';
        $row = $this->makeCacheRow(serialize($testData), $ttl, $createdAt);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->get('zero_ttl_key');

        $this->assertEquals($testData, $result);
    }
}

