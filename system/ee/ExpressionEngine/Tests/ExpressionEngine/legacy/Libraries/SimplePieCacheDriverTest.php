<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

require_once __DIR__ . '/../../../eeObjectMock.php';

use PHPUnit\Framework\TestCase;

class SimplePieCacheDriverCacheMock
{
    public $saveCalls = [];
    public $getCalls = [];
    public $metadataCalls = [];
    public $deleteCalls = [];

    public $saveReturn = true;
    public $getReturn = false;
    public $metadataReturn = false;
    public $deleteReturn = true;

    public function save($key, $data, $ttl, $scope)
    {
        $this->saveCalls[] = [$key, $data, $ttl, $scope];
        return $this->saveReturn;
    }

    public function get($key, $scope)
    {
        $this->getCalls[] = [$key, $scope];
        return $this->getReturn;
    }

    public function get_metadata($key, $scope)
    {
        $this->metadataCalls[] = [$key, $scope];
        return $this->metadataReturn;
    }

    public function delete($key, $scope)
    {
        $this->deleteCalls[] = [$key, $scope];
        return $this->deleteReturn;
    }
}

class SimplePieCacheDriverSimplePieStub
{
    public $data;
}

class SimplePieCacheDriverCacheConstantStub
{
    const GLOBAL_SCOPE = 'global';
}

class SimplePieCacheDriverTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public static function setUpBeforeClass(): void
    {
        if (!class_exists('SimplePie_Cache_Base', false)) {
            require_once SYSPATH . 'ee/legacy/libraries/simplepie/SimplePie/Cache/Base.php';
        }

        if (!class_exists('Cache', false)) {
            class_alias(SimplePieCacheDriverCacheConstantStub::class, 'Cache');
        }

        if (!class_exists('SimplePie', false)) {
            class_alias(SimplePieCacheDriverSimplePieStub::class, 'SimplePie');
        }

        require_once SYSPATH . 'ee/legacy/libraries/SimplePie_cache_driver.php';
    }

    public function testConstructorPrefixesNameWhenNamespaceExists()
    {
        $driver = new \EE_SimplePie_Cache_Driver('file:rss', 'feed_key', 'spc');

        $this->assertSame('rss/feed_key', $this->getProtectedProperty($driver, 'name'));
    }

    public function testConstructorLeavesNameUnprefixedWhenNamespaceMissing()
    {
        $driver = new \EE_SimplePie_Cache_Driver('file:', 'feed_key', 'spc');

        $this->assertSame('feed_key', $this->getProtectedProperty($driver, 'name'));
    }

    public function testSaveDelegatesArrayDataToCache()
    {
        $cache = new SimplePieCacheDriverCacheMock();
        ee()->setMock('cache', $cache);

        $driver = new \EE_SimplePie_Cache_Driver('file:rss', 'feed_key', 'spc');
        $result = $driver->save(['title' => 'Feed']);

        $this->assertTrue($result);
        $this->assertSame(
            [['/rss_parser/rss/feed_key', ['title' => 'Feed'], 0, \Cache::GLOBAL_SCOPE]],
            $cache->saveCalls
        );
    }

    public function testSaveExtractsDataFromSimplePieObject()
    {
        $cache = new SimplePieCacheDriverCacheMock();
        ee()->setMock('cache', $cache);

        $feed = new \SimplePie();
        $feed->data = ['items' => [1, 2]];

        $driver = new \EE_SimplePie_Cache_Driver('file:rss', 'feed_key', 'spc');
        $driver->save($feed);

        $this->assertSame(
            [['/rss_parser/rss/feed_key', ['items' => [1, 2]], 0, \Cache::GLOBAL_SCOPE]],
            $cache->saveCalls
        );
    }

    public function testLoadReadsValueFromCache()
    {
        $cache = new SimplePieCacheDriverCacheMock();
        $cache->getReturn = ['cached' => true];
        ee()->setMock('cache', $cache);

        $driver = new \EE_SimplePie_Cache_Driver('file:rss', 'feed_key', 'spc');
        $result = $driver->load();

        $this->assertSame(['cached' => true], $result);
        $this->assertSame(
            [['/rss_parser/rss/feed_key', \Cache::GLOBAL_SCOPE]],
            $cache->getCalls
        );
    }

    public function testMtimeReturnsTimestampWhenMetadataIsArray()
    {
        $cache = new SimplePieCacheDriverCacheMock();
        $cache->metadataReturn = ['mtime' => 1700000000];
        ee()->setMock('cache', $cache);

        $driver = new \EE_SimplePie_Cache_Driver('file:rss', 'feed_key', 'spc');

        $this->assertSame(1700000000, $driver->mtime());
        $this->assertSame(
            [['/rss_parser/rss/feed_key', \Cache::GLOBAL_SCOPE]],
            $cache->metadataCalls
        );
    }

    public function testMtimeReturnsFalseWhenMetadataIsNotArray()
    {
        $cache = new SimplePieCacheDriverCacheMock();
        $cache->metadataReturn = false;
        ee()->setMock('cache', $cache);

        $driver = new \EE_SimplePie_Cache_Driver('file:rss', 'feed_key', 'spc');

        $this->assertFalse($driver->mtime());
    }

    public function testTouchSavesLoadedDataWhenCacheHasValue()
    {
        $cache = new SimplePieCacheDriverCacheMock();
        $cache->getReturn = ['data' => 'payload'];
        $cache->saveReturn = true;
        ee()->setMock('cache', $cache);

        $driver = new \EE_SimplePie_Cache_Driver('file:rss', 'feed_key', 'spc');

        $this->assertTrue($driver->touch());
        $this->assertCount(1, $cache->saveCalls);
        $this->assertSame(
            ['/rss_parser/rss/feed_key', ['data' => 'payload'], 0, \Cache::GLOBAL_SCOPE],
            $cache->saveCalls[0]
        );
    }

    public function testTouchReturnsFalseWhenCacheMissOccurs()
    {
        $cache = new SimplePieCacheDriverCacheMock();
        $cache->getReturn = false;
        ee()->setMock('cache', $cache);

        $driver = new \EE_SimplePie_Cache_Driver('file:rss', 'feed_key', 'spc');

        $this->assertFalse($driver->touch());
        $this->assertSame([], $cache->saveCalls);
    }

    public function testUnlinkDelegatesDeleteToCache()
    {
        $cache = new SimplePieCacheDriverCacheMock();
        $cache->deleteReturn = false;
        ee()->setMock('cache', $cache);

        $driver = new \EE_SimplePie_Cache_Driver('file:rss', 'feed_key', 'spc');
        $result = $driver->unlink();

        $this->assertFalse($result);
        $this->assertSame(
            [['/rss_parser/rss/feed_key', \Cache::GLOBAL_SCOPE]],
            $cache->deleteCalls
        );
    }

    private function getProtectedProperty($object, $property)
    {
        $reflection = new \ReflectionClass($object);
        $propertyObj = $reflection->getProperty($property);
        \TestReflectionHelper::makeAccessible($propertyObj);

        return $propertyObj->getValue($object);
    }
}
