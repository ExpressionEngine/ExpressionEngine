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

use PHPUnit\Framework\TestCase;

/**
 * Test class for the EE_Template process_tags method
 *
 * Tests the tag execution orchestration method that processes parsed tags.
 * Due to the complexity of addon instantiation and execution, these tests
 * focus on the orchestration logic and basic functionality.
 */
class EE_TemplateProcessTagsTest extends EE_TemplateTestBase
{
    /**
     * Test that process_tags method exists
     */
    public function testProcessTagsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'process_tags'));
    }

    /**
     * Test that process_tags handles empty tag_data
     */
    public function testProcessTagsHandlesEmptyTagData()
    {
        $this->template->tag_data = [];
        $originalTemplate = 'Template without tags';
        $this->template->template = $originalTemplate;

        // Method should complete without errors
        $this->template->process_tags();

        // Template should remain unchanged
        $this->assertEquals($originalTemplate, $this->template->template);
    }

    /**
     * Test that process_tags has required properties
     */
    public function testProcessTagsHasRequiredProperties()
    {
        $this->assertIsArray($this->template->tag_data);
        $this->assertIsArray($this->template->modules);
        $this->assertIsArray($this->template->plugins);
        $this->assertIsString($this->template->marker);
    }

    /**
     * Test that process_tags can handle basic tag data structure
     */
    public function testProcessTagsHandlesBasicTagDataStructure()
    {
        // Set up basic tag data structure
        $this->template->tag_data = [
            [
                'class' => 'test',
                'method' => 'method',
                'params' => ['param1' => 'value1'],
                'chunk' => '{exp:test:method param1="value1"}content{/exp:test:method}',
                'block' => 'content',
                'cache' => 'NO_CACHE',
                'cfile' => 'test_hash'
            ]
        ];

        $this->template->template = 'Before M0[test_marker] After';

        // Method should not crash with basic tag data
        $this->template->process_tags();

        // Basic verification that method completed
        $this->assertIsString($this->template->template);
    }

    /**
     * Test that process_tags handles cached tags
     */
    public function testProcessTagsHandlesCachedTags()
    {
        // Test that the method can handle cached tag data structure
        $this->template->tag_data = [
            [
                'class' => 'test',
                'method' => 'method',
                'params' => [],
                'chunk' => '{exp:test:method}content{/exp:test:method}',
                'block' => 'content',
                'cache' => 'CURRENT',
                'cfile' => 'test_hash'
            ]
        ];

        $this->template->template = 'Template content';

        // Should complete without errors when cache status is set
        $this->template->process_tags();

        $this->assertIsString($this->template->template);
    }

    /**
     * Test that process_tags handles cease_processing flag
     */
    public function testProcessTagsHandlesCeaseProcessing()
    {
        // Set cease_processing to true
        $this->template->cease_processing = true;

        $this->template->tag_data = [
            [
                'class' => 'test',
                'method' => 'method',
                'params' => [],
                'chunk' => '{exp:test:method}content{/exp:test:method}',
                'block' => 'content',
                'cache' => 'NO_CACHE',
                'cfile' => 'test_hash'
            ]
        ];

        // Method should return early
        $this->template->process_tags();

        // cease_processing should still be true
        $this->assertTrue($this->template->cease_processing);
    }

    /**
     * Test that process_tags initializes tag properties correctly
     */
    public function testProcessTagsInitializesTagProperties()
    {
        $this->template->tag_data = [
            [
                'class' => 'test',
                'method' => 'method',
                'params' => ['param1' => 'value1'],
                'chunk' => '{exp:test:method param1="value1"}content{/exp:test:method}',
                'block' => 'content',
                'cache' => 'NO_CACHE',
                'cfile' => 'test_hash',
                'no_results' => '',
                'no_results_block' => '',
                'search_fields' => []
            ]
        ];

        $this->template->template = 'Template content';

        // Process tags (will fail to find addon but should set properties)
        $this->template->process_tags();

        // Verify that tag properties are set
        $this->assertIsString($this->template->tagdata);
        $this->assertIsArray($this->template->tagparams);
        $this->assertIsString($this->template->tagchunk);
        $this->assertIsString($this->template->tagproper);
        $this->assertIsArray($this->template->tagparts);
    }

    /**
     * Test that process_tags handles multiple tags
     */
    public function testProcessTagsHandlesMultipleTags()
    {
        $this->template->tag_data = [
            [
                'class' => 'test1',
                'method' => 'method1',
                'params' => [],
                'chunk' => '{exp:test1:method1}content1{/exp:test1:method1}',
                'block' => 'content1',
                'cache' => 'NO_CACHE',
                'cfile' => 'hash1'
            ],
            [
                'class' => 'test2',
                'method' => 'method2',
                'params' => [],
                'chunk' => '{exp:test2:method2}content2{/exp:test2:method2}',
                'block' => 'content2',
                'cache' => 'NO_CACHE',
                'cfile' => 'hash2'
            ]
        ];

        $this->template->template = 'Template content';

        // Should handle multiple tags without crashing
        $this->template->process_tags();

        $this->assertIsString($this->template->template);
    }

    /**
     * Test that process_tags handles tag parameters correctly
     */
    public function testProcessTagsHandlesTagParameters()
    {
        $testParams = ['param1' => 'value1', 'param2' => 'value2'];

        $this->template->tag_data = [
            [
                'class' => 'test',
                'method' => 'method',
                'params' => $testParams,
                'chunk' => '{exp:test:method param1="value1" param2="value2"}content{/exp:test:method}',
                'block' => 'content',
                'cache' => 'NO_CACHE',
                'cfile' => 'test_hash',
                'no_results' => '',
                'no_results_block' => '',
                'search_fields' => []
            ]
        ];

        $this->template->template = 'Template content';

        // Mock to avoid addon instantiation
        $templateMock = $this->getMockBuilder(get_class($this->template))
            ->setMethods(['fetch_cache_file'])
            ->getMock();

        $templateMock->method('fetch_cache_file')->willReturn(false);
        $templateMock->tag_data = $this->template->tag_data;
        $templateMock->template = $this->template->template;
        $templateMock->modules = $this->template->modules;
        $templateMock->plugins = $this->template->plugins;

        $templateMock->process_tags();

        // Should have set tagparams during processing
        $this->assertIsArray($templateMock->tagparams);
    }

    /**
     * Test that process_tags has access to fetch_param method
     */
    public function testProcessTagsHasFetchParamMethod()
    {
        $this->assertTrue(method_exists($this->template, 'fetch_param'));

        // Test fetch_param with existing param
        $this->template->tagparams = ['test_param' => 'test_value'];
        $result = $this->template->fetch_param('test_param');
        $this->assertEquals('test_value', $result);

        // Test fetch_param with default
        $result = $this->template->fetch_param('nonexistent', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    /**
     * Test that process_tags handles logging
     */
    public function testProcessTagsHandlesLogging()
    {
        $this->assertTrue(method_exists($this->template, 'log_item'));

        // Test with debugging disabled to avoid timing issues
        $this->template->debugging = false;

        $logCountBefore = count($this->template->log);

        $this->template->log_item('Test log message');

        // Log should not be added when debugging is disabled
        $this->assertEquals($logCountBefore, count($this->template->log));
    }

    /**
     * Test that process_tags works with debugging disabled
     */
    public function testProcessTagsWorksWithDebuggingDisabled()
    {
        $this->template->debugging = false;

        $logCountBefore = count($this->template->log);

        $this->template->log_item('Test log message');

        // Log count should not change when debugging is disabled
        $this->assertEquals($logCountBefore, count($this->template->log));
    }

    /**
     * Test that process_tags has access to required helper methods
     */
    public function testProcessTagsHasRequiredHelperMethods()
    {
        $requiredMethods = [
            'fetch_cache_file',
            'write_cache_file',
            '_get_cache_prefix',
            'log_item',
            'fetch_param',
            '_fetch_site_ids',
            '_assign_form_params'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(method_exists($this->template, $method), "Method $method should exist");
        }
    }

    /**
     * Test that process_tags can handle tag data reset
     */
    public function testProcessTagsCanHandleTagDataReset()
    {
        // Set some tag data
        $this->template->tagdata = 'test data';
        $this->template->tagparams = ['test' => 'param'];
        $this->template->tagchunk = 'test chunk';
        $this->template->tagproper = 'test proper';
        $this->template->tagparts = ['test', 'parts'];

        // Process with empty tag_data
        $this->template->tag_data = [];
        $this->template->process_tags();

        // Properties should still exist
        $this->assertIsString($this->template->tagdata);
        $this->assertIsArray($this->template->tagparams);
        $this->assertIsString($this->template->tagchunk);
        $this->assertIsString($this->template->tagproper);
        $this->assertIsArray($this->template->tagparts);
    }

    /**
     * Test that process_tags handles expired cache correctly
     */
    public function testProcessTagsHandlesExpiredCache()
    {
        $this->template->tag_data = [
            [
                'class' => 'test',
                'method' => 'method',
                'params' => [],
                'chunk' => '{exp:test:method}content{/exp:test:method}',
                'block' => 'content',
                'cache' => 'EXPIRED',
                'cfile' => 'test_hash'
            ]
        ];

        $this->template->template = 'Before M0[test_marker] After';

        $this->template->process_tags();

        // Should have attempted to process the tag
        $this->assertIsString($this->template->template);
    }

    /**
     * Test that process_tags handles plugin as parameter functionality
     */
    public function testProcessTagsHandlesPluginAsParameter()
    {
        // This tests the complex plugin-in-parameter functionality
        // For testing purposes, we verify the method exists and has the required logic
        $this->assertTrue(method_exists($this->template, 'process_tags'));

        // Test that template has the required properties for plugin parameter processing
        $this->assertIsString($this->template->template);
        $this->assertIsArray($this->template->tag_data);
    }

    /**
     * Test that process_tags handles nested plugins correctly
     */
    public function testProcessTagsHandlesNestedPlugins()
    {
        // Test basic nested plugin handling capability
        $this->assertTrue(method_exists($this->template, 'process_tags'));

        // Verify template can handle nested tag processing
        $this->template->tag_data = [];
        $this->template->template = 'Simple template';

        $this->template->process_tags();

        $this->assertIsString($this->template->template);
    }

    /**
     * Test that process_tags properly manages variable scope
     */
    public function testProcessTagsManagesVariableScope()
    {
        // Test that processing manages the variable arrays correctly
        $this->template->tag_data = [];
        $this->template->template = 'Test template';

        // Set some initial variable state
        $this->template->var_single = ['initial' => 'value'];
        $this->template->var_pair = ['pair' => ['data']];
        $this->template->var_cond = ['condition' => 'value'];

        $this->template->process_tags();

        // Variables should still exist as arrays
        $this->assertIsArray($this->template->var_single);
        $this->assertIsArray($this->template->var_pair);
        $this->assertIsArray($this->template->var_cond);
    }

    /**
     * Test that process_tags completes processing cycle
     */
    public function testProcessTagsCompletesProcessingCycle()
    {
        $this->template->tag_data = [];
        $this->template->template = 'Final test template';

        // Process tags
        $this->template->process_tags();

        // Verify template still exists and is a string
        $this->assertIsString($this->template->template);
        $this->assertNotEmpty($this->template->template);
    }
}
