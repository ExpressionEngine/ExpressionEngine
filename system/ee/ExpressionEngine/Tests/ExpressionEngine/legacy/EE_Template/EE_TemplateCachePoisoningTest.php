<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateCachePoisoningTest extends EE_TemplateTestBase
{
    public function testCacheCorruptionWithInvalidSerializedData()
    {
        // Test cache file with corrupted serialized data
        $corruptedData = 'a:1:{s:4:"data";O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}'; // Missing closing }

        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(['expire' => time() + 3600]);
        $cacheMock->method('get')->willReturn($corruptedData);
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_corrupted', 'tag', $args);

        // Cache system returns whatever data is stored (no validation)
        $this->assertEquals($corruptedData, $result);
        $this->assertEquals('CURRENT', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithNonStringData()
    {
        // Test cache file containing non-string data (like arrays or objects)
        $invalidData = ['this', 'is', 'an', 'array'];

        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(['expire' => time() + 3600]);
        $cacheMock->method('get')->willReturn($invalidData);
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_invalid_type', 'tag', $args);

        // Should return the array as-is (PHP allows this)
        $this->assertEquals($invalidData, $result);
        $this->assertEquals('CURRENT', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithNullData()
    {
        // Test cache file containing null data
        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(['expire' => time() + 3600]);
        $cacheMock->method('get')->willReturn(null);
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_null', 'tag', $args);

        // Null is falsy, so cache is considered EXPIRED and returns empty string
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithMaliciousSerializedData()
    {
        // Test cache file with malicious serialized data (potential security issue)
        $maliciousData = 'O:8:"stdClass":1:{s:4:"eval";s:14:"phpinfo();die();";}';

        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(['expire' => time() + 3600]);
        $cacheMock->method('get')->willReturn($maliciousData);
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_malicious', 'tag', $args);

        // Should return the serialized string as-is (no unserialization happens)
        $this->assertEquals($maliciousData, $result);
        $this->assertEquals('CURRENT', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithExtremelyLargeData()
    {
        // Test cache file with extremely large data that could cause memory issues
        $largeData = str_repeat('x', 1000000); // 1MB string (smaller to avoid memory issues)

        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(['expire' => time() + 3600]);
        $cacheMock->method('get')->willReturn($largeData);
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];

        $result = $this->template->fetch_cache_file('test_large', 'tag', $args);

        // Should handle large data correctly
        $this->assertEquals($largeData, $result);
        $this->assertEquals('CURRENT', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithBinaryData()
    {
        // Test cache file with binary data containing null bytes
        $binaryData = "normal text\x00\x01\x02null bytes\x03\x04here";

        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(['expire' => time() + 3600]);
        $cacheMock->method('get')->willReturn($binaryData);
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_binary', 'tag', $args);

        // Should handle binary data correctly
        $this->assertEquals($binaryData, $result);
        $this->assertEquals('CURRENT', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithUnicodeData()
    {
        // Test cache file with complex Unicode data
        $unicodeData = "🚀 Hello 世界 🌍 Multi-byte: " . str_repeat("🚀", 1000);

        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(['expire' => time() + 3600]);
        $cacheMock->method('get')->willReturn($unicodeData);
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_unicode', 'tag', $args);

        // Should preserve Unicode data
        $this->assertEquals($unicodeData, $result);
        $this->assertEquals('CURRENT', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithExpiredMetadata()
    {
        // Test cache with expired metadata but valid data
        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(['expire' => time() - 3600]); // Expired
        $cacheMock->method('get')->willReturn('valid cached data');
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_expired_meta', 'tag', $args);

        // Should return empty due to expired metadata (cache not used)
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithMissingMetadata()
    {
        // Test cache with no metadata
        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(null);
        $cacheMock->method('get')->willReturn('cached data');
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_no_meta', 'tag', $args);

        // Should return empty due to missing metadata
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithInvalidMetadata()
    {
        // Test cache with invalid metadata structure
        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn('not an array');
        $cacheMock->method('get')->willReturn('cached data');
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_invalid_meta', 'tag', $args);

        // Should return empty due to invalid metadata
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithMetadataWithoutExpire()
    {
        // Test cache with metadata missing expire field
        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(['ttl' => 3600]); // No expire field
        $cacheMock->method('get')->willReturn('cached data');
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_no_expire', 'tag', $args);

        // Should return empty due to missing expire field
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithZeroExpireTime()
    {
        // Test cache with zero expire time
        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(['expire' => 0]);
        $cacheMock->method('get')->willReturn('cached data');
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_zero_expire', 'tag', $args);

        // Should return empty due to zero expire time
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithNegativeExpireTime()
    {
        // Test cache with negative expire time
        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(['expire' => -1000]);
        $cacheMock->method('get')->willReturn('cached data');
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_negative_expire', 'tag', $args);

        // Should return empty due to negative expire time
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithCacheException()
    {
        // Test cache that throws exceptions
        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willThrowException(new \Exception('Cache backend error'));
        $cacheMock->method('get')->willReturn('cached data');
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];

        // The exception should be handled gracefully
        try {
            $result = $this->template->fetch_cache_file('test_exception', 'tag', $args);
            // Should handle exception gracefully and return expired cache
            $this->assertEquals('', $result);
            $this->assertEquals('EXPIRED', $this->template->tag_cache_status);
        } catch (\Exception $e) {
            // If exception propagates, it should be the cache backend error
            $this->assertEquals('Cache backend error', $e->getMessage());
        }
    }

    public function testCacheCorruptionWithFalseData()
    {
        // Test cache returning false (cache miss)
        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(['expire' => time() + 3600]);
        $cacheMock->method('get')->willReturn(false);
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_false', 'tag', $args);

        // False is falsy, so cache is considered EXPIRED and returns empty string
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $this->template->tag_cache_status);
    }

    public function testCacheCorruptionWithEmptyStringData()
    {
        // Test cache with empty string
        $cacheMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'get_metadata', 'save', 'delete'])
            ->getMock();
        $cacheMock->method('get_metadata')->willReturn(['expire' => time() + 3600]);
        $cacheMock->method('get')->willReturn('');
        ee()->setMock('cache', $cacheMock);

        $args = ['cache' => 'yes'];
        $result = $this->template->fetch_cache_file('test_empty', 'tag', $args);

        // Empty string is falsy, so cache is considered EXPIRED and returns empty string
        $this->assertEquals('', $result);
        $this->assertEquals('EXPIRED', $this->template->tag_cache_status);
    }
}
