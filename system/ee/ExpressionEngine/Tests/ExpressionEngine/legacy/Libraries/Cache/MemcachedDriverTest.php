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

class MemcachedDriverTest extends CacheTestBase
{
    /**
     * @var \EE_Cache_memcached
     */
    private $driver;

    /**
     * Mock Memcached instance
     * @var Memcached
     */
    private $memcachedStub;

    /**
     * Mock Memcache instance
     * @var Memcache
     */
    private $memcacheStub;

    public function setUp(): void
    {
        parent::setUp();

        // Use our test class in global namespace
        $this->memcachedStub = new \Memcached();

        // Create a mock Cache parent with required methods
        $cacheMock = $this->createMock(\Cache::class);
        $cacheMock->method('unique_key')
            ->willReturnCallback(function($key, $scope = \Cache::LOCAL_SCOPE) {
                $prefix = ($scope == \Cache::GLOBAL_SCOPE)
                    ? md5('127.0.0.1' . APPPATH) . ':'
                    : 'https://example.com/:';
                return $prefix . $key;
            });

        // Create driver and inject stub directly
        $this->driver = new \EE_Cache_memcached();
        $this->setProtectedProperty($this->driver, '_memcached', $this->memcachedStub);

        // Decorate the driver with the mock cache as parent
        $this->driver->decorate($cacheMock);
    }

    /**
     * Test save/get round trip with Memcached
     */
    public function testSaveGetRoundTripMemcached()
    {
        $testData = 'memcached test data';
        $key = 'memcached_test_key';

        $result = $this->driver->save($key, $testData, 60);
        $this->assertTrue($result);

        $retrieved = $this->driver->get($key);
        $this->assertEquals($testData, $retrieved);
    }

    /**
     * Test TTL capping at 30 days (2592000 seconds) for Memcached
     */
    public function testTtlCappedAt30DaysMemcached()
    {
        $testData = 'ttl capped data';
        $key = 'ttl_capped_key';

        // Save with TTL > 30 days
        $result = $this->driver->save($key, $testData, 4000000); // 46+ days
        $this->assertTrue($result);

        // Find the data key (not the namespace key)
        $storedKeys = array_keys($this->memcachedStub->data);
        $dataKey = null;
        foreach ($storedKeys as $key) {
            if (strpos($key, 'ttl_capped_key') !== false) {
                $dataKey = $key;
                break;
            }
        }
        $this->assertNotNull($dataKey, 'Data key should be found');

        // Verify the data was stored with capped TTL
        $storedData = $this->memcachedStub->data[$dataKey];
        $this->assertEquals([$testData, ee()->localize->now, 2592000], $storedData);
    }

    /**
     * Test delete specific key with Memcached
     */
    public function testDeleteSpecificKeyMemcached()
    {
        $testData = 'delete test data';
        $key = 'delete_test_key';

        // Save and verify
        $this->driver->save($key, $testData, 60);
        $this->assertEquals($testData, $this->driver->get($key));

        // Delete
        $result = $this->driver->delete($key);
        $this->assertTrue($result);

        // Verify it's gone
        $this->assertFalse($this->driver->get($key));
    }

    /**
     * Test clean (clear cache) with Memcached
     */
    public function testCleanClearsScopeByNamespaceMemcached()
    {
        // Save multiple items
        $this->driver->save('clean_test1', 'value1', 60);
        $this->driver->save('clean_test2', 'value2', 60);

        // Verify they exist
        $this->assertEquals('value1', $this->driver->get('clean_test1'));
        $this->assertEquals('value2', $this->driver->get('clean_test2'));

        // Clean
        $result = $this->driver->clean();
        $this->assertTrue($result);

        // Verify they're gone
        $this->assertFalse($this->driver->get('clean_test1'));
        $this->assertFalse($this->driver->get('clean_test2'));
    }

    /**
     * Test get_metadata returns expected array with Memcached
     */
    public function testGetMetadataReturnsExpectedArrayMemcached()
    {
        $testData = 'metadata test data';
        $key = 'metadata_test_key';
        $ttl = 300;

        // Save with specific TTL
        $this->driver->save($key, $testData, $ttl);

        // Get metadata
        $metadata = $this->driver->get_metadata($key);

        // Should return array with expire, mtime, data
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('expire', $metadata);
        $this->assertArrayHasKey('mtime', $metadata);
        $this->assertArrayHasKey('data', $metadata);
        $this->assertEquals($testData, $metadata['data']);
        $this->assertEquals(ee()->localize->now + $ttl, $metadata['expire']);
        $this->assertEquals(ee()->localize->now, $metadata['mtime']);
    }

