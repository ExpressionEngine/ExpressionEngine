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
}