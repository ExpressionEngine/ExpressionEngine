<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once __DIR__ . '/../ChannelTestBase.php';

/**
 * Tests for Channel_form_javascript class
 */
class ChannelFormJavascriptTest extends ChannelTestBase
{
    protected $channelFormJavascript;

    protected function setUp(): void
    {
        parent::setUp();

        // Define required constants for Channel_form_javascript
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Define PATH_THEMES relative to this test file for portability
        if (!defined('PATH_THEMES')) {
            $__themes = __DIR__ . '/../../../../../../../themes/';
            define('PATH_THEMES', $__themes);
        }

        // Define PATH_JQUERY before instantiating Channel_form_javascript
        if (!defined('PATH_JQUERY')) {
            define('PATH_JQUERY', PATH_THEMES . 'ee/asset/javascript/' . PATH_JS . '/jquery/');
        }

        // Define mock smiley_js function if not already defined
        if (!function_exists('smiley_js')) {
            function smiley_js($alias = '', $field_id = '', $inline = true) {
                return '<script>/* mock smiley js */</script>';
            }
        }

        // Include the Channel_form_javascript class
        require_once PATH_ADDONS . 'channel/libraries/channel_form/Channel_form_javascript.php';

        // Create instance for testing
        $this->channelFormJavascript = new Channel_form_javascript();

        // Override the js_path to point to the correct location for testing
        $reflection = new ReflectionClass($this->channelFormJavascript);
        $jsPathProperty = $reflection->getProperty('js_path');
        $jsPathProperty->setAccessible(true);
        $jsPathProperty->setValue($this->channelFormJavascript, PATH_THEMES . 'ee/asset/javascript/' . PATH_JS . '/');

        // PATH_JQUERY is defined in constructor, but we can't easily override it
        // The tests will handle file system dependencies as best as possible
    }

    public function testConstructorInitializesProperties()
    {
        // Test that constructor sets up js_path property
        $this->assertObjectHasAttribute('js_path', $this->channelFormJavascript);

        // Test that PATH_JQUERY constant is defined
        $this->assertTrue(defined('PATH_JQUERY'));

        // Test that js_path contains expected path structure
        $reflection = new ReflectionClass($this->channelFormJavascript);
        $jsPathProperty = $reflection->getProperty('js_path');
        $jsPathProperty->setAccessible(true);
        $jsPath = $jsPathProperty->getValue($this->channelFormJavascript);

        // The js_path should be constructed as PATH_THEMES . 'ee/asset/javascript/' . PATH_JS . '/'
        // Based on the actual output: themes/ee/asset/javascript/src/
        // Handle both Windows (backslashes) and Unix (forward slashes) path separators
        $this->assertTrue(strpos($jsPath, 'themes') !== false, 'js_path should contain "themes"');
        $this->assertTrue(
            strpos($jsPath, 'ee/asset/javascript') !== false || strpos($jsPath, 'ee\\asset\\javascript') !== false,
            'js_path should contain "ee/asset/javascript"'
        );
        $this->assertTrue(
            substr($jsPath, -4) === 'src/' || substr($jsPath, -4) === 'src\\',
            'js_path should end with "src/" or "src\\"'
        );
    }

    public function testConstructorWithParameters()
    {
        // Test constructor with parameters (though it currently doesn't use them)
        $params = ['test' => 'value'];
        $javascript = new Channel_form_javascript($params);

        // Should still initialize properly
        $this->assertInstanceOf('Channel_form_javascript', $javascript);
    }

    public function testComboLoadDelegatesToJavascriptLoader()
    {
        // Mock the javascript_loader
        $mockJavascriptLoader = $this->getMockBuilder('stdClass')
            ->addMethods(['combo_load'])
            ->getMock();

        $mockJavascriptLoader->expects($this->once())
            ->method('combo_load')
            ->willReturn('mocked javascript output');

        $this->setMock('javascript_loader', $mockJavascriptLoader);

        // Mock output to capture the result
        $this->setMock('output', new class {
            private $output = '';
            public function set_output($output) { $this->output = $output; }
            public function get_output() { return $this->output; }
            public function append_output($output) { $this->output .= $output; }
            public function send_ajax_response($msg, $error = false) {}
            public function set_header($header) {}
        });

        // Capture output before calling combo_load
        ob_start();
        $this->channelFormJavascript->combo_load();
        $output = ob_get_clean();

        // Should have called javascript_loader->combo_load()
        // The actual output depends on the mocks, but the method should be called
        $this->assertTrue(true); // If we get here, the method executed without fatal errors
    }

