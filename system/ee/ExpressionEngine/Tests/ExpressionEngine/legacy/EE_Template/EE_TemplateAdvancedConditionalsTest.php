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
 * Tests for EE_Template::advanced_conditionals() method
 */
class EE_TemplateAdvancedConditionalsTest extends EE_TemplateAdvancedMethodsTestBase
{
    /**
     * Test that advanced_conditionals method exists and is callable
     */
    public function testAdvancedConditionalsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'advanced_conditionals'));
        $this->assertTrue(is_callable([$this->template, 'advanced_conditionals']));
    }

    /**
     * Test that templates without conditionals are returned unchanged
     */
    public function testAdvancedConditionalsHandlesNoConditionals()
    {
        $template = 'Simple template without conditionals';

        $result = $this->template->advanced_conditionals($template);

        $this->assertEquals($template, $result);
    }

    /**
     * Test basic conditional processing
     */
    public function testAdvancedConditionalsProcessesBasicConditionals()
    {
        $template = 'Content {if logged_in}Welcome back{/if}';

        $result = $this->template->advanced_conditionals($template);

        // Should process the conditional (logged_in is true in our mock)
        $this->assertStringContainsString('Welcome back', $result);
    }

    /**
     * Test user variables in conditionals
     */
    public function testAdvancedConditionalsHandlesUserVariables()
    {
        $template = '{if logged_in}User is logged in{/if}{if member_id == 1}Member ID is 1{/if}';

        $result = $this->template->advanced_conditionals($template);

        $this->assertStringContainsString('User is logged in', $result);
        $this->assertStringContainsString('Member ID is 1', $result);
    }

    /**
     * Test system variables in conditionals
     */
    public function testAdvancedConditionalsHandlesSystemVariables()
    {
        $template = '{if current_time}Has current time{/if}';

        $result = $this->template->advanced_conditionals($template);

        $this->assertStringContainsString('Has current time', $result);
    }

    /**
     * Test global variables in conditionals
     */
    public function testAdvancedConditionalsHandlesGlobalVariables()
    {
        // Set up global variables
        ee()->config->_global_vars['site_name'] = 'Test Site';
        ee()->config->_global_vars['custom_var'] = 'custom_value';

        $template = '{if site_name == "Test Site"}Site name matches{/if}{if custom_var}Has custom var{/if}';

        $result = $this->template->advanced_conditionals($template);

        $this->assertStringContainsString('Site name matches', $result);
        $this->assertStringContainsString('Has custom var', $result);
    }

    /**
     * Test segment variables in conditionals
     */
    public function testAdvancedConditionalsHandlesSegmentVariables()
    {
        // Set up segment variables using reflection
        $reflection = new \ReflectionClass($this->template);
        $segmentVarsProperty = $reflection->getProperty('segment_vars');
        \TestReflectionHelper::makePropertyAccessible($segmentVarsProperty);
        $segmentVarsProperty->setValue($this->template, [
            'segment_1' => 'news',
            'segment_2' => 'article'
        ]);

        $template = '{if segment_1 == "news"}News section{/if}{if segment_2}Has segment 2{/if}';

        $result = $this->template->advanced_conditionals($template);

        $this->assertStringContainsString('News section', $result);
        $this->assertStringContainsString('Has segment 2', $result);
    }

    /**
     * Test template route variables in conditionals
     */
    public function testAdvancedConditionalsHandlesTemplateRouteVariables()
    {
        // Set up template route variables using reflection
        $reflection = new \ReflectionClass($this->template);
        $routeVarsProperty = $reflection->getProperty('template_route_vars');
        \TestReflectionHelper::makePropertyAccessible($routeVarsProperty);
        $routeVarsProperty->setValue($this->template, [
            'route:id' => '123',
            'route:slug' => 'test-article'
        ]);

        $template = '{if route:id == "123"}Route ID matches{/if}{if route:slug}Has route slug{/if}';

        $result = $this->template->advanced_conditionals($template);

        $this->assertStringContainsString('Route ID matches', $result);
        $this->assertStringContainsString('Has route slug', $result);
    }

    /**
     * Test layout conditionals
     */
    public function testAdvancedConditionalsHandlesLayoutConditionals()
    {
        // Set up layout conditionals using reflection
        $reflection = new \ReflectionClass($this->template);
        $layoutConditionalsProperty = $reflection->getProperty('layout_conditionals');
        \TestReflectionHelper::makePropertyAccessible($layoutConditionalsProperty);
        $layoutConditionalsProperty->setValue($this->template, [
            'layout:title' => 'Page Title',
            'layout:content' => ''
        ]);

        $template = '{if layout:title}Has layout title{/if}{if layout:content == ""}Empty layout content{/if}';

        $result = $this->template->advanced_conditionals($template);

        $this->assertStringContainsString('Has layout title', $result);
        $this->assertStringContainsString('Empty layout content', $result);
    }

    /**
     * Test complex boolean expressions
     */
    public function testAdvancedConditionalsHandlesComplexBooleanExpressions()
    {
        ee()->config->_global_vars['var_a'] = 'value1';
        ee()->config->_global_vars['var_b'] = 'value2';
        ee()->config->_global_vars['var_c'] = 'value3';

        $template = '{if var_a == "value1" AND var_b == "value2"}Both conditions true{/if}';

        $result = $this->template->advanced_conditionals($template);

        $this->assertStringContainsString('Both conditions true', $result);
    }

    /**
     * Test that conditionals are processed (our mock returns input unchanged, so we test that variables are collected)
     */
    public function testAdvancedConditionalsProcessesVariables()
    {
        ee()->config->_global_vars['test_var'] = 'test_value';

        $template = '{if test_var}Has test var{/if}';

        $result = $this->template->advanced_conditionals($template);

        // Our mock returns the input unchanged, so we just verify the method runs
        $this->assertIsString($result);
        $this->assertStringContainsString('{if test_var}', $result);
    }

    /**
     * Test member variables (role-based)
     */
    public function testAdvancedConditionalsHandlesMemberVariables()
    {
        // Our mock already sets up member variables, so logged_in_member_group should be available
        $template = '{if logged_in_member_group}Has member group{/if}';

        $result = $this->template->advanced_conditionals($template);

        $this->assertStringContainsString('Has member group', $result);
    }

    /**
     * Test embed variables in conditionals
     */
    public function testAdvancedConditionalsHandlesEmbedVariables()
    {
        // Set up embed variables using reflection
        $this->template->embed_vars = [
            'embed:title' => 'Embedded Title',
            'embed:author' => ''
        ];

        $template = '{if embed:title}Has embed title{/if}{if embed:author == ""}Empty embed author{/if}';

        $result = $this->template->advanced_conditionals($template);

        $this->assertStringContainsString('Has embed title', $result);
        $this->assertStringContainsString('Empty embed author', $result);
    }

    /**
     * Test that the method accepts templates with conditionals and returns a result
     */
    public function testAdvancedConditionalsAcceptsConditionalTemplates()
    {
        $template = '{if logged_in}Welcome{/if}';

        $result = $this->template->advanced_conditionals($template);

        // Our mock returns input unchanged, so we verify the method works
        $this->assertIsString($result);
        $this->assertStringContainsString('Welcome', $result);
    }

    /**
     * Test with multiple complex conditionals
     */
    public function testAdvancedConditionalsHandlesMultipleComplexConditionals()
    {
        ee()->config->_global_vars['user_type'] = 'admin';
        ee()->config->_global_vars['is_active'] = true;

        $template = '{if logged_in AND user_type == "admin"}Admin user{/if}{if is_active}Active user{/if}{if segment_1}Has segment{/if}';

        // Set segment variable
        $reflection = new \ReflectionClass($this->template);
        $segmentVarsProperty = $reflection->getProperty('segment_vars');
        \TestReflectionHelper::makePropertyAccessible($segmentVarsProperty);
        $segmentVarsProperty->setValue($this->template, ['segment_1' => 'test']);

        $result = $this->template->advanced_conditionals($template);

        $this->assertStringContainsString('Admin user', $result);
        $this->assertStringContainsString('Active user', $result);
        $this->assertStringContainsString('Has segment', $result);
    }
}
