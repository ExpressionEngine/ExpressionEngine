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

class RedisDriverTest extends CacheTestBase
{
    /**
     * @var \EE_Cache_redis
     */
    private $driver;

    /**
     * Mock Redis instance
     * @var RedisStub
     */
    private $redisStub;

    public function setUp(): void
    {
        parent::setUp();

        // Create Redis stub
        $this->redisStub = new RedisStub();

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
        $this->driver = new \EE_Cache_redis();
        $this->setProtectedProperty($this->driver, '_redis', $this->redisStub);

        // Decorate the driver with the mock cache as parent
        $this->driver->decorate($cacheMock);
    }

    /**
     * Test save/get round trip
     */
    public function testSaveGetRoundTrip()
    {
        $testData = 'redis test data';
        $key = 'redis_test_key';

        $result = $this->driver->save($key, $testData, 60);
        $this->assertTrue($result);

        $retrieved = $this->driver->get($key);
        $this->assertEquals($testData, $retrieved);
    }

    /**
     * Test save/get with complex data
     */
    public function testSaveGetComplexData()
    {
        $testData = ['array' => 'data', 'number' => 42, 'nested' => ['deep' => 'value']];
        $key = 'complex_redis_key';

        $result = $this->driver->save($key, $testData, 60);
        $this->assertTrue($result);

        $retrieved = $this->driver->get($key);
        $this->assertEquals($testData, $retrieved);
    }

    /**
     * Test save with TTL
     */
    public function testSaveWithTtl()
    {
        $testData = 'ttl test data';
        $key = 'ttl_redis_key';
        $ttl = 300;

        $result = $this->driver->save($key, $testData, $ttl);
        $this->assertTrue($result);

        // Check that setex was called with correct TTL
        $this->assertEquals($ttl, $this->redisStub->lastSetexTtl);
        $this->assertEquals('https://example.com/:ttl_redis_key', $this->redisStub->lastSetexKey);
    }

    /**
     * Test save with zero TTL (infinite)
     */
    public function testSaveWithZeroTtl()
    {
        $testData = 'infinite ttl data';
        $key = 'infinite_redis_key';

        $result = $this->driver->save($key, $testData, 0);
        $this->assertTrue($result);

        // Should use set instead of setex for infinite TTL
        $this->assertEquals('https://example.com/:infinite_redis_key', $this->redisStub->lastSetKey);
    }

    /**
     * Test delete specific key
     */
    public function testDeleteSpecificKey()
    {
        // Set up some data first
        $this->driver->save('delete_test', 'data', 60);

        $result = $this->driver->delete('delete_test');
        $this->assertTrue($result);

        // Verify it was deleted
        $this->assertFalse($this->driver->get('delete_test'));
    }

    /**
     * Test namespaced delete
     */
    public function testNamespacedDelete()
    {
        $result = $this->driver->delete('/namespace/');
        $this->assertTrue($result);

        // Check that keys was called with wildcard pattern
        $this->assertStringEndsWith('*', $this->redisStub->lastKeysPattern);
    }

    /**
     * Test clean by scope
     */
    public function testCleanByScope()
    {
        $result = $this->driver->clean(\Cache::LOCAL_SCOPE);
        $this->assertTrue($result);

        // Check that keys was called for cleanup
        $this->assertNotNull($this->redisStub->lastKeysPattern);
    }

    /**
     * Test get_metadata
     */
    public function testGetMetadata()
    {
        $testData = 'metadata test';
        $this->driver->save('metadata_test', $testData, 300);

        $metadata = $this->driver->get_metadata('metadata_test');

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('expire', $metadata);
        $this->assertArrayHasKey('mtime', $metadata);
        $this->assertArrayHasKey('data', $metadata);
        $this->assertEquals($testData, $metadata['data']);
    }

    /**
     * Test get_metadata with infinite TTL (ttl = -1)
     */
    public function testGetMetadataInfiniteTtl()
    {
        $testData = 'infinite metadata';
        $this->driver->save('infinite_metadata', $testData, 0); // 0 TTL = infinite

        // Mock ttl to return -1 (infinite)
        $this->redisStub->ttlReturnValue = -1;

        $metadata = $this->driver->get_metadata('infinite_metadata');

        $this->assertIsArray($metadata);
        // When ttl is -1, expire should equal mtime
        $this->assertEquals($metadata['mtime'], $metadata['expire']);
    }

    /**
     * Test cache_info
     */
    public function testCacheInfo()
    {
        $info = $this->driver->cache_info();

        $this->assertIsArray($info);
        $this->assertEquals(['mock' => 'info'], $info);
    }

    /**
     * Test global scope key prefixing
     */
    public function testGlobalScopePrefixing()
    {
        $this->driver->save('global_key', 'global_data', 60, \Cache::GLOBAL_SCOPE);

        // Check that the key was prefixed correctly for global scope
        $expectedKey = md5('127.0.0.1' . APPPATH) . ':global_key';
        $this->assertEquals($expectedKey, $this->redisStub->lastSetexKey);
    }

