<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelSaveCacheTest extends ChannelTestBase
{
    public function testSaveCacheReturnsTrueOnSuccess()
    {
        $sql = 'SELECT * FROM exp_channel_data';

        // Set up cache mock to return true
        $this->setMock('cache', new class {
            public function save($key, $val, $ttl = 0){ return true; }
        });

        $result = $this->channel->save_cache($sql);

        $this->assertTrue($result);
    }

    public function testSaveCacheReturnsFalseOnFailure()
    {
        $sql = 'SELECT * FROM exp_channel_data';

        // Set up cache mock to return false
        $this->setMock('cache', new class {
            public function save($key, $val, $ttl = 0){ return false; }
        });

        $result = $this->channel->save_cache($sql);

        $this->assertFalse($result);
    }

    public function testSaveCacheUsesCorrectCacheKey()
    {
        $sql = 'SELECT * FROM exp_channel_data';
        $savedKey = null;
        $savedValue = null;

        // Set up cache mock to capture the key and value
        $this->setMock('cache', new class($savedKey, $savedValue) {
            private $savedKey;
            private $savedValue;
            public function __construct(&$savedKey, &$savedValue) {
                $this->savedKey = &$savedKey;
                $this->savedValue = &$savedValue;
            }
            public function save($key, $val, $ttl = 0){
                $this->savedKey = $key;
                $this->savedValue = $val;
                return true;
            }
        });

        $this->channel->save_cache($sql);

        // Verify the cache key format (it's an MD5 hash so we just check the prefix)
        $this->assertStringStartsWith('/sql_cache/', $savedKey);
        $this->assertEquals(43, strlen($savedKey)); // /sql_cache/ + 32 character MD5 hash
        $this->assertEquals($sql, $savedValue);
    }

    public function testSaveCacheWithIdentifier()
    {
        $sql = 'SELECT * FROM exp_channel_data';
        $identifier = 'test_identifier';
        $savedKey = null;

        // Set up cache mock to capture the key
        $this->setMock('cache', new class($savedKey) {
            private $savedKey;
            public function __construct(&$savedKey) {
                $this->savedKey = &$savedKey;
            }
            public function save($key, $val, $ttl = 0){
                $this->savedKey = $key;
                return true;
            }
        });

        $this->channel->save_cache($sql, $identifier);

        // Verify the cache key format (it's an MD5 hash so we just check the prefix)
        $this->assertStringStartsWith('/sql_cache/', $savedKey);
        $this->assertEquals(43, strlen($savedKey)); // /sql_cache/ + 32 character MD5 hash
    }

    public function testSaveCacheUsesZeroTTL()
    {
        $sql = 'SELECT * FROM exp_channel_data';
        $usedTTL = null;

        // Set up cache mock to capture the TTL
        $this->setMock('cache', new class($usedTTL) {
            private $usedTTL;
            public function __construct(&$usedTTL) {
                $this->usedTTL = &$usedTTL;
            }
            public function save($key, $val, $ttl = 0){
                $this->usedTTL = $ttl;
                return true;
            }
        });

        $this->channel->save_cache($sql);

        // Verify TTL is 0 (no expiration)
        $this->assertEquals(0, $usedTTL);
    }
}
