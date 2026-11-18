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

    public function testLogItemPrefixesMessageWithDepthIndentation()
    {
        $this->template->debugging = true;
        $this->template->depth = 2;
        $this->template->start_microtime = microtime(true);

        $this->template->log_item('Nested message');

        $lastLog = end($this->template->log);
        $this->assertStringStartsWith(str_repeat('&nbsp;', 10), $lastLog['message']);
        $this->assertStringEndsWith('Nested message', $lastLog['message']);
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

    public function testAssignFormParamsReturnsTagDataWhenParamsMissing()
    {
        $this->template->form_id = 'stale-id';
        $this->template->form_class = 'stale-class';
        $tagData = ['foo' => 'bar'];

        $result = $this->template->_assign_form_params($tagData);

        $this->assertSame($tagData, $result);
        $this->assertSame('', $this->template->form_id);
        $this->assertSame('', $this->template->form_class);
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

    public function testFetchSiteIdsLoadsSitesFromDatabaseWhenMultipleSitesEnabled()
    {
        $this->template->tagparams = ['site' => 'site1|site2'];
        $this->template->sites = [];

        $configMock = $this->getMockBuilder('stdClass')
            ->setMethods(['item'])
            ->getMock();
        $configMock->method('item')->willReturnCallback(function($key) {
            if ($key === 'multiple_sites_enabled') {
                return 'y';
            }

            if ($key === 'site_id') {
                return 1;
            }

            return null;
        });
        ee()->setMock('config', $configMock);

        $dbResultMock = $this->getMockBuilder('stdClass')
            ->setMethods(['result_array'])
            ->getMock();
        $dbResultMock->method('result_array')->willReturn([
            ['site_id' => 1, 'site_name' => 'site1'],
            ['site_id' => 2, 'site_name' => 'site2'],
        ]);

        $dbMock = $this->getMockBuilder('stdClass')
            ->setMethods(['query'])
            ->getMock();
        $dbMock->method('query')->willReturn($dbResultMock);
        ee()->setMock('db', $dbMock);

        $this->template->_fetch_site_ids();

        $this->assertSame([1 => 'site1', 2 => 'site2'], $this->template->sites);
        $this->assertSame(['site1' => 1, 'site2' => 2], $this->template->site_ids);
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

    public function testParseVariablesHandlesSwitchValuesAndBackspace()
    {
        $variablesParserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['parseTagParameters'])
            ->getMock();
        $variablesParserMock->method('parseTagParameters')
            ->with('switch="odd|even"')
            ->willReturn(['switch' => 'odd|even']);
        ee()->setMock('Variables/Parser', $variablesParserMock);

        $this->template->tagparams = ['backspace' => '1'];

        $result = $this->template->parse_variables(
            '{switch="odd|even"}|{title},',
            [
                ['title' => 'First'],
                ['title' => 'Second'],
            ]
        );

        $this->assertStringContainsString('odd|First', $result);
        $this->assertStringContainsString('even|Second', $result);
        $this->assertStringEndsNotWith(',', $result);
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

    public function testParseVariablesRowReturnsOriginalTagdataForInvalidInput()
    {
        $this->assertSame('', $this->template->parse_variables_row('', ['title' => 'x']));
        $this->assertSame('unchanged', $this->template->parse_variables_row('unchanged', []));
    }

    public function testParseVariablesRowSkipsInvalidModifierWhenVariableExists()
    {
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['prep_conditionals'])
            ->getMock();
        $functionsMock->method('prep_conditionals')->willReturnArgument(0);
        ee()->setMock('functions', $functionsMock);

        $modifiersMock = $this->getMockBuilder('stdClass')
            ->setMethods(['has'])
            ->getMock();
        $modifiersMock->method('has')->willReturn(false);
        ee()->setMock('Variables/Modifiers', $modifiersMock);

        $this->setModifiedVars([
            'title:missing_modifier' => [
                'field_name' => 'title',
                'modifier' => 'missing_modifier',
                'params' => [],
            ],
        ]);

        $result = $this->template->parse_variables_row(
            '{title:missing_modifier}',
            ['title' => 'value']
        );

        $this->assertSame('{title:missing_modifier}', $result);
    }

    public function testParseVariablesRowHandlesEmptyPairValueAsBlankPair()
    {
        $result = $this->template->parse_variables_row(
            '{items}{value}{/items}',
            ['items' => []]
        );

        $this->assertSame('', $result);
    }

    public function testParseVariablesRowSkipsInvalidModifierAndProcessesValidModifierWithPathArray()
    {
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['prep_conditionals'])
            ->getMock();
        $functionsMock->method('prep_conditionals')->willReturnArgument(0);
        ee()->setMock('functions', $functionsMock);

        $modifiersMock = $this->getMockBuilder('stdClass')
            ->setMethods(['has'])
            ->getMock();
        $modifiersMock->method('has')->willReturn(false);
        ee()->setMock('Variables/Modifiers', $modifiersMock);

        $this->setModifiedVars([
            'missing:unknown' => [
                'field_name' => 'missing',
                'modifier' => 'unknown',
                'params' => [],
            ],
            'value:special_group_conditional' => [
                'field_name' => 'value',
                'modifier' => 'special_group_conditional',
                'params' => [],
                'all_modifiers' => [
                    'special_group_conditional' => [],
                ],
            ],
        ]);

        $tagdata = '{value:special_group_conditional}';
        $variables = [
            'value' => [
                '/asset/url',
                ['path_variable' => true],
            ],
        ];

        $result = $this->template->parse_variables_row($tagdata, $variables);

        $this->assertSame('{value}', $result);
    }

    public function testParseVariablesRowHandlesScalarAndNonScalarArrayModifierRawValues()
    {
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['prep_conditionals'])
            ->getMock();
        $functionsMock->method('prep_conditionals')->willReturnArgument(0);
        ee()->setMock('functions', $functionsMock);

        $modifiersMock = $this->getMockBuilder('stdClass')
            ->setMethods(['has'])
            ->getMock();
        $modifiersMock->method('has')->willReturn(false);
        ee()->setMock('Variables/Modifiers', $modifiersMock);

        $loadMock = $this->getMockBuilder('stdClass')
            ->setMethods(['library'])
            ->getMock();
        $loadMock->method('library')->willReturn(null);
        ee()->setMock('load', $loadMock);

        $typographyMock = $this->getMockBuilder('stdClass')
            ->setMethods(['initialize', 'parse_type'])
            ->getMock();
        $typographyMock->method('initialize')->willReturn(null);
        $typographyMock->method('parse_type')->willReturnCallback(function ($content) {
            return is_array($content) ? 'parsed-array' : (string) $content;
        });
        ee()->setMock('typography', $typographyMock);

        $this->setModifiedVars([
            'value:special_group_conditional' => [
                'field_name' => 'value',
                'modifier' => 'special_group_conditional',
                'params' => [],
                'all_modifiers' => [
                    'special_group_conditional' => [],
                ],
            ],
        ]);

        $scalarArrayResult = $this->template->parse_variables_row(
            '{value:special_group_conditional}',
            ['value' => ['scalar-first', ['text_format' => 'none']]]
        );
        $this->assertSame('scalar-first', $scalarArrayResult);

        $nonScalarArrayResult = $this->template->parse_variables_row(
            '{value:special_group_conditional}',
            ['value' => [['nested' => 'value'], ['text_format' => 'none']]]
        );
        $this->assertSame('parsed-array', $nonScalarArrayResult);
    }

    public function testParseVariablesRowUsesSingleModifierConfigWhenAllModifiersAbsent()
    {
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['prep_conditionals'])
            ->getMock();
        $functionsMock->method('prep_conditionals')->willReturnArgument(0);
        ee()->setMock('functions', $functionsMock);

        $modifiersMock = $this->getMockBuilder('stdClass')
            ->setMethods(['has'])
            ->getMock();
        $modifiersMock->method('has')->willReturn(false);
        ee()->setMock('Variables/Modifiers', $modifiersMock);

        $this->setModifiedVars([
            'title:special_group_conditional' => [
                'field_name' => 'title',
                'modifier' => 'special_group_conditional',
                'params' => [],
            ],
        ]);

        $result = $this->template->parse_variables_row(
            '{title:special_group_conditional}',
            ['title' => 'in_group(1|2)']
        );

        $this->assertStringContainsString('~', $result);
    }

    public function testParseVariablesRowSkipsInvalidModifierInsideAllModifiers()
    {
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['prep_conditionals'])
            ->getMock();
        $functionsMock->method('prep_conditionals')->willReturnArgument(0);
        ee()->setMock('functions', $functionsMock);

        $modifiersMock = $this->getMockBuilder('stdClass')
            ->setMethods(['has'])
            ->getMock();
        $modifiersMock->method('has')->willReturn(false);
        ee()->setMock('Variables/Modifiers', $modifiersMock);

        $this->setModifiedVars([
            'title:special_group_conditional' => [
                'field_name' => 'title',
                'modifier' => 'special_group_conditional',
                'params' => [],
                'all_modifiers' => [
                    'missing_modifier' => [],
                    'special_group_conditional' => [],
                ],
            ],
        ]);

        $result = $this->template->parse_variables_row(
            '{title:special_group_conditional}',
            ['title' => 'in_group(3)']
        );

        $this->assertStringContainsString('~', $result);
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

    public function testParseVarSingleDelegatesToParseDateVariablesForDateVars()
    {
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['parse_date_variables'])
            ->getMock();
        $templateMock->date_vars = ['entry_date'];
        $templateMock->expects($this->once())
            ->method('parse_date_variables')
            ->with('{entry_date}', ['entry_date' => 123456])
            ->willReturn('formatted-date');

        $this->assertSame(
            'formatted-date',
            $templateMock->_parse_var_single('entry_date', 123456, '{entry_date}')
        );
    }

    public function testParseVarSingleReplacesPathVariablesAndSkipsDuplicateMatches()
    {
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['extract_path', 'create_url'])
            ->getMock();
        $functionsMock->method('extract_path')->willReturnCallback(function($fullTag) {
            preg_match('/=([\"\']?)(.*?)\\1}/', $fullTag, $matches);
            return $matches[2];
        });
        $functionsMock->method('create_url')->willReturnCallback(function($path) {
            return 'https://example.com/' . trim($path, '/');
        });
        ee()->setMock('functions', $functionsMock);

        $result = $this->template->_parse_var_single(
            'id_path',
            ['slug', ['path_variable' => true]],
            "{id_path='news'} {id_path='news'} {id_path=\"blog\"}"
        );

        $this->assertStringNotContainsString('{id_path', $result);
        $this->assertStringContainsString('https://example.com/news/slug', $result);
        $this->assertStringContainsString('https://example.com/blog/slug', $result);
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

    public function testParseVarPairHandlesEmptyNestedArraysAndLimitBackspace()
    {
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['prep_conditionals'])
            ->getMock();
        $functionsMock->method('prep_conditionals')->willReturnArgument(0);
        ee()->setMock('functions', $functionsMock);

        $variablesParserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['parseTagParameters', 'parseModifiedVariables'])
            ->getMock();
        $variablesParserMock->method('parseTagParameters')
            ->willReturnCallback(function($params) {
                if (strpos($params, 'limit="1"') !== false) {
                    return ['limit' => 1, 'backspace' => 1];
                }

                return [];
            });
        $variablesParserMock->method('parseModifiedVariables')->willReturnArgument(0);
        ee()->setMock('Variables/Parser', $variablesParserMock);

        $tagdata = '{items limit="1" backspace="1"}{children}{value},{/children}{/items}';
        $variables = [
            ['children' => []],
            ['children' => [['value' => 'A']]],
        ];

        $result = $this->template->_parse_var_pair('items', $variables, $tagdata);

        $this->assertSame('', $result);
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

    public function testMatchDateVarsClearsDateVarsWhenFormatMarkerHasNoMatch()
    {
        $this->template->date_vars = ['stale_value'];

        $this->template->_match_date_vars('{entry_date format=}');

        $this->assertSame([], $this->template->date_vars);
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

    public function testParseDateVariablesHandlesRelativeVariableTimeDateArgument()
    {
        $variablesParserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['parseTagParameters'])
            ->getMock();
        $variablesParserMock->method('parseTagParameters')
            ->with('date="2024-03-01"')
            ->willReturn(['date' => '2024-03-01']);
        ee()->setMock('Variables/Parser', $variablesParserMock);

        $localizeMock = $this->getMockBuilder('stdClass')
            ->setMethods(['string_to_timestamp', 'format_date'])
            ->getMock();
        $localizeMock->method('string_to_timestamp')
            ->with('2024-03-01')
            ->willReturn(1709251200);
        $localizeMock->method('format_date')->willReturn('ignored');
        $localizeMock->now = time();
        $localizeMock->format = [];
        ee()->setMock('localize', $localizeMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['process_date'])
            ->getMock();
        $templateMock->expects($this->once())
            ->method('process_date')
            ->with(
                1709251200,
                ['date' => '2024-03-01'],
                true,
                true
            )
            ->willReturn('relative-date');

        $result = $templateMock->parse_date_variables(
            '{variable_time:relative date="2024-03-01"}',
            ['variable_time' => 123]
        );

        $this->assertSame('relative-date', $result);
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

    /**
     * Test parse_date_variables with multiple date variables
     */
    public function testParseDateVariablesWithMultipleDates()
    {
        $variablesParserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['parseTagParameters'])
            ->getMock();
        $variablesParserMock->method('parseTagParameters')
            ->willReturnCallback(function($param) {
                if (strpos($param, 'format="%Y-%m-%d"') !== false) {
                    return ['format' => '%Y-%m-%d'];
                }
                if (strpos($param, 'format="%H:%i"') !== false) {
                    return ['format' => '%H:%i'];
                }
                return [];
            });

        ee()->setMock('Variables/Parser', $variablesParserMock);

        $tagdata = '{created_date format="%Y-%m-%d"} at {updated_time format="%H:%i"}';
        $dates = [
            'created_date' => strtotime('2023-01-01 10:30:00'),
            'updated_time' => strtotime('2023-01-01 14:45:00')
        ];

        $result = $this->template->parse_date_variables($tagdata, $dates);

        $this->assertStringContainsString('2023-01-01', $result);
        $this->assertStringContainsString('14:45', $result);
    }

    /**
     * Test parse_date_variables with timezone consideration
     */
    public function testParseDateVariablesWithTimezone()
    {
        $variablesParserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['parseTagParameters'])
            ->getMock();
        $variablesParserMock->method('parseTagParameters')
            ->willReturn(['format' => '%Y-%m-%d %H:%i:%s']);

        ee()->setMock('Variables/Parser', $variablesParserMock);

        $tagdata = '{event_date format="%Y-%m-%d %H:%i:%s"}';
        $dates = ['event_date' => strtotime('2023-06-15 15:30:00')];

        $result = $this->template->parse_date_variables($tagdata, $dates);

        // Should format the date correctly regardless of timezone
        $this->assertStringContainsString('2023-06-15', $result);
        $this->assertStringContainsString('15:30:00', $result);
    }

    /**
     * Test process_date with basic format specifiers
     */
    public function testProcessDateWithBasicFormats()
    {
        $timestamp = strtotime('2023-12-25 09:15:30');

        // Test basic EE date format specifiers that are directly converted
        $formats = [
            '%Y-%m-%d' => '2023-12-25',
            '%H:%i:%s' => '09:15:30'
        ];

        foreach ($formats as $format => $expected) {
            $result = $this->template->process_date($timestamp, ['format' => $format]);
            $this->assertEquals($expected, $result, "Format $format should produce $expected");
        }
    }

    /**
     * Test process_date with relative dates
     */
    public function testProcessDateWithRelativeDates()
    {
        $pastTimestamp = time() - 3600; // 1 hour ago
        $futureTimestamp = time() + 3600; // in 1 hour

        // Test that relative dates are processed without crashing
        $pastResult = $this->template->process_date($pastTimestamp, [], true);
        $futureResult = $this->template->process_date($futureTimestamp, [], true);

        // Should return strings (the mock always returns '1 hour ago')
        $this->assertIsString($pastResult);
        $this->assertIsString($futureResult);
        $this->assertNotEmpty($pastResult);
        $this->assertNotEmpty($futureResult);
    }

    /**
     * Test process_date edge cases
     */
    public function testProcessDateEdgeCases()
    {
        // Test with invalid format
        $result = $this->template->process_date(time(), ['format' => 'invalid']);
        $this->assertIsString($result); // Should not crash

        // Test with empty format
        $result = $this->template->process_date(time(), ['format' => '']);
        $this->assertIsString($result); // Should not crash

        // Test with zero timestamp
        $result = $this->template->process_date(0, ['format' => '%Y-%m-%d']);
        $this->assertEquals('1970-01-01', $result); // Unix epoch

        // Test with negative timestamp
        $result = $this->template->process_date(-1, ['format' => '%Y-%m-%d']);
        $this->assertEquals('1969-12-31', $result); // Before Unix epoch
    }

    public function testProcessDateLogsInvalidStopParameter()
    {
        $this->template->debugging = true;
        $this->template->start_microtime = microtime(true);

        $result = $this->template->process_date(time(), ['stop' => 'not-a-date'], true);

        $this->assertIsString($result);
        $lastLog = end($this->template->log);
        $this->assertStringContainsString('Invalid Stop Parameter', $lastLog['message']);
    }

    public function testProcessDateStopParameterCanDisableRelativeFormatting()
    {
        $future = time() + 3600;

        $result = $this->template->process_date($future, ['stop' => '-2 hours'], true);

        $this->assertSame($future, $result);
    }

    public function testProcessDateLogsInvalidRelativeUnitAndAssignsCustomWords()
    {
        $this->template->debugging = true;
        $this->template->start_microtime = microtime(true);

        $loadMock = $this->getMockBuilder('stdClass')
            ->setMethods(['library'])
            ->getMock();
        $loadMock->method('library')->willReturn(null);
        ee()->setMock('load', $loadMock);

        $relativeDateMock = new class {
            public $valid_units = ['years', 'months', 'days'];
            public $singular;
            public $less_than;
            public $past;
            public $future;
            public $about;
            public $units_seen = [];
            public function create($timestamp)
            {
                return $this;
            }
            public function calculate($units)
            {
                $this->units_seen = $units;
                return $this;
            }
            public function render($depth)
            {
                return 'relative-render';
            }
        };
        ee()->setMock('relative_date', $relativeDateMock);

        $result = $this->template->process_date(time(), [
            'units' => 'years|bogus',
            'singular' => 'one',
            'less_than' => 'lt',
            'past' => 'past',
            'future' => 'future',
            'about' => 'about',
            'depth' => 2,
        ], true);

        $this->assertSame('relative-render', $result);
        $this->assertSame(['years'], $relativeDateMock->units_seen);
        $this->assertSame('one', $relativeDateMock->singular);
        $this->assertSame('lt', $relativeDateMock->less_than);
        $this->assertSame('past', $relativeDateMock->past);
        $this->assertSame('future', $relativeDateMock->future);
        $this->assertSame('about', $relativeDateMock->about);

        $lastLog = end($this->template->log);
        $this->assertStringContainsString('Invalid Relative Date Unit', $lastLog['message']);
    }

    public function testProcessDateReturnsTimestampWhenFormatFails()
    {
        $this->template->debugging = true;
        $this->template->start_microtime = microtime(true);

        $localizeMock = $this->getMockBuilder('stdClass')
            ->setMethods(['format_date'])
            ->getMock();
        $localizeMock->method('format_date')->willReturn(false);
        $localizeMock->now = time();
        $localizeMock->format = [];
        ee()->setMock('localize', $localizeMock);

        $result = $this->template->process_date(1234567890, ['format' => '%Y']);

        $this->assertSame(1234567890, $result);
        $lastLog = end($this->template->log);
        $this->assertStringContainsString('Invalid Timestamp', $lastLog['message']);
    }

    /**
     * Test parse_date_variables with missing date data
     */
    public function testParseDateVariablesWithMissingDates()
    {
        $variablesParserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['parseTagParameters'])
            ->getMock();
        $variablesParserMock->method('parseTagParameters')
            ->willReturn(['format' => '%Y']);

        ee()->setMock('Variables/Parser', $variablesParserMock);

        $tagdata = '{missing_date format="%Y"} {existing_date format="%Y"}';
        $dates = ['existing_date' => strtotime('2023-01-01')];

        $result = $this->template->parse_date_variables($tagdata, $dates);

        // Should leave missing dates unparsed and format existing ones
        $this->assertStringContainsString('{missing_date format="%Y"}', $result);
        $this->assertStringContainsString('2023', $result);
    }



    /**
     * Test parse_variables method with conditional logic in variables
     */
    public function testParseVariablesWithConditionalLogic()
    {
        $tagdata = '{items}{if featured}★ {/if}{title}{/items}';
        $variables = [
            [
                'items' => [
                    ['title' => 'Regular Item', 'featured' => false],
                    ['title' => 'Featured Item', 'featured' => true],
                    ['title' => 'Another Item', 'featured' => false]
                ]
            ]
        ];

        $result = $this->template->parse_variables($tagdata, $variables);

        // Test that conditionals within variable pairs work - method should run without crashing
        $this->assertIsString($result);
    }

    private function setModifiedVars(array $modifiedVars)
    {
        $property = new \ReflectionProperty(\EE_Template::class, 'modified_vars');
        \TestReflectionHelper::makeAccessible($property);
        $property->setValue($this->template, $modifiedVars);
    }
}
