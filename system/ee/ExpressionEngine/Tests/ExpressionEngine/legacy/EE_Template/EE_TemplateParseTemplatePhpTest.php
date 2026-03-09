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

/**
 * Tests for EE_Template::parse_template_php() method
 */
class EE_TemplateParseTemplatePhpTest extends EE_TemplateAdvancedMethodsTestBase
{
    /**
     * Test that parse_template_php method exists and is callable
     */
    public function testParseTemplatePhpMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'parse_template_php'));
        $this->assertTrue(is_callable([$this->template, 'parse_template_php']));
    }

    /**
     * Test basic PHP execution
     */
    public function testParseTemplatePhpExecutesBasicPhp()
    {
        $phpCode = '<?php echo "Hello World"; ?>';
        $result = $this->template->parse_template_php($phpCode);

        $this->assertEquals('Hello World', $result);
    }

    /**
     * Test PHP code with variables
     */
    public function testParseTemplatePhpHandlesVariables()
    {
        $phpCode = '<?php $name = "Test"; echo "Hello " . $name; ?>';
        $result = $this->template->parse_template_php($phpCode);

        $this->assertEquals('Hello Test', $result);
    }

    /**
     * Test PHP code with multiple statements
     */
    public function testParseTemplatePhpHandlesComplexStatements()
    {
        $phpCode = '<?php $numbers = [1, 2, 3, 4, 5]; $sum = array_sum($numbers); echo "Sum: " . $sum; ?>';
        $result = $this->template->parse_template_php($phpCode);

        $this->assertEquals('Sum: 15', $result);
    }

    /**
     * Test that parse_php flag is reset to false
     */
    public function testParseTemplatePhpResetsParsePhpFlag()
    {
        // Set parse_php to true initially
        $this->template->parse_php = true;

        $phpCode = '<?php echo "test"; ?>';
        $this->template->parse_template_php($phpCode);

        // Should be reset to false
        $this->assertFalse($this->template->parse_php);
    }

    /**
     * Test output buffering - multiple outputs
     */
    public function testParseTemplatePhpHandlesMultipleOutputs()
    {
        $phpCode = '<?php echo "First"; echo " "; echo "Second"; ?>';
        $result = $this->template->parse_template_php($phpCode);

        $this->assertEquals('First Second', $result);
    }

    /**
     * Test PHP code that returns values (but doesn't output them)
     */
    public function testParseTemplatePhpHandlesReturnValues()
    {
        $phpCode = '<?php $value = "not echoed"; return $value; ?>';
        $result = $this->template->parse_template_php($phpCode);

        // Return statements don't contribute to output buffering
        $this->assertEquals('', $result);
    }

    /**
     * Test PHP code with HTML and PHP mixed
     */
    public function testParseTemplatePhpHandlesMixedContent()
    {
        $phpCode = '<?php echo "<strong>"; ?>Bold Text<?php echo "</strong>"; ?>';
        $result = $this->template->parse_template_php($phpCode);

        $this->assertEquals('<strong>Bold Text</strong>', $result);
    }

    /**
     * Test empty PHP code
     */
    public function testParseTemplatePhpHandlesEmptyCode()
    {
        $phpCode = '';
        $result = $this->template->parse_template_php($phpCode);

        $this->assertEquals('', $result);
    }

    /**
     * Test PHP code with only whitespace
     */
    public function testParseTemplatePhpHandlesWhitespaceOnly()
    {
        $phpCode = '<?php // just a comment ?>';
        $result = $this->template->parse_template_php($phpCode);

        $this->assertEquals('', $result);
    }

    /**
     * Test PHP code that outputs numbers
     */
    public function testParseTemplatePhpHandlesNumericOutput()
    {
        $phpCode = '<?php echo 42; ?>';
        $result = $this->template->parse_template_php($phpCode);

        $this->assertEquals('42', $result);
        $this->assertIsString($result); // Should be string, not int
    }

    /**
     * Test PHP code that outputs arrays (converted to string)
     */
    public function testParseTemplatePhpHandlesArrayOutput()
    {
        $phpCode = '<?php $arr = [1, 2, 3]; echo implode(", ", $arr); ?>';
        $result = $this->template->parse_template_php($phpCode);

        $this->assertEquals('1, 2, 3', $result);
    }

    /**
     * Test PHP code with print instead of echo
     */
    public function testParseTemplatePhpHandlesPrintStatement()
    {
        $phpCode = '<?php print "Printed text"; ?>';
        $result = $this->template->parse_template_php($phpCode);

        $this->assertEquals('Printed text', $result);
    }
}
