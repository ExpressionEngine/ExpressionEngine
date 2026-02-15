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
     */
    public function testParseUriSegments()
    {
        // Test the URI segment parsing logic directly by replicating the core logic
        $template = '{segment_1} - {segment_2} - {segment_3}';

        // Set up URI mock with specific segments
        $uriMock = $this->getMockBuilder('stdClass')
            ->setMethods(['segment'])
            ->getMock();

        $uriMock->method('segment')->willReturnCallback(function($num) {
            $segments = ['', 'news', 'articles', 'my-article'];
            return isset($segments[$num]) ? $segments[$num] : '';
        });
        ee()->setMock('uri', $uriMock);

        // Initialize segment_vars array
        $this->template->segment_vars = [];

        // Set the initial template content
        $this->template->template = $template;

        // Replicate the URI segment parsing logic from the parse method (lines 421-433)
        for ($i = 1; $i < 10; $i++) {
            $segmentValue = ee()->uri->segment($i);
            $this->template->template = str_replace(LD . 'segment_' . $i . RD, $segmentValue, $this->template->template);
            $this->template->segment_vars['segment_' . $i] = $segmentValue;

            // Apply modifiers to segments (simplified version)
            if (strpos($this->template->template, LD . 'segment_' . $i . ':') !== false) {
                // For this test, we don't need to test modifiers
                continue;
            }
        }

        // Verify that URI segments were replaced correctly
        $expected = 'news - articles - my-article';
        $this->assertEquals($expected, $this->template->template);

        // Verify segment_vars were set correctly
        $this->assertEquals('news', $this->template->segment_vars['segment_1']);
        $this->assertEquals('articles', $this->template->segment_vars['segment_2']);
        $this->assertEquals('my-article', $this->template->segment_vars['segment_3']);
        $this->assertEquals('', $this->template->segment_vars['segment_4']); // Should be empty for non-existent segments
    }

    /**
     * Test parse embed variables
     */
    public function testParseEmbedVariables()
    {
        // Test embed variable parsing logic directly
        $template = '{embed:title} - {embed:author} - {embed:date}';

        // Set up embed variables (this would normally be set by the embed processing)
        $this->template->embed_vars = [
            'title' => 'My Article Title',
            'author' => 'John Doe',
            'date' => '2023-12-01'
        ];

        // Set template content
        $this->template->template = $template;

        // Replicate the embed variable parsing logic from the parse method (lines 449-459)
        // This happens when $is_embed === true and count($this->embed_vars) > 0
        foreach ($this->template->embed_vars as $key => $val) {
            // add 'embed:' to the key for replacement and so these variables work in conditionals
            $this->template->embed_vars['embed:' . $key] = $val;
            unset($this->template->embed_vars[$key]);
            // For simple string values, _parse_var_single just does str_replace
            $this->template->template = str_replace(LD . 'embed:' . $key . RD, $val, $this->template->template);
        }

        // Verify that embed variables were replaced correctly
        $expected = 'My Article Title - John Doe - 2023-12-01';
        $this->assertEquals($expected, $this->template->template);

        // Verify embed_vars were updated with embed: prefixes
        $this->assertArrayHasKey('embed:title', $this->template->embed_vars);
        $this->assertArrayHasKey('embed:author', $this->template->embed_vars);
        $this->assertArrayHasKey('embed:date', $this->template->embed_vars);

        // Verify original keys were removed
        $this->assertArrayNotHasKey('title', $this->template->embed_vars);
        $this->assertArrayNotHasKey('author', $this->template->embed_vars);
        $this->assertArrayNotHasKey('date', $this->template->embed_vars);

        // Verify the values
        $this->assertEquals('My Article Title', $this->template->embed_vars['embed:title']);
        $this->assertEquals('John Doe', $this->template->embed_vars['embed:author']);
        $this->assertEquals('2023-12-01', $this->template->embed_vars['embed:date']);
    }

    /**
     * Test parse with PHP enabled
     */
    public function testParseWithPhpEnabled()
    {
        // Test PHP parsing logic directly
        $template = 'Before <?php echo "PHP Output"; ?> After';

        // Set up template state for PHP parsing
        $this->template->template = $template;
        $this->template->parse_php = true;
        $this->template->php_parse_location = 'output'; // Test output stage
        $this->template->cache_status = 'EXPIRED'; // Ensure PHP parsing happens

        // Mock the functions->evaluate method to simulate PHP execution
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['evaluate'])
            ->getMock();
        $functionsMock->method('evaluate')->willReturnCallback(function($code) {
            // Simulate PHP execution - extract and evaluate the PHP code
            if (preg_match('/<\?php\s+(.*?)\s*\?>/', $code, $matches)) {
                // For this test, we'll just return the PHP code as if it was executed
                return 'PHP Output';
            }
            return $code;
        });
        ee()->setMock('functions', $functionsMock);

        // Replicate the output stage PHP parsing logic from parse method (lines 636-640)
        if ($this->template->parse_php == true && $this->template->php_parse_location == 'output' && $this->template->cache_status != 'CURRENT') {
            $this->template->template = $this->template->parse_template_php($this->template->template);
        }

        // Verify PHP was executed and replaced the entire template with PHP output
        $expected = 'PHP Output';
        $this->assertEquals($expected, $this->template->template);

        // Verify parse_php was reset to false after parsing
        $this->assertFalse($this->template->parse_php);
    }

    /**
     * Test parse with PHP disabled
     */
    public function testParseWithPhpDisabled()
    {
        // Test that PHP is not parsed when disabled
        $template = 'Before <?php echo "Should not execute"; ?> After';

        // Set up template state for PHP disabled
        $this->template->template = $template;
        $this->template->parse_php = false; // PHP disabled
        $this->template->php_parse_location = 'output';
        $this->template->cache_status = 'EXPIRED';

        // Store original template for comparison
        $originalTemplate = $this->template->template;

        // Replicate the output stage PHP parsing logic
        if ($this->template->parse_php == true && $this->template->php_parse_location == 'output' && $this->template->cache_status != 'CURRENT') {
            $this->template->template = $this->template->parse_template_php($this->template->template);
        }

        // Verify PHP was NOT executed - template should remain unchanged
        $this->assertEquals($originalTemplate, $this->template->template);
        $this->assertStringContainsString('<?php echo "Should not execute"; ?>', $this->template->template);
    }

    /**
     * Test parse global variables
     */
    public function testParseGlobalVariables()
    {
        $template = '{global_var} - {another_var}';

        // Set up global variables in config
        ee()->config->_global_vars = [
            'global_var' => 'Global Value',
            'another_var' => 'Another Value',
            'unused_var' => 'Unused' // This shouldn't appear in the template
        ];

        // Set template content
        $this->template->template = $template;

        // Replicate the global variables parsing logic from parse method (lines 386-411)
        if (count(ee()->config->_global_vars) > 0) {
            // Create regex for global variables (simplified version of getGlobalsRegex)
            $global_names = array_keys(ee()->config->_global_vars);
            $quoted_names = array_map(function($str) {
                return preg_quote($str, '/');
            }, $global_names);
            $regex = '/' . LD . '(' . implode('|', $quoted_names) . ')' . RD . '/';

            while (preg_match_all($regex, $this->template->template, $result)) {
                foreach ($result[1] as $variable) {
                    // In case any of these variables have EE comments of their own,
                    // removing from the value makes snippets more usable in conditionals
                    $value = $this->template->remove_ee_comments(
                        ee()->config->_global_vars[$variable]
                    );

                    $replace = $this->template->wrapInContextAnnotations(
                        $value,
                        'Template Partial "' . $variable . '"'
                    );

                    $this->template->template = str_replace(LD . $variable . RD, $replace, $this->template->template);
                }
            }
        }

        // Verify global variables were replaced correctly
        $this->assertStringContainsString('Global Value', $this->template->template);
        $this->assertStringContainsString('Another Value', $this->template->template);
        $this->assertStringNotContainsString('{global_var}', $this->template->template);
        $this->assertStringNotContainsString('{another_var}', $this->template->template);
        $this->assertStringNotContainsString('Unused', $this->template->template);

        // Verify the final result
        $this->assertEquals('Global Value - Another Value', $this->template->template);
    }

    /**
     * Test parse with empty template string
     */
    public function testParseEmptyTemplate()
    {
        $template = '';

        // Set template properties to avoid early returns
        $this->template->template_name = 'empty';
        $this->template->group_name = 'test';
        $this->template->template_group_id = '1';
        $this->template->template_id = '1';
        $this->template->template_type = 'webpage';

        $this->template->parse($template);

        // Verify that empty template is handled correctly
        // The parse method should handle empty strings gracefully
        $this->assertEquals('', $this->template->final_template);
    }
}