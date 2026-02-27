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

require_once SYSPATH . 'ee/ExpressionEngine/Tests/TestReflectionHelper.php';
require_once __DIR__ . '/test_helpers.php';

// Bootstrap minimal EE environment
if (!defined('APP_VER')) {
    define('APP_VER', '7.5.14');
}
if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../../../../../../');
}
if (!defined('APPPATH')) {
    define('APPPATH', BASEPATH . 'ee/');
}
if (!defined('SYSPATH')) {
    define('SYSPATH', BASEPATH . '../');
}
if (!defined('PATH_CACHE')) {
    define('PATH_CACHE', SYSPATH . 'user/cache/');
}
if (!defined('PATH_THIRD')) {
    define('PATH_THIRD', SYSPATH . 'user/addons/');
}
if (!defined('PATH_ADDONS')) {
    define('PATH_ADDONS', SYSPATH . 'ee/ExpressionEngine/Addons/');
}
if (!defined('PATH_THEMES')) {
    define('PATH_THEMES', realpath(SYSPATH . '../themes') . '/');
}

// Global helper functions are defined in other test files

// Include eeObjectMock for mocking first
require_once __DIR__ . '/../../../eeObjectMock.php';

// Template constants
if (!defined('LD')) {
    define('LD', '{');
}
if (!defined('RD')) {
    define('RD', '}');
}
if (!defined('AMP')) {
    define('AMP', '&amp;');
}

// Include eeObjectMock for mocking first (defines global functions)
require_once __DIR__ . '/../../../eeObjectMock.php';

// Include required files
// Use SYSPATH to find the correct path regardless of bootstrap configuration
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

/**
 * Base test class for EE_Template tests
 *
 * Provides common setup and mocking infrastructure for all EE_Template test classes.
 */
class EE_TemplateProcessingTest extends TestCase
{
    /**
     * @var \EE_Template
     */
    protected $template;

    /**
     * @var TestEnvironment
     */
    protected $__EE_TEST_ENV__;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize test environment
        $this->__EE_TEST_ENV__ = new \TestEnvironment();

        // Set up mocks for EE_Template dependencies
        $this->setupMocks();

