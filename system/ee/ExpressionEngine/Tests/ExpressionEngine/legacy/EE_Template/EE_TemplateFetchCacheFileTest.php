<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

class EE_TemplateFetchCacheFileTest extends EE_TemplateTestBase
{
    public function testFetchCacheFileMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'fetch_cache_file'));
        $this->assertTrue(is_callable([$this->template, 'fetch_cache_file']));
    }

    public function testFetchCacheFileNotSetToCache()
    {
        $args = array('cache' => 'no');
        $result = $this->template->fetch_cache_file('test_file', 'tag', $args);

        $this->assertFalse($result);
        $this->assertEquals('NO_CACHE', $this->template->tag_cache_status);
    }

    public function testFetchCacheFileLivePreviewActive()
    {
        // Mock LivePreview to return true
        $livePreviewMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasEntryData'])
            ->getMock();
        $livePreviewMock->method('hasEntryData')->willReturn(true);
        ee()->setMock('LivePreview', $livePreviewMock);

        $args = array('cache' => 'yes');
        $result = $this->template->fetch_cache_file('test_file', 'tag', $args);

        $this->assertFalse($result);
        $this->assertEquals('NO_CACHE', $this->template->tag_cache_status);
    }

    public function testFetchCacheFileProEditingActive()
    {
        // Mock Pro Access to return true for dock permission
        $proAccessMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasDockPermission'])
            ->getMock();
        $proAccessMock->method('hasDockPermission')->willReturn(true);
        ee()->setMock('pro:Access', $proAccessMock);

        $args = array('cache' => 'yes');
        $result = $this->template->fetch_cache_file('test_file', 'tag', $args);

        $this->assertFalse($result);
        $this->assertEquals('NO_CACHE', $this->template->tag_cache_status);
    }

    public function testFetchCacheFileCacheHit()
    {
        // Mock all dependencies for successful cache retrieval
        $livePreviewMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasEntryData'])
            ->getMock();
        $livePreviewMock->method('hasEntryData')->willReturn(false);
        ee()->setMock('LivePreview', $livePreviewMock);

        $proAccessMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasDockPermission'])
            ->getMock();
        $proAccessMock->method('hasDockPermission')->willReturn(false);
        ee()->setMock('pro:Access', $proAccessMock);

        // Mock template for cache prefix
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');

        // Mock localize for current time
        $localizeMock = ee()->localize;
        $localizeMock->now = 1000;
        ee()->setMock('localize', $localizeMock);

        // Mock cache metadata and data retrieval
        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_metadata', 'get'])
            ->getMock();

        // Cache metadata shows cache expires in future
        $cacheMock->method('get_metadata')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn(array('expire' => 2000));

        // Cache returns data
        $cacheMock->method('get')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn('cached_content');

        ee()->setMock('cache', $cacheMock);

        $args = array('cache' => 'yes', 'refresh' => 0);
        $result = $templateMock->fetch_cache_file('test_file', 'tag', $args);

        $this->assertEquals('cached_content', $result);
        $this->assertEquals('CURRENT', $templateMock->tag_cache_status);
    }

    public function testFetchCacheFileCacheMiss()
    {
        // Mock all dependencies for cache miss
        $livePreviewMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasEntryData'])
            ->getMock();
        $livePreviewMock->method('hasEntryData')->willReturn(false);
        ee()->setMock('LivePreview', $livePreviewMock);

        $proAccessMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasDockPermission'])
            ->getMock();
        $proAccessMock->method('hasDockPermission')->willReturn(false);
        ee()->setMock('pro:Access', $proAccessMock);

        // Mock template for cache prefix
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');

        // Mock localize for current time
        $localizeMock = ee()->localize;
        $localizeMock->now = 2000; // Current time is 2000
        ee()->setMock('localize', $localizeMock);

        // Mock cache metadata shows expired cache
        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_metadata', 'get'])
            ->getMock();

        $cacheMock->method('get_metadata')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn(array('expire' => 1000)); // Expired

        $cacheMock->method('get')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn(false); // No data

        ee()->setMock('cache', $cacheMock);

        $args = array('cache' => 'yes', 'refresh' => 0);
        $result = $templateMock->fetch_cache_file('test_file', 'tag', $args);

        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $templateMock->tag_cache_status);
    }

    public function testFetchCacheFileTemplateCache()
    {
        // Mock all dependencies for template cache
        $livePreviewMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasEntryData'])
            ->getMock();
        $livePreviewMock->method('hasEntryData')->willReturn(false);
        ee()->setMock('LivePreview', $livePreviewMock);

        $proAccessMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasDockPermission'])
            ->getMock();
        $proAccessMock->method('hasDockPermission')->willReturn(false);
        ee()->setMock('pro:Access', $proAccessMock);

        // Mock template for cache prefix
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');

        // Mock localize for current time
        $localizeMock = ee()->localize;
        $localizeMock->now = 1000;
        ee()->setMock('localize', $localizeMock);

        // Mock cache for template cache
        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_metadata', 'get'])
            ->getMock();

        $cacheMock->method('get_metadata')
            ->with('/page_cache/prefix_+test_file')
            ->willReturn(array('expire' => 2000));

        $cacheMock->method('get')
            ->with('/page_cache/prefix_+test_file')
            ->willReturn('template_cached_content');

        ee()->setMock('cache', $cacheMock);

        $args = array('cache' => 'yes');
        $result = $templateMock->fetch_cache_file('test_file', 'template', $args);

        $this->assertEquals('template_cached_content', $result);
        $this->assertEquals('CURRENT', $templateMock->cache_status);
    }

    public function testFetchCacheFileMalformedMetadata()
    {
        // Test cache metadata without expire field
        $livePreviewMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasEntryData'])
            ->getMock();
        $livePreviewMock->method('hasEntryData')->willReturn(false);
        ee()->setMock('LivePreview', $livePreviewMock);

        $proAccessMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasDockPermission'])
            ->getMock();
        $proAccessMock->method('hasDockPermission')->willReturn(false);
        ee()->setMock('pro:Access', $proAccessMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');

        $localizeMock = ee()->localize;
        $localizeMock->now = 1000;
        ee()->setMock('localize', $localizeMock);

        // Mock cache with malformed metadata (missing expire)
        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_metadata', 'get'])
            ->getMock();
        $cacheMock->method('get_metadata')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn(array('some_other_field' => 'value')); // No 'expire' field
        $cacheMock->method('get')->willReturn('cached_content');

        ee()->setMock('cache', $cacheMock);

        $args = array('cache' => 'yes', 'refresh' => 0);
        $result = $templateMock->fetch_cache_file('test_file', 'tag', $args);

        // Should handle missing expire field gracefully
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $templateMock->tag_cache_status);
    }

    public function testFetchCacheFileCorruptedCacheData()
    {
        // Test corrupted or invalid cache data
        $livePreviewMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasEntryData'])
            ->getMock();
        $livePreviewMock->method('hasEntryData')->willReturn(false);
        ee()->setMock('LivePreview', $livePreviewMock);

        $proAccessMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasDockPermission'])
            ->getMock();
        $proAccessMock->method('hasDockPermission')->willReturn(false);
        ee()->setMock('pro:Access', $proAccessMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');

        $localizeMock = ee()->localize;
        $localizeMock->now = 1000;
        ee()->setMock('localize', $localizeMock);

        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_metadata', 'get'])
            ->getMock();
        $cacheMock->method('get_metadata')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn(array('expire' => 2000));
        $cacheMock->method('get')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn(null); // Corrupted/null data

        ee()->setMock('cache', $cacheMock);

        $args = array('cache' => 'yes', 'refresh' => 0);
        $result = $templateMock->fetch_cache_file('test_file', 'tag', $args);

        // Should handle null cache data gracefully
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $templateMock->tag_cache_status);
    }

    public function testFetchCacheFileNegativeRefresh()
    {
        // Test negative refresh values
        $livePreviewMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasEntryData'])
            ->getMock();
        $livePreviewMock->method('hasEntryData')->willReturn(false);
        ee()->setMock('LivePreview', $livePreviewMock);

        $proAccessMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasDockPermission'])
            ->getMock();
        $proAccessMock->method('hasDockPermission')->willReturn(false);
        ee()->setMock('pro:Access', $proAccessMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');

        $localizeMock = ee()->localize;
        $localizeMock->now = 1000;
        ee()->setMock('localize', $localizeMock);

        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_metadata', 'get'])
            ->getMock();
        $cacheMock->method('get_metadata')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn(array('expire' => 2000));
        $cacheMock->method('get')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn('cached_content');

        ee()->setMock('cache', $cacheMock);

        $args = array('cache' => 'yes', 'refresh' => -1);
        $result = $templateMock->fetch_cache_file('test_file', 'tag', $args);

        // Negative refresh should still work (converted to seconds)
        $this->assertEquals('cached_content', $result);
        $this->assertEquals('CURRENT', $templateMock->tag_cache_status);
    }

    public function testFetchCacheFileExactExpireTime()
    {
        // Test when current time exactly equals expire time
        $livePreviewMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasEntryData'])
            ->getMock();
        $livePreviewMock->method('hasEntryData')->willReturn(false);
        ee()->setMock('LivePreview', $livePreviewMock);

        $proAccessMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasDockPermission'])
            ->getMock();
        $proAccessMock->method('hasDockPermission')->willReturn(false);
        ee()->setMock('pro:Access', $proAccessMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');

        $localizeMock = ee()->localize;
        $localizeMock->now = 1000; // Exactly equal to expire time
        ee()->setMock('localize', $localizeMock);

        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_metadata', 'get'])
            ->getMock();
        $cacheMock->method('get_metadata')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn(array('expire' => 1000)); // Exact match
        $cacheMock->method('get')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn('cached_content');

        ee()->setMock('cache', $cacheMock);

        $args = array('cache' => 'yes', 'refresh' => 0);
        $result = $templateMock->fetch_cache_file('test_file', 'tag', $args);

        // Should be considered expired when times are exactly equal
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $templateMock->tag_cache_status);
    }

    public function testFetchCacheFileCacheBackendException()
    {
        // Test when cache backend throws exceptions (network issues, etc.)
        $livePreviewMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasEntryData'])
            ->getMock();
        $livePreviewMock->method('hasEntryData')->willReturn(false);
        ee()->setMock('LivePreview', $livePreviewMock);

        $proAccessMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasDockPermission'])
            ->getMock();
        $proAccessMock->method('hasDockPermission')->willReturn(false);
        ee()->setMock('pro:Access', $proAccessMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');

        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_metadata', 'get'])
            ->getMock();
        $cacheMock->method('get_metadata')
            ->willThrowException(new \Exception('Cache backend connection failed'));

        ee()->setMock('cache', $cacheMock);

        $args = array('cache' => 'yes');

        // The method doesn't handle exceptions, so it will propagate
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cache backend connection failed');

        $templateMock->fetch_cache_file('test_file', 'tag', $args);
    }

    public function testFetchCacheFileSubSecondPrecision()
    {
        // Test microsecond precision timing edge cases
        $livePreviewMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasEntryData'])
            ->getMock();
        $livePreviewMock->method('hasEntryData')->willReturn(false);
        ee()->setMock('LivePreview', $livePreviewMock);

        $proAccessMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasDockPermission'])
            ->getMock();
        $proAccessMock->method('hasDockPermission')->willReturn(false);
        ee()->setMock('pro:Access', $proAccessMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');

        // Test with microsecond precision timestamps
        $localizeMock = ee()->localize;
        $localizeMock->now = 1001.000001; // Just after expiration
        ee()->setMock('localize', $localizeMock);

        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_metadata', 'get'])
            ->getMock();
        $cacheMock->method('get_metadata')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn(array('expire' => 1001.000000)); // Just expired
        $cacheMock->method('get')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn('cached_content');

        ee()->setMock('cache', $cacheMock);

        $args = array('cache' => 'yes', 'refresh' => 0);
        $result = $templateMock->fetch_cache_file('test_file', 'tag', $args);

        // Should be considered expired due to microsecond precision
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $templateMock->tag_cache_status);
    }

    public function testFetchCacheFileKeyLengthLimits()
    {
        // Test with cache keys that exceed filesystem limits
        $template = new \EE_Template();
        $template->disable_caching = false;

        // Set up config to not disable tag caching
        ee()->config->setItem('disable_tag_caching', 'n');

        $veryLongKey = str_repeat('x', 4096); // Exceed typical filesystem limits

        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save'])
            ->getMock();
        $cacheMock->expects($this->once())
            ->method('save')
            ->willReturn(false); // Cache save fails due to key length
        ee()->setMock('cache', $cacheMock);

        // Should handle key length limits gracefully (cache save failure)
        $result = $template->write_cache_file($veryLongKey, 'data', 'tag');
        $this->assertNull($result);
    }

    public function testFetchCacheFileRaceCondition()
    {
        // Test cache race conditions where cache expires between metadata and get calls
        $livePreviewMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasEntryData'])
            ->getMock();
        $livePreviewMock->method('hasEntryData')->willReturn(false);
        ee()->setMock('LivePreview', $livePreviewMock);

        $proAccessMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasDockPermission'])
            ->getMock();
        $proAccessMock->method('hasDockPermission')->willReturn(false);
        ee()->setMock('pro:Access', $proAccessMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');

        $localizeMock = ee()->localize;
        $localizeMock->now = 1000;
        ee()->setMock('localize', $localizeMock);

        // Mock cache to return valid metadata but null data (race condition)
        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_metadata', 'get'])
            ->getMock();
        $cacheMock->method('get_metadata')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn(array('expire' => 2000));
        $cacheMock->method('get')
            ->with('/tag_cache/prefix_+test_file')
            ->willReturn(null); // Race condition - cache expired between calls
        ee()->setMock('cache', $cacheMock);

        $args = array('cache' => 'yes', 'refresh' => 0);
        $result = $templateMock->fetch_cache_file('test_file', 'tag', $args);

        // Should handle race condition gracefully
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $templateMock->tag_cache_status);
    }

    public function testFetchCacheFileBackendOverload()
    {
        // Test when cache backend is overloaded and becomes unresponsive
        $livePreviewMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasEntryData'])
            ->getMock();
        $livePreviewMock->method('hasEntryData')->willReturn(false);
        ee()->setMock('LivePreview', $livePreviewMock);

        $proAccessMock = $this->getMockBuilder('stdClass')
            ->setMethods(['hasDockPermission'])
            ->getMock();
        $proAccessMock->method('hasDockPermission')->willReturn(false);
        ee()->setMock('pro:Access', $proAccessMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');

        // Mock cache to be extremely slow (simulating overload)
        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_metadata', 'get'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturnCallback(function() {
            usleep(100000); // 0.1 second delay
            return array('expire' => time() + 3600);
        });
        $cacheMock->method('get')->willReturnCallback(function() {
            usleep(100000); // Another 0.1 second delay
            return 'slow_cached_content';
        });
        ee()->setMock('cache', $cacheMock);

        $start = microtime(true);
        $args = array('cache' => 'yes', 'refresh' => 0);
        $result = $templateMock->fetch_cache_file('test_file', 'tag', $args);
        $duration = microtime(true) - $start;

        // Should eventually return content but may be slower than usual
        $this->assertEquals('slow_cached_content', $result);
        $this->assertGreaterThan(0.1, $duration); // Should take at least the delay time
    }

    public function testFetchCacheFileMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'fetch_cache_file');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('cfile', $parameters[0]->getName());
        $this->assertEquals('cache_type', $parameters[1]->getName());
        $this->assertEquals('args', $parameters[2]->getName());
        $this->assertEquals('tag', $parameters[1]->getDefaultValue());
        $this->assertEquals(array(), $parameters[2]->getDefaultValue());
    }
}