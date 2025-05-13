<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Addons\Rte;

// because of lowe case the class does not get autoloaded into PHPUnit
require_once SYSPATH . 'ee/ExpressionEngine/Addons/rte/RteHelper.php';

use Mockery as m;
use PHPUnit\Framework\TestCase;
use ExpressionEngine\Addons\Rte\RteHelper;

class RteTest extends TestCase
{
    protected $helper;

    public function setUp(): void
    {
        $this->helper = new RteHelperMock();
    }

    public function tearDown(): void
    {
        //
    }

    /**
     * @dataProvider pageUrlsDataProvider
     */
    public function testReplacePageUrls($url, $expected)
    {
        ee()->config->setItem('site_url', 'http://example.com');
        $this->helper::replacePageUrls($url);
        if ($expected !== false) {
            $this->assertEquals('"' . $expected . '"', $url);
        } else {
            $this->assertNotContains('{page_', [$url]);
        }
    }

    public function pageUrlsDataProvider()
    {
        return array(
            array('"/services"', '{page_111}'),
            array('"/services/"', '{page_111}'),
            array('"/services#anchor"', '{page_111}#anchor'),
            array('"/services/#anchor"', '{page_111}#anchor'),
            array('"/services?foo=bar"', '{page_111}?foo=bar'),
            array('"/services/?foo=bar"', '{page_111}?foo=bar'),
            array('"/services?foo=bar#anchor"', '{page_111}?foo=bar#anchor'),
            array('"/services/?foo=bar#anchor"', '{page_111}?foo=bar#anchor'),
            array('"javascript:goto("/services")"', 'javascript:goto("{page_111}")'),
            array('"javascript:goto("/services/")"', 'javascript:goto("{page_111}")'),
            array('"//example.com/services"', '{page_111}'),
            array('"//example.com/?/services"', '{page_111}'),
            array('"//example.com/?/services&foo=bar"', '{page_111}&foo=bar'),
            array('"//example.com/?/services#anchor"', '{page_111}#anchor'),
            array('"//example.com/?/services/"', '{page_111}'),
            array('"http://example.com/services"', '{page_111}'),
            array('"https://example.com/services"', '{page_111}'),
            array('"http://example.com/services/"', '{page_111}'),
            array('"http://example.com/services#anchor"', '{page_111}#anchor'),
            array('"http://example.com/services/#anchor"', '{page_111}#anchor'),
            array('"http://example.com/services?foo=bar"', '{page_111}?foo=bar'),
            array('"http://example.com/services/?foo=bar"', '{page_111}?foo=bar'),
            array('"http://example.com/services?foo=bar#anchor"', '{page_111}?foo=bar#anchor'),
            array('"http://example.com/services/?foo=bar#anchor"', '{page_111}?foo=bar#anchor'),
            array('"javascript:goto("http://example.com/services")"', 'javascript:goto("{page_111}")'),
            array('"javascript:goto("http://example.com/services/")"', 'javascript:goto("{page_111}")'),



            array('"/services/service"', '{page_222}'),
            array('"/services/service/"', '{page_222}'),
            array('"/services/service#anchor"', '{page_222}#anchor'),
            array('"/services/service/#anchor"', '{page_222}#anchor'),
            array('"/services/service?foo=bar"', '{page_222}?foo=bar'),
            array('"/services/service/?foo=bar"', '{page_222}?foo=bar'),
            array('"/services/service?foo=bar#anchor"', '{page_222}?foo=bar#anchor'),
            array('"/services/service/?foo=bar#anchor"', '{page_222}?foo=bar#anchor'),
            array('"javascript:goto("/services/service")"', 'javascript:goto("{page_222}")'),
            array('"javascript:goto("/services/service/")"', 'javascript:goto("{page_222}")'),
            array('"http://example.com/services/service"', '{page_222}'),
            array('"http://example.com/services/service/"', '{page_222}'),
            array('"http://example.com/services/service#anchor"', '{page_222}#anchor'),
            array('"http://example.com/services/service/#anchor"', '{page_222}#anchor'),
            array('"http://example.com/services/service?foo=bar"', '{page_222}?foo=bar'),
            array('"http://example.com/services/service/?foo=bar"', '{page_222}?foo=bar'),
            array('"http://example.com/services/service?foo=bar#anchor"', '{page_222}?foo=bar#anchor'),
            array('"http://example.com/services/service/?foo=bar#anchor"', '{page_222}?foo=bar#anchor'),
            array('"javascript:goto("http://example.com/services/service")"', 'javascript:goto("{page_222}")'),
            array('"javascript:goto("http://example.com/services/service/")"', 'javascript:goto("{page_222}")'),

            array('"/"', '{page_1}'),
            array('"//"', '{page_1}'),
            array('"https://example.com/"', '{page_1}'),
            array('"https://example.com//"', '{page_1}'),

            array('"/ "', false),

            array('"/location/services"', false),
            array('"/location/services/"', false),
            array('"/services/another-page"', false),
            array('"/services/another-page#anchor"', false),
            array('"/services/another-page/#anchor"', false),
            array('"/services/another-page?foo=bar"', false),
            array('"/services/another-page/?foo=bar"', false),
            array('"/services/another-page?foo=bar#anchor"', false),
            array('"/services/another-page/?foo=bar#anchor"', false),

            array('"/location/services/service"', false),
            array('"/location/services/service/"', false),
            array('"/services/service/another-page"', false),
            array('"/services/service/another-page#anchor"', false),
            array('"/services/service/another-page/#anchor"', false),
            array('"/services/service/another-page?foo=bar"', false),
            array('"/services/service/another-page/?foo=bar"', false),
            array('"/services/service/another-page?foo=bar#anchor"', false),
            array('"/services/service/another-page/?foo=bar#anchor"', false),

            array('"http://example.com/location/services"', false),
            array('"http://example.com/location/services/"', false),
            array('"http://example.com/services/another-page"', false),
            array('"http://example.com/services/another-page#anchor"', false),
            array('"http://example.com/services/another-page/#anchor"', false),
            array('"http://example.com/services/another-page?foo=bar"', false),
            array('"http://example.com/services/another-page/?foo=bar"', false),
            array('"http://example.com/services/another-page?foo=bar#anchor"', false),
            array('"http://example.com/services/another-page/?foo=bar#anchor"', false),

            array('"http://example.com/location/services/service"', false),
            array('"http://example.com/location/services/service/"', false),
            array('"http://example.com/services/service/another-page"', false),
            array('"http://example.com/services/service/another-page#anchor"', false),
            array('"http://example.com/services/service/another-page/#anchor"', false),
            array('"http://example.com/services/service/another-page?foo=bar"', false),
            array('"http://example.com/services/service/another-page/?foo=bar"', false),
            array('"http://example.com/services/service/another-page?foo=bar#anchor"', false),
            array('"http://example.com/services/service/another-page/?foo=bar#anchor"', false),

            array('"eeharbor.com/services "', false),
            array('"eeharbor.com/services/"', false),
            array('"eeharbor.com/services#anchor"', false),
            array('"eeharbor.com/services/#anchor"', false),
            array('"eeharbor.com/services?foo=bar"', false),
            array('"eeharbor.com/services/?foo=bar"', false),
            array('"eeharbor.com/services?foo=bar#anchor"', false),
            array('"eeharbor.com/services/?foo=bar#anchor"', false),

            array('"http://eeharbor.com/services "', false),
            array('"http://eeharbor.com/services/"', false),
            array('"http://eeharbor.com/services#anchor"', false),
            array('"http://eeharbor.com/services/#anchor"', false),
            array('"http://eeharbor.com/services?foo=bar"', false),
            array('"http://eeharbor.com/services/?foo=bar"', false),
            array('"http://eeharbor.com/services?foo=bar#anchor"', false),
            array('"http://eeharbor.com/services/?foo=bar#anchor""', false),
        );
    }
}

/**
 * Mock RteHelper class to expose protected methods
 */
class RteHelperMock extends RteHelper
{
    protected static $_pageTags = array(
        0 => array(
            '_222' => '{page_222}',
            '_111' => '{page_111}',
            '_1'   => '{page_1}',
        ),
        1 => array(
            '_222' => '/services/service/',
            '_111' => '/services/',
            '_1'   => '/',
        ),
    );
}

// EOF
