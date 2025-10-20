<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateGetCachePrefixTest extends EE_TemplateTestBase
{
    private $reflectionMethod;

    public function setUp(): void
    {
        parent::setUp();

        // Get the protected method using reflection
        $this->reflectionMethod = new \ReflectionMethod($this->template, '_get_cache_prefix');
        \TestReflectionHelper::makeMethodAccessible($this->reflectionMethod);
    }

    public function testGetCachePrefixWithUriString()
    {
        // Mock session language
        ee()->setMock('session', $this->getMockSession('english'));

        // Mock config
        ee()->setMock('config', $this->getMockConfigWithUriString());

        // Mock URI
        ee()->setMock('uri', $this->getMockUriWithString());

        $result = $this->reflectionMethod->invoke($this->template);

        // Should generate hash from: site_index + language + uri_string
        // fetch_site_index() returns '/' by default in mocks
        $siteIndex = '/';
        $language = 'english';
        $uriString = 'test/page';
        $expectedInput = $siteIndex . $language . $uriString;
        $expectedHash = md5($expectedInput);

        $this->assertEquals($expectedHash, $result);
        $this->assertIsString($result);
        $this->assertEquals(32, strlen($result)); // MD5 length
    }

    public function testGetCachePrefixWithoutUriString()
    {
        // Mock session language
        ee()->setMock('session', $this->getMockSession('french'));

        // Mock config
        ee()->setMock('config', $this->getMockConfigWithoutUriString());

        // Mock URI with no uri_string
        ee()->setMock('uri', $this->getMockUriWithoutString());

        $result = $this->reflectionMethod->invoke($this->template);

        // Should generate hash from: site_url + 'index' + language + query_string
        $siteUrl = 'https://example.com/';
        $index = 'index';
        $language = 'french';
        $queryString = 'param1=value1&param2=value2';
        $expectedInput = $siteUrl . $index . $language . $queryString;
        $expectedHash = md5($expectedInput);

        $this->assertEquals($expectedHash, $result);
    }

    public function testGetCachePrefixWithCachePrefixSet()
    {
        $this->template->cache_prefix = 'custom_prefix';

        // Mock session language
        ee()->setMock('session', $this->getMockSession('spanish'));

        // Mock config
        ee()->setMock('config', $this->getMockConfigWithUriString());

        // Mock URI
        ee()->setMock('uri', $this->getMockUriWithString());

        $result = $this->reflectionMethod->invoke($this->template);

        // Should use cache_prefix instead of uri_string
        $siteIndex = '/';
        $language = 'spanish';
        $cachePrefix = 'custom_prefix';
        $expectedInput = $siteIndex . $language . $cachePrefix;
        $expectedHash = md5($expectedInput);

        $this->assertEquals($expectedHash, $result);
    }

    public function testGetCachePrefixWithDifferentLanguages()
    {
        // Test with different languages to ensure they're included in hash
        $languages = ['english', 'french', 'german', 'spanish'];

        ee()->setMock('config', $this->getMockConfigWithUriString());
        ee()->setMock('uri', $this->getMockUriWithString());

        $results = [];
        foreach ($languages as $language) {
            ee()->setMock('session', $this->getMockSession($language));
            $result = $this->reflectionMethod->invoke($this->template);
            $results[$language] = $result;

            $this->assertIsString($result);
            $this->assertEquals(32, strlen($result));
        }

        // Different languages should produce different hashes
        $this->assertNotEquals($results['english'], $results['french']);
        $this->assertNotEquals($results['german'], $results['spanish']);
    }

    public function testGetCachePrefixReturnsMd5Hash()
    {
        ee()->setMock('session', $this->getMockSession('english'));
        ee()->setMock('config', $this->getMockConfigWithUriString());
        ee()->setMock('uri', $this->getMockUriWithString());

        $result = $this->reflectionMethod->invoke($this->template);

        $this->assertIsString($result);
        $this->assertEquals(32, strlen($result));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $result);
    }

    public function testGetCachePrefixConsistency()
    {
        ee()->setMock('session', $this->getMockSession('english'));
        ee()->setMock('config', $this->getMockConfigWithUriString());
        ee()->setMock('uri', $this->getMockUriWithString());

        $result1 = $this->reflectionMethod->invoke($this->template);
        $result2 = $this->reflectionMethod->invoke($this->template);

        // Same inputs should produce same result
        $this->assertEquals($result1, $result2);
    }

    public function testGetCachePrefixWithEmptyUriString()
    {
        // Mock session language
        ee()->setMock('session', $this->getMockSession('english'));

        // Mock config
        ee()->setMock('config', $this->getMockConfigWithoutUriString());

        // Mock URI with empty string
        $uriMock = $this->createMock('stdClass');
        $uriMock->uri_string = '';
        $uriMock->query_string = 'empty=test';
        ee()->setMock('uri', $uriMock);

        $result = $this->reflectionMethod->invoke($this->template);

        // Should generate hash from: site_url + 'index' + language + query_string
        $siteUrl = 'https://example.com/';
        $index = 'index';
        $language = 'english';
        $queryString = 'empty=test';
        $expectedInput = $siteUrl . $index . $language . $queryString;
        $expectedHash = md5($expectedInput);

        $this->assertEquals($expectedHash, $result);
    }

    public function testGetCachePrefixWithNullCachePrefix()
    {
        $this->template->cache_prefix = null;

        ee()->setMock('session', $this->getMockSession('english'));
        ee()->setMock('config', $this->getMockConfigWithUriString());
        ee()->setMock('uri', $this->getMockUriWithString());

        $result = $this->reflectionMethod->invoke($this->template);

        // Null cache_prefix should be treated as empty and uri_string should be used
        $siteIndex = '/';
        $language = 'english';
        $uriString = 'test/page';
        $expectedInput = $siteIndex . $language . $uriString;
        $expectedHash = md5($expectedInput);

        $this->assertEquals($expectedHash, $result);
    }

    public function testGetCachePrefixWithNullSiteIndex()
    {
        // Mock session language
        ee()->setMock('session', $this->getMockSession('english'));

        // Mock config with null site_index
        $configMock = $this->createMock('eeSingletonConfigMock');
        $configMock->method('item')->willReturnCallback(function($key) {
            $config = [
                'site_index' => null // Null value
            ];
            return isset($config[$key]) ? $config[$key] : false;
        });
        ee()->setMock('config', $configMock);

        // Mock URI
        ee()->setMock('uri', $this->getMockUriWithString());

        $result = $this->reflectionMethod->invoke($this->template);

        // Should handle null site_index gracefully (fetch_site_index() should return '/')
        $expectedInput = '/' . 'english' . 'test/page';
        $expectedHash = md5($expectedInput);

        $this->assertEquals($expectedHash, $result);
    }

    public function testGetCachePrefixWithEmptySiteIndex()
    {
        // Mock session language
        ee()->setMock('session', $this->getMockSession('english'));

        // Mock config with empty site_index
        $configMock = $this->createMock('eeSingletonConfigMock');
        $configMock->method('item')->willReturnCallback(function($key) {
            $config = [
                'site_index' => '' // Empty string
            ];
            return isset($config[$key]) ? $config[$key] : false;
        });
        ee()->setMock('config', $configMock);

        // Mock URI
        ee()->setMock('uri', $this->getMockUriWithString());

        $result = $this->reflectionMethod->invoke($this->template);

        // Should handle empty site_index (fetch_site_index() should return '/')
        $expectedInput = '/' . 'english' . 'test/page';
        $expectedHash = md5($expectedInput);

        $this->assertEquals($expectedHash, $result);
    }

    public function testGetCachePrefixWithNullSessionLanguage()
    {
        // Mock session with null language
        $sessionMock = $this->createMock('eeSingletonSessionMock');
        $sessionMock->method('get_language')->willReturn(null);
        ee()->setMock('session', $sessionMock);

        // Mock config
        ee()->setMock('config', $this->getMockConfigWithUriString());

        // Mock URI
        ee()->setMock('uri', $this->getMockUriWithString());

        $result = $this->reflectionMethod->invoke($this->template);

        // Should handle null language gracefully
        $expectedInput = '/' . '' . 'test/page'; // null becomes empty string
        $expectedHash = md5($expectedInput);

        $this->assertEquals($expectedHash, $result);
    }

    public function testGetCachePrefixWithEmptySessionLanguage()
    {
        // Mock session with empty language
        $sessionMock = $this->createMock('eeSingletonSessionMock');
        $sessionMock->method('get_language')->willReturn('');
        ee()->setMock('session', $sessionMock);

        // Mock config
        ee()->setMock('config', $this->getMockConfigWithUriString());

        // Mock URI
        ee()->setMock('uri', $this->getMockUriWithString());

        $result = $this->reflectionMethod->invoke($this->template);

        // Should handle empty language string
        $expectedInput = '/' . '' . 'test/page';
        $expectedHash = md5($expectedInput);

        $this->assertEquals($expectedHash, $result);
    }

    public function testGetCachePrefixWithVeryLongUriString()
    {
        // Mock session language
        ee()->setMock('session', $this->getMockSession('english'));

        // Mock config
        ee()->setMock('config', $this->getMockConfigWithUriString());

        // Mock URI with very long string
        $longUri = str_repeat('a', 10000); // 10KB URI
        $uriMock = $this->createMock('stdClass');
        $uriMock->uri_string = $longUri;
        ee()->setMock('uri', $uriMock);

        $result = $this->reflectionMethod->invoke($this->template);

        // Should handle very long URIs
        $expectedInput = '/' . 'english' . $longUri;
        $expectedHash = md5($expectedInput);

        $this->assertEquals($expectedHash, $result);
        $this->assertEquals(32, strlen($result)); // MD5 should always be 32 chars
    }

    public function testGetCachePrefixWithSpecialCharactersInUri()
    {
        // Mock session language
        ee()->setMock('session', $this->getMockSession('english'));

        // Mock config
        ee()->setMock('config', $this->getMockConfigWithUriString());

        // Mock URI with special characters
        $specialUri = 'test/page?param=value&other=🚀&unicode=测试';
        $uriMock = $this->createMock('stdClass');
        $uriMock->uri_string = $specialUri;
        ee()->setMock('uri', $uriMock);

        $result = $this->reflectionMethod->invoke($this->template);

        // Should handle special characters and unicode in URIs
        $expectedInput = '/' . 'english' . $specialUri;
        $expectedHash = md5($expectedInput);

        $this->assertEquals($expectedHash, $result);
    }

    public function testGetCachePrefixWithQueryStringSpecialChars()
    {
        // Mock session language
        ee()->setMock('session', $this->getMockSession('french'));

        // Mock config without URI string
        ee()->setMock('config', $this->getMockConfigWithoutUriString());

        // Mock URI with complex query string
        $complexQuery = 'param1=hello%20world&param2=%3D%26%3F&emoji=🚀&chinese=测试';
        $uriMock = $this->createMock('stdClass');
        $uriMock->uri_string = '';
        $uriMock->query_string = $complexQuery;
        ee()->setMock('uri', $uriMock);

        $result = $this->reflectionMethod->invoke($this->template);

        // Should handle complex query strings with encoding
        $expectedInput = 'https://example.com/indexfrench' . $complexQuery;
        $expectedHash = md5($expectedInput);

        $this->assertEquals($expectedHash, $result);
    }

    public function testGetCachePrefixWithVeryLongCachePrefix()
    {
        $this->template->cache_prefix = str_repeat('prefix_', 1000); // Very long prefix

        // Mock session language
        ee()->setMock('session', $this->getMockSession('english'));

        // Mock config
        ee()->setMock('config', $this->getMockConfigWithUriString());

        // Mock URI
        ee()->setMock('uri', $this->getMockUriWithString());

        $result = $this->reflectionMethod->invoke($this->template);

        // Should handle very long cache prefixes
        $expectedInput = '/' . 'english' . $this->template->cache_prefix;
        $expectedHash = md5($expectedInput);

        $this->assertEquals($expectedHash, $result);
    }

    // Helper methods for creating mocks

    private function getMockSession($language)
    {
        $sessionMock = $this->createMock('eeSingletonSessionMock');
        $sessionMock->method('get_language')->willReturn($language);
        return $sessionMock;
    }

    private function getMockConfigWithUriString()
    {
        $configMock = $this->createMock('eeSingletonConfigMock');
        $configMock->method('item')->willReturnCallback(function($key) {
            $config = [
                'site_index' => '/site-index'
            ];
            return isset($config[$key]) ? $config[$key] : false;
        });
        return $configMock;
    }

    private function getMockConfigWithoutUriString()
    {
        $configMock = $this->createMock('eeSingletonConfigMock');
        $configMock->method('item')->willReturnCallback(function($key) {
            $config = [
                'site_url' => 'https://example.com/'
            ];
            return isset($config[$key]) ? $config[$key] : false;
        });
        return $configMock;
    }

    private function getMockUriWithString()
    {
        $uriMock = $this->createMock('stdClass');
        $uriMock->uri_string = 'test/page';
        return $uriMock;
    }

    private function getMockUriWithoutString()
    {
        $uriMock = $this->createMock('stdClass');
        $uriMock->uri_string = '';
        $uriMock->query_string = 'param1=value1&param2=value2';
        return $uriMock;
    }
}
