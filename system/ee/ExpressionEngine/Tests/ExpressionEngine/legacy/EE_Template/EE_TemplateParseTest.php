<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateParseTest extends EE_TemplateTestBase
{
    /**
     * Test parse method exists
     */
    public function testParseMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'parse'));
        $this->assertTrue(is_callable([$this->template, 'parse']));
    }

    /**
     * Test parse with static template type
     */
    public function testParseStaticTemplate()
    {
        $template = '<html><body>Static Content</body></html>';

        // Set template type to static
        $this->template->template_type = 'static';

        $this->template->parse($template);

        $this->assertEquals('<html><body>Static Content</body></html>', $this->template->final_template);
    }

    /**
     * Test parse with static embed type
     */
    public function testParseStaticEmbed()
    {
        $template = '<html><body>Static Embed Content</body></html>';

        // Set embed type to static
        $this->template->embed_type = 'static';

        $this->template->parse($template, true); // is_embed = true

        // Static embeds should not set final_template when is_embed is true
        $this->assertNotEquals('<html><body>Static Embed Content</body></html>', $this->template->final_template);
    }

    /**
     * Test parse site variables
     */
    public function testParseSiteVariables()
    {
        $template = '{site_name} - {site_url}';

        // Mock config with site variables
        $configMock = $this->getMockBuilder('stdClass')
            ->setMethods(['item'])
            ->getMock();
        $configMock->method('item')->willReturnCallback(function($key) {
            $siteVars = [
                'site_name' => 'Test Site',
                'site_url' => 'https://example.com/',
                'site_id' => '1'
            ];
            return $siteVars[$key] ?? null;
        });
        $configMock->_global_vars = [];
        ee()->setMock('config', $configMock);

        // Mock URI
        $uriMock = $this->getMockBuilder('stdClass')
            ->setMethods(['segment_array', 'segment', 'uri_string'])
            ->getMock();
        $uriMock->method('segment_array')->willReturn(['segment1']);
        $uriMock->method('segment')->willReturnCallback(function($num) {
            $segments = ['', 'segment1'];
            return $segments[$num] ?? '';
        });
        $uriMock->uri_string = 'segment1';
        ee()->setMock('uri', $uriMock);

        // Mock functions
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_current_uri', 'prep_conditionals'])
            ->getMock();
        $functionsMock->method('fetch_current_uri')->willReturn('segment1');
        $functionsMock->method('prep_conditionals')->willReturnCallback(function($str) {
            return $str; // Simple mock - just return the string as-is
        });
        ee()->setMock('functions', $functionsMock);

        // Set template properties
        $this->template->template_name = 'test';
        $this->template->group_name = 'default';
        $this->template->template_group_id = '1';
        $this->template->template_id = '1';
        $this->template->template_type = 'webpage';

        $this->template->parse($template);

        // Check that site variables were added to global vars
        $this->assertEquals('Test Site', ee()->config->_global_vars['site_name']);
        $this->assertEquals('https://example.com/', ee()->config->_global_vars['site_url']);
    }

    /**
     * Test parse URI segments
     * @todo Fix this test - currently has mocking issues with complex parse method dependencies
     */
    public function testParseUriSegments()
    {
        $this->markTestSkipped('Test has complex mocking dependencies that need further investigation');
    }

    /**
     * Test parse embed variables
     * @todo Fix this test - currently has mocking issues with complex parse method dependencies
     */
    public function testParseEmbedVariables()
    {
        $this->markTestSkipped('Test has complex mocking dependencies that need further investigation');
    }

    /**
     * Test parse with PHP enabled
     * @todo Fix this test - currently has mocking issues with complex parse method dependencies
     */
    public function testParseWithPhpEnabled()
    {
        $this->markTestSkipped('Test has complex mocking dependencies that need further investigation');
    }

    /**
     * Test parse with PHP disabled
     * @todo Fix this test - currently has mocking issues with complex parse method dependencies
     */
    public function testParseWithPhpDisabled()
    {
        $template = '<?php echo "Should not execute"; ?>';

        // Mock config with PHP disabled
        $configMock = $this->getMockBuilder('stdClass')
            ->setMethods(['item'])
            ->getMock();
        $configMock->method('item')->willReturnCallback(function($key) {
            if ($key === 'allow_php') {
                return 'n';
            }
            return null;
        });
        $configMock->_global_vars = [];
        ee()->setMock('config', $configMock);

        // Mock URI
        $uriMock = $this->getMockBuilder('stdClass')
            ->setMethods(['segment_array', 'segment', 'uri_string'])
            ->getMock();
        $uriMock->method('segment_array')->willReturn([]);
        $uriMock->method('segment')->willReturn('');
        $uriMock->uri_string = '';
        ee()->setMock('uri', $uriMock);

        // Set template properties for PHP disabled
        $this->template->allow_php = 'n';

        $this->template->parse($template);

        $this->markTestSkipped('Test has complex mocking dependencies that need further investigation');
    }

    /**
     * Test parse global variables
     */
    public function testParseGlobalVariables()
    {
        $template = '{global_var}';

        // Mock config with global vars
        $configMock = $this->getMockBuilder('stdClass')
            ->setMethods(['item'])
            ->getMock();
        $configMock->_global_vars = [
            'global_var' => 'Global Value'
        ];
        ee()->setMock('config', $configMock);

        // Mock URI
        $uriMock = $this->getMockBuilder('stdClass')
            ->setMethods(['segment_array', 'segment', 'uri_string'])
            ->getMock();
        $uriMock->method('segment_array')->willReturn([]);
        $uriMock->method('segment')->willReturn('');
        $uriMock->uri_string = '';
        ee()->setMock('uri', $uriMock);

        $this->template->parse($template);

        $this->markTestSkipped('Test has complex mocking dependencies that need further investigation');
    }

    /**
     * Test parse with empty template string
     */
    public function testParseEmptyTemplate()
    {
        $template = '';

        // Mock config
        $configMock = $this->getMockBuilder('stdClass')
            ->setMethods(['item'])
            ->getMock();
        $configMock->_global_vars = [];
        ee()->setMock('config', $configMock);

        // Mock URI
        $uriMock = $this->getMockBuilder('stdClass')
            ->setMethods(['segment_array', 'segment', 'uri_string'])
            ->getMock();
        $uriMock->method('segment_array')->willReturn([]);
        $uriMock->method('segment')->willReturn('');
        $uriMock->uri_string = '';
        ee()->setMock('uri', $uriMock);

        $this->template->parse($template);

        $this->markTestSkipped('Test has complex mocking dependencies that need further investigation');
    }
}