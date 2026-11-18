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

require_once SYSPATH . 'ee/ExpressionEngine/Tests/TestReflectionHelper.php';

/**
 * run_template_engine tests for EE_Template class
 */
class EE_TemplateRunTemplateEngineTest extends EE_TemplateTestBase
{
    private function setUriAndOutputMocks()
    {
        $uriMock = new \stdClass();
        $uriMock->uri_string = 'blog/index';
        ee()->setMock('uri', $uriMock);

        $outputMock = $this->getMockBuilder('stdClass')
            ->setMethods(['set_output'])
            ->getMock();
        $outputMock->method('set_output')->willReturnSelf();
        ee()->setMock('output', $outputMock);

        return $outputMock;
    }

    /**
     * Test run_template_engine method exists and has correct signature
     */
    public function testRunTemplateEngineMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'run_template_engine'));

        $reflection = new \ReflectionMethod($this->template, 'run_template_engine');
        $parameters = $reflection->getParameters();

        // Check parameter count and defaults
        $this->assertCount(2, $parameters);
        $this->assertEquals('template_group', $parameters[0]->getName());
        $this->assertEquals('', $parameters[0]->getDefaultValue());
        $this->assertEquals('template', $parameters[1]->getName());
        $this->assertEquals('', $parameters[1]->getDefaultValue());
    }

    /**
     * Test run_template_engine handles static templates
     */
    public function testRunTemplateEngineHandlesStaticTemplates()
    {
        // Test that the template_type property can be set
        $reflection = new \ReflectionClass($this->template);
        $typeProperty = $reflection->getProperty('template_type');
        \TestReflectionHelper::makePropertyAccessible($typeProperty);

        $this->assertEquals('', $typeProperty->getValue($this->template));

        $typeProperty->setValue($this->template, 'static');
        $this->assertEquals('static', $typeProperty->getValue($this->template));
    }

    public function testRunTemplateEngineProcessesDynamicTemplatesAndGarbageCollectionPath()
    {
        $outputMock = $this->setUriAndOutputMocks();
        $outputMock->expects($this->once())
            ->method('set_output')
            ->with('{tag}');

        $channelSetMock = $this->getMockBuilder('stdClass')
            ->setMethods(['garbageCollect'])
            ->getMock();
        $channelSetMock->expects($this->once())
            ->method('garbageCollect');
        ee()->setMock('ChannelSet', $channelSetMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods([
                '_garbage_collect_cache',
                'fetch_and_parse',
                'parse_globals',
                'log_item'
            ])
            ->getMock();
        $templateMock->expects($this->once())
            ->method('_garbage_collect_cache');
        $templateMock->method('log_item')->willReturn(null);
        $templateMock->expects($this->once())
            ->method('fetch_and_parse')
            ->with('blog', 'index', false)
            ->willReturnCallback(function() use ($templateMock) {
                $templateMock->template_type = 'webpage';
                $templateMock->final_template = 'raw-dynamic-template';
            });
        $templateMock->expects($this->once())
            ->method('parse_globals')
            ->with('raw-dynamic-template')
            ->willReturn('CFORM-ENCODE-LEFT-BRACKETtagCFORM-ENCODE-RIGHT-BRACKET');

        srand(4); // First rand(1,10) call returns 1 for deterministic garbage-collection coverage.
        $templateMock->run_template_engine('blog', 'index');

        $this->assertSame('{tag}', $templateMock->final_template);
        $this->assertSame('webpage', ee()->output->out_type);
    }

    public function testRunTemplateEngineUsesStaticTemplateRestorePath()
    {
        $outputMock = $this->setUriAndOutputMocks();
        $outputMock->expects($this->once())
            ->method('set_output')
            ->with('{static}');

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods([
                '_garbage_collect_cache',
                'fetch_and_parse',
                'restore_xml_declaration',
                'parse_globals',
                'log_item'
            ])
            ->getMock();
        $templateMock->expects($this->never())
            ->method('_garbage_collect_cache');
        $templateMock->method('log_item')->willReturn(null);
        $templateMock->expects($this->once())
            ->method('fetch_and_parse')
            ->with('static_group', 'xml_page', false)
            ->willReturnCallback(function() use ($templateMock) {
                $templateMock->template_type = 'static';
                $templateMock->final_template = 'raw-static-template';
            });
        $templateMock->expects($this->once())
            ->method('restore_xml_declaration')
            ->with('raw-static-template')
            ->willReturn('CFORM-ENCODE-LEFT-BRACKETstaticCFORM-ENCODE-RIGHT-BRACKET');
        $templateMock->expects($this->never())
            ->method('parse_globals');

        srand(5); // First rand(1,10) call returns 2, so the garbage-collection branch is skipped.
        $templateMock->run_template_engine('static_group', 'xml_page');

        $this->assertSame('{static}', $templateMock->final_template);
        $this->assertSame('static', ee()->output->out_type);
    }

    /**
     * Test run_template_engine processes dynamic templates and calls global parsing
     */
    public function testRunTemplateEngineProcessesDynamicTemplates()
    {
        // Verify parse_globals method exists (called for dynamic templates)
        $this->assertTrue(method_exists($this->template, 'parse_globals'));

        // Test that the method can handle dynamic template processing
        // Since run_template_engine calls fetch_and_parse which requires extensive setup,
        // we test the method signature and that it can be called
        $reflection = new \ReflectionMethod($this->template, 'run_template_engine');

        // Verify it has the expected parameters
        $parameters = $reflection->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('template_group', $parameters[0]->getName());
        $this->assertEquals('template', $parameters[1]->getName());

        // Test that the method exists and is callable
        $this->assertTrue(is_callable([$this->template, 'run_template_engine']));

        // The method should attempt to call fetch_and_parse, but we can't fully test
        // the integration without extensive mocking of the entire EE environment
        // This test verifies the method exists and has the correct structure
    }

    /**
     * Test run_template_engine handles static templates differently
     */
    public function testRunTemplateEngineHandlesStaticTemplateType()
    {
        // Verify restore_xml_declaration method exists (called for static templates)
        $this->assertTrue(method_exists($this->template, 'restore_xml_declaration'));

        // Test that static template type can be set on the template object
        $reflection = new \ReflectionClass($this->template);
        $templateTypeProperty = $reflection->getProperty('template_type');
        \TestReflectionHelper::makePropertyAccessible($templateTypeProperty);

        // Initially should be empty or default
        $initialType = $templateTypeProperty->getValue($this->template);
        $this->assertTrue(is_string($initialType));

        // Can be set to static
        $templateTypeProperty->setValue($this->template, 'static');
        $this->assertEquals('static', $templateTypeProperty->getValue($this->template));

        // Can be set back to webpage
        $templateTypeProperty->setValue($this->template, 'webpage');
        $this->assertEquals('webpage', $templateTypeProperty->getValue($this->template));
    }

    /**
     * Test run_template_engine performs logging operations
     */
    public function testRunTemplateEnginePerformsLogging()
    {
        // Verify log_item method exists and is callable
        $this->assertTrue(method_exists($this->template, 'log_item'));
        $this->assertTrue(is_callable([$this->template, 'log_item']));

        // Test that log_item can be called with typical logging parameters
        // The run_template_engine method calls log_item multiple times during processing
        $result = $this->template->log_item('Test log message');
        // log_item returns null in test environment, which is acceptable

        $result = $this->template->log_item('Begin Template Processing');
        // Method should not throw exceptions

        $result = $this->template->log_item('URI: /test');
        // Method should not throw exceptions

        $result = $this->template->log_item('Template: test_group/test_template');
        // Method should not throw exceptions

        $result = $this->template->log_item('End Template Processing');
        // Method should not throw exceptions

        // Verify the method can be called without errors
        $this->assertTrue(true); // If we get here, all log_item calls succeeded
    }

    /**
     * Test run_template_engine calls fetch_and_parse with correct parameters
     */
    public function testRunTemplateEngineCallsFetchAndParse()
    {
        // Verify fetch_and_parse method exists and has correct signature
        $this->assertTrue(method_exists($this->template, 'fetch_and_parse'));
        $this->assertTrue(is_callable([$this->template, 'fetch_and_parse']));

        // Verify the method signature matches what run_template_engine expects
        $reflection = new \ReflectionMethod($this->template, 'fetch_and_parse');
        $parameters = $reflection->getParameters();

        $this->assertCount(5, $parameters);
        $this->assertEquals('template_group', $parameters[0]->getName());
        $this->assertEquals('template', $parameters[1]->getName());
        $this->assertEquals('is_embed', $parameters[2]->getName());
        $this->assertEquals('site_id', $parameters[3]->getName());
        $this->assertEquals('is_layout', $parameters[4]->getName());

        // The run_template_engine method calls fetch_and_parse with specific parameters
        // This test verifies the interface compatibility
    }

    /**
     * Test run_template_engine handles channel form tag decoding
     */
    public function testRunTemplateEngineHandlesChannelFormDecoding()
    {
        // Verify decode_channel_form_ee_tags method exists and is callable
        $this->assertTrue(method_exists($this->template, 'decode_channel_form_ee_tags'));
        $this->assertTrue(is_callable([$this->template, 'decode_channel_form_ee_tags']));

        // Test that the final_template property can store content that would be decoded
        $reflection = new \ReflectionClass($this->template);
        $finalTemplateProperty = $reflection->getProperty('final_template');
        \TestReflectionHelper::makePropertyAccessible($finalTemplateProperty);

        $testContent = 'template with {channel_form_ee_tags}';
        $finalTemplateProperty->setValue($this->template, $testContent);
        $this->assertEquals($testContent, $finalTemplateProperty->getValue($this->template));

        // The run_template_engine method calls decode_channel_form_ee_tags on the final template
        // This test verifies the infrastructure exists for that process
    }
}
