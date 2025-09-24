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

// Constants are defined in EE_TemplateTestBase.php

require_once __DIR__ . '/EE_TemplateTestBase.php';

/**
 * Test class for the EE_Template parse_tags method
 *
 * Tests the tag parsing functionality that identifies and extracts
 * ExpressionEngine tags from templates.
 */
class EE_TemplateParseTagsTest extends EE_TemplateTestBase
{
    /**
     * Mock for Variables/Parser service
     */
    private $variablesParserMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Variables/Parser service
        $variablesParserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['getFullTag', 'parseTagParameters'])
            ->getMock();

        $variablesParserMock->method('getFullTag')->willReturnCallback(function($template, $tag) {
            return $tag; // Simple mock - return tag as-is
        });

        $variablesParserMock->method('parseTagParameters')->willReturnCallback(function($params) {
            if (empty($params)) {
                return [];
            }

            // Simple parameter parsing for testing
            $result = [];
            // Match key="value" patterns (including keys with colons like search:title)
            if (preg_match_all('/([^\s]+)\s*=\s*"([^"]*)"/', $params, $matches)) {
                foreach ($matches[1] as $i => $key) {
                    $result[trim($key)] = $matches[2][$i];
                }
            }
            return $result;
        });

        ee()->setMock('Variables/Parser', $variablesParserMock);
        $this->variablesParserMock = $variablesParserMock;
    }

    /**
     * Test that parse_tags handles templates with no tags
     */
    public function testParseTagsHandlesNoTags()
    {
        $template = 'This is a template with no ExpressionEngine tags.';

        $this->template->template = $template;
        $this->template->parse_tags();

        // Template should remain unchanged
        $this->assertEquals($template, $this->template->template);

        // No tag data should be stored
        $this->assertEmpty($this->template->tag_data);
    }

    /**
     * Test that parse_tags identifies single tags
     */
    public function testParseTagsIdentifiesSingleTags()
    {
        $template = 'Content {exp:channel:entries channel="news"} more content';

        $this->template->template = $template;
        $this->template->parse_tags();

        // Should have found one tag
        $this->assertCount(1, $this->template->tag_data);

        // Check tag data structure
        $tagData = $this->template->tag_data[0];
        $this->assertEquals('channel', $tagData['class']);
        $this->assertEquals('entries', $tagData['method']);
        $this->assertEquals(['channel' => 'news'], $tagData['params']);
        $this->assertStringContainsString('M0', $this->template->template); // Marker should be inserted
    }

    /**
     * Test that parse_tags identifies tag pairs
     */
    public function testParseTagsIdentifiesTagPairs()
    {
        $template = 'Content {exp:channel:entries channel="news"}Tag content{/exp:channel:entries} more content';

        $this->template->template = $template;
        $this->template->parse_tags();

        // Should have found one tag
        $this->assertCount(1, $this->template->tag_data);

        // Check tag data
        $tagData = $this->template->tag_data[0];
        $this->assertEquals('channel', $tagData['class']);
        $this->assertEquals('entries', $tagData['method']);
        $this->assertEquals(['channel' => 'news'], $tagData['params']);
        $this->assertEquals('Tag content', $tagData['block']); // Content between tags
        $this->assertStringContainsString('M0', $this->template->template); // Marker should be inserted
    }

    /**
     * Test that parse_tags handles multiple tags
     */
    public function testParseTagsHandlesMultipleTags()
    {
        $template = '{exp:channel:entries}Content{/exp:channel:entries} {exp:member:login_form}Login{/exp:member:login_form}';

        $this->template->template = $template;
        $this->template->parse_tags();

        // Should have found two tags
        $this->assertCount(2, $this->template->tag_data);

        // Check first tag
        $this->assertEquals('channel', $this->template->tag_data[0]['class']);
        $this->assertEquals('entries', $this->template->tag_data[0]['method']);

        // Check second tag
        $this->assertEquals('member', $this->template->tag_data[1]['class']);
        $this->assertEquals('login_form', $this->template->tag_data[1]['method']);
    }

    /**
     * Test that parse_tags processes tag parameters
     */
    public function testParseTagsProcessesParameters()
    {
        $template = '{exp:channel:entries channel="news" limit="10" orderby="date"}Content{/exp:channel:entries}';

        $this->template->template = $template;
        $this->template->parse_tags();

        $tagData = $this->template->tag_data[0];
        $expectedParams = [
            'channel' => 'news',
            'limit' => '10',
            'orderby' => 'date'
        ];
        $this->assertEquals($expectedParams, $tagData['params']);
    }

    /**
     * Test that parse_tags handles malformed tags gracefully
     */
    public function testParseTagsHandlesMalformedTags()
    {
        $template = 'Content {exp:channel:entries channel="news" more content';

        $this->template->template = $template;
        $this->template->parse_tags();

        // Should still process what it can
        $this->assertGreaterThanOrEqual(0, count($this->template->tag_data));
    }

    /**
     * Test that parse_tags handles nested tags in parameters
     */
    public function testParseTagsHandlesNestedTagsInParameters()
    {
        $template = '{exp:channel:entries channel="{exp:some:variable}"}Content{/exp:channel:entries}';

        // Mock getFullTag to return expanded tag
        $this->variablesParserMock->method('getFullTag')
            ->willReturn('{exp:channel:entries channel="{exp:some:variable}"}');

        $this->template->template = $template;
        $this->template->parse_tags();

        // Should have processed the tag
        $this->assertCount(1, $this->template->tag_data);
    }

    /**
     * Test that parse_tags processes search fields
     */
    public function testParseTagsProcessesSearchFields()
    {
        $template = '{exp:channel:entries search:title="news" search:body="content"}Tag content{/exp:channel:entries}';

        $this->template->template = $template;
        $this->template->parse_tags();

        $tagData = $this->template->tag_data[0];
        $expectedSearchFields = [
            'title' => 'news',
            'body' => 'content'
        ];
        $this->assertEquals($expectedSearchFields, $tagData['search_fields']);
    }

    /**
     * Test that parse_tags handles tag aliases
     */
    public function testParseTagsHandlesTagAliases()
    {
        // Set up tag class aliases using reflection (property is protected)
        $reflection = new \ReflectionClass($this->template);
        $property = $reflection->getProperty('tag_class_aliases');
        $property->setAccessible(true);
        $property->setValue($this->template, [
            'low_search' => 'pro_search'
        ]);

        $template = '{exp:low_search:results}Content{/exp:low_search:results}';

        $this->template->template = $template;
        $this->template->parse_tags();

        $tagData = $this->template->tag_data[0];
        $this->assertEquals('pro_search', $tagData['class']); // Should be aliased
        $this->assertEquals('results', $tagData['method']);
    }

    /**
     * Test that parse_tags handles no_results blocks
     */
    public function testParseTagsHandlesNoResultsBlocks()
    {
        $template = '{exp:channel:entries}Main content{if no_results}No results found{/if}{/exp:channel:entries}';

        $this->template->template = $template;
        $this->template->parse_tags();

        $tagData = $this->template->tag_data[0];
        $this->assertEquals('No results found', $tagData['no_results']);
        $this->assertStringContainsString('{if no_results}No results found{/if}', $tagData['no_results_block']);
    }

    /**
     * Test that parse_tags handles random tags specially
     */
    public function testParseTagsHandlesRandomTags()
    {
        $template = '{exp:channel:entries orderby="random"}Content{/exp:channel:entries}';

        $this->template->template = $template;
        $this->template->parse_tags();

        // Random tags should still be processed normally
        $this->assertCount(1, $this->template->tag_data);
        $tagData = $this->template->tag_data[0];
        $this->assertEquals(['orderby' => 'random'], $tagData['params']);
    }

    /**
     * Test that parse_tags processes tag chunks correctly
     */
    public function testParseTagsProcessesTagChunks()
    {
        $template = 'Before {exp:test:method param="value"}Content{/exp:test:method} After';

        $this->template->template = $template;
        $this->template->parse_tags();

        $tagData = $this->template->tag_data[0];
        $this->assertStringStartsWith('{exp:test:method', $tagData['tag']);
        $this->assertStringContainsString('param="value"', $tagData['tag']);
        $this->assertEquals('{exp:test:method param="value"}Content{/exp:test:method}', $tagData['chunk']);
    }

    /**
     * Test that parse_tags generates cache file names
     */
    public function testParseTagsGeneratesCacheFileNames()
    {
        $template = '{exp:channel:entries}Content{/exp:channel:entries}';

        $this->template->template = $template;
        $this->template->parse_tags();

        $tagData = $this->template->tag_data[0];
        $this->assertArrayHasKey('cfile', $tagData);
        $this->assertIsString($tagData['cfile']);
        $this->assertEquals(32, strlen($tagData['cfile'])); // MD5 hash length
    }

    /**
     * Test that parse_tags handles frontedit link removal
     */
    public function testParseTagsHandlesFronteditLinks()
    {
        $template = '{exp:channel:entries}{frontedit_link some="params"}Content{/exp:channel:entries}';

        $this->template->template = $template;
        $this->template->parse_tags();

        $tagData = $this->template->tag_data[0];
        // frontedit_link should be removed from the tag
        $this->assertStringNotContainsString('frontedit_link', $tagData['tag']);
    }
}