    public function testSetupRedisDoesNotPassContextToLegacyConnect()
    {
        $redis = new RedisSetupLegacyConnectStub();
        $driver = $this->makeRedisSetupDriver($redis);

        ee()->config->setItem('redis', [
            'host' => 'redis.example.com',
            'port' => 6380,
            'timeout' => 1.5,
            'scheme' => 'tls',
            'context' => [
                'stream' => [
                    'verify_peer' => false,
                    'cafile' => '/etc/ssl/redis-ca.pem',
                ],
            ],
        ]);

        $this->assertTrue($driver->setupRedisForTest());
        $this->assertCount(3, $redis->connectArgs);
        $this->assertSame('tls://redis.example.com', $redis->connectArgs[0]);
        $this->assertSame(6380, $redis->connectArgs[1]);
        $this->assertSame(1.5, $redis->connectArgs[2]);
    }

    public function testSetupRedisMergesTlsContextOptions()
    {
        $redis = new RedisSetupContextConnectStub();
        $driver = $this->makeRedisSetupDriver($redis);

        ee()->config->setItem('redis', [
            'host' => 'redis.example.com',
            'port' => 6380,
            'timeout' => 1.5,
            'scheme' => 'tls',
            'context' => [
                'stream' => [
                    'verify_peer' => false,
                    'peer_name' => 'cache.internal',
                    'cafile' => '/etc/ssl/redis-ca.pem',
                ],
            ],
        ]);

        $this->assertTrue($driver->setupRedisForTest());
        $this->assertCount(7, $redis->connectArgs);
        $this->assertSame('tls://redis.example.com', $redis->connectArgs[0]);

        $context = $redis->connectArgs[6];
        $this->assertSame(false, $context['stream']['verify_peer']);
        $this->assertSame(true, $context['stream']['verify_peer_name']);
        $this->assertSame('cache.internal', $context['stream']['peer_name']);
        $this->assertSame('/etc/ssl/redis-ca.pem', $context['stream']['cafile']);
    }

    public function testSetupRedisAuthenticatesWithAclCredentials()
    {
        $redis = new RedisSetupLegacyConnectStub();
        $driver = $this->makeRedisSetupDriver($redis);

        ee()->config->setItem('redis', [
            'username' => 'default',
            'password' => 'secret',
        ]);

        $this->assertTrue($driver->setupRedisForTest());
        $this->assertSame(['default', 'secret'], $redis->authArg);
    }

    private function makeRedisSetupDriver($redis)
    {
        return new class($redis) extends \EE_Cache_redis {
            private $redis;

            public function __construct($redis)
            {
                $this->redis = $redis;
            }

            public function setupRedisForTest()
            {
                return $this->_setup_redis();
            }

            protected function _new_redis()
            {
                return $this->redis;
            }
        };
    }
}

class RedisSetupLegacyConnectStub
{
    public $connectArgs = [];
    public $authArg;
    public $selectedDatabase;

    public function connect($host, $port = 6379, $timeout = 0, $reserved = null, $retry_interval = 0, $read_timeout = 0)
    {
        $this->connectArgs = func_get_args();

        return true;
    }

    public function auth($auth)
    {
        $this->authArg = $auth;

        return true;
    }

    public function select($database)
    {
        $this->selectedDatabase = $database;

        return true;
    }

    public function close()
    {
        return true;
    }
}

class RedisSetupContextConnectStub
{
    public $connectArgs = [];
    public $authArg;
    public $selectedDatabase;

    public function connect($host, $port = 6379, $timeout = 0, $reserved = null, $retry_interval = 0, $read_timeout = 0, $context = [])
    {
        $this->connectArgs = func_get_args();

        return true;
    }

    public function auth($auth)
    {
        $this->authArg = $auth;

        return true;
    }

    public function select($database)
    {
        $this->selectedDatabase = $database;

        return true;
    }

    public function close()
    {
        return true;
    }
}

/**
 * Stub Redis class for testing
 */
class RedisStub
{
    public $data = [];
    public $lastSetKey;
    public $lastSetexKey;
    public $lastSetexTtl;
    public $lastKeysPattern;
    public $ttlReturnValue = 300;

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : false;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
        $this->lastSetKey = $key;
        return true;
    }

    public function setex($key, $ttl, $value)
    {
        $this->data[$key] = $value;
        $this->lastSetexKey = $key;
        $this->lastSetexTtl = $ttl;
        return true;
    }

    public function del($key)
    {
        if (is_array($key)) {
            // For arrays, return the count of keys (simulating successful deletion)
            return count($key);
        } else {
            if (isset($this->data[$key])) {
                unset($this->data[$key]);
                return 1;
            }
            return 0;
        }
    }

    public function delete($key)
    {
        return $this->del($key);
    }

    public function keys($pattern)
    {
        $this->lastKeysPattern = $pattern;
        // Return one mock key for pattern matching
        return ['mock_key_1'];
    }

    public function ttl($key)
    {
        return $this->ttlReturnValue;
    }

    public function info()
    {
        return ['mock' => 'info'];
    }

    public function close()
    {
        return true;
    }
}
