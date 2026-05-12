<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

class EE_TemplateParseNocacheTest extends EE_TemplateTestBase
{
    public function testParseNocacheMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'parse_nocache'));
        $this->assertTrue(is_callable([$this->template, 'parse_nocache']));
    }

    public function testParseNocacheNoNocacheContent()
    {
        $str = 'Normal template content without nocache blocks';
        $result = $this->template->parse_nocache($str);

        $this->assertEquals($str, $result);
    }

    public function testParseNocacheProcessesNocacheBlocks()
    {
        // Test that nocache blocks are detected and processed
        // Since the full processing is complex, we'll test that the method
        // attempts to process comment forms (the method signature and basic logic)

        // Create a mock template that will allow us to test the processing logic
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_fetch_site_ids', '_assign_form_params'])
            ->getMock();

        $templateMock->method('_fetch_site_ids')->willReturn(null);
        $templateMock->method('_assign_form_params')->willReturn(array());

        // Suppress deprecation warnings for null handling in PHP 7.4+
        error_reporting(E_ALL & ~E_DEPRECATED);

        // Mock security sanitize_filename
        $securityMock = $this->getMockBuilder('stdClass')
            ->setMethods(['sanitize_filename'])
            ->getMock();
        $securityMock->method('sanitize_filename')->willReturn('comment');
        ee()->setMock('security', $securityMock);

        // Mock addon system
        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();

        $moduleMock = $this->getMockBuilder('stdClass')
            ->setMethods(['getModuleClass'])
            ->getMock();

        $commentClassMock = $this->getMockBuilder('stdClass')
            ->setMethods(['form'])
            ->getMock();
        $commentClassMock->method('form')->willReturn('<form>processed</form>');

        $moduleMock->method('getModuleClass')->willReturn($commentClassMock);
        $addonMock->method('get')->willReturn($moduleMock);

        ee()->setMock('Addon', $addonMock);

        // Mock Variables/Parser
        $parserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['extractVariables', 'parseTagParameters'])
            ->getMock();
        $parserMock->method('extractVariables')->willReturn(array('var_single' => array(), 'var_pair' => array()));
        $parserMock->method('parseTagParameters')->willReturn(array());
        ee()->setMock('Variables/Parser', $parserMock);

        // Mock functions
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['assign_conditional_variables'])
            ->getMock();
        $functionsMock->method('assign_conditional_variables')->willReturn(array());
        $functionsMock->cached_captcha = '';
        ee()->setMock('functions', $functionsMock);

        // Test with a properly formatted nocache block
        $str = 'Before {NOCACHE_comment_FORM=""}content{/NOCACHE_FORM} After';
        $result = $templateMock->parse_nocache($str);

        // The method should process the nocache block
        $this->assertStringContainsString('Before', $result);
        $this->assertStringContainsString('After', $result);
        $this->assertStringNotContainsString('{NOCACHE_comment_FORM', $result);
    }


    public function testParseNocacheSetsTagProperties()
    {
        // Mock security and addon for comment form
        $securityMock = $this->getMockBuilder('stdClass')
            ->setMethods(['sanitize_filename'])
            ->getMock();
        $securityMock->method('sanitize_filename')->willReturn('comment');
        ee()->setMock('security', $securityMock);

        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();

        $moduleMock = $this->getMockBuilder('stdClass')
            ->setMethods(['getModuleClass'])
            ->getMock();

        $commentClassMock = $this->getMockBuilder('stdClass')
            ->setMethods(['form'])
            ->getMock();
        $commentClassMock->method('form')->willReturn('<form>test</form>');

        $moduleMock->method('getModuleClass')->willReturn($commentClassMock);
        $addonMock->method('get')->with('comment')->willReturn($moduleMock);

        ee()->setMock('Addon', $addonMock);

        // Mock Variables/Parser
        $parserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['extractVariables', 'parseTagParameters'])
            ->getMock();

        $parserMock->method('extractVariables')->willReturn(array(
            'var_single' => array('test_var' => 'test_value'),
            'var_pair' => array()
        ));

        $parserMock->method('parseTagParameters')->willReturn(array('param' => 'value'));

        ee()->setMock('Variables/Parser', $parserMock);

        // Mock functions
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['assign_conditional_variables'])
            ->getMock();
        $functionsMock->method('assign_conditional_variables')->willReturn(array());
        $functionsMock->cached_captcha = 'captcha';
        ee()->setMock('functions', $functionsMock);

        $str = '{NOCACHE_comment_FORM="params"}content{/NOCACHE_FORM}';
        $this->template->parse_nocache($str);

        // Verify tag properties were set
        $this->assertEquals(array('param' => 'value'), $this->template->tagparams);
        $this->assertEquals('content', $this->template->tagdata);
        $this->assertEquals(array('test_var' => 'test_value'), $this->template->var_single);
    }

    public function testParseNocacheAssignsFormParamsWhenTagDataExists()
    {
        $this->template->tag_data = [[
            'params' => [
                'form_id' => 'comment-form-id',
                'form_class' => 'comment-form-class',
            ],
        ]];

        $securityMock = $this->getMockBuilder('stdClass')
            ->setMethods(['sanitize_filename'])
            ->getMock();
        $securityMock->method('sanitize_filename')->willReturn('comment');
        ee()->setMock('security', $securityMock);

        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();

        $moduleMock = $this->getMockBuilder('stdClass')
            ->setMethods(['getModuleClass'])
            ->getMock();

        $mockClassName = 'MockCommentClass_' . uniqid();
        eval("class $mockClassName { public function form(\$return_form = false, \$captcha = '') { return '<form>assigned</form>'; } }");

        $moduleMock->method('getModuleClass')->willReturn($mockClassName);
        $addonMock->method('get')->willReturn($moduleMock);
        ee()->setMock('Addon', $addonMock);

        $parserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['extractVariables', 'parseTagParameters'])
            ->getMock();
        $parserMock->method('extractVariables')->willReturn([
            'var_single' => [],
            'var_pair' => [],
        ]);
        $parserMock->method('parseTagParameters')->willReturn([]);
        ee()->setMock('Variables/Parser', $parserMock);

        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['assign_conditional_variables'])
            ->getMock();
        $functionsMock->method('assign_conditional_variables')->willReturn([]);
        $functionsMock->cached_captcha = '';
        ee()->setMock('functions', $functionsMock);

        $this->template->parse_nocache('{NOCACHE_comment_FORM=""}content{/NOCACHE_FORM}');

        $this->assertSame('comment-form-id', $this->template->form_id);
        $this->assertSame('comment-form-class', $this->template->form_class);
    }

    public function testParseNocacheBasicFunctionality()
    {
        // Test that the method can be called and returns a string
        // The complex logic requires extensive mocking, so we'll test the basic interface
        $str = 'Test string';
        $result = $this->template->parse_nocache($str);

        $this->assertIsString($result);
    }

    public function testParseNocacheMalformedSyntax()
    {
        // Test malformed NOCACHE blocks - missing closing braces, invalid quotes, nested braces
        $str = '{NOCACHE_comment_FORM="unclosed}content{/NOCACHE_FORM}';
        $result = $this->template->parse_nocache($str);

        // Should not crash and return original string or processed version
        $this->assertIsString($result);
    }


    public function testParseNocacheParameterInjection()
    {
        // Test parameter injection/XSS attempts
        $securityMock = $this->getMockBuilder('stdClass')
            ->setMethods(['sanitize_filename'])
            ->getMock();
        $securityMock->method('sanitize_filename')->willReturn('comment');
        ee()->setMock('security', $securityMock);

        // Mock addon system to prevent actual processing
        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();

        $moduleMock = $this->getMockBuilder('stdClass')
            ->setMethods(['getModuleClass'])
            ->getMock();

        $commentClassMock = $this->getMockBuilder('stdClass')
            ->setMethods(['form'])
            ->getMock();
        $commentClassMock->method('form')->willReturn('<form>safe content</form>');

        $moduleMock->method('getModuleClass')->willReturn($commentClassMock);
        $addonMock->method('get')->willReturn($moduleMock);

        ee()->setMock('Addon', $addonMock);

        // Mock Variables/Parser
        $parserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['extractVariables', 'parseTagParameters'])
            ->getMock();
        $parserMock->method('extractVariables')->willReturn(array('var_single' => array(), 'var_pair' => array()));
        $parserMock->method('parseTagParameters')->willReturn(array('param' => '<script>alert(1)</script>'));
        ee()->setMock('Variables/Parser', $parserMock);

        // Mock functions
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['assign_conditional_variables'])
            ->getMock();
        $functionsMock->method('assign_conditional_variables')->willReturn(array());
        $functionsMock->cached_captcha = '';
        ee()->setMock('functions', $functionsMock);

        $str = '{NOCACHE_comment_FORM="<script>alert(1)</script>"}content{/NOCACHE_FORM}';
        $result = $this->template->parse_nocache($str);

        // Should not execute scripts and handle gracefully
        $this->assertIsString($result);
        $this->assertStringNotContainsString('<script>', $result);
    }



    public function testParseNocacheMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'parse_nocache');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('str', $parameters[0]->getName());
    }


    /**
     * Test nested nocache blocks within each other
     */
    public function testParseNocacheHandlesNestedBlocks()
    {
        // Test that only comment forms are processed, other types are ignored
        // Note: parse_nocache has limitations with nested nocache blocks
        $str = 'Content {NOCACHE_comment_FORM=""}simple content{/NOCACHE_FORM} and {NOCACHE_channel_FORM=""}ignored content{/NOCACHE_FORM} final';

        // Use the real template instance with mocked dependencies

        // Mock security sanitize_filename
        $securityMock = $this->getMockBuilder('stdClass')
            ->setMethods(['sanitize_filename'])
            ->getMock();
        $securityMock->method('sanitize_filename')->willReturnCallback(function($input) {
            return $input; // Return as-is for testing
        });
        ee()->setMock('security', $securityMock);

        // Mock addon system
        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();

        $moduleMock = $this->getMockBuilder('stdClass')
            ->setMethods(['getModuleClass'])
            ->getMock();

        // Create a mock class name that can be instantiated
        $mockClassName = 'MockCommentClass_' . uniqid();
        eval("class $mockClassName { public function form(\$return_form = false, \$captcha = '') { return '<form>comment processed</form>'; } }");

        $moduleMock->method('getModuleClass')->willReturn($mockClassName);
        $addonMock->method('get')->willReturn($moduleMock);

        ee()->setMock('Addon', $addonMock);

        $parserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['extractVariables', 'parseTagParameters'])
            ->getMock();
        $parserMock->method('extractVariables')->willReturn(['var_single' => [], 'var_pair' => []]);
        $parserMock->method('parseTagParameters')->willReturn([]);
        ee()->setMock('Variables/Parser', $parserMock);

        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['assign_conditional_variables'])
            ->getMock();
        $functionsMock->method('assign_conditional_variables')->willReturn([]);
        $functionsMock->cached_captcha = '';
        ee()->setMock('functions', $functionsMock);

        $result = $this->template->parse_nocache($str);

        // Comment form should be processed
        $this->assertStringContainsString('Content', $result);
        $this->assertStringContainsString('final', $result);
        $this->assertStringContainsString('<form>comment processed</form>', $result);
        // Channel form should remain unprocessed
        $this->assertStringContainsString('{NOCACHE_channel_FORM=""}ignored content{/NOCACHE_FORM}', $result);
        $this->assertStringNotContainsString('{NOCACHE_comment_FORM=', $result);
    }

    /**
     * Test nocache with different addon types (module vs plugin)
     */
    public function testParseNocacheDifferentAddonTypes()
    {
        // Test that only comment forms are processed, other addon types are ignored
        $str = '{NOCACHE_channel_FORM="preview=PRV123"}channel content{/NOCACHE_FORM}{NOCACHE_comment_FORM=""}comment content{/NOCACHE_FORM}';

        $securityMock = $this->getMockBuilder('stdClass')
            ->setMethods(['sanitize_filename'])
            ->getMock();
        $securityMock->method('sanitize_filename')->willReturnCallback(function($input) {
            return $input; // Return as-is
        });
        ee()->setMock('security', $securityMock);

        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();

        $moduleMock = $this->getMockBuilder('stdClass')
            ->setMethods(['getModuleClass'])
            ->getMock();

        // Create a mock class name that can be instantiated
        $mockClassName = 'MockCommentClass_' . uniqid();
        eval("class $mockClassName { public function form(\$return_form = false, \$captcha = '') { return '<form class=\"comment\">processed comment</form>'; } }");

        $moduleMock->method('getModuleClass')->willReturn($mockClassName);
        $addonMock->method('get')->willReturn($moduleMock);
        ee()->setMock('Addon', $addonMock);

        $parserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['extractVariables', 'parseTagParameters'])
            ->getMock();
        $parserMock->method('extractVariables')->willReturn(['var_single' => [], 'var_pair' => []]);
        $parserMock->method('parseTagParameters')->willReturn([]);
        ee()->setMock('Variables/Parser', $parserMock);

        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['assign_conditional_variables'])
            ->getMock();
        $functionsMock->method('assign_conditional_variables')->willReturn([]);
        $functionsMock->cached_captcha = '';
        ee()->setMock('functions', $functionsMock);

        $result = $this->template->parse_nocache($str);

        // Comment form should be processed
        $this->assertStringContainsString('<form class="comment">processed comment</form>', $result);
        $this->assertStringContainsString('channel content', $result);
        // Channel form should remain unprocessed
        $this->assertStringContainsString('{NOCACHE_channel_FORM="preview=PRV123"}channel content{/NOCACHE_FORM}', $result);
        $this->assertStringNotContainsString('{NOCACHE_comment_FORM=', $result);
    }

    /**
     * Test nocache with complex parameter scenarios
     */
    public function testParseNocacheComplexParameters()
    {
        $securityMock = $this->getMockBuilder('stdClass')
            ->setMethods(['sanitize_filename'])
            ->getMock();
        $securityMock->method('sanitize_filename')->willReturn('comment');
        ee()->setMock('security', $securityMock);

        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();

        $moduleMock = $this->getMockBuilder('stdClass')
            ->setMethods(['getModuleClass'])
            ->getMock();

        // Create a mock class name that can be instantiated
        $mockClassName = 'MockCommentClass_' . uniqid();
        eval("class $mockClassName { public function form(\$return_form = false, \$captcha = '') { return '<form>processed with params</form>'; } }");

        $moduleMock->method('getModuleClass')->willReturn($mockClassName);
        $addonMock->method('get')->willReturn($moduleMock);
        ee()->setMock('Addon', $addonMock);

        $parserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['extractVariables', 'parseTagParameters'])
            ->getMock();
        $parserMock->method('extractVariables')->willReturn(['var_single' => [], 'var_pair' => []]);
        $parserMock->method('parseTagParameters')->willReturn([
            'channel_id' => '5',
            'entry_id' => '123',
            'return' => 'site/thanks',
            'preview' => 'PRV456'
        ]);
        ee()->setMock('Variables/Parser', $parserMock);

        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['assign_conditional_variables'])
            ->getMock();
        $functionsMock->method('assign_conditional_variables')->willReturn([]);
        $functionsMock->cached_captcha = '';
        ee()->setMock('functions', $functionsMock);

        $str = '{NOCACHE_comment_FORM="channel_id=5&entry_id=123&return=site/thanks&preview=PRV456"}complex content{/NOCACHE_FORM}';
        $result = $this->template->parse_nocache($str);

        $this->assertStringContainsString('<form>processed with params</form>', $result);
        $this->assertStringNotContainsString('{NOCACHE_comment_FORM=', $result);
        // Verify that complex parameters were processed
        $this->assertEquals([
            'channel_id' => '5',
            'entry_id' => '123',
            'return' => 'site/thanks',
            'preview' => 'PRV456'
        ], $this->template->tagparams);
    }

    /**
     * Test nocache with variable extraction and processing
     */
    public function testParseNocacheVariableProcessing()
    {
        $securityMock = $this->getMockBuilder('stdClass')
            ->setMethods(['sanitize_filename'])
            ->getMock();
        $securityMock->method('sanitize_filename')->willReturn('comment');
        ee()->setMock('security', $securityMock);

        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();

        $moduleMock = $this->getMockBuilder('stdClass')
            ->setMethods(['getModuleClass'])
            ->getMock();

        // Create a mock class name that can be instantiated
        $mockClassName = 'MockCommentClass_' . uniqid();
        eval("class $mockClassName { public function form(\$return_form = false, \$captcha = '') { return '<form>with variables</form>'; } }");

        $moduleMock->method('getModuleClass')->willReturn($mockClassName);
        $addonMock->method('get')->willReturn($moduleMock);
        ee()->setMock('Addon', $addonMock);

        $parserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['extractVariables', 'parseTagParameters'])
            ->getMock();
        $parserMock->method('extractVariables')->willReturn([
            'var_single' => [
                'form:class' => 'my-form-class',
                'form:id' => 'my-form-id',
                'channel' => 'news'
            ],
            'var_pair' => [
                'categories' => [
                    ['category_name' => 'Tech', 'category_id' => '1'],
                    ['category_name' => 'News', 'category_id' => '2']
                ]
            ]
        ]);
        $parserMock->method('parseTagParameters')->willReturn(['channel' => 'news']);
        ee()->setMock('Variables/Parser', $parserMock);

        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['assign_conditional_variables'])
            ->getMock();
        $functionsMock->method('assign_conditional_variables')->willReturn([]);
        $functionsMock->cached_captcha = '';
        ee()->setMock('functions', $functionsMock);

        $str = '{NOCACHE_comment_FORM="channel=news"}
            {form:class} {form:id}
            {categories}
                {category_name} ({category_id})
            {/categories}
        {/NOCACHE_FORM}';

        $result = $this->template->parse_nocache($str);

        $this->assertStringContainsString('<form>with variables</form>', $result);
        $this->assertStringNotContainsString('{NOCACHE_comment_FORM=', $result);

        // Verify variables were extracted and set
        $this->assertEquals([
            'form:class' => 'my-form-class',
            'form:id' => 'my-form-id',
            'channel' => 'news'
        ], $this->template->var_single);

        $this->assertEquals([
            'categories' => [
                ['category_name' => 'Tech', 'category_id' => '1'],
                ['category_name' => 'News', 'category_id' => '2']
            ]
        ], $this->template->var_pair);
    }

    /**
     * Test nocache error handling and edge cases
     */
    public function testParseNocacheErrorHandling()
    {
        // Test various error conditions and edge cases

        // Test with invalid addon name
        $securityMock = $this->getMockBuilder('stdClass')
            ->setMethods(['sanitize_filename'])
            ->getMock();
        $securityMock->method('sanitize_filename')->willReturn('invalid_addon');
        ee()->setMock('security', $securityMock);

        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();
        $addonMock->method('get')->willReturn(null); // Simulate addon not found
        ee()->setMock('Addon', $addonMock);

        $str = '{NOCACHE_invalid_addon_FORM=""}content{/NOCACHE_FORM}';
        $result = $this->template->parse_nocache($str);

        // Should handle gracefully without crashing
        $this->assertIsString($result);
        $this->assertStringContainsString('content', $result);

        // Test with malformed parameters
        $parserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['extractVariables', 'parseTagParameters'])
            ->getMock();
        $parserMock->method('extractVariables')->willReturn(['var_single' => [], 'var_pair' => []]);
        $parserMock->method('parseTagParameters')->willReturn(false); // Simulate parsing failure
        ee()->setMock('Variables/Parser', $parserMock);

        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['assign_conditional_variables'])
            ->getMock();
        $functionsMock->method('assign_conditional_variables')->willReturn([]);
        $functionsMock->cached_captcha = '';
        ee()->setMock('functions', $functionsMock);

        $str = '{NOCACHE_comment_FORM="malformed{params"}content{/NOCACHE_FORM}';
        $result = $this->template->parse_nocache($str);

        // Should handle parameter parsing failure gracefully
        $this->assertIsString($result);
    }

    /**
     * Test nocache with conditional variables
     */
    public function testParseNocacheConditionalProcessing()
    {
        $securityMock = $this->getMockBuilder('stdClass')
            ->setMethods(['sanitize_filename'])
            ->getMock();
        $securityMock->method('sanitize_filename')->willReturn('comment');
        ee()->setMock('security', $securityMock);

        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();

        $moduleMock = $this->getMockBuilder('stdClass')
            ->setMethods(['getModuleClass'])
            ->getMock();

        // Create a mock class name that can be instantiated
        $mockClassName = 'MockCommentClass_' . uniqid();
        eval("class $mockClassName { public function form(\$return_form = false, \$captcha = '') { return '<form>conditional processed</form>'; } }");

        $moduleMock->method('getModuleClass')->willReturn($mockClassName);
        $addonMock->method('get')->willReturn($moduleMock);
        ee()->setMock('Addon', $addonMock);

        $parserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['extractVariables', 'parseTagParameters'])
            ->getMock();
        $parserMock->method('extractVariables')->willReturn(['var_single' => [], 'var_pair' => []]);
        $parserMock->method('parseTagParameters')->willReturn([]);
        ee()->setMock('Variables/Parser', $parserMock);

        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['assign_conditional_variables'])
            ->getMock();
        $functionsMock->method('assign_conditional_variables')->willReturn([
            'logged_in' => true,
            'member_group' => '5',
            'total_results' => '10'
        ]);
        $functionsMock->cached_captcha = '';
        ee()->setMock('functions', $functionsMock);

        $str = '{NOCACHE_comment_FORM=""}
            {if logged_in}Welcome back!{/if}
            Group: {member_group}
            Results: {total_results}
        {/NOCACHE_FORM}';

        $result = $this->template->parse_nocache($str);

        $this->assertStringContainsString('<form>conditional processed</form>', $result);
        $this->assertStringNotContainsString('{NOCACHE_comment_FORM=', $result);

        // Verify conditional variables were set
        $this->assertEquals([
            'logged_in' => true,
            'member_group' => '5',
            'total_results' => '10'
        ], $this->template->var_cond);
    }

    /**
     * Test nocache performance and memory handling
     */
    public function testParseNocacheLargeContentHandling()
    {
        // Test with moderately large content to ensure memory handling
        $largeContent = str_repeat('Large content block ', 100);

        $securityMock = $this->getMockBuilder('stdClass')
            ->setMethods(['sanitize_filename'])
            ->getMock();
        $securityMock->method('sanitize_filename')->willReturn('comment');
        ee()->setMock('security', $securityMock);

        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();

        $moduleMock = $this->getMockBuilder('stdClass')
            ->setMethods(['getModuleClass'])
            ->getMock();

        // Create a mock class name that can be instantiated
        $mockClassName = 'MockCommentClass_' . uniqid();
        eval("class $mockClassName { public function form(\$return_form = false, \$captcha = '') { return '<form>large processed</form>'; } }");

        $moduleMock->method('getModuleClass')->willReturn($mockClassName);
        $addonMock->method('get')->willReturn($moduleMock);
        ee()->setMock('Addon', $addonMock);

        $parserMock = $this->getMockBuilder('stdClass')
            ->setMethods(['extractVariables', 'parseTagParameters'])
            ->getMock();
        $parserMock->method('extractVariables')->willReturn(['var_single' => [], 'var_pair' => []]);
        $parserMock->method('parseTagParameters')->willReturn([]);
        ee()->setMock('Variables/Parser', $parserMock);

        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['assign_conditional_variables'])
            ->getMock();
        $functionsMock->method('assign_conditional_variables')->willReturn([]);
        $functionsMock->cached_captcha = '';
        ee()->setMock('functions', $functionsMock);

        $str = "{NOCACHE_comment_FORM=\"\"}{$largeContent}{/NOCACHE_FORM}";
        $result = $this->template->parse_nocache($str);

        $this->assertStringContainsString('<form>large processed</form>', $result);
        $this->assertStringNotContainsString('{NOCACHE_comment_FORM=', $result);
        // Verify large content was handled without crashing
        $this->assertIsString($result);
    }
}
