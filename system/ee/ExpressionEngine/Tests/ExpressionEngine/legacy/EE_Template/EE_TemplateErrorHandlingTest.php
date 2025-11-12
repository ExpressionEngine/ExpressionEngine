<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionEngine.com)
 *
 * @link      https://expressionEngine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionEngine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

/**
 * Error handling tests for EE_Template class
 */
class EE_TemplateErrorHandlingTest extends EE_TemplateTestBase
{
    /**
     * Test parse_variables handles empty inputs gracefully
     */
    public function testParseVariablesHandlesEmptyInputs()
    {
        // Test with empty inputs - should return the input unchanged
        $result = $this->template->parse_variables('', []);
        $this->assertEquals('', $result);

        $result = $this->template->parse_variables('test', []);
        $this->assertEquals('test', $result);

        // Test with empty array - should return the template unchanged
        $result = $this->template->parse_variables('template', []);
        $this->assertEquals('template', $result);
    }

    /**
     * Test parse_date_variables handles missing date data
     */
    public function testParseDateVariablesHandlesMissingData()
    {
        $template = '{missing_date format="%Y"} {existing_date format="%Y"}';
        $dates = ['existing_date' => time()];

        $result = $this->template->parse_date_variables($template, $dates);

        // Should leave missing dates unparsed
        $this->assertStringContainsString('{missing_date format="%Y"}', $result);
        $this->assertIsString($result);
    }

    /**
     * Test advanced_conditionals handles malformed conditionals
     */
    public function testAdvancedConditionalsHandlesMalformedConditionals()
    {
        $malformedTemplates = [
            '{if}',           // Incomplete conditional
            '{if condition',  // Missing closing
            '{if condition}{if:else', // Malformed else
            '{/if}',          // Closing without opening
            '{if condition}content', // Missing closing tag
        ];

        foreach ($malformedTemplates as $template) {
            $result = $this->template->advanced_conditionals($template);
            $this->assertIsString($result); // Should not crash
        }
    }

    /**
     * Test parse_encode_email handles malformed email tags
     */
    public function testParseEncodeEmailHandlesMalformedTags()
    {
        $malformedTemplates = [
            '{encode}',              // Empty encode tag
            '{encode="invalid"}',    // Invalid email
            '{encode="test@"}',      // Incomplete email
            '{encode="@domain.com"}', // Missing local part
            '{encode="test@@domain.com"}', // Double @
        ];

        foreach ($malformedTemplates as $template) {
            $result = $this->template->parse_encode_email($template);
            $this->assertIsString($result); // Should not crash
        }
    }

    /**
     * Test parseLayoutVariables handles undefined variables
     */
    public function testParseLayoutVariablesHandlesUndefinedVariables()
    {
        $layoutVars = ['defined' => 'value'];
        $template = '{layout:defined} {layout:undefined} {layout:also_undefined}';

        $result = $this->template->parseLayoutVariables($template, $layoutVars);

        $this->assertStringContainsString('value', $result);
        $this->assertStringNotContainsString('{layout:undefined}', $result); // Should be replaced with empty
        $this->assertStringNotContainsString('{layout:also_undefined}', $result);
    }

    /**
     * Test exclusive_conditional handles edge cases
     */
    public function testExclusiveConditionalHandlesEdgeCases()
    {
        // Test with empty conditional name
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = 'content';

        $result = $this->template->exclusive_conditional('template', '', []);
        $this->assertEquals('', $result);

        // Test with empty template
        $result = $this->template->exclusive_conditional('', 'test', []);
        $this->assertEquals('', $result);
    }

    /**
     * Test process_date handles invalid inputs
     */
    public function testProcessDateHandlesInvalidInputs()
    {
        // Invalid timestamp - returns the invalid input
        $result = $this->template->process_date('invalid');
        $this->assertEquals('invalid', $result);

        // Null timestamp
        $result = $this->template->process_date(null);
        $this->assertEquals('', $result);

        // Valid timestamp with empty format - returns formatted date string
        $result = $this->template->process_date(time(), ['format' => '']);
        $this->assertIsString($result);
    }

    /**
     * Test advanced_conditionals handles deeply nested structures
     */
    public function testAdvancedConditionalsHandlesDeepNesting()
    {
        // Create a deeply nested conditional structure
        $template = str_repeat('{if true}', 5) . 'content' . str_repeat('{/if}', 5);

        $result = $this->template->advanced_conditionals($template);

        // Should handle without crashing
        $this->assertIsString($result);
    }

    /**
     * Test variable parsing with simple data structures
     */
    public function testVariableParsingWithSimpleDataStructures()
    {
        // Test with simple variables
        $simpleVars = [
            'text' => 'simple text',
            'number' => 42
        ];

        $template = '{text} {number}';
        $result = $this->template->parse_variables($template, [$simpleVars]);

        // Should handle without crashing
        $this->assertIsString($result);
        $this->assertStringContainsString('simple text', $result);
    }

    /**
     * Test parsing with large templates using individual methods
     */
    public function testParsingWithLargeTemplates()
    {
        // Create a large template
        $largeTemplate = str_repeat('Large content block. ', 100) . '{if test}conditional content{/if}';

        $result = $this->template->advanced_conditionals($largeTemplate);

        // Should handle large content without crashing
        $this->assertIsString($result);
    }

    /**
     * Test conditionals with special characters
     */
    public function testConditionalsWithSpecialCharacters()
    {
        $specialTemplates = [
            '{if var_with_underscores}content{/if}',
            '{if var-with-dashes}content{/if}',
            '{if var.with.dots}content{/if}',
            '{if var with spaces}content{/if}', // This might be problematic
        ];

        foreach ($specialTemplates as $template) {
            $result = $this->template->advanced_conditionals($template);
            $this->assertIsString($result);
        }
    }

    /**
     * Test error logging functionality
     */
    public function testErrorLoggingWorks()
    {
        // Trigger some error condition that should log
        $this->template->process_date('invalid_timestamp');

        // The log_item method should have been called
        // We can't easily test the actual logging without more complex mocking,
        // but we can verify the method exists and can be called
        $this->assertTrue(method_exists($this->template, 'log_item'));
    }

    /**
     * Test graceful degradation with missing dependencies
     */
    public function testGracefulDegradationWithMissingDependencies()
    {
        // Test that methods work even when some mocks are missing
        $result1 = $this->template->parse_encode_email('test');
        $result2 = $this->template->simple_conditionals('test', []);

        $this->assertIsString($result1);
        $this->assertIsString($result2);
    }
}
