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
 * Constructor tests for EE_Template class
 */
class EE_TemplateConstructorTest extends EE_TemplateTestBase
{
    /**
     * Test EE_Template constructor creates valid instance
     */
    public function testConstructorCreatesValidInstance()
    {
        $this->assertInstanceOf(\EE_Template::class, $this->template);
    }

    /**
     * Test constructor initializes user variables array
     */
    public function testConstructorInitializesUserVariables()
    {
        $reflection = new ReflectionClass($this->template);
        $userVarsProperty = $reflection->getProperty('user_vars');
        \TestReflectionHelper::makePropertyAccessible($userVarsProperty);

        $userVars = $userVarsProperty->getValue($this->template);

        $this->assertIsArray($userVars);
        $this->assertContains('member_id', $userVars);
        $this->assertContains('group_id', $userVars);
        $this->assertContains('username', $userVars);
        $this->assertContains('email', $userVars);
    }

    /**
     * Test constructor sets marker property
     */
    public function testConstructorSetsMarker()
    {
        $reflection = new ReflectionClass($this->template);
        $markerProperty = $reflection->getProperty('marker');
        \TestReflectionHelper::makePropertyAccessible($markerProperty);

        $marker = $markerProperty->getValue($this->template);

        $this->assertIsString($marker);
        $this->assertNotEmpty($marker);
        $this->assertEquals(32, strlen($marker)); // MD5 hash length
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $marker); // Valid MD5 hash
    }

    /**
     * Test constructor detects multibyte support
     */
    public function testConstructorDetectsMultibyteSupport()
    {
        $reflection = new ReflectionClass($this->template);
        $mbProperty = $reflection->getProperty('mb_available');
        \TestReflectionHelper::makePropertyAccessible($mbProperty);

        $mbAvailable = $mbProperty->getValue($this->template);

        $this->assertIsBool($mbAvailable);
        $this->assertEquals(extension_loaded('mbstring'), $mbAvailable);
    }

    /**
     * Test constructor initializes tag class aliases
     */
    public function testConstructorInitializesTagAliases()
    {
        $reflection = new ReflectionClass($this->template);
        $aliasesProperty = $reflection->getProperty('tag_class_aliases');
        \TestReflectionHelper::makePropertyAccessible($aliasesProperty);

        $aliases = $aliasesProperty->getValue($this->template);

        $this->assertIsArray($aliases);
        $this->assertArrayHasKey('low_search', $aliases);
        $this->assertArrayHasKey('low_variables', $aliases);
        $this->assertEquals('pro_search', $aliases['low_search']);
        $this->assertEquals('pro_variables', $aliases['low_variables']);
    }

    /**
     * Test constructor handles profiler configuration
     */
    public function testConstructorHandlesProfilerConfig()
    {
        // Test with profiler enabled
        ee()->config->setItem('show_profiler', 'y');
        $template = new \EE_Template();

        $this->assertTrue($template->debugging);

        // Reset for next test
        ee()->config->setItem('show_profiler', 'n');
    }
}


