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

class CacheDatabaseNamespacedKeyTest extends CacheDatabaseTestBase
{
    /**
     * Test that _namespaced_key adds site prefix for local scope
     */
    public function testNamespacedKeyWithLocalScope()
    {
        ee()->config->setItem('site_short_name', 'test_site');

        $driver = $this->makeDatabaseDriver();
        $result = $this->invokeProtectedMethod($driver, '_namespaced_key', ['my_key', \Cache::LOCAL_SCOPE]);

        $this->assertStringStartsWith('test_site_', $result);
        $this->assertEquals('test_site_my_key', $result);
    }

    /**
     * Test that _namespaced_key adds global hash prefix for global scope
     */
    public function testNamespacedKeyWithGlobalScope()
    {
        $driver = $this->makeDatabaseDriver();
        $result = $this->invokeProtectedMethod($driver, '_namespaced_key', ['my_key', \Cache::GLOBAL_SCOPE]);

        // Should start with a 32-character MD5 hash
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}_my_key$/', $result);
    }

    /**
     * Test that _namespaced_key trims leading separator
     */
    public function testNamespacedKeyTrimsLeadingSlash()
    {
        ee()->config->setItem('site_short_name', 'test_site');

        $driver = $this->makeDatabaseDriver();
        $result = $this->invokeProtectedMethod($driver, '_namespaced_key', ['/my_key', \Cache::LOCAL_SCOPE]);

        $this->assertEquals('test_site_my_key', $result);
    }

    /**
     * Test that _namespaced_key trims trailing separator
     */
    public function testNamespacedKeyTrimsTrailingSlash()
    {
        ee()->config->setItem('site_short_name', 'test_site');

        $driver = $this->makeDatabaseDriver();
        $result = $this->invokeProtectedMethod($driver, '_namespaced_key', ['my_key/', \Cache::LOCAL_SCOPE]);

        $this->assertEquals('test_site_my_key', $result);
    }

    /**
     * Test that _namespaced_key replaces slashes with underscores
     */
    public function testNamespacedKeyReplacesSlashesWithUnderscores()
    {
        ee()->config->setItem('site_short_name', 'test_site');

        $driver = $this->makeDatabaseDriver();
        $result = $this->invokeProtectedMethod($driver, '_namespaced_key', ['namespace/sub/key', \Cache::LOCAL_SCOPE]);

        $this->assertEquals('test_site_namespace_sub_key', $result);
    }

    /**
     * Test that _namespaced_key uses site_short_name from config
     */
    public function testNamespacedKeyUsesSiteShortName()
    {
        ee()->config->setItem('site_short_name', 'my_custom_site');

        $driver = $this->makeDatabaseDriver();
        $result = $this->invokeProtectedMethod($driver, '_namespaced_key', ['test', \Cache::LOCAL_SCOPE]);

        $this->assertStringStartsWith('my_custom_site_', $result);
    }

    /**
     * Test that _namespaced_key global scope uses APPPATH in hash
     */
    public function testNamespacedKeyGlobalUsesAppPath()
    {
        $driver = $this->makeDatabaseDriver();
        
        // Call twice to ensure consistent hashing
        $result1 = $this->invokeProtectedMethod($driver, '_namespaced_key', ['test', \Cache::GLOBAL_SCOPE]);
        $result2 = $this->invokeProtectedMethod($driver, '_namespaced_key', ['test', \Cache::GLOBAL_SCOPE]);

        // Should be identical
        $this->assertEquals($result1, $result2);
        
        // Should be in format: md5_hash_key
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}_test$/', $result1);
    }

    /**
     * Test that _namespaced_key handles nested namespaces
     */
    public function testNamespacedKeyWithNestedNamespace()
    {
        ee()->config->setItem('site_short_name', 'site');

        $driver = $this->makeDatabaseDriver();
        $result = $this->invokeProtectedMethod($driver, '_namespaced_key', ['ns1/ns2/ns3/key', \Cache::LOCAL_SCOPE]);

        $this->assertEquals('site_ns1_ns2_ns3_key', $result);
    }

    /**
     * Test that _namespaced_key handles empty site_short_name
     */
    public function testNamespacedKeyWithEmptySiteShortName()
    {
        ee()->config->setItem('site_short_name', '');

        $driver = $this->makeDatabaseDriver();
        $result = $this->invokeProtectedMethod($driver, '_namespaced_key', ['my_key', \Cache::LOCAL_SCOPE]);

        // When site_short_name is empty, should just be the key
        $this->assertEquals('my_key', $result);
    }

    /**
     * Test that _namespaced_key handles both leading and trailing slashes
     */
    public function testNamespacedKeyTrimsBothSlashes()
    {
        ee()->config->setItem('site_short_name', 'test_site');

        $driver = $this->makeDatabaseDriver();
        $result = $this->invokeProtectedMethod($driver, '_namespaced_key', ['/my_key/', \Cache::LOCAL_SCOPE]);

        $this->assertEquals('test_site_my_key', $result);
    }
}

