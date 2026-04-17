<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

use ReflectionClass;

require_once SYSPATH . 'ee/ExpressionEngine/Tests/TestReflectionHelper.php';

/**
 * parseLayoutVariables tests for EE_Template class
 */
class EE_TemplateParseLayoutVariablesTest extends EE_TemplateTestBase
{
    /**
     * Test parseLayoutVariables handles simple variables
     */
    public function testParseLayoutVariablesHandlesSimpleVariables()
    {
        $layoutVars = [
            'title' => 'Test Title',
            'content' => 'Test Content'
        ];

        $template = '{layout:title} - {layout:content}';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        $this->assertEquals('Test Title - Test Content', $result);
    }

    /**
     * Test parseLayoutVariables handles array variables
     */
    public function testParseLayoutVariablesHandlesArrayVariables()
    {
        $layoutVars = [
            'titles' => ['Title 1', 'Title 2', 'Title 3']
        ];

        $template = '{layout:titles}';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        // For array variables used as single variables, should output the last item
        $this->assertEquals('Title 3', $result);
    }

    /**
     * Test parseLayoutVariables handles index-based access
     */
    public function testParseLayoutVariablesHandlesIndexAccess()
    {
        $layoutVars = [
            'titles' => ['First', 'Second', 'Third']
        ];

        $template = '{layout:titles index="1"}';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        $this->assertEquals('Second', $result);
    }

    /**
     * Test parseLayoutVariables handles undefined variables
     */
    public function testParseLayoutVariablesHandlesUndefinedVariables()
    {
        $layoutVars = [
            'title' => 'Test Title'
        ];

        $template = '{layout:title} - {layout:undefined}';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        $this->assertEquals('Test Title - ', $result);
    }

    public function testParseLayoutVariablesTreatsEmptyDeclaredPairAsEmptyArray()
    {
        $layoutVars = [
            'items' => '',
        ];

        $template = '{layout:items}{value}{/layout:items}';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        $this->assertSame('', $result);
    }

    /**
     * Test parseLayoutVariables sets up conditionals
     */
    public function testParseLayoutVariablesSetsUpConditionals()
    {
        $layoutVars = [
            'title' => 'Test Title',
            'show_sidebar' => true,
            'empty_array' => []
        ];

        $template = '{layout:title}';

        $this->template->parseLayoutVariables($template, $layoutVars);

        $reflection = new ReflectionClass($this->template);
        $conditionalsProperty = $reflection->getProperty('layout_conditionals');
        \TestReflectionHelper::makePropertyAccessible($conditionalsProperty);

        $conditionals = $conditionalsProperty->getValue($this->template);

        $this->assertEquals('Test Title', $conditionals['layout:title']);
        $this->assertTrue($conditionals['layout:show_sidebar']);
        $this->assertFalse($conditionals['layout:empty_array']);
    }

    /**
     * Test parseLayoutVariables with array values
     */
    public function testParseLayoutVariablesWithArrayValues()
    {
        $layoutVars = [
            'tags' => ['php', 'javascript', 'html']
        ];

        $template = '{layout:tags}';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        // Array values are converted to strings, typically the last item
        $this->assertIsString($result);
    }

    /**
     * Test parseLayoutVariables with special characters
     */
    public function testParseLayoutVariablesWithSpecialCharacters()
    {
        $layoutVars = [
            'special' => 'Test & < > " \' content'
        ];

        $template = '{layout:special}';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        $this->assertEquals('Test & < > " \' content', $result);
    }

    /**
     * Test parseLayoutVariables with numeric values
     */
    public function testParseLayoutVariablesWithNumericValues()
    {
        $layoutVars = [
            'count' => 42,
            'price' => 19.99,
            'zero' => 0
        ];

        $template = '{layout:count} - {layout:price} - {layout:zero}';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        $this->assertEquals('42 - 19.99 - 0', $result);
    }

    /**
     * Test parseLayoutVariables with boolean values
     */
    public function testParseLayoutVariablesWithBooleanValues()
    {
        $layoutVars = [
            'flag_true' => true,
            'flag_false' => false
        ];

        $template = '{layout:flag_true} - {layout:flag_false}';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        $this->assertEquals('1 - ', $result); // true becomes '1', false becomes ''
    }

