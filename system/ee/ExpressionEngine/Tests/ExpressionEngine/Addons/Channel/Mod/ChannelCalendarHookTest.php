<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelCalendarHookTest extends ChannelTestBase
{
    public function testCalendarReturnsEarlyWhenHookEndsScript()
    {
        $expectedResult = 'hook_result';

        $this->setMock('extensions', new class($expectedResult) {
            private $result;
            public function __construct($result) { $this->result = $result; }
            public $end_script = true;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { return $this->result; }
        });

        $result = $this->channel->calendar();
        $this->assertEquals($expectedResult, $result);
    }

    public function testCalendarContinuesWhenHookDoesNotEndScript()
    {
        $this->setMock('extensions', new class {
            public $end_script = false;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { return 'hook_data'; }
        });

        if (!class_exists('Channel_calendar')) {
            eval('class Channel_calendar { public function calendar() { return "calendar_output"; } }');
        }

        $result = $this->channel->calendar();
        $this->assertEquals('calendar_output', $result);
    }

    public function testCalendarWorksWhenHookNotActive()
    {
        $this->setMock('extensions', new class {
            public $end_script = false;
            public function active_hook($hook) { return false; }
            public function call($hook) { return null; }
        });

        if (!class_exists('Channel_calendar')) {
            eval('class Channel_calendar { public function calendar() { return "calendar_output"; } }');
        }

        $result = $this->channel->calendar();
        $this->assertEquals('calendar_output', $result);
    }

    public function testCalendarHandlesHookWithNullReturn()
    {
        $this->setMock('extensions', new class {
            public $end_script = true;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { return null; }
        });

        $result = $this->channel->calendar();
        $this->assertNull($result);
    }

    public function testCalendarHandlesHookWithEmptyStringReturn()
    {
        $this->setMock('extensions', new class {
            public $end_script = true;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { return ''; }
        });

        $result = $this->channel->calendar();
        $this->assertEquals('', $result);
    }

    public function testCalendarHandlesHookWithFalseReturn()
    {
        $this->setMock('extensions', new class {
            public $end_script = true;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { return false; }
        });

        $result = $this->channel->calendar();
        $this->assertFalse($result);
    }

    public function testCalendarHandlesHookWithZeroReturn()
    {
        $this->setMock('extensions', new class {
            public $end_script = true;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { return 0; }
        });

        $result = $this->channel->calendar();
        $this->assertEquals(0, $result);
    }

    public function testCalendarHandlesMultipleHookCalls()
    {
        $callCount = 0;
        $this->setMock('extensions', new class($callCount) {
            private $count;
            public function __construct(&$count) { $this->count = &$count; }
            public $end_script = false;
            public function active_hook($hook) {
                $this->count++;
                return $hook === 'channel_module_calendar_start';
            }
            public function call($hook) { return 'hook_result_' . $this->count; }
        });

        if (!class_exists('Channel_calendar')) {
            eval('class Channel_calendar { public function calendar() { return "calendar_output"; } }');
        }

        $result = $this->channel->calendar();
        $this->assertEquals('calendar_output', $result);
        $this->assertEquals(1, $callCount);
    }

    public function testCalendarHandlesHookWithComplexEndScriptLogic()
    {
        $testCases = [
            ['end_script' => true, 'expected' => 'early_return'],
            ['end_script' => false, 'expected' => 'calendar_output'],
        ];

        foreach ($testCases as $case) {
            $this->setMock('extensions', new class($case) {
                private $case;
                public function __construct($case) { $this->case = $case; }
                public $end_script;
                public function active_hook($hook) {
                    $this->end_script = $this->case['end_script'];
                    return $hook === 'channel_module_calendar_start';
                }
                public function call($hook) {
                    return $this->case['expected'] === 'early_return' ? $this->case['expected'] : 'ignored';
                }
            });

            if (!class_exists('Channel_calendar') && $case['expected'] === 'calendar_output') {
                eval('class Channel_calendar { public function calendar() { return "calendar_output"; } }');
            }

            $result = $this->channel->calendar();
            $this->assertEquals($case['expected'], $result,
                "Failed for end_script = {$case['end_script']}");
        }
    }

    public function testCalendarHandlesHookPriorityOverCalendarClass()
    {
        // Test that hook takes priority even when calendar class exists
        $this->setMock('extensions', new class {
            public $end_script = true;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { return 'hook_priority'; }
        });

        if (!class_exists('Channel_calendar')) {
            eval('class Channel_calendar { public function calendar() { return "should_not_run"; } }');
        }

        $result = $this->channel->calendar();
        $this->assertEquals('hook_priority', $result, 'Hook should take priority over calendar class');
    }

    public function testCalendarHandlesInactiveHookWithExistingCalendarClass()
    {
        $this->setMock('extensions', new class {
            public $end_script = false;
            public function active_hook($hook) { return false; } // Hook not active
            public function call($hook) { return 'should_not_call'; }
        });

        // Create a unique test class to avoid conflicts with existing Channel_calendar
        if (!class_exists('TestChannelCalendar')) {
            eval('class TestChannelCalendar { public function calendar() { return "test_calendar_output"; } }');
        }

        if (!class_exists('Channel_calendar')) {
            eval('class Channel_calendar extends TestChannelCalendar {}');
        }

        $result = $this->channel->calendar();
        $this->assertStringContainsString('calendar', $result); // More flexible assertion
    }

    public function testCalendarHandlesHookExceptionGracefully()
    {
        $this->setMock('extensions', new class {
            public $end_script = true;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { throw new Exception('Hook processing failed'); }
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hook processing failed');
        $this->channel->calendar();
    }

    public function testCalendarHandlesHookWithInvalidReturnType()
    {
        // Test hook returning non-callable object
        $invalidObject = new stdClass();
        $invalidObject->invalid = 'data';

        $this->setMock('extensions', new class($invalidObject) {
            private $obj;
            public function __construct($obj) { $this->obj = $obj; }
            public $end_script = true;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { return $this->obj; }
        });

        $result = $this->channel->calendar();
        $this->assertEquals($invalidObject, $result);
    }

    public function testCalendarHandlesHookWithResourceReturn()
    {
        // Test hook returning a resource (like file handle)
        $tempFile = tmpfile();

        $this->setMock('extensions', new class($tempFile) {
            private $resource;
            public function __construct($resource) { $this->resource = $resource; }
            public $end_script = true;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { return $this->resource; }
        });

        $result = $this->channel->calendar();
        $this->assertTrue(is_resource($result), 'Should handle resource return from hook');

        // Clean up
        if (is_resource($result)) {
            fclose($result);
        }
    }

    public function testCalendarHandlesHookWithCallableReturn()
    {
        // Test hook returning a callable
        $callable = function() { return 'callable_result'; };

        $this->setMock('extensions', new class($callable) {
            private $callable;
            public function __construct($callable) { $this->callable = $callable; }
            public $end_script = true;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { return $this->callable; }
        });

        $result = $this->channel->calendar();
        $this->assertTrue(is_callable($result), 'Should handle callable return from hook');
    }

    public function testCalendarHandlesHookPerformance()
    {
        // Test hook performance with multiple rapid calls
        $callCount = 0;
        $startTime = microtime(true);

        $this->setMock('extensions', new class($callCount) {
            private $count;
            public function __construct(&$count) { $this->count = &$count; }
            public $end_script = false;
            public function active_hook($hook) {
                $this->count++;
                return $hook === 'channel_module_calendar_start';
            }
            public function call($hook) { return 'perf_test_' . $this->count; }
        });

        if (!class_exists('Channel_calendar')) {
            eval('class Channel_calendar { public function calendar() { return "calendar_perf"; } }');
        }

        // Make 100 rapid calls
        for ($i = 0; $i < 100; $i++) {
            $result = $this->channel->calendar();
            $this->assertStringStartsWith('calendar', $result);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete in reasonable time (less than 1 second for 100 calls)
        $this->assertLessThan(1.0, $executionTime,
            '100 calls should complete in less than 1 second');
    }

    public function testCalendarHandlesHookWithNestedArrays()
    {
        // Test hook returning deeply nested array
        $nestedData = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'data' => 'nested_value',
                        'array' => [1, 2, 3],
                        'object' => (object)['prop' => 'value']
                    ]
                ]
            ]
        ];

        $this->setMock('extensions', new class($nestedData) {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public $end_script = true;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { return $this->data; }
        });

        $result = $this->channel->calendar();
        $this->assertEquals($nestedData, $result);
        $this->assertEquals('nested_value', $result['level1']['level2']['level3']['data']);
    }

    public function testCalendarHandlesHookWithSpecialCharacters()
    {
        // Test hook returning data with special characters
        $specialData = [
            'html' => '<script>alert("xss")</script>',
            'json' => '{"key": "value with spaces"}',
            'unicode' => '测试数据 🚀',
            'binary' => base64_encode(random_bytes(100))
        ];

        $this->setMock('extensions', new class($specialData) {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public $end_script = true;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { return $this->data; }
        });

        $result = $this->channel->calendar();
        $this->assertEquals($specialData, $result);
        $this->assertEquals('<script>alert("xss")</script>', $result['html']);
        $this->assertEquals('测试数据 🚀', $result['unicode']);
    }

    public function testCalendarHandlesHookWithCircularReference()
    {
        // Test hook attempting to return circular reference
        $circular = new stdClass();
        $circular->self = $circular; // Circular reference

        $this->setMock('extensions', new class($circular) {
            private $circular;
            public function __construct($circular) { $this->circular = $circular; }
            public $end_script = true;
            public function active_hook($hook) { return $hook === 'channel_module_calendar_start'; }
            public function call($hook) { return $this->circular; }
        });

        $result = $this->channel->calendar();
        $this->assertEquals($circular, $result);
        $this->assertSame($result, $result->self, 'Should handle circular reference');
    }
}
