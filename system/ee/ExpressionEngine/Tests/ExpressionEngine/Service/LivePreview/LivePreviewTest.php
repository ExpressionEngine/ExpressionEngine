<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\LivePreview;

use ExpressionEngine\Service\LivePreview\LivePreview;
use PHPUnit\Framework\TestCase;

class LivePreviewTest extends TestCase
{
    private $post;

    /**
     * Preserve request data before each test.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->post = $_POST;
        $_POST = ['title' => 'Preview title'];
    }

    /**
     * Restore request data and ExpressionEngine mocks after each test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $_POST = $this->post;
        ee()->resetMocks();
    }

    /**
     * Verify an invalid hook result keeps the original preview route.
     *
     * @dataProvider invalidHookResultProvider
     *
     * @param mixed $routeResult Invalid route returned by the extension hook
     * @return void
     */
    public function testInvalidHookResultKeepsOriginalPreviewRoute($routeResult)
    {
        $this->assertPreviewRoute($routeResult, 'original');
    }

    /**
     * Verify a valid hook result replaces the original preview route.
     *
     * @return void
     */
    public function testValidHookResultReplacesOriginalPreviewRoute()
    {
        $this->assertPreviewRoute([
            'uri' => 'alternate',
            'template_id' => null,
        ], 'alternate');
    }

    /**
     * Run live preview and assert the URI selected after the route hook.
     *
     * @param mixed  $routeResult Hook result returned by the extension
     * @param string $expectedUri URI expected by the template router
     * @return void
     */
    private function assertPreviewRoute($routeResult, $expectedUri)
    {
        $channel = (object) [
            'enable_versioning' => false,
            'preview_url' => '',
        ];

        $channelQuery = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['filter', 'first'])
            ->getMock();
        $channelQuery->method('filter')->willReturnSelf();
        $channelQuery->method('first')->willReturn($channel);

        $structure = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getAllCustomFields'])
            ->getMock();
        $structure->method('getAllCustomFields')->willReturn([]);

        $entry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['set', 'getModChannelResultsArray', 'getStructure', 'evaluateConditionalFields'])
            ->getMock();
        $entry->method('getModChannelResultsArray')->willReturn([]);
        $entry->method('getStructure')->willReturn($structure);
        $entry->method('evaluateConditionalFields')->willReturn([]);

        $model = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['get', 'make'])
            ->getMock();
        $model->method('get')->willReturn($channelQuery);
        $model->method('make')->willReturn($entry);

        $session = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['userdata'])
            ->getMock();
        $session->userdata = ['ip_address' => '127.0.0.1'];
        $session->method('userdata')->willReturn(1);

        $legacyApi = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['instantiate'])
            ->getMock();

        $livePreview = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['setEntryData'])
            ->getMock();

        $loader = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['library'])
            ->getMock();

        $functions = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['fetch_site_index'])
            ->getMock();
        $functions->method('fetch_site_index')->willReturn('https://example.com/');

        $extensions = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['active_hook', 'call'])
            ->getMock();
        $extensions->method('active_hook')->willReturn(true);
        $extensions->method('call')->willReturn($routeResult);

        $uri = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['_set_uri_string', '_explode_segments', '_reindex_segments'])
            ->getMock();
        $uri->expects($this->once())
            ->method('_set_uri_string')
            ->with($expectedUri);

        $core = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['loadSnippets'])
            ->getMock();

        $template = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['run_template_engine'])
            ->getMock();

        ee()->setMock('Model', $model);
        ee()->setMock('session', $session);
        ee()->setMock('legacy_api', $legacyApi);
        ee()->setMock('LivePreview', $livePreview);
        ee()->setMock('load', $loader);
        ee()->setMock('functions', $functions);
        ee()->setMock('extensions', $extensions);
        ee()->setMock('uri', $uri);
        ee()->setMock('core', $core);
        ee()->setMock('TMPL', $template);

        (new LivePreview($session))->preview(1, null, 'https://example.com/original');
    }

    /**
     * Provide invalid live-preview route hook results.
     *
     * @return array
     */
    public function invalidHookResultProvider()
    {
        return [
            'false' => [false],
            'null' => [null],
            'empty array' => [[]],
            'missing URI' => [['template_id' => 1]],
            'missing template ID' => [['uri' => 'alternate']],
        ];
    }
}
