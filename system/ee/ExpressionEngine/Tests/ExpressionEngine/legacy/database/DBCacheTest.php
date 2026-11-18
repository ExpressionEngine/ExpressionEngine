<?php

require_once SYSPATH . 'ee/legacy/database/DB_cache.php';

use PHPUnit\Framework\TestCase;

class DBCacheTest extends TestCase
{
    private $cache;
    private $uri;
    private $dbCache;

    protected function setUp(): void
    {
        $this->cache = new DBCacheCacheStub();
        $this->uri = new DBCacheUriStub();

        ee()->setMock('cache', $this->cache);
        ee()->setMock('uri', $this->uri);

        $this->dbCache = new CI_DB_Cache();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testReadUsesDefaultSegmentsWhenUriSegmentsAreMissing(): void
    {
        $sql = 'SELECT * FROM exp_members';

        $result = $this->dbCache->read($sql);

        $this->assertSame('cached-result', $result);
        $this->assertSame(
            '/db_cache/default+index+' . md5($sql),
            $this->cache->lastGetKey
        );
    }

    public function testWriteUsesUriSegmentsAndZeroTtl(): void
    {
        $this->uri->segments[1] = 'news';
        $this->uri->segments[2] = 'archive';
        $sql = 'SELECT title FROM exp_channel_titles';
        $payload = (object) ['items' => [1, 2, 3]];

        $result = $this->dbCache->write($sql, $payload);

        $this->assertTrue($result);
        $this->assertSame(
            '/db_cache/news+archive+' . md5($sql),
            $this->cache->lastSaveArgs['key']
        );
        $this->assertSame($payload, $this->cache->lastSaveArgs['object']);
        $this->assertSame(0, $this->cache->lastSaveArgs['ttl']);
    }

    public function testDeleteAllClearsDbCacheNamespace(): void
    {
        $result = $this->dbCache->delete_all();

        $this->assertTrue($result);
        $this->assertSame('db_cache', $this->cache->clearedNamespace);
    }
}

class DBCacheCacheStub
{
    public $lastGetKey;
    public $lastSaveArgs = [];
    public $clearedNamespace;

    public function get($key)
    {
        $this->lastGetKey = $key;

        return 'cached-result';
    }

    public function save($key, $object, $ttl)
    {
        $this->lastSaveArgs = [
            'key' => $key,
            'object' => $object,
            'ttl' => $ttl,
        ];

        return true;
    }

    public function clear_namespace($namespace)
    {
        $this->clearedNamespace = $namespace;

        return true;
    }
}

class DBCacheUriStub
{
    public $segments = [];

    public function segment($segment)
    {
        return $this->segments[$segment] ?? false;
    }
}
