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

class CacheUniqueKeyLocalScopeTest extends CacheTestBase
{
    /**
     * Test that unique_key prefixes with base_url for local scope
     */
    public function testUniqueKeyPrefixesWithBaseUrl()
    {
        $cache = new \Cache();

        $key = 'test_key';
        $expected = 'https://example.com/:' . $key;

        $result = $cache->unique_key($key, \Cache::LOCAL_SCOPE);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that unique_key preserves the original key structure
     */
    public function testUniqueKeyPreservesKeyStructure()
    {
        $cache = new \Cache();

        $key = 'some/nested/key';
        $expected = 'https://example.com/:' . $key;

        $result = $cache->unique_key($key, \Cache::LOCAL_SCOPE);

        $this->assertEquals($expected, $result);
        $this->assertStringContainsString('some/nested/key', $result);
    }

    /**
     * Test that unique_key works with empty key
     */
    public function testUniqueKeyWithEmptyKey()
    {
        $cache = new \Cache();

        $key = '';
        $expected = 'https://example.com/:';

        $result = $cache->unique_key($key, \Cache::LOCAL_SCOPE);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that unique_key defaults to local scope
     */
    public function testUniqueKeyDefaultsToLocalScope()
    {
        $cache = new \Cache();

        $key = 'default_test';
        $expected = 'https://example.com/:' . $key;

        $result = $cache->unique_key($key);

        $this->assertEquals($expected, $result);
    }
}
