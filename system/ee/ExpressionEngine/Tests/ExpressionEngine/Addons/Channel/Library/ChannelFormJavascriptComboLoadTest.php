<?php

require_once __DIR__ . '/ChannelFormJavascriptTest.php';

/**
 * Tests for Channel_form_javascript::combo_load() method
 */
class ChannelFormJavascriptComboLoadTest extends ChannelFormJavascriptTest
{
    /**
     * Setup common mocks for combo_load tests
     */
    protected function setupComboLoadMocks()
    {
        // Mock output to capture the result
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
            public function combo_load() { return 'mocked javascript output'; }
        });
    }

    /**
     * Data provider for combo_load test scenarios
     */
    public function comboLoadScenarios()
    {
        return [
            'basic_combo_load' => [
                'mocks' => [],
                'expected_output_contains' => [],
                'description' => 'Basic combo_load execution'
            ],
            'with_jquery_inclusion' => [
                'mocks' => [
                    'input' => [
                        'include_jquery' => 'y'
                    ]
                ],
                'expected_output_contains' => [],
                'description' => 'Combo_load with jQuery inclusion parameter'
            ],
            'with_live_url' => [
                'mocks' => [
                    'input' => [
                        'use_live_url' => 'y'
                    ],
                    'channel_form' => [
                        '_url_title_js' => 'mocked url title js'
                    ]
                ],
                'expected_output_contains' => [],
                'description' => 'Combo_load with live URL parameter'
            ],
            'with_smiley_js' => [
                'mocks' => [],
                'expected_output_contains' => [],
                'description' => 'Combo_load with smiley JS handling'
            ],
            'with_content_length_header' => [
                'mocks' => [
                    'output' => [
                        'initial_output' => 'test output content'
                    ]
                ],
                'expected_output_contains' => [],
                'description' => 'Combo_load with content length header setting'
            ]
        ];
    }

    /**
     * @dataProvider comboLoadScenarios
     */
    public function testComboLoad($mocks, $expectedOutputContains, $description)
    {
        // Setup common mocks
        $this->setupComboLoadMocks();

        // Apply scenario-specific mocks
        if (isset($mocks['input'])) {
            $this->setMock('input', new class($mocks['input']) {
                private $params;
                public function __construct($params) { $this->params = $params; }
                public function get($key) { return $this->params[$key] ?? null; }
                public function get_post($key) { return null; }
            });
        }

        if (isset($mocks['channel_form'])) {
            $this->setMock('channel_form', new class($mocks['channel_form']) {
                private $methods;
                public function __construct($methods) { $this->methods = $methods; }
                public function __call($name, $args) { return $this->methods[$name] ?? null; }
            });
        }

        if (isset($mocks['output'])) {
            $this->setMock('output', new class($mocks['output']['initial_output'] ?? '') {
                private $output;
                public function __construct($initialOutput) { $this->output = $initialOutput; }
                public function set_output($output) { $this->output = $output; }
                public function get_output() { return $this->output; }
                public function append_output($output) { $this->output .= $output; }
                public function send_ajax_response($msg, $error = false) {}
                public function set_header($header) {}
            });
        }

        // Capture output
        ob_start();
        $result = $this->channelFormJavascript->combo_load();
        $output = ob_get_clean();

        // Verify method executed without fatal errors
        $this->assertNull($result, 'combo_load should not return a value');

        // Verify method exists and is callable
        $this->assertTrue(method_exists($this->channelFormJavascript, 'combo_load'));

        // Verify constants are properly defined
        $this->assertTrue(defined('PATH_JQUERY'));
        $this->assertTrue(defined('PATH_JS'));

        // Verify PATH_JQUERY ends with jquery/
        $this->assertStringEndsWith('jquery/', PATH_JQUERY);
    }

    public function testComboLoadDelegatesToJavascriptLoader()
    {
        // Mock the javascript_loader with expectations
        $mockJavascriptLoader = $this->getMockBuilder('stdClass')
            ->addMethods(['combo_load'])
            ->getMock();

        $mockJavascriptLoader->expects($this->once())
            ->method('combo_load')
            ->willReturn('mocked javascript output');

        $this->setMock('javascript_loader', $mockJavascriptLoader);

        // Setup output mock (without overriding javascript_loader)
        $this->setMock('output', new class {
            private $output = '';
            public function set_output($output) { $this->output = $output; }
            public function get_output() { return $this->output; }
            public function append_output($output) { $this->output .= $output; }
            public function send_ajax_response($msg, $error = false) {}
            public function set_header($header) {}
        });

        // Call the method
        ob_start();
        $this->channelFormJavascript->combo_load();
        ob_end_clean();

        // The mock expectation will verify that combo_load was called once
        // If we get here without exception, the delegation worked
        $this->assertTrue(true, 'Javascript loader delegation should work');
    }

    public function testComboLoadHandlesPathJsConstant()
    {
        // Ensure PATH_JS is defined
        if (!defined('PATH_JS')) {
            define('PATH_JS', 'src');
        }

        // Setup mocks
        $this->setupComboLoadMocks();

        // Call the method
        ob_start();
        $this->channelFormJavascript->combo_load();
        ob_end_clean();

        // Verify PATH_JS constant is handled
        $this->assertTrue(defined('PATH_JS'));
        $this->assertEquals('src', PATH_JS);
    }

    public function testComboLoadIncludesJqueryWhenRequested()
    {
        // Mock input with include_jquery parameter
        $this->setMock('input', new class {
            public function get($key) {
                return ($key === 'include_jquery') ? 'y' : null;
            }
            public function get_post($key) { return null; }
        });

        // Create a mock jquery.js file for testing
        $jqueryContent = '/* jQuery mock content */';

        // Get js_path using reflection since it's private
        $reflection = new ReflectionClass($this->channelFormJavascript);
        $jsPathProperty = $reflection->getProperty('js_path');
        $jsPathProperty->setAccessible(true);
        $jsPath = $jsPathProperty->getValue($this->channelFormJavascript);
        $jqueryPath = $jsPath . 'jquery/jquery.js';

        // Create temporary test file for jQuery
        $dir = dirname($jqueryPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Backup original jQuery file if it exists
        $originalJqueryContent = null;
        if (file_exists($jqueryPath)) {
            $originalJqueryContent = file_get_contents($jqueryPath);
        }

        file_put_contents($jqueryPath, $jqueryContent);

        // Mock output to capture the result
        $capturedOutput = '';
        $this->setMock('output', new class($capturedOutput) {
            private $output = '';
            public function set_output($output) { $this->output = $output; }
            public function get_output() { return $this->output; }
            public function append_output($output) { $this->output .= $output; }
            public function send_ajax_response($msg, $error = false) {}
            public function set_header($header) {}
        });

        // Mock javascript_loader
        $this->setMock('javascript_loader', new class($jqueryPath, $jqueryContent) {
            private $jqueryPath;
            private $jqueryContent;

            public function __construct($path, $content) {
                $this->jqueryPath = $path;
                $this->jqueryContent = $content;
            }

            public function combo_load() {
                $output = 'js loader output';
                if (file_exists($this->jqueryPath)) {
                    $output .= file_get_contents($this->jqueryPath);
                }
                ee()->output->set_output($output);
            }
        });

        // Mock channel_form for _url_title_js method
        $this->setMock('channel_form', new class {
            public function _url_title_js() {
                return '/* URL title JS */';
            }
        });

        // Call the method
        ob_start();
        $this->channelFormJavascript->combo_load();
        ob_end_clean();

        // Verify that jquery content was included in output
        $outputMock = ee()->output;
        $this->assertStringContainsString($jqueryContent, $outputMock->get_output());

        // Clean up the test file - restore original content if it existed
        if ($originalJqueryContent !== null) {
            file_put_contents($jqueryPath, $originalJqueryContent);
        } else {
            if (file_exists($jqueryPath)) {
                unlink($jqueryPath);
            }
        }
    }

    public function testComboLoadIncludesLiveUrlWhenRequested()
    {
        // Mock input with use_live_url parameter
        $this->setMock('input', new class {
            public function get($key) {
                return ($key === 'use_live_url') ? 'y' : null;
            }
            public function get_post($key) { return null; }
        });

        // Mock channel_form with _url_title_js method
        $urlTitleJs = '/* URL title JS content */';
        $this->setMock('channel_form', new class($urlTitleJs) {
            private $urlJs;
            public function __construct($urlJs) { $this->urlJs = $urlJs; }
            public function _url_title_js() { return $this->urlJs; }
        });

        // Mock output to capture the result
        $this->setMock('output', new class {
            private $output = 'initial content';
            public function set_output($output) { $this->output = $output; }
            public function get_output() { return $this->output; }
            public function append_output($output) { $this->output .= $output; }
            public function send_ajax_response($msg, $error = false) {}
            public function set_header($header) {}
        });

        // Mock javascript_loader
        $this->setMock('javascript_loader', new class {
            public function combo_load() { return 'js loader output'; }
        });

        // Call the method
        ob_start();
        $this->channelFormJavascript->combo_load();
        ob_end_clean();

        // Verify that URL title JS was appended to output
        $outputMock = ee()->output;
        $this->assertStringContainsString($urlTitleJs, $outputMock->get_output());
    }

    public function testComboLoadAlwaysIncludesSmileyJs()
    {
        // Setup mocks
        $this->setupComboLoadMocks();

        // Call the method
        ob_start();
        $this->channelFormJavascript->combo_load();
        ob_end_clean();

        // Verify that smiley JS was included in output
        $outputMock = ee()->output;
        $output = $outputMock->get_output();

        // Smiley JS should contain script tags (from our mock function)
        $this->assertStringContainsString('<script>', $output);
        $this->assertStringContainsString('mock smiley js', $output);
    }

    public function testComboLoadAlwaysIncludesChannelFormJs()
    {
        // Mock the channel_form.js file content
        $channelFormJs = '/* Channel form JS content */';

        // Get js_path using reflection since it's private (no absolute paths)
        $reflection = new ReflectionClass($this->channelFormJavascript);
        $jsPathProperty = $reflection->getProperty('js_path');
        $jsPathProperty->setAccessible(true);
        $jsPath = $jsPathProperty->getValue($this->channelFormJavascript);
        $channelFormJsPath = $jsPath . 'channel_form.js';

        // Create a temporary test file for channel_form.js
        // Create directory if it doesn't exist
        $dir = dirname($channelFormJsPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Backup original channel_form.js content if it exists
        $originalChannelFormJsContent = null;
        if (file_exists($channelFormJsPath)) {
            $originalChannelFormJsContent = file_get_contents($channelFormJsPath);
        }

        file_put_contents($channelFormJsPath, $channelFormJs);

        // Setup mocks
        $this->setupComboLoadMocks();

        // Call the method
        ob_start();
        $this->channelFormJavascript->combo_load();
        ob_end_clean();

        // Verify that channel_form.js content was included
        $outputMock = ee()->output;
        $output = $outputMock->get_output();

        $this->assertStringContainsString($channelFormJs, $output);

        // Clean up the test file - restore original content if it existed
        if ($originalChannelFormJsContent !== null) {
            file_put_contents($channelFormJsPath, $originalChannelFormJsContent);
        } else {
            if (file_exists($channelFormJsPath)) {
                unlink($channelFormJsPath);
            }
        }
    }

    public function testComboLoadSetsContentLengthHeader()
    {
        // Setup mocks with initial output
        $initialContent = 'initial output content';
        $this->setMock('output', new class($initialContent) {
            private $output;
            private $headers = [];
            public function __construct($initialOutput) { $this->output = $initialOutput; }
            public function set_output($output) { $this->output = $output; }
            public function get_output() { return $this->output; }
            public function append_output($output) { $this->output .= $output; }
            public function send_ajax_response($msg, $error = false) {}
            public function set_header($header) { $this->headers[] = $header; }
            public function get_headers() { return $this->headers; }
        });

        // Mock javascript_loader
        $this->setMock('javascript_loader', new class {
            public function combo_load() { return 'js loader output'; }
        });

        // Call the method
        ob_start();
        $this->channelFormJavascript->combo_load();
        ob_end_clean();

        // Verify that Content-Length header was set
        $outputMock = ee()->output;
        $headers = $outputMock->get_headers();

        $contentLengthHeader = null;
        foreach ($headers as $header) {
            if (strpos($header, 'Content-Length:') === 0) {
                $contentLengthHeader = $header;
                break;
            }
        }

        $this->assertNotNull($contentLengthHeader, 'Content-Length header should be set');

        // Verify the content length matches the actual output length
        $expectedLength = strlen($outputMock->get_output());
        $this->assertStringContainsString((string)$expectedLength, $contentLengthHeader);
    }
}
