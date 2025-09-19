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

use ReflectionClass;

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
        $conditionalsProperty->setAccessible(true);

        $conditionals = $conditionalsProperty->getValue($this->template);

        $this->assertEquals('Test Title', $conditionals['layout:title']);
        $this->assertTrue($conditionals['layout:show_sidebar']);
        $this->assertFalse($conditionals['layout:empty_array']);
    }
}
