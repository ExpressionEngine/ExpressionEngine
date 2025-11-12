<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibSendAjaxResponseTest extends ChannelFormLibTestBase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock config to disable header sending to avoid output issues
        $mockConfig = new class {
            public $config = [];
            public function item($key) {
                if ($key === 'send_headers') {
                    return 'n'; // Disable headers to avoid output issues
                }
                return null;
            }
        };

        ee()->setMock('config', $mockConfig);

        // Mock load library
        $mockLoad = new class {
            public function library($name) {
                return null;
            }
        };

        ee()->setMock('load', $mockLoad);

        // Mock user_agent
        $mockUserAgent = new class {
            public function browser() {
                return 'Chrome';
            }
        };

        ee()->setMock('user_agent', $mockUserAgent);
    }

    /**
     * Test send_ajax_response method exists and is callable
     */
    public function testSendAjaxResponseMethodExists()
    {
        $this->assertTrue(method_exists($this->channelFormLib, 'send_ajax_response'));
    }

    /**
     * Test send_ajax_response calls the underlying output method
     */
    public function testSendAjaxResponseCallsOutputMethod()
    {
        $message = 'Test message';
        $error = false;

        $callCount = 0;

        // Mock output to count calls
        $mockOutput = new class($callCount) {
            private $callCount;
            public function __construct(&$callCount) {
                $this->callCount = &$callCount;
            }
            public function send_ajax_response($msg, $err) {
                $this->callCount++;
                return null;
            }
        };

        ee()->setMock('output', $mockOutput);

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertEquals(1, $callCount);
    }

    /**
     * Test send_ajax_response passes message and error parameters correctly
     */
    public function testSendAjaxResponsePassesParametersCorrectly()
    {
        $message = 'Test message';
        $error = true;

        $capturedMessage = null;
        $capturedError = null;

        // Mock output to capture parameters
        $mockOutput = new class($capturedMessage, $capturedError) {
            private $capturedMessage;
            private $capturedError;
            public function __construct(&$capturedMessage, &$capturedError) {
                $this->capturedMessage = &$capturedMessage;
                $this->capturedError = &$capturedError;
            }
            public function send_ajax_response($msg, $err) {
                $this->capturedMessage = $msg;
                $this->capturedError = $err;
                return null;
            }
        };

        ee()->setMock('output', $mockOutput);

        $this->channelFormLib->send_ajax_response($message, $error);

        $this->assertEquals('Test message', $capturedMessage);
        $this->assertTrue($capturedError);
    }

    /**
     * Test send_ajax_response handles different message types
     */
    public function testSendAjaxResponseHandlesDifferentMessageTypes()
    {
        $testCases = [
            'string' => 'Hello world',
            'array' => ['key' => 'value'],
            'integer' => 42,
            'boolean' => true,
            'null' => null,
            'object' => (object)['test' => 'value']
        ];

        foreach ($testCases as $type => $message) {
            $capturedMessage = null;

            // Mock output to capture message
            $mockOutput = new class($capturedMessage) {
                private $capturedMessage;
                public function __construct(&$capturedMessage) {
                    $this->capturedMessage = &$capturedMessage;
                }
                public function send_ajax_response($msg, $err) {
                    $this->capturedMessage = $msg;
                    return null;
                }
            };

            ee()->setMock('output', $mockOutput);

            $this->channelFormLib->send_ajax_response($message, false);

            $this->assertEquals($message, $capturedMessage, "Failed for $type message type");
        }
    }
}