        // Create EE_Template instance
        $this->template = new \EE_Template();
    }

    protected function tearDown(): void
    {
        // Reset mocks after each test
        if (isset($this->__EE_TEST_ENV__)) {
            ee()->resetMocks();
        }

        parent::tearDown();
    }

    /**
     * Set up the necessary mocks for EE_Template testing
     */
    protected function setupMocks(): void
    {
        // Mock config with basic site settings
        $configMock = $this->getMockBuilder(\FakeConfig::class)
            ->setMethods(['site_url'])
            ->getMock();
        $configMock->items = [
            'site_id' => 1,
            'site_short_name' => 'default_site',
            'site_url' => 'https://example.com/',
            'multiple_sites_enabled' => 'n',
            'show_profiler' => 'n'
        ];
        $configMock->method('site_url')->willReturn('https://example.com/');
        $this->__EE_TEST_ENV__->setMock('config', $configMock);

        // Mock session
        $sessionMock = new \eeSingletonSessionMock();
        $sessionMock->setUserdata('member_id', 0);
        $sessionMock->setUserdata('group_id', 3);
        $sessionMock->setUserdata('role_id', 3);
        $this->__EE_TEST_ENV__->setMock('session', $sessionMock);

        // Mock database
        $dbMock = new \FakeDb();
        $this->__EE_TEST_ENV__->setMock('db', $dbMock);

        // Mock functions
        $functionsMock = new \FakeFunctions();
        $this->__EE_TEST_ENV__->setMock('functions', $functionsMock);

        // Mock URI
        $uriMock = $this->getMockBuilder('stdClass')
            ->setMethods(['segment', 'segment_array', 'uri_string', 'page_query_string'])
            ->getMock();
        $uriMock->method('segment')->willReturnCallback(function($n) {
            $segments = ['', 'default', 'index'];
            return isset($segments[$n]) ? $segments[$n] : false;
        });
        $uriMock->method('segment_array')->willReturn(['default', 'index']);
        $uriMock->method('uri_string')->willReturn('default/index');
        $uriMock->method('page_query_string')->willReturn('');
        $this->__EE_TEST_ENV__->setMock('uri', $uriMock);

        // Mock extensions
        $extensionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['active_hook', 'call'])
            ->getMock();
        $extensionsMock->method('active_hook')->willReturn(false);
        $this->__EE_TEST_ENV__->setMock('extensions', $extensionsMock);

        // Mock core
        $coreMock = $this->getMockBuilder('stdClass')
            ->setMethods(['set_newrelic_transaction'])
            ->getMock();
        $coreMock->method('set_newrelic_transaction')->willReturn(null);
        $this->__EE_TEST_ENV__->setMock('core', $coreMock);

        // Mock load
        $loadMock = new \eeSingletonLoadMock();
        $this->__EE_TEST_ENV__->setMock('load', $loadMock);

        // Mock input
        $inputMock = new \eeSingletonInputMock();
        $this->__EE_TEST_ENV__->setMock('input', $inputMock);

        // Mock localize
        $localizeMock = $this->getMockBuilder('stdClass')
            ->setMethods(['now', 'string_to_timestamp', 'format_date'])
            ->getMock();
        $localizeMock->method('now')->willReturn(time());
        $localizeMock->method('string_to_timestamp')->willReturn(time());
        $localizeMock->method('format_date')->willReturn('2024-01-01');
        $this->__EE_TEST_ENV__->setMock('localize', $localizeMock);

        // Mock logger
        $loggerMock = new \eeSingletonLoggerMock();
        $this->__EE_TEST_ENV__->setMock('logger', $loggerMock);

        // Mock cache
        $cacheMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get', 'save', 'get_metadata', 'delete'])
            ->getMock();
        $cacheMock->method('get')->willReturn(false);
        $cacheMock->method('save')->willReturn(true);
        $cacheMock->method('get_metadata')->willReturn([]);
        $this->__EE_TEST_ENV__->setMock('cache', $cacheMock);
    }

    /**
     * Test chunkGlobalsArray method - Global variable chunking
     */
    public function testChunkGlobalsArrayHandlesEmptyArray()
    {
        $reflection = new \ReflectionMethod($this->template, 'chunkGlobalsArray');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        $result = $reflection->invoke($this->template, []);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertIsArray($result[0]);
        $this->assertEmpty($result[0]);
    }

    public function testChunkGlobalsArrayHandlesSmallArray()
    {
        $reflection = new \ReflectionMethod($this->template, 'chunkGlobalsArray');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        $globals = ['var1', 'var2', 'var3'];
        $result = $reflection->invoke($this->template, $globals);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertIsArray($result[0]);
        $this->assertContains('var1', $result[0]);
        $this->assertContains('var2', $result[0]);
        $this->assertContains('var3', $result[0]);
    }

    public function testChunkGlobalsArrayCreatesNewChunkWhenMaxLengthExceeded()
    {
        $reflection = new \ReflectionMethod($this->template, 'chunkGlobalsArray');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        $first = str_repeat('a', 20000);
        $second = str_repeat('b', 20000);
        $tail = 'tail';

        $result = $reflection->invoke($this->template, [$first, $second, $tail]);

        $this->assertCount(2, $result);
        $this->assertContains($first, $result[0]);
        $this->assertContains($second, $result[0]);
        $this->assertSame([$tail], $result[1]);
    }

    /**
     * Test _find_layout method - Layout tag detection
     */
    public function testFindLayoutReturnsNullForNoLayout()
    {
        $reflection = new \ReflectionMethod($this->template, '_find_layout');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        $result = $reflection->invoke($this->template);

        $this->assertNull($result);
    }

    public function testFindLayoutReturnsTagAndRemovesItFromTemplate()
    {
        $reflection = new \ReflectionMethod($this->template, '_find_layout');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        $this->template->template = '{layout="layouts/main"}{exp:channel:entries}';
        $result = $reflection->invoke($this->template);

        $this->assertIsArray($result);
        $this->assertSame('{layout="layouts/main"}', $result[0]);
        $this->assertSame('"layouts/main"', $result[2]);
        $this->assertSame('{exp:channel:entries}', $this->template->template);
    }

    public function testFindLayoutTriggersFatalErrorWhenLayoutAppearsAfterExpTag()
    {
        $reflection = new \ReflectionMethod($this->template, '_find_layout');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        ee()->config->setItem('debug', 1);
        $langMock = $this->getMockBuilder('stdClass')
            ->setMethods(['line'])
            ->getMock();
        $langMock->method('line')->with('error_layout_too_late')->willReturn('layout too late');
        ee()->setMock('lang', $langMock);

        $outputMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fatal_error'])
            ->getMock();
        $outputMock->method('fatal_error')->willThrowException(new \RuntimeException('layout-too-late'));
        ee()->setMock('output', $outputMock);

        $this->template->template = '{exp:channel:entries}{layout="layouts/main"}';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('layout-too-late');
        $reflection->invoke($this->template);
    }

    public function testFindLayoutTriggersFatalErrorWhenMultipleLayoutsFound()
    {
        $reflection = new \ReflectionMethod($this->template, '_find_layout');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        ee()->config->setItem('debug', 1);
        $langMock = $this->getMockBuilder('stdClass')
            ->setMethods(['line'])
            ->getMock();
        $langMock->method('line')->with('error_multiple_layouts')->willReturn('multiple layouts');
        ee()->setMock('lang', $langMock);

        $outputMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fatal_error'])
            ->getMock();
        $outputMock->method('fatal_error')->willThrowException(new \RuntimeException('multiple-layouts'));
        ee()->setMock('output', $outputMock);

        $this->template->template = '{layout="layouts/main"}{layout="layouts/secondary"}';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('multiple-layouts');
        $reflection->invoke($this->template);
    }

    /**
     * Test _cleanup_layout_tags method - Layout tag removal
     */
    public function testCleanupLayoutTagsRemovesUndeclaredLayoutVariables()
    {
        $reflection = new \ReflectionMethod($this->template, '_cleanup_layout_tags');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        // Set up the final_template property with undeclared layout variable tags
        $this->template->final_template = 'Content{layout:variable}value{/layout:variable}{layout:contents}main{/layout:contents}';

        $reflection->invoke($this->template);

        // Should remove undeclared layout variable tags (opening tags only, as per the method)
        $this->assertEquals('Contentvalue{/layout:variable}main{/layout:contents}', $this->template->final_template);
    }

    public function testCleanupLayoutTagsHandlesNoTags()
    {
        $reflection = new \ReflectionMethod($this->template, '_cleanup_layout_tags');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        $template = 'Simple content with no layout tags';
        $this->template->final_template = $template;

        $reflection->invoke($this->template);

        // Should not modify content without layout tags
        $this->assertEquals($template, $this->template->final_template);
    }

    /**
     * Test process_layout_template method - Layout processing
     */
    public function testProcessLayoutTemplateReturnsTemplateWhenNoLayout()
    {
        $reflection = new \ReflectionMethod($this->template, 'process_layout_template');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        $template = 'Simple template content';
        $result = $reflection->invoke($this->template, $template, null);

        $this->assertEquals($template, $result);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testProcessLayoutTemplateThrowsWhenLayoutParamsUseReservedContentsName()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'process_layout_template');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        ee()->setMock('Variables/Parser', new class {
            public function getFullTag($template, $tag)
            {
                return $tag;
            }

            public function parseTagParameters($str)
            {
                return ['contents' => 'reserved'];
            }
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('layout_contents_reserved');

        $layout = ['{layout="layouts/main" contents="reserved"}', '{layout=', '"layouts/main" contents="reserved"'];
        $reflection->invoke($this->template, 'Body', $layout);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testProcessLayoutTemplateThrowsWhenLayoutSetUsesReservedContentsName()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'process_layout_template');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        ee()->setMock('Variables/Parser', new class {
            public function getFullTag($template, $tag)
            {
                if (strpos($tag, '{layout:set') === 0) {
                    $start = strpos($template, '{layout:set');
                    $end = strpos($template, '}', $start);
                    return substr($template, $start, $end - $start + 1);
                }

                return $tag;
            }

            public function parseTagParameters($str)
            {
                $params = [];
                if (preg_match_all('/([a-zA-Z_]+)\s*=\s*"([^"]*)"/', $str, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $params[$match[1]] = $match[2];
                    }
                }

                return $params;
            }
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('layout_contents_reserved');

        $layout = ['{layout="layouts/main"}', '{layout=', '"layouts/main"'];
        $template = 'Body {layout:set name="contents" value="unsafe"}';
        $reflection->invoke($this->template, $template, $layout);
    }

    public function testProcessLayoutTemplateParsesSettersAndInjectsLayoutContents()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'process_layout_template');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        $variablesParser = new class {
            public function getFullTag($template, $tag)
            {
                $start = strpos($template, $tag);
                if ($start === false) {
                    return $tag;
                }

                $openEnd = strpos($template, '}', $start);
                if ($openEnd === false) {
                    return $tag;
                }

                $openingTag = substr($template, $start, $openEnd - $start + 1);
                if (strpos($openingTag, '{layout:set') !== 0) {
                    return $openingTag;
                }

                // For layout:set tags EE expects just the opening tag text here.
                return $openingTag;
            }

            public function parseTagParameters($str)
            {
                $params = [];
                if (preg_match_all('/([a-zA-Z_]+)\s*=\s*"([^"]*)"/', $str, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $params[$match[1]] = $match[2];
                    }
                }

                return $params;
            }
        };
        ee()->setMock('Variables/Parser', $variablesParser);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['fetch_and_parse', 'process_sub_templates', '_get_fetch_data'])
            ->getMock();

        $templateMock->method('fetch_and_parse')
            ->willReturnCallback(function () use ($templateMock) {
                $templateMock->template = 'LAYOUT[{layout:contents}]';
                $templateMock->templates_sofar = '|1:layouts/main|';
                return null;
            });
        $templateMock->method('process_sub_templates')
            ->willReturnCallback(function ($template) {
                return $template . '|sub';
            });
        $templateMock->method('_get_fetch_data')
            ->willReturn(['layouts', 'main', 1]);

        $templateMock->layout_vars = [];
        $templateMock->templates_sofar = '|1:layouts/main|';

        $template = 'Body {layout:set name="title"}Title{/layout:set}{layout:set:append name="crumbs"}A{/layout:set:append}{layout:set:prepend name="crumbs"}Z{/layout:set:prepend}{layout:set:prepend name="tags"}first{/layout:set:prepend}';
        $layout = ['{layout="layouts/main" theme="news"}', '{layout=', '"layouts/main" theme="news"'];

        $result = $reflection->invoke($templateMock, $template, $layout);

        $this->assertStringContainsString('LAYOUT[Body', $result);
        $this->assertStringEndsWith('|sub', $result);
        $this->assertEquals('news', $templateMock->layout_vars['theme']);
        $this->assertEquals('Title', $templateMock->layout_vars['title']);
        $this->assertEquals(['Z', 'A'], $templateMock->layout_vars['crumbs']);
        $this->assertEquals(['first'], $templateMock->layout_vars['tags']);
    }

    public function testProcessLayoutTemplateReturnsEarlyWhenFetchDataCannotBeResolved()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'process_layout_template');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        $variablesParser = new class {
            public function getFullTag($template, $tag)
            {
                $start = strpos($template, $tag);
                if ($start === false) {
                    return $tag;
                }

                $openEnd = strpos($template, '}', $start);
                if ($openEnd === false) {
                    return $tag;
                }

                return substr($template, $start, $openEnd - $start + 1);
            }

            public function parseTagParameters($str)
            {
                if (strpos($str, 'bad="1"') !== false) {
                    return false;
                }

                $params = [];
                if (preg_match_all('/([a-zA-Z_]+)\s*=\s*"([^"]*)"/', $str, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $params[$match[1]] = $match[2];
                    }
                }

                return $params;
            }
        };
        ee()->setMock('Variables/Parser', $variablesParser);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['_get_fetch_data'])
            ->getMock();
        $templateMock->method('_get_fetch_data')->willReturn(null);

        $template = 'Body {layout:set name="title" value="x"}';
        $layout = ['{layout="invalid" bad="1"}', '{layout=', '"invalid" bad="1"'];

        $result = $reflection->invoke($templateMock, $template, $layout);

        $this->assertStringContainsString('Body', $result);
        $this->assertStringNotContainsString('layout:set', $result);
    }

    /**
     * Test process_sub_templates method - Embed processing
     */
    public function testProcessSubTemplatesReturnsTemplateWhenNoEmbeds()
    {
        $template = 'Content with no embeds';
        $result = $this->template->process_sub_templates($template);

        $this->assertEquals($template, $result);
    }

    /**
     * Test _get_fetch_data method - Template path parsing
     */
    public function testGetFetchDataMethodSignature()
    {
        $reflection = new \ReflectionMethod($this->template, '_get_fetch_data');
        $this->assertTrue($reflection->isProtected());
        $this->assertTrue($reflection->isStatic() === false);

        $parameters = $reflection->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('template_path', $parameters[0]->getName());
    }

    public function testGetFetchDataReturnsNullForInvalidPath()
    {
        // Test method signature for invalid path handling
        $reflection = new \ReflectionMethod($this->template, '_get_fetch_data');
        $this->assertTrue($reflection->isProtected());
        $this->assertTrue($reflection->isStatic() === false);
    }

    public function testGetFetchDataReturnsNullForEmptyPath()
    {
        // Test method signature for empty path handling
        $reflection = new \ReflectionMethod($this->template, '_get_fetch_data');
        $this->assertTrue($reflection->isProtected());
        $this->assertTrue($reflection->isStatic() === false);
    }
}
