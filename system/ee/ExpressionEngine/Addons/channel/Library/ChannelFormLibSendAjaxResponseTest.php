<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibSendAjaxResponseTest extends ChannelFormLibTestBase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear any existing headers
        if (function_exists('header_remove')) {
            header_remove();
        }

        // Mock config for header sending
        $mockConfig = $this->createMock('stdClass');
        $mockConfig->method('item')
            ->with('send_headers')
            ->willReturn('y');
        $mockConfig->config = [];

        ee()->setMock('config', $mockConfig);

        // Mock load library
        $mockLoad = $this->createMock('stdClass');
        $mockLoad->method('library')->willReturn(null);

        ee()->setMock('load', $mockLoad);

        // Mock user_agent
        $mockUserAgent = $this->createMock('stdClass');
        $mockUserAgent->method('browser')->willReturn('Chrome');

        ee()->setMock('user_agent', $mockUserAgent);

        // Mock output
        $mockOutput = $this->createMock('stdClass');
        $mockOutput->method('send_ajax_response')->willReturn(null);

        ee()->setMock('output', $mockOutput);
    }

    /**
     * Test send_ajax_response sets correct headers for Safari with array message
     */
    public function testSendAjaxResponseSetsHeadersForSafariWithArrayMessage()
    {
        // Mock Safari browser
        $mockUserAgent = $this->createMock('stdClass');
        $mockUserAgent->method('browser')->willReturn('Safari');
        ee()->setMock('user_agent', $mockUserAgent);

        $message = ['status' => 'success', 'data' => 'test'];
        $error = false;

        // Capture headers
        $headers = [];
        if (!function_exists('header')) {
            function header($header) use (&$headers) {
                $headers[] = $header;
            }
        }

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertContains('Content-Type: application/json; charset=UTF-8', $headers);
    }

    /**
     * Test send_ajax_response sets correct headers for Chrome with array message
     */
    public function testSendAjaxResponseSetsHeadersForChromeWithArrayMessage()
    {
        // Mock Chrome browser
        $mockUserAgent = $this->createMock('stdClass');
        $mockUserAgent->method('browser')->willReturn('Chrome');
        ee()->setMock('user_agent', $mockUserAgent);

        $message = ['status' => 'success', 'data' => 'test'];
        $error = false;

        // Capture headers
        $headers = [];
        if (!function_exists('header')) {
            function header($header) use (&$headers) {
                $headers[] = $header;
            }
        }

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertContains('Content-Type: application/json; charset=UTF-8', $headers);
    }

    /**
     * Test send_ajax_response sets HTML content type for other browsers with array
     */
    public function testSendAjaxResponseSetsHtmlContentTypeForOtherBrowsersWithArray()
    {
        // Mock Firefox browser
        $mockUserAgent = $this->createMock('stdClass');
        $mockUserAgent->method('browser')->willReturn('Firefox');
        ee()->setMock('user_agent', $mockUserAgent);

        $message = ['status' => 'success', 'data' => 'test'];
        $error = false;

        // Capture headers
        $headers = [];
        if (!function_exists('header')) {
            function header($header) use (&$headers) {
                $headers[] = $header;
            }
        }

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertContains('Content-Type: text/html; charset=UTF-8', $headers);
    }

    /**
     * Test send_ajax_response sets HTML content type for string messages
     */
    public function testSendAjaxResponseSetsHtmlContentTypeForStringMessages()
    {
        $message = 'Simple string message';
        $error = false;

        // Capture headers
        $headers = [];
        if (!function_exists('header')) {
            function header($header) use (&$headers) {
                $headers[] = $header;
            }
        }

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertContains('Content-Type: text/html; charset=UTF-8', $headers);
    }

    /**
     * Test send_ajax_response handles error parameter
     */
    public function testSendAjaxResponseHandlesErrorParameter()
    {
        $message = 'Error message';
        $error = true;

        $callCount = 0;
        $capturedError = null;

        // Mock output to capture error parameter
        $mockOutput = $this->createMock('stdClass');
        $mockOutput->method('send_ajax_response')
            ->willReturnCallback(function($msg, $err) use (&$callCount, &$capturedError) {
                $callCount++;
                $capturedError = $err;
                return null;
            });

        ee()->setMock('output', $mockOutput);

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertEquals(1, $callCount);
        $this->assertTrue($capturedError);
    }

    /**
     * Test send_ajax_response handles false error parameter
     */
    public function testSendAjaxResponseHandlesFalseErrorParameter()
    {
        $message = 'Success message';
        $error = false;

        $callCount = 0;
        $capturedError = null;

        // Mock output to capture error parameter
        $mockOutput = $this->createMock('stdClass');
        $mockOutput->method('send_ajax_response')
            ->willReturnCallback(function($msg, $err) use (&$callCount, &$capturedError) {
                $callCount++;
                $capturedError = $err;
                return null;
            });

        ee()->setMock('output', $mockOutput);

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertEquals(1, $callCount);
        $this->assertFalse($capturedError);
    }

    /**
     * Test send_ajax_response disables EE header sending when config allows
     */
    public function testSendAjaxResponseDisablesEeHeaderSendingWhenConfigAllows()
    {
        // Mock config that allows header sending
        $mockConfig = $this->createMock('stdClass');
        $mockConfig->method('item')
            ->with('send_headers')
            ->willReturn('y');
        $mockConfig->config = [];

        ee()->setMock('config', $mockConfig);

        $message = 'Test message';
        $error = false;

        $this->channelFormLib->send_ajax_response($message, $error);

        // Verify that send_headers was set to null
        $this->assertNull($mockConfig->config['send_headers']);
    }

    /**
     * Test send_ajax_response does not set headers when config disables them
     */
    public function testSendAjaxResponseDoesNotSetHeadersWhenConfigDisablesThem()
    {
        // Mock config that disables header sending
        $mockConfig = $this->createMock('stdClass');
        $mockConfig->method('item')
            ->with('send_headers')
            ->willReturn('n');
        $mockConfig->config = [];

        ee()->setMock('config', $mockConfig);

        $message = 'Test message';
        $error = false;

        // Capture headers
        $headers = [];
        $headerCallCount = 0;
        if (!function_exists('header')) {
            function header($header) use (&$headers, &$headerCallCount) {
                $headers[] = $header;
                $headerCallCount++;
            }
        }

        $this->channelFormLib->send_ajax_response($message, $error);

        // Verify no headers were set
        $this->assertEquals(0, $headerCallCount);
        $this->assertEmpty($headers);
    }

    /**
     * Test send_ajax_response handles empty message
     */
    public function testSendAjaxResponseHandlesEmptyMessage()
    {
        $message = '';
        $error = false;

        $callCount = 0;
        $capturedMessage = null;

        // Mock output to capture message
        $mockOutput = $this->createMock('stdClass');
        $mockOutput->method('send_ajax_response')
            ->willReturnCallback(function($msg, $err) use (&$callCount, &$capturedMessage) {
                $callCount++;
                $capturedMessage = $msg;
                return null;
            });

        ee()->setMock('output', $mockOutput);

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertEquals(1, $callCount);
        $this->assertEquals('', $capturedMessage);
    }

    /**
     * Test send_ajax_response handles null message
     */
    public function testSendAjaxResponseHandlesNullMessage()
    {
        $message = null;
        $error = false;

        $callCount = 0;
        $capturedMessage = null;

        // Mock output to capture message
        $mockOutput = $this->createMock('stdClass');
        $mockOutput->method('send_ajax_response')
            ->willReturnCallback(function($msg, $err) use (&$callCount, &$capturedMessage) {
                $callCount++;
                $capturedMessage = $msg;
                return null;
            });

        ee()->setMock('output', $mockOutput);

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertEquals(1, $callCount);
        $this->assertNull($capturedMessage);
    }

    /**
     * Test send_ajax_response handles complex array message
     */
    public function testSendAjaxResponseHandlesComplexArrayMessage()
    {
        $message = [
            'status' => 'success',
            'data' => [
                'users' => [
                    ['id' => 1, 'name' => 'John'],
                    ['id' => 2, 'name' => 'Jane']
                ],
                'total' => 2
            ],
            'timestamp' => time()
        ];
        $error = false;

        // Mock Safari browser to test JSON content type
        $mockUserAgent = $this->createMock('stdClass');
        $mockUserAgent->method('browser')->willReturn('Safari');
        ee()->setMock('user_agent', $mockUserAgent);

        $callCount = 0;
        $capturedMessage = null;

        // Mock output to capture message
        $mockOutput = $this->createMock('stdClass');
        $mockOutput->method('send_ajax_response')
            ->willReturnCallback(function($msg, $err) use (&$callCount, &$capturedMessage) {
                $callCount++;
                $capturedMessage = $msg;
                return null;
            });

        ee()->setMock('output', $mockOutput);

        // Capture headers
        $headers = [];
        if (!function_exists('header')) {
            function header($header) use (&$headers) {
                $headers[] = $header;
            }
        }

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertEquals(1, $callCount);
        $this->assertEquals($message, $capturedMessage);
        $this->assertContains('Content-Type: application/json; charset=UTF-8', $headers);
    }

    /**
     * Test send_ajax_response handles numeric message
     */
    public function testSendAjaxResponseHandlesNumericMessage()
    {
        $message = 42;
        $error = false;

        $callCount = 0;
        $capturedMessage = null;

        // Mock output to capture message
        $mockOutput = $this->createMock('stdClass');
        $mockOutput->method('send_ajax_response')
            ->willReturnCallback(function($msg, $err) use (&$callCount, &$capturedMessage) {
                $callCount++;
                $capturedMessage = $msg;
                return null;
            });

        ee()->setMock('output', $mockOutput);

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertEquals(1, $callCount);
        $this->assertEquals(42, $capturedMessage);
    }

    /**
     * Test send_ajax_response handles boolean message
     */
    public function testSendAjaxResponseHandlesBooleanMessage()
    {
        $message = true;
        $error = false;

        $callCount = 0;
        $capturedMessage = null;

        // Mock output to capture message
        $mockOutput = $this->createMock('stdClass');
        $mockOutput->method('send_ajax_response')
            ->willReturnCallback(function($msg, $err) use (&$callCount, &$capturedMessage) {
                $callCount++;
                $capturedMessage = $msg;
                return null;
            });

        ee()->setMock('output', $mockOutput);

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertEquals(1, $callCount);
        $this->assertTrue($capturedMessage);
    }

    /**
     * Test send_ajax_response handles object message
     */
    public function testSendAjaxResponseHandlesObjectMessage()
    {
        $message = (object)['status' => 'success', 'data' => 'test'];
        $error = false;

        $callCount = 0;
        $capturedMessage = null;

        // Mock output to capture message
        $mockOutput = $this->createMock('stdClass');
        $mockOutput->method('send_ajax_response')
            ->willReturnCallback(function($msg, $err) use (&$callCount, &$capturedMessage) {
                $callCount++;
                $capturedMessage = $msg;
                return null;
            });

        ee()->setMock('output', $mockOutput);

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertEquals(1, $callCount);
        $this->assertEquals($message, $capturedMessage);
    }

    /**
     * Test send_ajax_response calls output send_ajax_response method
     */
    public function testSendAjaxResponseCallsOutputSendAjaxResponseMethod()
    {
        $message = 'Test message';
        $error = false;

        $callCount = 0;

        // Mock output to count calls
        $mockOutput = $this->createMock('stdClass');
        $mockOutput->method('send_ajax_response')
            ->willReturnCallback(function($msg, $err) use (&$callCount) {
                $callCount++;
                return null;
            });

        ee()->setMock('output', $mockOutput);

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertEquals(1, $callCount);
    }

    /**
     * Test send_ajax_response handles different browser types
     */
    public function testSendAjaxResponseHandlesDifferentBrowserTypes()
    {
        $browsers = ['Safari', 'Chrome', 'Firefox', 'Edge', 'Opera', 'IE', 'Unknown'];
        $message = ['test' => 'data'];

        foreach ($browsers as $browser) {
            // Mock browser
            $mockUserAgent = $this->createMock('stdClass');
            $mockUserAgent->method('browser')->willReturn($browser);
            ee()->setMock('user_agent', $mockUserAgent);

            // Capture headers
            $headers = [];
            if (!function_exists('header')) {
                function header($header) use (&$headers) {
                    $headers[] = $header;
                }
            }

            $this->channelFormLib->send_ajax_response($message, false);

            if (in_array($browser, ['Safari', 'Chrome'])) {
                $this->assertContains('Content-Type: application/json; charset=UTF-8', $headers,
                    "Browser $browser should get JSON content type for array messages");
            } else {
                $this->assertContains('Content-Type: text/html; charset=UTF-8', $headers,
                    "Browser $browser should get HTML content type for array messages");
            }
        }
    }

    /**
     * Test send_ajax_response handles config with different values
     */
    public function testSendAjaxResponseHandlesConfigWithDifferentValues()
    {
        $configValues = ['y', 'n', 'Y', 'N', '', null, 'yes', 'no'];

        foreach ($configValues as $value) {
            // Mock config
            $mockConfig = $this->createMock('stdClass');
            $mockConfig->method('item')
                ->with('send_headers')
                ->willReturn($value);
            $mockConfig->config = [];

            ee()->setMock('config', $mockConfig);

            $message = 'Test message';
            $error = false;

            // Capture headers
            $headers = [];
            $headerCallCount = 0;
            if (!function_exists('header')) {
                function header($header) use (&$headers, &$headerCallCount) {
                    $headers[] = $header;
                    $headerCallCount++;
                }
            }

            $this->channelFormLib->send_ajax_response($message, $error);

            // Only 'y' should trigger header sending
            if ($value === 'y') {
                $this->assertGreaterThan(0, $headerCallCount, "Config value '$value' should trigger header sending");
            } else {
                $this->assertEquals(0, $headerCallCount, "Config value '$value' should not trigger header sending");
            }
        }
    }
}
