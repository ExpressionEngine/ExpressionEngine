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

class FileDriverCoreTest extends CacheTestBase
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
     * Test save/get round trip with scalar values
     */
    public function testSaveGetRoundTripScalar()
    {
        $testData = 'test string';
        $key = 'scalar_test';

        $result = $this->driver->save($key, $testData, 60);
        $this->assertTrue($result);

        $retrieved = $this->driver->get($key);
        $this->assertEquals($testData, $retrieved);
    }

    /**
     * Test save/get round trip with array values
     */
    public function testSaveGetRoundTripArray()
    {
        $testData = ['key' => 'value', 'nested' => ['array' => 'data'], 'number' => 42];
        $key = 'array_test';

        $result = $this->driver->save($key, $testData, 60);
        $this->assertTrue($result);

        $retrieved = $this->driver->get($key);
        $this->assertEquals($testData, $retrieved);
    }

    /**
     * Test save/get round trip with object values
     */
    public function testSaveGetRoundTripObject()
    {
        $testData = (object)['property' => 'value', 'number' => 123];
        $key = 'object_test';

        $result = $this->driver->save($key, $testData, 60);
        $this->assertTrue($result);

        $retrieved = $this->driver->get($key);
        $this->assertEquals($testData, $retrieved);
    }

    /**
     * Test TTL expiry
     */
    public function testTtlExpiry()
    {
        $testData = 'expires soon';
        $key = 'ttl_test';

        // Save with 1 second TTL
        $result = $this->driver->save($key, $testData, 1);
        $this->assertTrue($result);

        // Should still be retrievable immediately
        $retrieved = $this->driver->get($key);
        $this->assertEquals($testData, $retrieved);

        // Advance time by 2 seconds (past TTL)
        ee()->localize->now += 2;

        // Should now be expired
        $expired = $this->driver->get($key);
        $this->assertFalse($expired);
    }

    /**
     * Test delete specific key
     */
    public function testDeleteSpecificKey()
    {
        $key1 = 'delete_test1';
        $key2 = 'delete_test2';

        // Save two items
        $this->driver->save($key1, 'value1', 60);
        $this->driver->save($key2, 'value2', 60);

        // Verify both exist
        $this->assertEquals('value1', $this->driver->get($key1));
        $this->assertEquals('value2', $this->driver->get($key2));

        // Delete first key
        $result = $this->driver->delete($key1);
        $this->assertTrue($result);

        // First should be gone, second should remain
        $this->assertFalse($this->driver->get($key1));
        $this->assertEquals('value2', $this->driver->get($key2));
    }

    /**
     * Test delete namespaced directory
     */
    public function testDeleteNamespacedDirectory()
    {
        $namespace = '/test_namespace/';
        $key1 = $namespace . 'item1';
        $key2 = $namespace . 'item2';
        $otherKey = 'outside_namespace';

        // Save items in namespace and outside
        $this->driver->save($key1, 'ns_value1', 60);
        $this->driver->save($key2, 'ns_value2', 60);
        $this->driver->save($otherKey, 'outside_value', 60);

        // Verify all exist
        $this->assertEquals('ns_value1', $this->driver->get($key1));
        $this->assertEquals('ns_value2', $this->driver->get($key2));
        $this->assertEquals('outside_value', $this->driver->get($otherKey));

        // Delete namespace (trailing slash indicates namespace deletion)
        $result = $this->driver->delete($namespace);
        $this->assertTrue($result);

        // Namespaced items should be gone, outside item should remain
        $this->assertFalse($this->driver->get($key1));
        $this->assertFalse($this->driver->get($key2));
        $this->assertEquals('outside_value', $this->driver->get($otherKey));
    }

    /**
     * Test saving with zero TTL (infinite)
     */
    public function testZeroTtl()
    {
        $testData = 'infinite ttl';
        $key = 'zero_ttl_test';

        $result = $this->driver->save($key, $testData, 0);
        $this->assertTrue($result);

        // Advance time significantly
        ee()->localize->now += 1000000; // 1 million seconds

        // Should still be retrievable
        $retrieved = $this->driver->get($key);
        $this->assertEquals($testData, $retrieved);
    }

    /**
     * Test getting non-existent key
     */
    public function testGetNonExistentKey()
    {
        $result = $this->driver->get('nonexistent_key');
        $this->assertFalse($result);
    }

    /**
     * Test deleting non-existent key
     */
    public function testDeleteNonExistentKey()
    {
        $result = $this->driver->delete('nonexistent_key');
        $this->assertFalse($result);
    }
}
