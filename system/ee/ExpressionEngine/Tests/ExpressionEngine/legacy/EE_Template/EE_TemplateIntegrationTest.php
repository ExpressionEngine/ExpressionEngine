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
 * Integration tests for EE_Template class - testing component interactions
 */
class EE_TemplateIntegrationTest extends EE_TemplateTestBase
{
    /**
     * Test that multiple template methods can be called in sequence
     */
    public function testTemplateMethodsCanBeCalledTogether()
    {
        // Test that various template methods exist and are callable
        $this->assertTrue(method_exists($this->template, 'parse'));
        $this->assertTrue(method_exists($this->template, 'parse_variables'));
        $this->assertTrue(method_exists($this->template, 'advanced_conditionals'));
        $this->assertTrue(method_exists($this->template, 'parse_date_variables'));

        // Test simple method calls
        $result1 = $this->template->parse_variables('test', []);
        $result2 = $this->template->advanced_conditionals('test');
        $result3 = $this->template->parse_date_variables('test', []);

        // Should all return strings or expected types
        $this->assertIsString($result1);
        $this->assertIsString($result2);
        $this->assertIsString($result3);
    }

    /**
     * Test variable processing with conditional logic
     */
    public function testVariableProcessingWithConditionalLogic()
    {
        // Test that methods can be called in sequence
        $template = '{if test}yes{/if}';
        $vars = ['test' => true];

        $conditionalResult = $this->template->simple_conditionals($template, $vars);

        // Should process without crashing
        $this->assertIsString($conditionalResult);
    }

    /**
     * Test date and variable processing together
     */
    public function testDateAndVariableProcessingTogether()
    {
        // Mock Variables/Parser for date formatting
        $variablesParserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['parseTagParameters'])
            ->getMock();
        $variablesParserMock->method('parseTagParameters')
            ->willReturn(['format' => '%Y-%m-%d']);
        ee()->setMock('Variables/Parser', $variablesParserMock);

        $template = '{date format="%Y-%m-%d"}';
        $dates = ['date' => strtotime('2023-01-01')];

        // Test date parsing
        $dateResult = $this->template->parse_date_variables($template, $dates);

        // Test variable parsing
        $varResult = $this->template->parse_variables('{test}', [['test' => 'value']]);

        // Both should work
        $this->assertIsString($dateResult);
        $this->assertIsString($varResult);
        $this->assertStringContainsString('2023-01-01', $dateResult);
        $this->assertStringContainsString('value', $varResult);
    }

    /**
     * Test method coexistence and basic functionality
     */
    public function testTemplateMethodsCoexist()
    {
        // Test that various parsing methods can coexist
        $conditionalResult = $this->template->simple_conditionals('{if test}yes{/if}', ['test' => true]);
        $emailResult = $this->template->parse_encode_email('{encode="test@example.com"}');

        $this->assertIsString($conditionalResult);
        $this->assertIsString($emailResult);
    }

}
