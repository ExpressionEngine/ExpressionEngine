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
 * Tests for EE_Template::no_results() method
 */
class EE_TemplateNoResultsTest extends EE_TemplateAdvancedMethodsTestBase
{
    /**
     * Test that no_results method exists and is callable
     */
    public function testNoResultsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'no_results'));
        $this->assertTrue(is_callable([$this->template, 'no_results']));
    }

    /**
     * Test basic no results content return
     */
    public function testNoResultsReturnsBasicContent()
    {
        $this->setupTemplateWithNoResults('No entries found.');

        $result = $this->template->no_results();

        $this->assertEquals('No entries found.', $result);
    }

    /**
     * Test no results without redirect syntax
     */
    public function testNoResultsHandlesContentWithoutRedirect()
    {
        $this->setupTemplateWithNoResults('Simple no results message');

        $result = $this->template->no_results();

        $this->assertEquals('Simple no results message', $result);
    }

    /**
     * Test 404 redirect handling
     */
    public function testNoResultsHandles404Redirect()
    {
        $this->setupTemplateWithNoResults('{redirect="404"}');

        // Mock the show_404 method to avoid actual exit
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['show_404'])
            ->getMock();
        $templateMock->method('show_404')->willThrowException(new \Exception('404 called'));

        $this->template = $templateMock;
        $this->setupTemplateWithNoResults('{redirect="404"}');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('404 called');

        $this->template->no_results();
    }

    /**
     * Test URL redirect handling
     */
    public function testNoResultsHandlesUrlRedirect()
    {
        $this->setupTemplateWithNoResults('{redirect="https://example.com/page"}');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Redirect called: https://example.com/page');

        $this->template->no_results();
    }

    /**
     * Test relative path redirect handling
     */
    public function testNoResultsHandlesRelativePathRedirect()
    {
        $this->setupTemplateWithNoResults('{redirect="search/results"}');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Redirect called: https://example.com/search/results');

        $this->template->no_results();
    }

    /**
     * Test invalid redirect syntax - should return content as-is
     */
    public function testNoResultsHandlesInvalidRedirectSyntax()
    {
        $this->setupTemplateWithNoResults('{redirect="invalid syntax');

        $result = $this->template->no_results();

        $this->assertEquals('{redirect="invalid syntax', $result);
    }

    /**
     * Test malformed redirect tag
     */
    public function testNoResultsHandlesMalformedRedirectTag()
    {
        $this->setupTemplateWithNoResults('{redirect}');

        $result = $this->template->no_results();

        $this->assertEquals('{redirect}', $result);
    }

    /**
     * Test redirect with status code
     */
    public function testNoResultsHandlesRedirectWithStatusCode()
    {
        $this->setupTemplateWithNoResults('{redirect="page" status_code="301"}');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Redirect called: https://example.com/page');

        $this->template->no_results();
    }

    /**
     * Test empty no_results content
     */
    public function testNoResultsHandlesEmptyContent()
    {
        $this->setupTemplateWithNoResults('');

        $result = $this->template->no_results();

        $this->assertEquals('', $result);
    }

    /**
     * Test no_results with complex content
     */
    public function testNoResultsHandlesComplexContent()
    {
        $complexContent = '<div class="no-results"><h3>No Results Found</h3><p>Please try a different search.</p></div>';
        $this->setupTemplateWithNoResults($complexContent);

        $result = $this->template->no_results();

        $this->assertEquals($complexContent, $result);
    }

    /**
     * Test multiple redirect tags - should use first one
     */
    public function testNoResultsHandlesMultipleRedirectTags()
    {
        $this->setupTemplateWithNoResults('{redirect="first"}{redirect="second"}');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Redirect called: https://example.com/first');

        $this->template->no_results();
    }

    /**
     * Test redirect tag with quotes in URL
     */
    public function testNoResultsHandlesRedirectWithQuotesInUrl()
    {
        $this->setupTemplateWithNoResults('{redirect="search?q=test&sort=date"}');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Redirect called: https://example.com/search?q=test&sort=date');

        $this->template->no_results();
    }

    public function testNoResultsReturnsContentWhen404RedirectConfiguredWithoutTemplatePath()
    {
        $configMock = $this->getMockBuilder('stdClass')
            ->setMethods(['item', 'setItem', 'site_url'])
            ->getMock();
        $configMock->method('item')->willReturnCallback(function($key) {
            if ($key === 'site_404') {
                return '404';
            }

            $defaults = [
                'site_id' => 1,
                'site_short_name' => 'default_site',
                'multiple_sites_enabled' => 'n',
                'template_loop_prevention' => 'y',
                'debug' => 1,
                'save_tmpl_files' => 'n'
            ];

            return $defaults[$key] ?? null;
        });
        $configMock->method('setItem')->willReturnSelf();
        $configMock->method('site_url')->willReturn('https://example.com/');
        $configMock->_global_vars = [];
        ee()->setMock('config', $configMock);

        $this->setupTemplateWithNoResults('{redirect="404"}');

        $result = $this->template->no_results();

        $this->assertSame('{redirect="404"}', $result);
    }
}