    /**
     * Test cache_info returns getStats for Memcached
     */
    public function testCacheInfoUsesGetStatsForMemcached()
    {
        $info = $this->driver->cache_info();

        // Should return the stats array from getStats()
        $this->assertIsArray($info);
        $this->assertArrayHasKey('localhost:11211', $info);
        $this->assertArrayHasKey('time', $info['localhost:11211']);
        $this->assertArrayHasKey('uptime', $info['localhost:11211']);
        $this->assertArrayHasKey('version', $info['localhost:11211']);
    }

    /**
     * Set up driver with Memcache stub for Memcache-specific tests
     */
    public function setUpMemcache()
    {
        parent::setUp();

        // Use our test class in global namespace
        $this->memcacheStub = new \Memcache();

        // Create a mock Cache parent with required methods
        $cacheMock = $this->createMock(\Cache::class);
        $cacheMock->method('unique_key')
            ->willReturnCallback(function($key, $scope = \Cache::LOCAL_SCOPE) {
                $prefix = ($scope == \Cache::GLOBAL_SCOPE)
                    ? md5('127.0.0.1' . APPPATH) . ':'
                    : 'https://example.com/:';
                return $prefix . $key;
            });

        // Create driver and inject stub
        $this->driver = new \EE_Cache_memcached();
        $this->setProtectedProperty($this->driver, '_memcached', $this->memcacheStub);

        // Decorate the driver with the mock cache as parent
        $this->driver->decorate($cacheMock);
    }

    /**
     * Test save/get round trip with Memcache
     */
    public function testSaveGetRoundTripMemcache()
    {
        $this->setUpMemcache();

        $testData = 'memcache test data';
        $key = 'memcache_test_key';

        $result = $this->driver->save($key, $testData, 60);
        $this->assertTrue($result);

        $retrieved = $this->driver->get($key);
        $this->assertEquals($testData, $retrieved);
    }

    /**
     * Test delete specific key with Memcache
     */
    public function testDeleteSpecificKeyMemcache()
    {
        $this->setUpMemcache();

        $testData = 'delete test data';
        $key = 'delete_test_key';

        // Save and verify
        $this->driver->save($key, $testData, 60);
        $this->assertEquals($testData, $this->driver->get($key));

        // Delete
        $result = $this->driver->delete($key);
        $this->assertTrue($result);

        // Verify it's gone
        $this->assertFalse($this->driver->get($key));
    }

    /**
     * Test get_metadata returns expected array with Memcache
     */
    public function testGetMetadataReturnsExpectedArrayMemcache()
    {
        $this->setUpMemcache();

        $testData = 'metadata test data';
        $key = 'metadata_test_key';
        $ttl = 300;

        // Save with specific TTL
        $this->driver->save($key, $testData, $ttl);

        // Get metadata
        $metadata = $this->driver->get_metadata($key);

        // Should return array with expire, mtime, data
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('expire', $metadata);
        $this->assertArrayHasKey('mtime', $metadata);
        $this->assertArrayHasKey('data', $metadata);
        $this->assertEquals($testData, $metadata['data']);
        $this->assertEquals(ee()->localize->now + $ttl, $metadata['expire']);
        $this->assertEquals(ee()->localize->now, $metadata['mtime']);
    }

    /**
     * Test cache_info returns getExtendedStats for Memcache
     */
    public function testCacheInfoUsesGetExtendedStatsForMemcache()
    {
        $this->setUpMemcache();

        $info = $this->driver->cache_info();

        // Should return the stats array from getExtendedStats()
        $this->assertIsArray($info);
        $this->assertArrayHasKey('localhost:11211', $info);
        $this->assertArrayHasKey('time', $info['localhost:11211']);
        $this->assertArrayHasKey('uptime', $info['localhost:11211']);
        $this->assertArrayHasKey('version', $info['localhost:11211']);
    }

    /**
     * Test namespaced key construction for local scope
     */
    public function testNamespacedKeyLocalScopeAndNestedNamespaces()
    {
        // Save a key with namespace
        $result = $this->driver->save('/page/contact', 'page data', 60);
        $this->assertTrue($result);

        // Check that the key was stored with proper namespacing
        $storedKeys = array_keys($this->memcachedStub->data);
        $this->assertGreaterThan(1, count($storedKeys)); // Should have namespace keys too

        // Verify we can retrieve the data
        $retrieved = $this->driver->get('/page/contact');
        $this->assertEquals('page data', $retrieved);
    }

