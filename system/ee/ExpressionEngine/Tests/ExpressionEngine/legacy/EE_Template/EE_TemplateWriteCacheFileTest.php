<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

class EE_TemplateWriteCacheFileTest extends EE_TemplateTestBase
{
    public function testWriteCacheFileMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'write_cache_file'));
        $this->assertTrue(is_callable([$this->template, 'write_cache_file']));
    }

    public function testWriteCacheFileCachingDisabled()
    {
        // Set caching disabled
        $this->template->disable_caching = true;

        // Mock cache save to verify it's not called
        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save'])
            ->getMock();
        $cacheMock->expects($this->never())->method('save');
        ee()->setMock('cache', $cacheMock);

        $this->template->write_cache_file('test_file', 'test_data');

        // Reset for other tests
        $this->template->disable_caching = false;
    }

    public function testWriteCacheFileTagCachingDisabled()
    {
        // Mock config to disable tag caching
        $configMock = ee()->config;
        $configMock->items['disable_tag_caching'] = 'y';
        ee()->setMock('config', $configMock);

        // Mock cache save to verify it's not called for tag cache
        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save'])
            ->getMock();
        $cacheMock->expects($this->never())->method('save');
        ee()->setMock('cache', $cacheMock);

        $this->template->write_cache_file('test_file', 'test_data', 'tag');

        // Reset config
        $configMock->items['disable_tag_caching'] = 'n';
        ee()->setMock('config', $configMock);
    }

    public function testWriteCacheFileSuccessfulWrite()
    {
        // Mock cache prefix
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');
        $templateMock->disable_caching = false;

        // Mock cache save to return success
        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save'])
            ->getMock();
        $cacheMock->expects($this->once())
            ->method('save')
            ->with('/tag_cache/prefix_+test_file', 'test_data', 0)
            ->willReturn(true);
        ee()->setMock('cache', $cacheMock);

        $templateMock->write_cache_file('test_file', 'test_data', 'tag');
    }


    public function testWriteCacheFileTemplateCache()
    {
        // Mock cache prefix
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');
        $templateMock->disable_caching = false;

        // Mock config to enable tag caching
        $configMock = ee()->config;
        $configMock->items['disable_tag_caching'] = 'n';
        ee()->setMock('config', $configMock);

        // Mock cache save for template cache
        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save'])
            ->getMock();
        $cacheMock->expects($this->once())
            ->method('save')
            ->with('/page_cache/prefix_+test_file', 'test_data', 0)
            ->willReturn(true);
        ee()->setMock('cache', $cacheMock);

        $templateMock->write_cache_file('test_file', 'test_data', 'template');
    }

    public function testWriteCacheFileLargeData()
    {
        // Test writing very large data to cache
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');
        $templateMock->disable_caching = false;

        $largeData = str_repeat('x', 100000); // 100KB of data

        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save'])
            ->getMock();
        $cacheMock->expects($this->once())
            ->method('save')
            ->with('/tag_cache/prefix_+test_file', $largeData, 0)
            ->willReturn(true);
        ee()->setMock('cache', $cacheMock);

        $templateMock->write_cache_file('test_file', $largeData, 'tag');

        // Should handle large data without issues
        $this->assertTrue(true);
    }

    public function testWriteCacheFileSpecialCharacters()
    {
        // Test data with special characters, null bytes, UTF-8
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');
        $templateMock->disable_caching = false;

        $specialData = "data\x00with\x01null\x02bytes 🔥👨‍💻🚀";

        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save'])
            ->getMock();
        $cacheMock->expects($this->once())
            ->method('save')
            ->with('/tag_cache/prefix_+test_file', $specialData, 0)
            ->willReturn(true);
        ee()->setMock('cache', $cacheMock);

        $templateMock->write_cache_file('test_file', $specialData, 'tag');

        // Should handle special characters without corruption
        $this->assertTrue(true);
    }

    public function testWriteCacheFileLongCacheKey()
    {
        // Test with very long cache key
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');
        $templateMock->disable_caching = false;

        $longKey = str_repeat('x', 1000); // Very long cache key

        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save'])
            ->getMock();
        $cacheMock->expects($this->once())
            ->method('save')
            ->with('/tag_cache/prefix_+' . $longKey, 'test_data', 0)
            ->willReturn(true);
        ee()->setMock('cache', $cacheMock);

        $templateMock->write_cache_file($longKey, 'test_data', 'tag');

        // Should handle long cache keys
        $this->assertTrue(true);
    }

    public function testWriteCacheFileSpecialKeyCharacters()
    {
        // Test cache key with special characters
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');
        $templateMock->disable_caching = false;

        $specialKey = 'key with spaces & special chars !@#$%^&*()';

        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save'])
            ->getMock();
        $cacheMock->expects($this->once())
            ->method('save')
            ->with('/tag_cache/prefix_+' . $specialKey, 'test_data', 0)
            ->willReturn(true);
        ee()->setMock('cache', $cacheMock);

        $templateMock->write_cache_file($specialKey, 'test_data', 'tag');

        // Should handle special characters in cache keys
        $this->assertTrue(true);
    }

    public function testWriteCacheFileFailedWrite()
    {
        // Test that failed writes don't throw exceptions
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');
        $templateMock->disable_caching = false;

        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save'])
            ->getMock();
        $cacheMock->expects($this->once())
            ->method('save')
            ->with('/tag_cache/prefix_+test_file', 'test_data', 0)
            ->willReturn(false);
        ee()->setMock('cache', $cacheMock);

        // Should not throw exceptions on cache write failure
        $result = $templateMock->write_cache_file('test_file', 'test_data', 'tag');
        $this->assertNull($result); // Method returns void
    }

    public function testWriteCacheFileCacheBackendException()
    {
        // Test when cache backend throws exceptions during write
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');
        $templateMock->disable_caching = false;

        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save'])
            ->getMock();
        $cacheMock->expects($this->once())
            ->method('save')
            ->with('/tag_cache/prefix_+test_file', 'test_data', 0)
            ->willThrowException(new \Exception('Cache backend write failed'));
        ee()->setMock('cache', $cacheMock);

        // The method doesn't handle exceptions, so they will propagate
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cache backend write failed');

        $templateMock->write_cache_file('test_file', 'test_data', 'tag');
    }

    public function testWriteCacheFileExtremeKeyLength()
    {
        // Test with extremely long cache keys (filesystem limits)
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_cache_prefix'])
            ->getMock();
        $templateMock->method('_get_cache_prefix')->willReturn('prefix_');
        $templateMock->disable_caching = false;

        $veryLongKey = str_repeat('x', 2000); // Very long key

        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save'])
            ->getMock();
        $cacheMock->expects($this->once())
            ->method('save')
            ->with('/tag_cache/prefix_+' . $veryLongKey, 'test_data', 0)
            ->willReturn(true);
        ee()->setMock('cache', $cacheMock);

        // Should handle extremely long keys
        $result = $templateMock->write_cache_file($veryLongKey, 'test_data', 'tag');
        $this->assertNull($result);
    }

    public function testWriteCacheFileMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'write_cache_file');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('cfile', $parameters[0]->getName());
        $this->assertEquals('data', $parameters[1]->getName());
        $this->assertEquals('cache_type', $parameters[2]->getName());
        $this->assertEquals('tag', $parameters[2]->getDefaultValue());
    }
}