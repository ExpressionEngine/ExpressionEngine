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

class CacheDatabaseCacheInfoTest extends CacheDatabaseTestBase
{
    /**
     * Test that cache_info returns item count and size
     */
    public function testCacheInfoReturnsItemCountAndSize()
    {
        $row = (object) [
            'total_items' => 5,
            'total_size' => 1024
        ];
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $info = $driver->cache_info();

        $this->assertIsArray($info);
        $this->assertEquals(5, $info['total_items']);
        $this->assertEquals(1024, $info['total_size']);
    }

    /**
     * Test that cache_info returns array with correct keys
     */
    public function testCacheInfoReturnsArrayWithCorrectKeys()
    {
        $row = (object) [
            'total_items' => 10,
            'total_size' => 2048
        ];
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $info = $driver->cache_info();

        $this->assertArrayHasKey('total_items', $info);
        $this->assertArrayHasKey('total_size', $info);
    }

    /**
     * Test that cache_info returns false when no rows
     */
    public function testCacheInfoReturnsFalseWhenNoRows()
    {
        $this->setQueryResult([]);

        $driver = $this->makeDatabaseDriver();
        $info = $driver->cache_info();

        $this->assertFalse($info);
    }

    /**
     * Test that cache_info calculates total size correctly
     */
    public function testCacheInfoCalculatesTotalSize()
    {
        $row = (object) [
            'total_items' => 3,
            'total_size' => 512
        ];
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $info = $driver->cache_info();

        $this->assertEquals(512, $info['total_size']);
    }

    /**
     * Test that cache_info counts all items
     */
    public function testCacheInfoCountsAllItems()
    {
        $row = (object) [
            'total_items' => 100,
            'total_size' => 10240
        ];
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $info = $driver->cache_info();

        $this->assertEquals(100, $info['total_items']);
    }

    /**
     * Test that cache_info handles zero items
     */
    public function testCacheInfoHandlesZeroItems()
    {
        $row = (object) [
            'total_items' => 0,
            'total_size' => 0
        ];
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $info = $driver->cache_info();

        $this->assertIsArray($info);
        $this->assertEquals(0, $info['total_items']);
        $this->assertEquals(0, $info['total_size']);
    }

    /**
     * Test that cache_info executes the correct query
     */
    public function testCacheInfoExecutesCorrectQuery()
    {
        $row = (object) [
            'total_items' => 1,
            'total_size' => 100
        ];
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $driver->cache_info();

        // Verify the query builder methods were called
        $this->assertTrue(ee()->db->selectCalled);
        $this->assertTrue(ee()->db->fromCalled);
        $this->assertTrue(ee()->db->getCalled);
    }
}