    /**
     * Test global scope keys use global prefix
     */
    public function testNamespacedKeyGlobalScope()
    {
        // Save with global scope
        $result = $this->driver->save('global_key', 'global data', 60, \Cache::GLOBAL_SCOPE);
        $this->assertTrue($result);

        // Check that global prefix was used
        $storedKeys = array_keys($this->memcachedStub->data);
        $globalPrefix = md5('127.0.0.1' . APPPATH) . ':';
        $hasGlobalKey = false;
        foreach ($storedKeys as $key) {
            if (strpos($key, $globalPrefix) === 0) {
                $hasGlobalKey = true;
                break;
            }
        }
        $this->assertTrue($hasGlobalKey, 'Should have a key with global prefix');

        // Verify we can retrieve with global scope
        $retrieved = $this->driver->get('global_key', \Cache::GLOBAL_SCOPE);
        $this->assertEquals('global data', $retrieved);
    }

    /**
     * Test deleting a namespace key triggers namespace renewal
     */
    public function testDeleteNamespaceStringTriggersCreateNewNamespace()
    {
        // Save keys in a namespace
        $this->driver->save('/test/namespace/key1', 'data1', 60);
        $this->driver->save('/test/namespace/key2', 'data2', 60);

        // Verify they exist
        $this->assertEquals('data1', $this->driver->get('/test/namespace/key1'));
        $this->assertEquals('data2', $this->driver->get('/test/namespace/key2'));

        // Delete the namespace (key ending with /)
        $result = $this->driver->delete('/test/namespace/');
        $this->assertTrue($result);

        // Keys should no longer be retrievable
        $this->assertFalse($this->driver->get('/test/namespace/key1'));
        $this->assertFalse($this->driver->get('/test/namespace/key2'));
    }

    /**
     * Test clean() creates a new root namespace for the current scope
     */
    public function testCleanCreatesNewRootNamespace()
    {
        // Save some data
        $this->driver->save('clean_test1', 'value1', 60);
        $this->driver->save('clean_test2', 'value2', 60);

        // Verify they exist
        $this->assertEquals('value1', $this->driver->get('clean_test1'));
        $this->assertEquals('value2', $this->driver->get('clean_test2'));

        // Clean
        $result = $this->driver->clean();
        $this->assertTrue($result);

        // Verify they're gone
        $this->assertFalse($this->driver->get('clean_test1'));
        $this->assertFalse($this->driver->get('clean_test2'));
    }

    /**
     * Test get() returns false for missing or malformed stored values
     */
    public function testGetReturnsFalseOnMissingOrMalformedStoredValue()
    {
        // Test missing key
        $result = $this->driver->get('nonexistent_key');
        $this->assertFalse($result);

        // Test malformed stored value (not an array)
        $key = 'malformed_key';
        $this->memcachedStub->data['https://example.com/:' . $key] = 'not_an_array';
        $result = $this->driver->get($key);
        $this->assertFalse($result);

        // Test stored value that is an array but doesn't have 3 elements
        $this->memcachedStub->data['https://example.com/:' . $key] = ['only_two'];
        $result = $this->driver->get($key);
        $this->assertFalse($result);
    }

    /**
     * Test save() returns false when client class is unknown
     */
    public function testSaveReturnsFalseWhenClientClassUnknown()
    {
        // Create a mock with unknown class name
        $unknownStub = $this->getMockBuilder('stdClass')
            ->setMockClassName('UnknownCache')
            ->setMethods(['set'])
            ->getMock();

        $unknownStub->method('set')->willReturn(true);

        // Inject the unknown client
        $this->setProtectedProperty($this->driver, '_memcached', $unknownStub);

        // Save should return false for unknown client class
        $result = $this->driver->save('test_key', 'test_data', 60);
        $this->assertFalse($result);
    }
}

// Mock classes for testing cache drivers

class MemcachedMock
{
    public $data = [];

    public function addServer($host, $port, $weight = 0)
    {
        // No-op for testing
    }

    public function set($key, $value, $ttl = 0)
    {
        $this->data[$key] = $value;
        return true;
    }

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : false;
    }

    public function delete($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
            return true;
        }
        return false;
    }

    public function getStats()
    {
        return [
            'localhost:11211' => [
                'time' => time(),
                'uptime' => 3600,
                'version' => '1.6.9'
            ]
        ];
    }
}

class MemcacheMock
{
    public $data = [];

    public function addServer($host, $port, $persistent = true, $weight = 1)
    {
        // No-op for testing
    }

    public function set($key, $value, $flags = 0, $ttl = 0)
    {
        $this->data[$key] = $value;
        return true;
    }

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : false;
    }

    public function delete($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
            return true;
        }
        return false;
    }

    public function getExtendedStats()
    {
        return [
            'localhost:11211' => [
                'time' => time(),
                'uptime' => 3600,
                'version' => '3.0.8'
            ]
        ];
    }
}

// memcached_stubs.php is loaded by CacheTestBase

// EOF
