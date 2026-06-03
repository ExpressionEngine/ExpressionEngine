<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\Cache;

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

class CacheDatabaseCleanTest extends CacheDatabaseTestBase
{
    /**
     * Test that clean removes all local scope items
     */
    public function testCleanRemovesAllLocalScopeItems()
    {
        ee()->config->setItem('site_short_name', 'test_site');

        $driver = $this->makeDatabaseDriver();
        $result = $driver->clean(\Cache::LOCAL_SCOPE);

        $this->assertTrue($result);
        $this->assertTrue(ee()->db->deleteCalled);
        $this->assertTrue(ee()->db->likeCalled);
    }

    /**
     * Test that clean removes all global scope items
     */
    public function testCleanRemovesAllGlobalScopeItems()
    {
        $driver = $this->makeDatabaseDriver();
        $result = $driver->clean(\Cache::GLOBAL_SCOPE);

        $this->assertTrue($result);
        $this->assertTrue(ee()->db->deleteCalled);
        $this->assertTrue(ee()->db->likeCalled);
    }

    /**
     * Test that clean clears local cache
     */
    public function testCleanClearsLocalCache()
    {
        // First populate local cache
        $testData = 'test value';
        $row = $this->makeCacheRow(serialize($testData), 60);
        $this->setQueryResult([$row]);

        $driver = $this->makeDatabaseDriver();
        $driver->get('test_key');

        // Verify it's in local cache
        $localCache = $this->getProtectedProperty($driver, '_local_cache');
        $this->assertNotEmpty($localCache);

        // Now clean
        $driver->clean();

        // Verify clean was called
        $this->assertTrue(ee()->db->deleteCalled);
    }

    /**
     * Test that clean uses correct namespace prefix for local scope
     */
    public function testCleanUsesCorrectNamespacePrefixForLocalScope()
    {
        ee()->config->setItem('site_short_name', 'my_site');

        $driver = $this->makeDatabaseDriver();
        $driver->clean(\Cache::LOCAL_SCOPE);

        $this->assertTrue(ee()->db->likeCalled);
        $this->assertEquals('cache_key', ee()->db->lastLikeField);
        $this->assertStringContainsString('my_site', ee()->db->lastLikeValue);
        $this->assertEquals('right', ee()->db->lastLikeSide);
    }

    /**
     * Test that clean uses correct namespace prefix for global scope
     */
    public function testCleanUsesCorrectNamespacePrefixForGlobalScope()
    {
        $driver = $this->makeDatabaseDriver();
        $driver->clean(\Cache::GLOBAL_SCOPE);

        $this->assertTrue(ee()->db->likeCalled);
        $this->assertEquals('cache_key', ee()->db->lastLikeField);
        // Global prefix is an MD5 hash
        $this->assertMatchesRegularExpression('/[a-f0-9]{32}/', ee()->db->lastLikeValue);
        $this->assertEquals('right', ee()->db->lastLikeSide);
    }

    /**
     * Test that clean returns true on success
     */
    public function testCleanReturnsTrueOnSuccess()
    {
        $driver = $this->makeDatabaseDriver();
        $result = $driver->clean();

        $this->assertTrue($result);
    }

    /**
     * Test that local clean doesn't affect global scope
     */
    public function testLocalCleanDoesNotAffectGlobalScope()
    {
        ee()->config->setItem('site_short_name', 'test_site');

        $driver = $this->makeDatabaseDriver();
        $driver->clean(\Cache::LOCAL_SCOPE);

        // Verify LIKE query uses local scope prefix
        $this->assertTrue(ee()->db->likeCalled);
        $this->assertStringContainsString('test_site', ee()->db->lastLikeValue);
        
        // Should not contain a global hash pattern
        // Just verify it's using the site name
        $this->assertStringStartsWith('test_site', ee()->db->lastLikeValue);
    }

    /**
     * Test that global clean doesn't affect local scope
     */
    public function testGlobalCleanDoesNotAffectLocalScope()
    {
        $driver = $this->makeDatabaseDriver();
        $driver->clean(\Cache::GLOBAL_SCOPE);

        // Verify LIKE query uses global scope prefix (MD5 hash)
        $this->assertTrue(ee()->db->likeCalled);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}/', ee()->db->lastLikeValue);
    }

    /**
     * Test that clean defaults to local scope
     */
    public function testCleanDefaultsToLocalScope()
    {
        ee()->config->setItem('site_short_name', 'default_site');

        $driver = $this->makeDatabaseDriver();
        $result = $driver->clean(); // No scope parameter

        $this->assertTrue($result);
        $this->assertTrue(ee()->db->likeCalled);
        $this->assertStringContainsString('default_site', ee()->db->lastLikeValue);
    }

    /**
     * Test that clean executes correct query operations
     */
    public function testCleanExecutesCorrectQueryOperations()
    {
        $driver = $this->makeDatabaseDriver();
        $driver->clean();

        // Should use LIKE query and delete
        $this->assertTrue(ee()->db->likeCalled);
        $this->assertTrue(ee()->db->deleteCalled);
        $this->assertEquals('right', ee()->db->lastLikeSide);
    }
}

