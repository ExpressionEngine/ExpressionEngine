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

class CacheUniqueKeyGlobalScopeTest extends CacheTestBase
{
    /**
     * Test that unique_key prefixes with md5 hash for global scope
     */
    public function testUniqueKeyPrefixesWithMd5Hash()
    {
        $cache = new \Cache();

        $key = 'test_key';
        // Expected: md5('127.0.0.1' . APPPATH) . ':' . key
        $expectedPrefix = md5('127.0.0.1' . APPPATH) . ':';
        $expected = $expectedPrefix . $key;

        $result = $cache->unique_key($key, \Cache::GLOBAL_SCOPE);

        $this->assertEquals($expected, $result);
        $this->assertStringStartsWith($expectedPrefix, $result);
    }

    /**
     * Test that unique_key preserves the original key structure for global scope
     */
    public function testUniqueKeyPreservesKeyStructure()
    {
        $cache = new \Cache();

        $key = 'some/nested/key';
        $expectedPrefix = md5('127.0.0.1' . APPPATH) . ':';
        $expected = $expectedPrefix . $key;

        $result = $cache->unique_key($key, \Cache::GLOBAL_SCOPE);

        $this->assertEquals($expected, $result);
        $this->assertStringContainsString('some/nested/key', $result);
    }

    /**
     * Test that unique_key works with empty key for global scope
     */
    public function testUniqueKeyWithEmptyKey()
    {
        $cache = new \Cache();

        $key = '';
        $expected = md5('127.0.0.1' . APPPATH) . ':';

        $result = $cache->unique_key($key, \Cache::GLOBAL_SCOPE);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that global scope uses different prefix than local scope
     */
    public function testGlobalScopeDiffersFromLocalScope()
    {
        $cache = new \Cache();

        $key = 'same_key';

        $localResult = $cache->unique_key($key, \Cache::LOCAL_SCOPE);
        $globalResult = $cache->unique_key($key, \Cache::GLOBAL_SCOPE);

        $this->assertNotEquals($localResult, $globalResult);
        $this->assertStringStartsWith('https://example.com/:', $localResult);
        $this->assertStringStartsWith(md5('127.0.0.1' . APPPATH) . ':', $globalResult);
        $this->assertStringEndsWith(':' . $key, $localResult);
        $this->assertStringEndsWith(':' . $key, $globalResult);
    }
}
