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

class CacheDatabaseGetMetadataTest extends CacheDatabaseTestBase
{
    /**
     * Test that get_metadata returns correct structure
     */
    public function testGetMetadataReturnsCorrectStructure()
    {
        $testData = ['key' => 'value'];
        $row = $this->makeCacheRow(serialize($testData), 300, 1000000000);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $metadata = $driver->get_metadata('test_key');

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('expire', $metadata);
        $this->assertArrayHasKey('mtime', $metadata);
        $this->assertArrayHasKey('data', $metadata);
    }

    /**
     * Test that get_metadata retrieves metadata for valid key
     */
    public function testGetMetadataWithValidKey()
    {
        $testData = ['test' => 'data'];
        $row = $this->makeCacheRow(serialize($testData), 60, 1000000000);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $metadata = $driver->get_metadata('valid_key');

        $this->assertIsArray($metadata);
        $this->assertEquals($testData, $metadata['data']);
    }

    /**
     * Test that get_metadata returns false for missing key
     */
    public function testGetMetadataReturnsFalseForMissingKey()
    {
        $this->setQueryResult([]);

        $driver = $this->makeDatabaseDriver();
        $metadata = $driver->get_metadata('missing_key');

        $this->assertFalse($metadata);
    }

    /**
     * Test that get_metadata with local scope uses correct namespacing
     */
    public function testGetMetadataWithLocalScope()
    {
        ee()->config->setItem('site_short_name', 'test_site');

        $testData = 'local data';
        $row = $this->makeCacheRow(serialize($testData), 60);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $metadata = $driver->get_metadata('local_key', \Cache::LOCAL_SCOPE);

        $this->assertIsArray($metadata);
        $this->assertEquals($testData, $metadata['data']);
        
        // Verify where clause was called with namespaced key
        $this->assertTrue(ee()->db->whereCalled);
        $this->assertStringContainsString('test_site_local_key', ee()->db->lastWhereValue);
    }

    /**
     * Test that get_metadata with global scope uses correct namespacing
     */
    public function testGetMetadataWithGlobalScope()
    {
        $testData = 'global data';
        $row = $this->makeCacheRow(serialize($testData), 60);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $metadata = $driver->get_metadata('global_key', \Cache::GLOBAL_SCOPE);

        $this->assertIsArray($metadata);
        $this->assertEquals($testData, $metadata['data']);
        
        // Verify where clause was called
        $this->assertTrue(ee()->db->whereCalled);
        // Global key should have MD5 hash prefix
        $this->assertMatchesRegularExpression('/[a-f0-9]{32}_global_key/', ee()->db->lastWhereValue);
    }

    /**
     * Test that get_metadata calculates expire time correctly
     */
    public function testGetMetadataCalculatesExpireTime()
    {
        $createdAt = 1000000000;
        $ttl = 300;
        $row = $this->makeCacheRow(serialize('test'), $ttl, $createdAt);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $metadata = $driver->get_metadata('test_key');

        $expectedExpire = $createdAt + $ttl;
        $this->assertEquals($expectedExpire, $metadata['expire']);
        $this->assertEquals($createdAt, $metadata['mtime']);
    }

    /**
     * Test that get_metadata includes deserialized data
     */
    public function testGetMetadataIncludesDeserializedData()
    {
        $testData = [
            'string' => 'value',
            'number' => 42,
            'array' => ['nested' => 'data']
        ];
        $row = $this->makeCacheRow(serialize($testData), 60);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $metadata = $driver->get_metadata('complex_key');

        $this->assertEquals($testData, $metadata['data']);
    }

    /**
     * Test that get_metadata handles zero TTL
     */
    public function testGetMetadataHandlesZeroTTL()
    {
        $createdAt = 1000000000;
        $row = $this->makeCacheRow(serialize('test'), 0, $createdAt);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $metadata = $driver->get_metadata('zero_ttl_key');

        $this->assertEquals($createdAt, $metadata['expire']);
        $this->assertEquals($createdAt, $metadata['mtime']);
    }

    /**
     * Test that get_metadata executes correct query
     */
    public function testGetMetadataExecutesCorrectQuery()
    {
        $row = $this->makeCacheRow(serialize('test'), 60);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $driver->get_metadata('test_key');

        $this->assertTrue(ee()->db->selectCalled);
        $this->assertTrue(ee()->db->fromCalled);
        $this->assertTrue(ee()->db->whereCalled);
        $this->assertTrue(ee()->db->getCalled);
    }
}