    /**
     * Test parseLayoutVariables with null values
     */
    public function testParseLayoutVariablesWithNullValues()
    {
        $layoutVars = [
            'null_value' => null,
            'empty_string' => ''
        ];

        $template = '{layout:null_value} - {layout:empty_string}';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        $this->assertEquals(' - ', $result);
    }

    /**
     * Test parseLayoutVariables with complex template structures
     */
    public function testParseLayoutVariablesWithComplexTemplates()
    {
        $layoutVars = [
            'title' => 'Page Title',
            'meta_description' => 'Page description',
            'body_class' => 'home-page'
        ];

        $template = '<html><head><title>{layout:title}</title><meta name="description" content="{layout:meta_description}"></head><body class="{layout:body_class}">';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        $expected = '<html><head><title>Page Title</title><meta name="description" content="Page description"></head><body class="home-page">';
        $this->assertEquals($expected, $result);
    }

    public function testParseLayoutVariablesParsesModifiedIndexVariables()
    {
        $variablesParserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['parseModifiedVariables'])
            ->getMock();
        $variablesParserMock->expects($this->once())
            ->method('parseModifiedVariables')
            ->with(
                $this->stringContains("{layout:titles[1]:length index='1'}"),
                $this->callback(function($modifiedVars) {
                    return isset($modifiedVars['layout:titles[1]']) && $modifiedVars['layout:titles[1]'] === 'Beta';
                })
            )
            ->willReturn('Length:4');
        ee()->setMock('Variables/Parser', $variablesParserMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['_parse_var_pair', '_parse_var_single', 'log_item'])
            ->getMock();
        $templateMock->method('_parse_var_pair')->willReturnArgument(2);
        $templateMock->method('_parse_var_single')->willReturnArgument(2);
        $templateMock->method('log_item');

        $result = $templateMock->parseLayoutVariables(
            "Length:{layout:titles:length index='1'}",
            ['titles' => ['Alpha', 'Beta']]
        );

        $this->assertSame('Length:4', $result);
    }

    /**
     * Test parseLayoutVariables with malformed layout tags
     */
    public function testParseLayoutVariablesWithMalformedTags()
    {
        $layoutVars = [
            'title' => 'Test Title'
        ];

        $template = '{layout:title} {layout:incomplete} {layout:} {not_layout:title}';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        // Should replace valid layout tags and leave truly malformed ones
        $this->assertStringContainsString('Test Title', $result);
        // {layout:incomplete} gets replaced with empty string since 'incomplete' is undefined
        $this->assertStringContainsString('{layout:}', $result); // malformed - empty variable name
        $this->assertStringContainsString('{not_layout:title}', $result); // not a layout tag
    }

    /**
     * Test parseLayoutVariables with empty layout vars
     */
    public function testParseLayoutVariablesWithEmptyVars()
    {
        $layoutVars = [];

        $template = '{layout:title} - {layout:content}';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        $this->assertEquals(' - ', $result);
    }

    /**
     * Test parseLayoutVariables preserves layout conditionals after processing
     */
    public function testParseLayoutVariablesPreservesConditionals()
    {
        $layoutVars = [
            'title' => 'Test Title',
            'sidebar' => 'Sidebar Content',
            'footer' => ''
        ];

        $template = '{layout:title}';

        $this->template->parseLayoutVariables($template, $layoutVars);

        $reflection = new ReflectionClass($this->template);
        $conditionalsProperty = $reflection->getProperty('layout_conditionals');
        \TestReflectionHelper::makePropertyAccessible($conditionalsProperty);

        $conditionals = $conditionalsProperty->getValue($this->template);

        $this->assertArrayHasKey('layout:title', $conditionals);
        $this->assertArrayHasKey('layout:sidebar', $conditionals);
        $this->assertArrayHasKey('layout:footer', $conditionals);
        $this->assertTrue(empty($conditionals['layout:footer'])); // Empty string is falsy
    }
}
