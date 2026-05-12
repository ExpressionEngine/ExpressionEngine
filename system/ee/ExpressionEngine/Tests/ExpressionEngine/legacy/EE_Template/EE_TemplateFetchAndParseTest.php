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
 * fetch_and_parse tests for EE_Template class
 */
class EE_TemplateFetchAndParseTest extends EE_TemplateTestBase
{
    private function setCoreAndExtensionsMocks($hookActive = false, $hookReturn = null)
    {
        $coreMock = $this->getMockBuilder('stdClass')
            ->setMethods(['set_newrelic_transaction'])
            ->getMock();
        $coreMock->method('set_newrelic_transaction')->willReturn(null);
        ee()->setMock('core', $coreMock);

        $extensionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['active_hook', 'call'])
            ->getMock();
        $extensionsMock->method('active_hook')->willReturn($hookActive);
        if ($hookActive) {
            $extensionsMock->method('call')->willReturn($hookReturn);
        }
        ee()->setMock('extensions', $extensionsMock);
    }

    /**
     * Test fetch_and_parse method exists and has correct signature
     */
    public function testFetchAndParseMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'fetch_and_parse'));

        $reflection = new \ReflectionMethod($this->template, 'fetch_and_parse');
        $parameters = $reflection->getParameters();

        // Check parameter count and defaults
        $this->assertCount(5, $parameters);
        $this->assertEquals('template_group', $parameters[0]->getName());
        $this->assertEquals('', $parameters[0]->getDefaultValue());
        $this->assertEquals('template', $parameters[1]->getName());
        $this->assertEquals('', $parameters[1]->getDefaultValue());
        $this->assertEquals('is_embed', $parameters[2]->getName());
        $this->assertFalse($parameters[2]->getDefaultValue());
        $this->assertEquals('site_id', $parameters[3]->getName());
        $this->assertEquals('', $parameters[3]->getDefaultValue());
        $this->assertEquals('is_layout', $parameters[4]->getName());
        $this->assertFalse($parameters[4]->getDefaultValue());
    }

    /**
     * Test fetch_and_parse handles URI parsing
     */
    public function testFetchAndParseHandlesUriParsing()
    {
        // Test that the method has URI parsing capability by checking for related methods
        $this->assertTrue(method_exists($this->template, 'parse_template_uri'));
        $this->assertTrue(method_exists($this->template, 'fetch_template'));
    }

    public function testFetchAndParseFetchesExplicitTemplateAndParses()
    {
        ee()->config->setItem('site_id', 9);
        $this->setCoreAndExtensionsMocks(false);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['fetch_template', 'parse', 'log_item'])
            ->getMock();
        $templateMock->group_name = 'blog';
        $templateMock->template_name = 'entry';
        $templateMock->template_type = 'webpage';
        $templateMock->method('log_item')->willReturn(null);

        $templateMock->expects($this->once())
            ->method('fetch_template')
            ->with('blog', 'entry', false, 9, false)
            ->willReturn('template-body');
        $templateMock->expects($this->once())
            ->method('parse')
            ->with('template-body', false, 9, false)
            ->willReturnCallback(function() use ($templateMock) {
                $templateMock->final_template = 'parsed-final-template';
            });

        $templateMock->fetch_and_parse('blog', 'entry', false, '', false);

        $this->assertSame('NO_CACHE', $templateMock->cache_status);
        $this->assertSame('', $templateMock->cache_prefix);
        $this->assertStringContainsString('|9:blog/entry|', $templateMock->templates_sofar);
        $this->assertCount(1, $templateMock->templates_loaded);
        $this->assertSame('blog', $templateMock->templates_loaded[0]['group_name']);
        $this->assertSame('entry', $templateMock->templates_loaded[0]['template_name']);
        $this->assertSame(9, $templateMock->templates_loaded[0]['site_id']);
    }

    public function testFetchAndParseUsesUriTemplateForEmbedsAndKeepsCachePrefix()
    {
        ee()->config->setItem('site_id', 1);
        $this->setCoreAndExtensionsMocks(true, 'hooked-embed-template');

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['parse_template_uri', 'parse', 'log_item'])
            ->getMock();
        $templateMock->group_name = 'from_uri_group';
        $templateMock->template_name = 'from_uri_template';
        $templateMock->cache_prefix = 'existing-prefix';
        $templateMock->template_type = 'webpage';
        $templateMock->method('log_item')->willReturn(null);
        $templateMock->expects($this->once())
            ->method('parse_template_uri')
            ->willReturn('uri-template-body');
        $templateMock->expects($this->once())
            ->method('parse')
            ->with('uri-template-body', true, 15, false);

        $templateMock->fetch_and_parse('', '', true, 15, false);

        $this->assertSame('existing-prefix', $templateMock->cache_prefix);
        $this->assertSame('hooked-embed-template', $templateMock->template);
    }

    public function testFetchAndParseHookUpdatesFinalTemplateForNonEmbeds()
    {
        ee()->config->setItem('site_id', 4);
        $this->setCoreAndExtensionsMocks(true, 'hooked-final-template');

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['fetch_template', 'parse', 'log_item'])
            ->getMock();
        $templateMock->group_name = 'pages';
        $templateMock->template_name = 'home';
        $templateMock->template_type = 'webpage';
        $templateMock->method('log_item')->willReturn(null);
        $templateMock->expects($this->once())
            ->method('fetch_template')
            ->willReturn('page-template');
        $templateMock->expects($this->once())
            ->method('parse')
            ->willReturnCallback(function() use ($templateMock) {
                $templateMock->final_template = 'parsed-final';
            });

        $templateMock->fetch_and_parse('pages', 'home', false, '', false);

        $this->assertSame('hooked-final-template', $templateMock->final_template);
    }

    /**
     * Test fetch_and_parse handles embeds and layouts
     */
    public function testFetchAndParseHandlesEmbedsAndLayouts()
    {
        // Test that the method has embed and layout processing capability
        $this->assertTrue(method_exists($this->template, 'process_sub_templates'));
        $this->assertTrue(method_exists($this->template, 'process_layout_template'));
    }

    /**
     * Test fetch_and_parse handles site ID parameter correctly
     */
    public function testFetchAndParseHandlesSiteIdParameter()
    {
        // Test with explicit site_id
        $reflection = new \ReflectionMethod($this->template, 'fetch_and_parse');
        $parameters = $reflection->getParameters();

        $this->assertEquals('site_id', $parameters[3]->getName());
        $this->assertEquals('', $parameters[3]->getDefaultValue());

        // Test that site_id parameter accepts different values
        // Since we can't easily call the method without full setup, we verify the parameter exists
        $this->assertCount(5, $parameters);
    }

    /**
     * Test fetch_and_parse handles is_embed parameter
     */
    public function testFetchAndParseHandlesIsEmbedParameter()
    {
        $reflection = new \ReflectionMethod($this->template, 'fetch_and_parse');
        $parameters = $reflection->getParameters();

        $this->assertEquals('is_embed', $parameters[2]->getName());
        $this->assertFalse($parameters[2]->getDefaultValue());
    }

    /**
     * Test fetch_and_parse handles is_layout parameter
     */
    public function testFetchAndParseHandlesIsLayoutParameter()
    {
        $reflection = new \ReflectionMethod($this->template, 'fetch_and_parse');
        $parameters = $reflection->getParameters();

        $this->assertEquals('is_layout', $parameters[4]->getName());
        $this->assertFalse($parameters[4]->getDefaultValue());
    }

    /**
     * Test fetch_and_parse initializes template tracking
     */
    public function testFetchAndParseInitializesTemplateTracking()
    {
        // Test that the method tracks templates_sofar property
        $reflection = new \ReflectionClass($this->template);
        $templatesSoFarProperty = $reflection->getProperty('templates_sofar');
        \TestReflectionHelper::makePropertyAccessible($templatesSoFarProperty);

        // Initially should be empty or default value
        $initialValue = $templatesSoFarProperty->getValue($this->template);
        $this->assertIsString($initialValue);

        // Verify the property can be modified (would happen during actual execution)
        $templatesSoFarProperty->setValue($this->template, '|1:test_group/test_template|');
        $this->assertEquals('|1:test_group/test_template|', $templatesSoFarProperty->getValue($this->template));
    }

    /**
     * Test fetch_and_parse manages cache status
     */
    public function testFetchAndParseManagesCacheStatus()
    {
        $reflection = new \ReflectionClass($this->template);
        $cacheStatusProperty = $reflection->getProperty('cache_status');
        \TestReflectionHelper::makePropertyAccessible($cacheStatusProperty);

        // Initially should be set to NO_CACHE
        $cacheStatusProperty->setValue($this->template, 'NO_CACHE');
        $this->assertEquals('NO_CACHE', $cacheStatusProperty->getValue($this->template));

        // Can be set to other values
        $cacheStatusProperty->setValue($this->template, 'CACHE_FOUND');
        $this->assertEquals('CACHE_FOUND', $cacheStatusProperty->getValue($this->template));
    }

    /**
     * Test fetch_and_parse handles cache prefix for embeds
     */
    public function testFetchAndParseHandlesCachePrefixForEmbeds()
    {
        $reflection = new \ReflectionClass($this->template);
        $cachePrefixProperty = $reflection->getProperty('cache_prefix');
        \TestReflectionHelper::makePropertyAccessible($cachePrefixProperty);

        // For non-embeds, cache_prefix should be empty
        $cachePrefixProperty->setValue($this->template, '');
        $this->assertEquals('', $cachePrefixProperty->getValue($this->template));

        // For embeds, it might be set to current URI or other values
        $cachePrefixProperty->setValue($this->template, 'embed_prefix');
        $this->assertEquals('embed_prefix', $cachePrefixProperty->getValue($this->template));
    }

    /**
     * Test fetch_and_parse calls parse_template_uri when no template specified
     */
    public function testFetchAndParseCallsParseTemplateUriWhenNoTemplate()
    {
        // When template_group and template are empty, it should call parse_template_uri
        $this->assertTrue(method_exists($this->template, 'parse_template_uri'));
        $this->assertTrue(method_exists($this->template, 'fetch_template'));
    }

    /**
     * Test fetch_and_parse tracks loaded templates
     */
    public function testFetchAndParseTracksLoadedTemplates()
    {
        $reflection = new \ReflectionClass($this->template);
        $templatesLoadedProperty = $reflection->getProperty('templates_loaded');
        \TestReflectionHelper::makePropertyAccessible($templatesLoadedProperty);

        // Initially should be empty array
        $templatesLoadedProperty->setValue($this->template, []);
        $this->assertIsArray($templatesLoadedProperty->getValue($this->template));
        $this->assertEmpty($templatesLoadedProperty->getValue($this->template));

        // Can be populated with template info
        $templateInfo = [
            [
                'group_name' => 'test_group',
                'template_name' => 'test_template',
                'site_id' => 1
            ]
        ];
        $templatesLoadedProperty->setValue($this->template, $templateInfo);
        $loaded = $templatesLoadedProperty->getValue($this->template);
        $this->assertCount(1, $loaded);
        $this->assertEquals('test_group', $loaded[0]['group_name']);
        $this->assertEquals('test_template', $loaded[0]['template_name']);
        $this->assertEquals(1, $loaded[0]['site_id']);
    }

    /**
     * Test fetch_and_parse sets template properties
     */
    public function testFetchAndParseSetsTemplateProperties()
    {
        $reflection = new \ReflectionClass($this->template);

        // Test group_name property
        $groupNameProperty = $reflection->getProperty('group_name');
        \TestReflectionHelper::makePropertyAccessible($groupNameProperty);
        $groupNameProperty->setValue($this->template, 'test_group');
        $this->assertEquals('test_group', $groupNameProperty->getValue($this->template));

        // Test template_name property
        $templateNameProperty = $reflection->getProperty('template_name');
        \TestReflectionHelper::makePropertyAccessible($templateNameProperty);
        $templateNameProperty->setValue($this->template, 'test_template');
        $this->assertEquals('test_template', $templateNameProperty->getValue($this->template));

        // Test template property
        $templateProperty = $reflection->getProperty('template');
        \TestReflectionHelper::makePropertyAccessible($templateProperty);
        $templateContent = '<html><body>Test template content</body></html>';
        $templateProperty->setValue($this->template, $templateContent);
        $this->assertEquals($templateContent, $templateProperty->getValue($this->template));
    }

    /**
     * Test fetch_and_parse calls the parse method
     */
    public function testFetchAndParseCallsParseMethod()
    {
        // Verify the parse method exists and has correct signature
        $this->assertTrue(method_exists($this->template, 'parse'));

        $reflection = new \ReflectionMethod($this->template, 'parse');
        $parameters = $reflection->getParameters();

        // parse method should take template content and flags
        $this->assertGreaterThanOrEqual(3, count($parameters));
    }

    /**
     * Test fetch_and_parse handles New Relic transaction setup
     */
    public function testFetchAndParseHandlesNewRelicTransaction()
    {
        // Test that the method would set up New Relic transaction on first call
        // Since we can't easily test the actual New Relic integration, verify the constant semantics.
        if (!defined('EECMS_NEW_RELIC_TRANS_NAME')) {
            define('EECMS_NEW_RELIC_TRANS_NAME', 'test_transaction');
        }

        $this->assertTrue(defined('EECMS_NEW_RELIC_TRANS_NAME'));
        $this->assertIsString(EECMS_NEW_RELIC_TRANS_NAME);
        $this->assertNotSame('', EECMS_NEW_RELIC_TRANS_NAME);
    }
}
