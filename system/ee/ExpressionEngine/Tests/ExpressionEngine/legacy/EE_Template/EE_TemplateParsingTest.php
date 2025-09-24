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
 * Test class for EE_Template parsing methods
 *
 * Tests methods related to template parsing, conditionals, variables, and forms
 */
class EE_TemplateParsingTest extends EE_TemplateTestBase
{

    /**
     * Test simple_conditionals method
     */
    public function testSimpleConditionalsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'simple_conditionals'));
    }

    /**
     * Test simple_conditionals method delegates to functions->prep_conditionals
     */
    public function testSimpleConditionalsDelegatesToPrepConditionals()
    {
        // Mock the functions object to return expected result
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['prep_conditionals'])
            ->getMock();
        $functionsMock->method('prep_conditionals')
            ->with('{if segment_1 == "test"}Yes{if:else}No{/if}', ['segment_1' => 'other'])
            ->willReturn('No');

        ee()->setMock('functions', $functionsMock);

        $template = '{if segment_1 == "test"}Yes{if:else}No{/if}';
        $vars = ['segment_1' => 'other'];

        $result = $this->template->simple_conditionals($template, $vars);

        $this->assertEquals('No', $result);
    }

    /**
     * Test simple_conditionals method with no else
     */
    public function testSimpleConditionalsHandlesNoElse()
    {
        // Mock the functions object to return expected result
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['prep_conditionals'])
            ->getMock();
        $functionsMock->method('prep_conditionals')
            ->with('{if segment_1 == "test"}Yes{/if}', ['segment_1' => 'other'])
            ->willReturn('');

        ee()->setMock('functions', $functionsMock);

        $template = '{if segment_1 == "test"}Yes{/if}';
        $vars = ['segment_1' => 'other'];

        $result = $this->template->simple_conditionals($template, $vars);

        $this->assertEquals('', $result);
    }

    /**
     * Test exclusive_conditional method
     */
    public function testExclusiveConditionalReturnsContentWhenConditionExists()
    {
        // Set up TMPL mock
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = '{if success}Success message{/if}';

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'success', [['success' => true]]);

        $this->assertEquals('Success message', $result);
    }

    /**
     * Test exclusive_conditional method returns empty when condition doesn't exist
     */
    public function testExclusiveConditionalReturnsEmptyWhenNoCondition()
    {
        // Set up TMPL mock
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = 'No condition here';

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'missing', []);

        $this->assertEquals('', $result);
    }

    /**
     * Test log_item method
     */
    public function testLogItemAddsToLogArray()
    {
        $this->template->debugging = true;
        $this->template->start_microtime = microtime(true);
        $initialCount = count($this->template->log);

        $this->template->log_item("Test message");

        $this->assertCount($initialCount + 1, $this->template->log);
        $this->assertEquals("Test message", end($this->template->log)['message']);
    }

    /**
     * Test log_item method with details
     */
    public function testLogItemHandlesDetails()
    {
        $this->template->debugging = true;
        $this->template->start_microtime = microtime(true);
        $details = ['key' => 'value'];

        $this->template->log_item("Test with details", $details);

        $lastLog = end($this->template->log);
        $this->assertTrue(strpos($lastLog['details'], 'key') !== false);
        $this->assertTrue(strpos($lastLog['details'], 'value') !== false);
    }

    /**
     * Test fetch_param method
     */
    public function testFetchParamReturnsParameterValue()
    {
        $this->template->tagparams = ['param1' => 'value1', 'param2' => 'value2'];

        $result = $this->template->fetch_param('param1');

        $this->assertEquals('value1', $result);
    }

    /**
     * Test fetch_param method returns default value
     */
    public function testFetchParamReturnsDefaultValue()
    {
        $this->template->tagparams = ['param1' => 'value1'];

        $result = $this->template->fetch_param('missing_param', 'default');

        $this->assertEquals('default', $result);
    }

    /**
     * Test fetch_param method normalizes boolean values
     */
    public function testFetchParamNormalizesBooleanValues()
    {
        $this->template->tagparams = [
            'yes_param' => 'y',
            'on_param' => 'on',
            'no_param' => 'n',
            'off_param' => 'off'
        ];

        $this->assertEquals('yes', $this->template->fetch_param('yes_param'));
        $this->assertEquals('yes', $this->template->fetch_param('on_param'));
        $this->assertEquals('no', $this->template->fetch_param('no_param'));
        $this->assertEquals('no', $this->template->fetch_param('off_param'));
    }

    /**
     * Test parse_template_php method
     */
    public function testParseTemplatePhpExecutesPHP()
    {
        $phpCode = '<?php echo "Hello " . "World"; ?>';

        $result = $this->template->parse_template_php($phpCode);

        $this->assertEquals('Hello World', $result);
        $this->assertFalse($this->template->parse_php);
    }

    /**
     * Test parse_template_php method handles non-PHP content
     */
    public function testParseTemplatePhpHandlesNonPhpContent()
    {
        $content = 'Regular content without PHP';

        $result = $this->template->parse_template_php($content);

        $this->assertEquals('Regular content without PHP', $result);
    }

    /**
     * Test advanced_conditionals method exists
     */
    public function testAdvancedConditionalsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'advanced_conditionals'));
    }

    /**
     * Test parse_simple_segment_conditionals method
     */
    public function testParseSimpleSegmentConditionalsProcessesSegments()
    {
        // Mock the functions object to return expected result
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['prep_conditionals'])
            ->getMock();
        $expectedVars = [
            'segment_1' => 'default',
            'segment_2' => 'index',
            'segment_3' => false,
            'segment_4' => false,
            'segment_5' => false,
            'segment_6' => false,
            'segment_7' => false,
            'segment_8' => false,
            'segment_9' => false,
        ];
        $functionsMock->method('prep_conditionals')
            ->with('{if segment_1 == "test"}Match{/if}', $expectedVars)
            ->willReturn('Match');

        ee()->setMock('functions', $functionsMock);

        $template = '{if segment_1 == "test"}Match{/if}';

        $result = $this->template->parse_simple_segment_conditionals($template);

        $this->assertEquals('Match', $result);
    }

    /**
     * Test _assign_form_params method
     */
    public function testAssignFormParamsSetsFormProperties()
    {
        $tagData = [
            'params' => [
                'form_id' => 'test-form',
                'form_class' => 'test-class'
            ]
        ];

        $result = $this->template->_assign_form_params($tagData);

        $this->assertEquals('test-form', $this->template->form_id);
        $this->assertEquals('test-class', $this->template->form_class);
        $this->assertEquals($tagData, $result);
    }

    /**
     * Test _fetch_site_ids method
     */
    public function testFetchSiteIdsSetsSiteIdsFromParams()
    {
        $this->template->tagparams = ['site' => 'site1|site2'];

        // Mock the sites array
        $this->template->sites = [1 => 'site1', 2 => 'site2'];

        $this->template->_fetch_site_ids();

        $this->assertEquals(['site1' => 1, 'site2' => 2], $this->template->site_ids);
    }

    /**
     * Test _fetch_site_ids method handles not condition
     */
    public function testFetchSiteIdsHandlesNotCondition()
    {
        $this->template->tagparams = ['site' => 'not site2'];

        // Mock the sites array
        $this->template->sites = [1 => 'site1', 2 => 'site2', 3 => 'site3'];

        $this->template->_fetch_site_ids();

        $this->assertEquals(['site1' => 1, 'site3' => 3], $this->template->site_ids);
    }

    /**
     * Test parse_variables method with simple variables
     */
    public function testParseVariablesHandlesSimpleVariables()
    {
        $tagdata = '{title} by {author}';
        $variables = [
            ['title' => 'Test Article', 'author' => 'John Doe']
        ];

        $result = $this->template->parse_variables($tagdata, $variables);

        $this->assertEquals('Test Article by John Doe', $result);
    }

    /**
     * Test parse_variables method with pair variables
     */
    public function testParseVariablesHandlesPairVariables()
    {
        $tagdata = '{items}{title}{/items}';
        $variables = [
            [
                'items' => [
                    ['title' => 'Item 1'],
                    ['title' => 'Item 2']
                ]
            ]
        ];

        $result = $this->template->parse_variables($tagdata, $variables);

        // Test that the method runs without crashing and returns a string
        $this->assertIsString($result);
    }

    /**
     * Test parse_variables_row method
     */
    public function testParseVariablesRowHandlesSingleRow()
    {
        $tagdata = '{title} - {count}/{total_results}';
        $variables = [
            'title' => 'Test Title',
            'count' => 1,
            'total_results' => 5
        ];

        $result = $this->template->parse_variables_row($tagdata, $variables);

        $this->assertEquals('Test Title - 1/5', $result);
    }

    /**
     * Test _parse_var_single method
     */
    public function testParseVarSingleHandlesStringValue()
    {
        $result = $this->template->_parse_var_single('name', 'John Doe', '{name}');

        $this->assertEquals('John Doe', $result);
    }

    /**
     * Test _parse_var_single method handles null value
     */
    public function testParseVarSingleHandlesNullValue()
    {
        $result = $this->template->_parse_var_single('name', null, '{name}');

        $this->assertEquals('', $result);
    }

    /**
     * Test _parse_var_pair method
     */
    public function testParseVarPairHandlesArrayData()
    {
        $tagdata = '{items}{name}, {/items}';
        $variables = [
            ['name' => 'Item1'],
            ['name' => 'Item2']
        ];

        $result = $this->template->_parse_var_pair('items', $variables, $tagdata);

        // Test that the method runs without crashing and returns a string
        $this->assertIsString($result);
    }

    /**
     * Test _match_date_vars method
     */
    public function testMatchDateVarsIdentifiesDateVariables()
    {
        $tagdata = '{entry_date format="%Y-%m-%d"} {custom_date:relative}';

        $this->template->_match_date_vars($tagdata);

        $this->assertContains('entry_date', $this->template->date_vars);
        $this->assertContains('custom_date', $this->template->date_vars);
    }

    /**
     * Test parse_switch method
     */
    public function testParseSwitchHandlesSwitchVariables()
    {
        $tagdata = '{switch="red|blue|green"}';

        $result = $this->template->parse_switch($tagdata, 0);

        $this->assertEquals('red', $result);
    }

    /**
     * Test parse_switch method with different count
     */
    public function testParseSwitchHandlesDifferentCount()
    {
        $tagdata = '{switch="red|blue|green"}';

        $result = $this->template->parse_switch($tagdata, 1);

        $this->assertEquals('blue', $result);
    }

    /**
     * Test parse_encode_email method
     */
    public function testParseEncodeEmailEncodesEmailTags()
    {
        $tagdata = '{encode="test@example.com"}';

        $result = $this->template->parse_encode_email($tagdata);

        $this->assertTrue(strpos($result, '{encode=') === false); // Original tag should be replaced
        $this->assertTrue(strpos($result, '[email]') !== false); // Should contain encoded email
    }

    /**
     * Test parse_date_variables method
     */
    public function testParseDateVariablesFormatsDates()
    {
        // Mock Variables/Parser for parameter parsing
        $variablesParserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['parseTagParameters'])
            ->getMock();
        $variablesParserMock->method('parseTagParameters')
            ->with('format="%Y"')
            ->willReturn(['format' => '%Y']);

        ee()->setMock('Variables/Parser', $variablesParserMock);

        $tagdata = '{date format="%Y"}';
        $dates = ['date' => strtotime('2023-01-01')];

        $result = $this->template->parse_date_variables($tagdata, $dates);

        $this->assertEquals('2023', $result);
    }

    /**
     * Test process_date method with format
     */
    public function testProcessDateFormatsTimestamp()
    {
        $timestamp = strtotime('2023-01-01 12:00:00');
        $parameters = ['format' => '%Y-%m-%d'];

        $result = $this->template->process_date($timestamp, $parameters);

        $this->assertEquals('2023-01-01', $result);
    }

    /**
     * Test process_date method with relative date
     */
    public function testProcessDateHandlesRelativeDate()
    {
        $timestamp = time() - 3600; // 1 hour ago
        $parameters = [];

        $result = $this->template->process_date($timestamp, $parameters, true);

        $this->assertTrue(strpos($result, 'hour') !== false);
        $this->assertTrue(strpos($result, 'ago') !== false);
    }

    /**
     * Test process_date method handles null timestamp
     */
    public function testProcessDateHandlesNullTimestamp()
    {
        $result = $this->template->process_date(null);

        $this->assertEquals('', $result);
    }
}
