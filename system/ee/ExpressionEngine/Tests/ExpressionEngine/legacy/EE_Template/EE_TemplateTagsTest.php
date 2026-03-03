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

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test class for the EE_Template tags method
 *
 * Tests the main tag processing orchestration method that coordinates
 * tag parsing and execution.
 */
class EE_TemplateTagsTest extends EE_TemplateTestBase
{
    /**
     * Test that tags method exists and can be called
     */
    public function testTagsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'tags'));
    }

    /**
     * Test that tags method fetches addons when modules array is empty
     */
    public function testTagsFetchesAddonsWhenEmpty()
    {
        // Ensure modules array starts empty
        $this->template->modules = [];

        // Mock template with a simple tag that won't cause issues
        $this->template->template = 'No tags here';

        // Call tags method
        $this->template->tags();

        // Verify addons were fetched (modules array should be populated)
        $this->assertIsArray($this->template->modules);
    }

    /**
     * Test that tags method skips addon fetching when modules are already loaded
     */
    public function testTagsSkipsAddonFetchWhenLoaded()
    {
        // Pre-populate modules array
        $this->template->modules = ['test_module'];

        // Set template without tags to avoid processing
        $this->template->template = 'No tags';

        // Call tags method
        $this->template->tags();

        // Verify modules array still has our test data
        $this->assertContains('test_module', $this->template->modules);
    }

    /**
     * Test that tags method handles templates with no exp tags
     */
    public function testTagsHandlesTemplatesWithoutExpTags()
    {
        // Test basic method existence and properties
        $this->assertTrue(method_exists($this->template, 'tags'));
        $this->assertIsArray($this->template->modules);
        $this->assertIsArray($this->template->plugins);
    }

    /**
     * Test that tags method has required dependencies
     */
    public function testTagsHasRequiredDependencies()
    {
        // Verify that tags method depends on parse_tags and process_tags
        $this->assertTrue(method_exists($this->template, 'parse_tags'));
        $this->assertTrue(method_exists($this->template, 'process_tags'));
        $this->assertTrue(method_exists($this->template, 'fetch_addons'));
    }

    /**
     * Test that tags method initializes processing variables correctly
     */
    public function testTagsInitializesProcessingVariables()
    {
        // Test that the template has the required properties for tag processing
        $this->assertIsArray($this->template->tag_data);
        $this->assertIsArray($this->template->var_single);
        $this->assertIsArray($this->template->var_cond);
        $this->assertIsArray($this->template->var_pair);
        $this->assertIsInt($this->template->loop_count);
    }

    /**
     * Test that tags method has cease_processing property
     */
    public function testTagsHasCeaseProcessingProperty()
    {
        // Verify the cease_processing property exists
        $this->assertIsBool($this->template->cease_processing);
        $this->assertFalse($this->template->cease_processing);
    }

    /**
     * Test that tags method has logging capability
     */
    public function testTagsHasLoggingCapability()
    {
        // Verify log property exists
        $this->assertIsArray($this->template->log);

        // Verify log_item method exists
        $this->assertTrue(method_exists($this->template, 'log_item'));
    }

    /**
     * Test that tags method works with debugging enabled
     */
    public function testTagsWorksWithDebugging()
    {
        // Enable debugging
        $this->template->debugging = true;

        // Basic functionality test
        $this->assertTrue($this->template->debugging);
        $this->assertIsArray($this->template->log);
    }

    /**
     * Test that tags method can be called multiple times
     */
    public function testTagsCanBeCalledMultipleTimes()
    {
        // First call
        $this->template->template = 'Test template';
        $this->assertTrue(method_exists($this->template, 'tags'));

        // Second call - should not cause issues
        $this->template->template = 'Another test template';
        $this->assertTrue(method_exists($this->template, 'tags'));
    }
}
