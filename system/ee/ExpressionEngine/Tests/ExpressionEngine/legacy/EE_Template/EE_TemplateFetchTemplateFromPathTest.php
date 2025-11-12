<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateFetchTemplateFromPathTest extends EE_TemplateTestBase
{
    /**
     * Test fetch_template_from_path method exists
     */
    public function testFetchTemplateFromPathMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'fetch_template_from_path'));
        $this->assertTrue(is_callable([$this->template, 'fetch_template_from_path']));
    }

    /**
     * Test fetch_template_from_path with valid template path
     */
    public function testFetchTemplateFromPathValidTemplatePath()
    {
        // Mock _get_fetch_data to return parsed data
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_fetch_data', 'fetch_template'])
            ->getMock();

        $templateMock->method('_get_fetch_data')
            ->with('group/template')
            ->willReturn(['group', 'template', 1]);

        $templateMock->method('fetch_template')
            ->with('group', 'template', false, 1)
            ->willReturn('<html>Template Content</html>');

        $result = $templateMock->fetch_template_from_path('group/template');

        $this->assertEquals('<html>Template Content</html>', $result);
    }

    /**
     * Test fetch_template_from_path with invalid template path
     */
    public function testFetchTemplateFromPathInvalidTemplatePath()
    {
        // Mock _get_fetch_data to return null (invalid path)
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_fetch_data', 'fetch_template'])
            ->getMock();

        $templateMock->method('_get_fetch_data')
            ->with('invalid/path')
            ->willReturn(null);

        $templateMock->expects($this->once())
            ->method('fetch_template')
            ->with(null, null, false, null)
            ->willReturn(false);

        $result = $templateMock->fetch_template_from_path('invalid/path');

        $this->assertFalse($result);
    }

    /**
     * Test fetch_template_from_path with site specification
     */
    public function testFetchTemplateFromPathWithSiteId()
    {
        // Mock _get_fetch_data to return data with site ID
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_fetch_data', 'fetch_template'])
            ->getMock();

        $templateMock->method('_get_fetch_data')
            ->with('mysite:group/template')
            ->willReturn(['group', 'template', 2]);

        $templateMock->method('fetch_template')
            ->with('group', 'template', false, 2)
            ->willReturn('Site 2 Template Content');

        $result = $templateMock->fetch_template_from_path('mysite:group/template');

        $this->assertEquals('Site 2 Template Content', $result);
    }

    /**
     * Test fetch_template_from_path with malformed path
     */
    public function testFetchTemplateFromPathMalformedPath()
    {
        // Mock _get_fetch_data to return null for malformed path
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_fetch_data', 'fetch_template'])
            ->getMock();

        $templateMock->method('_get_fetch_data')
            ->with('malformed-path')
            ->willReturn(null);

        $templateMock->expects($this->once())
            ->method('fetch_template')
            ->with(null, null, false, null)
            ->willReturn(false);

        $result = $templateMock->fetch_template_from_path('malformed-path');

        $this->assertFalse($result);
    }

    /**
     * Test fetch_template_from_path with empty path
     */
    public function testFetchTemplateFromPathEmptyPath()
    {
        // Mock _get_fetch_data to return null for empty path
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_fetch_data', 'fetch_template'])
            ->getMock();

        $templateMock->method('_get_fetch_data')
            ->with('')
            ->willReturn(null);

        $templateMock->expects($this->once())
            ->method('fetch_template')
            ->with(null, null, false, null)
            ->willReturn(false);

        $result = $templateMock->fetch_template_from_path('');

        $this->assertFalse($result);
    }

    /**
     * Test fetch_template_from_path delegates to correct methods
     */
    public function testFetchTemplateFromPathDelegatesCorrectly()
    {
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_fetch_data', 'fetch_template'])
            ->getMock();

        $templateMock->expects($this->once())
            ->method('_get_fetch_data')
            ->with('test/path')
            ->willReturn(['testgroup', 'testtemplate', 5]);

        $templateMock->expects($this->once())
            ->method('fetch_template')
            ->with('testgroup', 'testtemplate', false, 5)
            ->willReturn('Success Content');

        $result = $templateMock->fetch_template_from_path('test/path');

        $this->assertEquals('Success Content', $result);
    }
}
