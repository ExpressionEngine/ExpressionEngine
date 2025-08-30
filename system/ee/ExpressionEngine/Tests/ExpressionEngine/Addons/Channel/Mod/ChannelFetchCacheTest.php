<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFetchCacheTest extends ChannelTestBase
{
    public function testFetchCacheReturnsFalseWhenCacheMiss()
    {
        // Set up cache mock to return false
        $this->setMock('cache', new class {
            public function get($key){ return false; }
        });

        $result = $this->channel->fetch_cache();

        $this->assertFalse($result);
    }

    public function testFetchCacheReturnsCachedDataWhenHit()
    {
        $cachedData = 'cached sql data';

        // Set up cache mock to return cached data
        $this->setMock('cache', new class($cachedData) {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public function get($key){ return $this->data; }
        });

        $result = $this->channel->fetch_cache();

        $this->assertEquals($cachedData, $result);
    }

    public function testFetchCacheUsesCorrectCacheKey()
    {
        $expectedKey = null;

        // Set up cache mock to capture the key
        $this->setMock('cache', new class($expectedKey) {
            private $expectedKey;
            public function __construct(&$expectedKey) { $this->expectedKey = &$expectedKey; }
            public function get($key){
                $this->expectedKey = $key;
                return false;
            }
        });

        $this->channel->fetch_cache();

        // Verify the cache key format (it's an MD5 hash so we just check the prefix)
        $this->assertStringStartsWith('/sql_cache/', $expectedKey);
        $this->assertEquals(43, strlen($expectedKey)); // /sql_cache/ + 32 character MD5 hash
    }

    public function testFetchCacheWithIdentifier()
    {
        $identifier = 'test_identifier';
        $expectedKey = null;

        // Set up cache mock to capture the key
        $this->setMock('cache', new class($expectedKey) {
            private $expectedKey;
            public function __construct(&$expectedKey) { $this->expectedKey = &$expectedKey; }
            public function get($key){
                $this->expectedKey = $key;
                return false;
            }
        });

        $this->channel->fetch_cache($identifier);

        // Verify the cache key format (it's an MD5 hash so we just check the prefix)
        $this->assertStringStartsWith('/sql_cache/', $expectedKey);
        $this->assertEquals(43, strlen($expectedKey)); // /sql_cache/ + 32 character MD5 hash
    }
}
