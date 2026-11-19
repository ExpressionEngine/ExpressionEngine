<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Library\Resource;

use ExpressionEngine\Library\Resource\Request;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class RequestTest extends TestCase
{
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * @dataProvider nameProvider
     */
    public function testTemplateAndGroupNames($name, $expected)
    {
        $this->assertSame($expected, $this->invoke('isValidName', $name));
    }

    public function nameProvider()
    {
        return [
            ['styles', true],
            ['site.min', true],
            ['a-b_c.9', true],
            ['brand colors', true],
            ["caf\u{e9}", true],
            ['', false],
            ['.', false],
            ['..', false],
            ['styles/extra', false],
            ['styles\\extra', false],
            ['site:styles', false],
            ["styles\n", false],
            ["sty\tles", false],
            ["sty\xFFles", false],
        ];
    }

    /**
     * @dataProvider siteNameProvider
     */
    public function testSiteNames($name, $expected)
    {
        $this->assertSame($expected, $this->invoke('isValidSiteName', $name));
    }

    public function siteNameProvider()
    {
        return [
            ['', true],
            ['default_site', true],
            ['Site-2', true],
            ['my.site', false],
            ['my site', false],
            ['my/site', false],
            ['my:site', false],
            ["site\n", false],
            ['..', false],
        ];
    }

    public function testCacheKeyIsBuiltFromSiteGroupAndTemplate()
    {
        $this->mockSiteShortName('default_site');

        $this->assertSame('resource_cache/default_site/site/styles', $this->invoke('_cache_path', 'site/styles'));
        $this->assertSame('resource_cache/second_site/site/styles', $this->invoke('_cache_path', 'second_site:site/styles'));
        $this->assertSame('resource_cache/default_site/site/styles.min', $this->invoke('_cache_path', 'site/styles.min'));
    }

    public function testCacheKeyForAGroupCoversTheGroup()
    {
        $this->mockSiteShortName('default_site');

        $this->assertSame('resource_cache/default_site/site/', $this->invoke('_cache_path', 'site/'));
    }

    public function testCacheKeyIgnoresSpaceAroundNames()
    {
        $this->mockSiteShortName('default_site');

        $this->assertSame('resource_cache/default_site/site/styles', $this->invoke('_cache_path', 'site /styles '));
        $this->assertSame('resource_cache/second_site/site/styles', $this->invoke('_cache_path', ' second_site : site / styles'));
    }

    public function testCacheKeyWithoutAGroupCoversTheSite()
    {
        $this->mockSiteShortName('default_site');

        $this->assertSame('resource_cache/default_site/', $this->invoke('_cache_path', '/'));
        $this->assertSame('resource_cache/second_site/', $this->invoke('_cache_path', 'second_site:/'));
    }

    /**
     * @dataProvider unnamedResourceProvider
     */
    public function testResourcesThatAreNotValidlyNamedHaveNoCacheKey($resource)
    {
        $this->mockSiteShortName('default_site');

        $this->assertNull($this->invoke('_cache_path', $resource));
    }

    public function unnamedResourceProvider()
    {
        return [
            ['site/styles/extra'],
            ['second_site:site:more/styles'],
            ['my.site:site/styles'],
            ['site/..'],
            ['../styles'],
            ['/styles'],
        ];
    }

    public function testResourceWithoutAGroupIsAnError()
    {
        $this->expectException(\Exception::class);

        $this->invoke('_cache_path', 'styles');
    }

    /**
     * Call a method of the request class without running its constructor.
     *
     * @param string $method
     * @param mixed ...$arguments
     * @return mixed
     */
    private function invoke($method, ...$arguments)
    {
        $class = new ReflectionClass(Request::class);
        $reflected = $class->getMethod($method);
        \TestReflectionHelper::makeAccessible($reflected);

        return $reflected->invokeArgs($class->newInstanceWithoutConstructor(), $arguments);
    }

    /**
     * Provide the current site's short name.
     *
     * @param string $shortName
     * @return void
     */
    private function mockSiteShortName($shortName)
    {
        $config = $this->getMockBuilder(stdClass::class)->addMethods(array('item'))->getMock();
        $config->method('item')->willReturnCallback(function ($key) use ($shortName) {
            return $key === 'site_short_name' ? $shortName : null;
        });
        ee()->setMock('config', $config);
    }
}
