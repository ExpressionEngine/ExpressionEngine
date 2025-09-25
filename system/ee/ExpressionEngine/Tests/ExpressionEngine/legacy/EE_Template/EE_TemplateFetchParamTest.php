<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

/**
 * Tests for EE_Template::fetch_param() method
 */
class EE_TemplateFetchParamTest extends EE_TemplateAdvancedMethodsTestBase
{
    /**
     * Test that fetch_param method exists and is callable
     */
    public function testFetchParamMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'fetch_param'));
        $this->assertTrue(is_callable([$this->template, 'fetch_param']));
    }

    /**
     * Test basic parameter retrieval
     */
    public function testFetchParamRetrievesBasicParameter()
    {
        $this->setupTemplateWithParams(['channel' => 'news', 'limit' => '10']);

        $result = $this->template->fetch_param('channel');
        $this->assertEquals('news', $result);

        $result = $this->template->fetch_param('limit');
        $this->assertEquals('10', $result);
    }

    /**
     * Test default value handling when parameter doesn't exist
     */
    public function testFetchParamReturnsDefaultWhenParameterMissing()
    {
        $this->setupTemplateWithParams(['channel' => 'news']);

        $result = $this->template->fetch_param('nonexistent', 'default_value');
        $this->assertEquals('default_value', $result);

        $result = $this->template->fetch_param('missing');
        $this->assertFalse($result);
    }

    /**
     * Test boolean value normalization ('y' -> 'yes', 'n' -> 'no')
     */
    public function testFetchParamNormalizesBooleanValues()
    {
        $this->setupTemplateWithParams([
            'y_param' => 'y',
            'n_param' => 'n',
            'on_param' => 'on',
            'off_param' => 'off',
            'yes_param' => 'yes',
            'no_param' => 'no'
        ]);

        $this->assertEquals('yes', $this->template->fetch_param('y_param'));
        $this->assertEquals('no', $this->template->fetch_param('n_param'));
        $this->assertEquals('yes', $this->template->fetch_param('on_param'));
        $this->assertEquals('no', $this->template->fetch_param('off_param'));
        $this->assertEquals('yes', $this->template->fetch_param('yes_param'));
        $this->assertEquals('no', $this->template->fetch_param('no_param'));
    }

    /**
     * Test that other values are returned unchanged
     */
    public function testFetchParamReturnsOtherValuesUnchanged()
    {
        $this->setupTemplateWithParams([
            'string_param' => 'some_value',
            'numeric_param' => '123',
            'empty_param' => '',
            'null_param' => null
        ]);

        $this->assertEquals('some_value', $this->template->fetch_param('string_param'));
        $this->assertEquals('123', $this->template->fetch_param('numeric_param'));
        $this->assertEquals('', $this->template->fetch_param('empty_param'));
        $this->assertEquals(null, $this->template->fetch_param('null_param'));
    }

    /**
     * Test that ignored parameters are still returned normally
     */
    public function testFetchParamHandlesIgnoredParameters()
    {
        // The ignore_fetch array is set in setupTemplateWithParams
        $this->setupTemplateWithParams(['url_title' => 'some-title', 'channel' => 'news']);

        // url_title is in ignore_fetch, but should still be returned
        $result = $this->template->fetch_param('url_title');
        $this->assertEquals('some-title', $result);

        // Other parameters should work normally
        $result = $this->template->fetch_param('channel');
        $this->assertEquals('news', $result);
    }

    /**
     * Test parameter name case sensitivity
     */
    public function testFetchParamIsCaseSensitive()
    {
        $this->setupTemplateWithParams(['Channel' => 'news', 'channel' => 'sports']);

        $result = $this->template->fetch_param('Channel');
        $this->assertEquals('news', $result);

        $result = $this->template->fetch_param('channel');
        $this->assertEquals('sports', $result);

        // Non-existent case variation should return default
        $result = $this->template->fetch_param('CHANNEL', 'default');
        $this->assertEquals('default', $result);
    }

    /**
     * Test with special characters in parameter names
     */
    public function testFetchParamHandlesSpecialCharactersInNames()
    {
        $this->setupTemplateWithParams([
            'param-with-dashes' => 'dash-value',
            'param_with_underscores' => 'underscore-value',
            'param.with.dots' => 'dot-value'
        ]);

        $this->assertEquals('dash-value', $this->template->fetch_param('param-with-dashes'));
        $this->assertEquals('underscore-value', $this->template->fetch_param('param_with_underscores'));
        $this->assertEquals('dot-value', $this->template->fetch_param('param.with.dots'));
    }

    /**
     * Test with array values
     */
    public function testFetchParamHandlesArrayValues()
    {
        $this->setupTemplateWithParams([
            'array_param' => ['value1', 'value2'],
            'empty_array' => []
        ]);

        $result = $this->template->fetch_param('array_param');
        $this->assertEquals(['value1', 'value2'], $result);

        $result = $this->template->fetch_param('empty_array');
        $this->assertEquals([], $result);
    }
}

