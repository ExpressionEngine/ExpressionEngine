<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateFetchTemplateAndParseFromPathTest extends EE_TemplateTestBase
{
    public function testFetchTemplateAndParseFromPathMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'fetch_template_and_parse_from_path'));
        $this->assertTrue(is_callable([$this->template, 'fetch_template_and_parse_from_path']));
    }

    public function testFetchTemplateAndParseFromPathValidTemplatePath()
    {
        // Mock _get_fetch_data to return parsed data
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_fetch_data', 'run_template_engine'])
            ->getMock();

        $templateMock->method('_get_fetch_data')
            ->with('group/template')
            ->willReturn(['group', 'template', 1]);

        // Mock run_template_engine to be called with correct parameters
        $templateMock->expects($this->once())
            ->method('run_template_engine')
            ->with('group', 'template');

        // Mock ee()->output->get_output() to return rendered content
        $outputMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_output'])
            ->getMock();
        $outputMock->method('get_output')->willReturn('<html>Rendered Content</html>');
        ee()->setMock('output', $outputMock);

        $result = $templateMock->fetch_template_and_parse_from_path('group/template');

        $this->assertEquals('<html>Rendered Content</html>', $result);
    }

    public function testFetchTemplateAndParseFromPathInvalidTemplatePath()
    {
        // Mock _get_fetch_data to return null (invalid path)
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_fetch_data', 'run_template_engine'])
            ->getMock();

        $templateMock->method('_get_fetch_data')
            ->with('invalid/path')
            ->willReturn(null);

        // run_template_engine is called even with null values
        $templateMock->expects($this->once())
            ->method('run_template_engine')
            ->with(null, null);

        $outputMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_output'])
            ->getMock();
        $outputMock->method('get_output')->willReturn('');
        ee()->setMock('output', $outputMock);

        $result = $templateMock->fetch_template_and_parse_from_path('invalid/path');

        // Should return whatever output->get_output() returns
        $this->assertEquals('', $result);
    }

    public function testFetchTemplateAndParseFromPathWithSiteId()
    {
        // Mock _get_fetch_data to return data with site ID
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->setMethods(['_get_fetch_data', 'run_template_engine'])
            ->getMock();

        $templateMock->method('_get_fetch_data')
            ->with('mysite:group/template')
            ->willReturn(['group', 'template', 2]);

        $templateMock->expects($this->once())
            ->method('run_template_engine')
            ->with('group', 'template');

        $outputMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_output'])
            ->getMock();
        $outputMock->method('get_output')->willReturn('Site 2 Content');
        ee()->setMock('output', $outputMock);

        $result = $templateMock->fetch_template_and_parse_from_path('mysite:group/template');

        $this->assertEquals('Site 2 Content', $result);
    }
}