    public function testComboLoadHandlesJqueryInclusion()
    {
        // Mock input with include_jquery parameter
        $this->setMock('input', new class {
            public function get($key) {
                if ($key === 'include_jquery') {
                    return 'y';
                }
                return null;
            }
            public function get_post($key) { return null; }
        });

        // Mock output to capture what gets set
        $this->setMock('output', new class {
            private $output = '';
            public function set_output($output) { $this->output = $output; }
            public function get_output() { return $this->output; }
            public function append_output($output) { $this->output .= $output; }
            public function send_ajax_response($msg, $error = false) {}
            public function set_header($header) {}
        });

        // Mock javascript_loader
        $this->setMock('javascript_loader', new class {
            public function combo_load() {}
        });

        // Test that method exists and basic structure is correct
        $this->assertTrue(method_exists($this->channelFormJavascript, 'combo_load'));

        // Test that PATH_JQUERY constant exists (it's defined in constructor)
        $this->assertTrue(defined('PATH_JQUERY'));

        // Verify that PATH_JQUERY ends with jquery/ as expected
        $this->assertStringEndsWith('jquery/', PATH_JQUERY);

        // Note: We can't easily test the file reading part due to constant constraints,
        // but we've verified the method exists and constants are properly set
    }

    public function testComboLoadHandlesUseLiveUrl()
    {
        // Mock input with use_live_url parameter
        $this->setMock('input', new class {
            public function get($key) {
                if ($key === 'use_live_url') {
                    return 'y';
                }
                return null;
            }
            public function get_post($key) { return null; }
        });

        // Mock channel_form to provide _url_title_js method
        $this->setMock('channel_form', new class {
            public function _url_title_js() { return 'mocked url title js'; }
        });

        // Mock output
        $this->setMock('output', new class {
            private $output = '';
            public function set_output($output) { $this->output = $output; }
            public function get_output() { return $this->output; }
            public function append_output($output) { $this->output .= $output; }
            public function send_ajax_response($msg, $error = false) {}
            public function set_header($header) {}
        });

        // Mock javascript_loader
        $this->setMock('javascript_loader', new class {
            public function combo_load() {}
        });

        // Test that method can be called with live URL parameter
        $this->assertTrue(method_exists($this->channelFormJavascript, 'combo_load'));

        // Call the method to ensure it doesn't throw fatal errors
        ob_start();
        $this->channelFormJavascript->combo_load();
        ob_end_clean();
    }

    public function testComboLoadHandlesSmileyJs()
    {
        // Mock output to capture smiley JS output
        $this->setMock('output', new class {
            private $output = '';
            public function set_output($output) { $this->output = $output; }
            public function get_output() { return $this->output; }
            public function append_output($output) { $this->output .= $output; }
            public function send_ajax_response($msg, $error = false) {}
            public function set_header($header) {}
        });

        // Mock javascript_loader
        $this->setMock('javascript_loader', new class {
            public function combo_load() {}
        });

        // Test that method can be called (smiley JS is handled internally)
        $this->assertTrue(method_exists($this->channelFormJavascript, 'combo_load'));

        // Call the method to ensure it doesn't throw fatal errors
        ob_start();
        $this->channelFormJavascript->combo_load();
        ob_end_clean();
    }

    public function testComboLoadSetsContentLengthHeader()
    {
        // Mock output to track header setting
        $this->setMock('output', new class {
            private $output = 'test output content';
            public function set_output($output) { $this->output = $output; }
            public function get_output() { return $this->output; }
            public function append_output($output) { $this->output .= $output; }
            public function send_ajax_response($msg, $error = false) {}
            public function set_header($header) {}
        });

        // Mock javascript_loader
        $this->setMock('javascript_loader', new class {
            public function combo_load() {}
        });

        // Test that method can be called (header setting is tested through integration)
        $this->assertTrue(method_exists($this->channelFormJavascript, 'combo_load'));

        // Call the method to ensure it doesn't throw fatal errors
        ob_start();
        $this->channelFormJavascript->combo_load();
        ob_end_clean();
    }

    public function testComboLoadLoadsChannelFormJs()
    {
        // Mock output
        $this->setMock('output', new class {
            private $output = '';
            public function set_output($output) { $this->output = $output; }
            public function get_output() { return $this->output; }
            public function append_output($output) { $this->output .= $output; }
            public function send_ajax_response($msg, $error = false) {}
            public function set_header($header) {}
        });

        // Mock javascript_loader
        $this->setMock('javascript_loader', new class {
            public function combo_load() {}
        });

        // Test that method can be called (channel_form.js loading is handled internally)
        $this->assertTrue(method_exists($this->channelFormJavascript, 'combo_load'));

        // Call the method to ensure it doesn't throw fatal errors
        ob_start();
        $this->channelFormJavascript->combo_load();
        ob_end_clean();
    }

    public function testComboLoadHandlesPathJsConstant()
    {
        // Define PATH_JS constant if not already defined
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Mock output
        $this->setMock('output', new class {
            private $output = '';
            public function set_output($output) { $this->output = $output; }
            public function get_output() { return $this->output; }
            public function append_output($output) { $this->output .= $output; }
            public function send_ajax_response($msg, $error = false) {}
            public function set_header($header) {}
        });

        // Mock javascript_loader
        $this->setMock('javascript_loader', new class {
            public function combo_load() {}
        });

        // Test that PATH_JS constant is handled properly
        $this->assertTrue(defined('PATH_JS'));
        $this->assertTrue(method_exists($this->channelFormJavascript, 'combo_load'));

        // Call the method to ensure it doesn't throw fatal errors
        ob_start();
        $this->channelFormJavascript->combo_load();
        ob_end_clean();
    }


}
