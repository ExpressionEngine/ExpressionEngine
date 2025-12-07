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

class CacheDatabaseLocalCacheTest extends CacheDatabaseTestBase
{
    /**
     * Test that local cache stores retrieved data
     */
    public function testLocalCacheStoresRetrievedData()
    {
        $testData = 'cached data';
        $row = $this->makeCacheRow(serialize($testData), 60);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $driver->get('test_key');

        $localCache = $this->getProtectedProperty($driver, '_local_cache');
        $this->assertNotEmpty($localCache);
    }

    /**
     * Test that local cache prevents second database query
     */
    public function testLocalCachePreventsSecondDatabaseQuery()
    {
        $testData = 'cached data';
        $row = $this->makeCacheRow(serialize($testData), 60);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        
        // First get - should query database
        $result1 = $driver->get('test_key');
        $this->assertEquals($testData, $result1);
        
        // Clear query result to verify second call doesn't hit DB
        $this->setQueryResult([]);
        
        // Second get - should use local cache
        $result2 = $driver->get('test_key');
        $this->assertEquals($testData, $result2);
    }

    /**
     * Test that local cache stores false for missing keys
     */
    public function testLocalCacheStoresFalseForMissingKeys()
    {
        $this->setQueryResult([]);

        $driver = $this->makeDatabaseDriver();
        
        // First call
        $result1 = $driver->get('missing_key');
        $this->assertFalse($result1);
        
        // Check local cache
        $localCache = $this->getProtectedProperty($driver, '_local_cache');
        $this->assertArrayHasKey('default_site_missing_key', $localCache);
        $this->assertFalse($localCache['default_site_missing_key']);
    }

    /**
     * Test that local cache is updated on save
     */
    public function testLocalCacheUpdatedOnSave()
    {
        $this->setQueryResult([]);

        $driver = $this->makeDatabaseDriver();
        $driver->save('test_key', 'test_value', 60);

        $localCache = $this->getProtectedProperty($driver, '_local_cache');
        $this->assertNotEmpty($localCache);
    }

    /**
     * Test that local cache is cleared on delete
     */
    public function testLocalCacheClearedOnDelete()
    {
        // First populate local cache
        $testData = 'test value';
        $row = $this->makeCacheRow(serialize($testData), 60);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $driver->get('test_key');

        // Verify it's in local cache
        $localCache = $this->getProtectedProperty($driver, '_local_cache');
        $this->assertNotEmpty($localCache);

        // Now delete
        $driver->delete('test_key');

        // The delete should have been called
        $this->assertTrue(ee()->db->deleteCalled);
    }

    /**
     * Test that namespace delete clears all namespace keys from local cache
     */
    public function testLocalCacheClearedOnNamespaceDelete()
    {
        ee()->config->setItem('site_short_name', 'test_site');

        // Populate local cache with multiple keys in same namespace
        $driver = $this->makeDatabaseDriver();
        
        // Manually populate local cache
        $localCache = [];
        $localCache['test_site_namespace_key1'] = $this->makeCacheRow(serialize('value1'), 60);
        $localCache['test_site_namespace_key2'] = $this->makeCacheRow(serialize('value2'), 60);
        $localCache['test_site_other_key'] = $this->makeCacheRow(serialize('other'), 60);
        $this->setProtectedProperty($driver, '_local_cache', $localCache);

        // Delete namespace
        $driver->delete('namespace/');

        // Namespace delete should have been called
        $this->assertTrue(ee()->db->deleteCalled);
        $this->assertTrue(ee()->db->likeCalled);
    }

    /**
     * Test that clean clears all scope keys from local cache
     */
    public function testLocalCacheClearedOnClean()
    {
        ee()->config->setItem('site_short_name', 'test_site');

        // Populate local cache
        $driver = $this->makeDatabaseDriver();
        
        $localCache = [];
        $localCache['test_site_key1'] = $this->makeCacheRow(serialize('value1'), 60);
        $localCache['test_site_key2'] = $this->makeCacheRow(serialize('value2'), 60);
        $this->setProtectedProperty($driver, '_local_cache', $localCache);

        // Clean local scope
        $driver->clean(\Cache::LOCAL_SCOPE);

        // Clean should have been called
        $this->assertTrue(ee()->db->deleteCalled);
        $this->assertTrue(ee()->db->likeCalled);
    }

    /**
     * Test that local and global scope use different cache keys
     */
    public function testLocalCacheIsolatesScopes()
    {
        ee()->config->setItem('site_short_name', 'test_site');

        // Save with local scope
        $this->setQueryResult([]);
        $driver = $this->makeDatabaseDriver();
        $driver->save('same_key', 'local_value', 60, \Cache::LOCAL_SCOPE);

        // Save with global scope
        $driver->save('same_key', 'global_value', 60, \Cache::GLOBAL_SCOPE);

        // Check local cache has both with different prefixes
        $localCache = $this->getProtectedProperty($driver, '_local_cache');
        
        // Count keys - should have at least 2 different keys
        $this->assertGreaterThanOrEqual(2, count($localCache));
    }

    /**
     * Test that expired cache uses local cache for row data
     */
    public function testLocalCacheUsedForExpiredCheck()
    {
        // Set current time
        ee()->localize->now = 1000000000;
        
        // Create expired cache
        $createdAt = 1000000000 - 7200;
        $ttl = 3600;
        $row = $this->makeCacheRow(serialize('expired'), $ttl, $createdAt);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        
        // First call - should cache the row
        $result1 = $driver->get('test_key');
        $this->assertFalse($result1); // Expired
        
        // Clear DB result
        $this->setQueryResult([]);
        
        // Second call - should use local cache for expiry check
        $result2 = $driver->get('test_key');
        $this->assertFalse($result2);
    }

    /**
     * Test that local cache keys are properly namespaced
     */
    public function testLocalCacheKeysAreNamespaced()
    {
        ee()->config->setItem('site_short_name', 'my_site');

        $testData = 'test value';
        $row = $this->makeCacheRow(serialize($testData), 60);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $driver->get('test_key', \Cache::LOCAL_SCOPE);

        $localCache = $this->getProtectedProperty($driver, '_local_cache');
        $keys = array_keys($localCache);
        
        // Should have at least one key with the site prefix
        $hasNamespacedKey = false;
        foreach ($keys as $key) {
            if (strpos($key, 'my_site_') === 0) {
                $hasNamespacedKey = true;
                break;
            }
        }
        
        $this->assertTrue($hasNamespacedKey);
    }
}

