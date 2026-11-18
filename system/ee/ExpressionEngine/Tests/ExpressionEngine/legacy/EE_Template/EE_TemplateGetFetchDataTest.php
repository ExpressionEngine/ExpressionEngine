<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once __DIR__ . '/test_helpers.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateGetFetchDataTest extends EE_TemplateTestBase
{
    private $method;

    protected function setUp(): void
    {
        parent::setUp();

        if (!function_exists('\\strip_quotes')) {
            eval('function strip_quotes($str) { return str_replace(array("\"", "\'"), "", $str); }');
        }

        $this->method = new \ReflectionMethod(\EE_Template::class, '_get_fetch_data');
        \TestReflectionHelper::makeAccessible($this->method);
        ee()->config->setItem('site_id', 1);
        ee()->config->setItem('multiple_sites_enabled', 'n');

        $sitesProperty = new \ReflectionProperty(\EE_Template::class, 'sites');
        \TestReflectionHelper::makeAccessible($sitesProperty);
        $sitesProperty->setValue($this->template, []);
    }

    public function testGetFetchDataReturnsNullWhenPathMissingSlash()
    {
        $this->assertNull($this->invokeGetFetchData('group'));
    }

    public function testGetFetchDataReturnsNullWhenPathHasTooManySegments()
    {
        $this->assertNull($this->invokeGetFetchData('group/template/extra'));
    }

    public function testGetFetchDataParsesGroupTemplateAndUsesDefaultSiteId()
    {
        ee()->config->setItem('site_id', 42);

        $this->assertSame(['news', 'index', 42], $this->invokeGetFetchData('"news/index"'));
    }

    public function testGetFetchDataResolvesSitePrefixWhenMultipleSitesEnabled()
    {
        ee()->config->setItem('site_id', 1);
        ee()->config->setItem('multiple_sites_enabled', 'y');

        $dbMock = new \FakeDb();
        $dbMock->setRows([
            ['site_id' => 1, 'site_name' => 'main'],
            ['site_id' => 2, 'site_name' => 'site_two'],
        ]);
        ee()->setMock('db', $dbMock);

        $this->assertSame(['blog', 'entry', 2], $this->invokeGetFetchData('site_two:blog/entry'));
    }

    public function testGetFetchDataFallsBackToConfiguredSiteIdWhenPrefixMissing()
    {
        ee()->config->setItem('site_id', 7);
        ee()->config->setItem('multiple_sites_enabled', 'y');

        $dbMock = new \FakeDb();
        $dbMock->setRows([
            ['site_id' => 7, 'site_name' => 'main'],
            ['site_id' => 8, 'site_name' => 'site_two'],
        ]);
        ee()->setMock('db', $dbMock);

        $this->assertSame(['docs', 'index', 7], $this->invokeGetFetchData('missing_site:docs/index'));
    }

    public function testGetFetchDataStripsPrefixWhenMultiSiteDisabled()
    {
        ee()->config->setItem('site_id', 11);
        ee()->config->setItem('multiple_sites_enabled', 'n');

        $this->assertSame(['blog', 'index', 11], $this->invokeGetFetchData('site_name:blog/index'));
    }

    private function invokeGetFetchData($path)
    {
        return $this->method->invoke($this->template, $path);
    }
}
