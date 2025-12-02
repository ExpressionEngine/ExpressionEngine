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

class CacheDatabaseSaveTest extends CacheDatabaseTestBase
{
    /**
     * Test that save inserts new cache item
     */
    public function testSaveInsertsNewCacheItem()
    {
        // No existing rows (new item)
        $this->setQueryResult([]);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->save('new_key', 'new_value', 60);

        $this->assertTrue($result);
        $this->assertTrue(ee()->db->insertCalled);
        $this->assertFalse(ee()->db->updateCalled);
    }

    /**
     * Test that save updates existing cache item
     */
    public function testSaveUpdatesExistingCacheItem()
    {
        // Existing row found
        $existingRow = (object)['cache_key' => 'existing_key'];
        $this->setQueryResult([$existingRow]);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->save('existing_key', 'updated_value', 60);

        $this->assertTrue($result);
        $this->assertTrue(ee()->db->updateCalled);
        $this->assertFalse(ee()->db->insertCalled);
    }

    /**
     * Test that save serializes data correctly
     */
    public function testSaveSerializesData()
    {
        $this->setQueryResult([]);

        $testData = ['complex' => 'data'];
        $driver = $this->makeDatabaseDriver();
        $driver->save('test_key', $testData, 60);

        $this->assertTrue(ee()->db->insertCalled);
        $this->assertArrayHasKey('data', ee()->db->lastInsertData);
        
        // Data should be serialized
        $savedData = ee()->db->lastInsertData['data'];
        $this->assertEquals($testData, unserialize($savedData));
    }

    /**
     * Test that save with local scope uses correct namespacing
     */
    public function testSaveWithLocalScope()
    {
        ee()->config->setItem('site_short_name', 'test_site');
        $this->setQueryResult([]);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->save('local_key', 'value', 60, \Cache::LOCAL_SCOPE);

        $this->assertTrue($result);
        $this->assertTrue(ee()->db->insertCalled);
        
        // Check that the cache_key includes the site prefix
        $cacheKey = ee()->db->lastInsertData['cache_key'];
        $this->assertStringStartsWith('test_site_', $cacheKey);
    }

    /**
     * Test that save with global scope uses correct namespacing
     */
    public function testSaveWithGlobalScope()
    {
        $this->setQueryResult([]);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->save('global_key', 'value', 60, \Cache::GLOBAL_SCOPE);

        $this->assertTrue($result);
        $this->assertTrue(ee()->db->insertCalled);
        
        // Check that the cache_key includes the global hash prefix
        $cacheKey = ee()->db->lastInsertData['cache_key'];
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}_global_key$/', $cacheKey);
    }

    /**
     * Test that save with custom TTL values
     */
    public function testSaveWithCustomTTL()
    {
        $ttlValues = [0, 60, 3600, 86400];

        foreach ($ttlValues as $ttl) {
            $this->resetDatabaseMocks();
            $this->setQueryResult([]);

            $driver = $this->makeDatabaseDriver();
            $driver->save("key_ttl_$ttl", 'value', $ttl);

            $this->assertEquals($ttl, ee()->db->lastInsertData['ttl'], "Failed for TTL: $ttl");
        }
    }

    /**
     * Test that save updates local cache
     */
    public function testSaveUpdatesLocalCache()
    {
        $this->setQueryResult([]);

        $driver = $this->makeDatabaseDriver();
        $driver->save('test_key', 'test_value', 60);

        // Now get should use local cache
        $localCache = $this->getProtectedProperty($driver, '_local_cache');
        $this->assertNotEmpty($localCache);
    }

    /**
     * Test that save with complex data types
     */
    public function testSaveWithComplexDataTypes()
    {
        $testCases = [
            'array' => ['nested' => ['data' => 'value']],
            'object' => (object)['prop' => 'value'],
            'mixed' => ['string' => 'text', 'number' => 42, 'bool' => true],
        ];

        foreach ($testCases as $type => $testData) {
            $this->resetDatabaseMocks();
            $this->setQueryResult([]);

            $driver = $this->makeDatabaseDriver();
            $result = $driver->save("complex_$type", $testData, 60);

            $this->assertTrue($result);
            $savedData = ee()->db->lastInsertData['data'];
            $this->assertEquals($testData, unserialize($savedData), "Failed for type: $type");
        }
    }

    /**
     * Test that save uses current timestamp
     */
    public function testSaveUsesCurrentTimestamp()
    {
        ee()->localize->now = 1234567890;
        $this->setQueryResult([]);

        $driver = $this->makeDatabaseDriver();
        $driver->save('test_key', 'value', 60);

        $this->assertEquals(1234567890, ee()->db->lastInsertData['created_at']);
    }

    /**
     * Test that save returns true on success
     */
    public function testSaveReturnsTrueOnSuccess()
    {
        $this->setQueryResult([]);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->save('test_key', 'value', 60);

        $this->assertTrue($result);
    }

    /**
     * Test that save includes all required fields for insert
     */
    public function testSaveIncludesAllRequiredFieldsForInsert()
    {
        $this->setQueryResult([]);

        $driver = $this->makeDatabaseDriver();
        $driver->save('test_key', 'test_value', 60);

        $this->assertTrue(ee()->db->insertCalled);
        $this->assertArrayHasKey('cache_key', ee()->db->lastInsertData);
        $this->assertArrayHasKey('data', ee()->db->lastInsertData);
        $this->assertArrayHasKey('ttl', ee()->db->lastInsertData);
        $this->assertArrayHasKey('created_at', ee()->db->lastInsertData);
    }

    /**
     * Test that save update includes required fields
     */
    public function testSaveUpdateIncludesRequiredFields()
    {
        // Existing row found
        $existingRow = (object)['cache_key' => 'existing_key'];
        $this->setQueryResult([$existingRow]);

        $driver = $this->makeDatabaseDriver();
        $driver->save('existing_key', 'updated_value', 120);

        $this->assertTrue(ee()->db->updateCalled);
        $this->assertArrayHasKey('data', ee()->db->lastUpdateData);
        $this->assertArrayHasKey('ttl', ee()->db->lastUpdateData);
        $this->assertArrayHasKey('created_at', ee()->db->lastUpdateData);
    }
}

