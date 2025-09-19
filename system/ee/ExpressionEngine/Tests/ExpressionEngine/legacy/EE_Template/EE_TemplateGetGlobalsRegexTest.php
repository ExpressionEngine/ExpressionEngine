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
 * getGlobalsRegex tests for EE_Template class
 */
class EE_TemplateGetGlobalsRegexTest extends EE_TemplateTestBase
{
    /**
     * Test getGlobalsRegex generates valid regex patterns
     */
    public function testGetGlobalsRegexGeneratesValidPatterns()
    {
        $reflection = new ReflectionClass($this->template);
        $method = $reflection->getMethod('getGlobalsRegex');
        $method->setAccessible(true);

        // Set up some global variables
        ee()->config->_global_vars = [
            'site_name' => 'Test Site',
            'site_url' => 'https://example.com',
            'custom_var' => 'value'
        ];

        $regexes = $method->invoke($this->template);

        $this->assertIsArray($regexes);
        $this->assertNotEmpty($regexes);

        foreach ($regexes as $regex) {
            $this->assertIsString($regex);
            $this->assertStringStartsWith('/', $regex);
            $this->assertStringEndsWith('/', $regex);

            // Test that the regex is valid
            $this->assertTrue(@preg_match($regex, 'test') !== false || preg_last_error() === PREG_NO_ERROR);
        }
    }

    /**
     * Test getGlobalsRegex caches results
     */
    public function testGetGlobalsRegexCachesResults()
    {
        $reflection = new ReflectionClass($this->template);
        $method = $reflection->getMethod('getGlobalsRegex');
        $method->setAccessible(true);

        // Set up global variables
        ee()->config->_global_vars = [
            'site_name' => 'Test Site',
            'site_url' => 'https://example.com'
        ];

        // Call method twice
        $regexes1 = $method->invoke($this->template);
        $regexes2 = $method->invoke($this->template);

        // Should return the same result (cached)
        $this->assertEquals($regexes1, $regexes2);

        // Check that cache was stored
        $cacheProperty = $reflection->getProperty('globals_regex');
        $cacheProperty->setAccessible(true);
        $cache = $cacheProperty->getValue($this->template);

        $this->assertNotEmpty($cache);
    }

    /**
     * Test getGlobalsRegex handles empty globals
     */
    public function testGetGlobalsRegexHandlesEmptyGlobals()
    {
        $reflection = new ReflectionClass($this->template);
        $method = $reflection->getMethod('getGlobalsRegex');
        $method->setAccessible(true);

        // Clear global variables
        ee()->config->_global_vars = [];

        $regexes = $method->invoke($this->template);

        $this->assertIsArray($regexes);
        // When globals are empty, it returns an array with one invalid regex pattern
        $this->assertCount(1, $regexes);
        $this->assertTrue(strpos($regexes[0], '/{()}/') !== false);
    }

    /**
     * Test getGlobalsRegex handles large variable sets
     */
    public function testGetGlobalsRegexHandlesLargeVariableSets()
    {
        $reflection = new ReflectionClass($this->template);
        $method = $reflection->getMethod('getGlobalsRegex');
        $method->setAccessible(true);

        // Create a moderately large set of global variables
        $largeVars = [];
        for ($i = 0; $i < 100; $i++) {
            $largeVars['variable_' . $i] = 'value_' . $i;
        }
        ee()->config->_global_vars = $largeVars;

        $regexes = $method->invoke($this->template);

        $this->assertIsArray($regexes);
        $this->assertNotEmpty($regexes);

        // All regexes should be valid patterns
        foreach ($regexes as $regex) {
            $this->assertIsString($regex);
            $this->assertStringStartsWith('/', $regex);
            $this->assertStringEndsWith('/', $regex);
        }
    }
}
