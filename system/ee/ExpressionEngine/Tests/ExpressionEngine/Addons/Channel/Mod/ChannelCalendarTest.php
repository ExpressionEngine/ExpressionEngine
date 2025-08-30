<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelCalendarTest extends ChannelTestBase
{
    public function testCalendarReturnsEarlyWhenHookEndsScript()
    {
        $expectedResult = 'hook_result';

        // Mock extensions to return early
        $this->setMock('extensions', new class($expectedResult) {
            private $result;
            public function __construct($result) { $this->result = $result; }
            public $end_script = true;
            public function active_hook($hook) { return true; }
            public function call($hook) { return $this->result; }
        });

        $result = $this->channel->calendar();

        $this->assertEquals($expectedResult, $result);
    }

    public function testCalendarContinuesWhenHookDoesNotEndScript()
    {
        // Mock extensions to not end script
        $this->setMock('extensions', new class {
            public $end_script = false;
            public function active_hook($hook) { return true; }
            public function call($hook) { return 'hook_data'; }
        });

        // Mock Channel_calendar class
        if (!class_exists('Channel_calendar')) {
            eval('class Channel_calendar { public function calendar() { return "calendar_output"; } }');
        }

        $result = $this->channel->calendar();

        $this->assertEquals('calendar_output', $result);
    }

    public function testCalendarWorksWhenHookNotActive()
    {
        // Mock extensions to indicate hook not active
        $this->setMock('extensions', new class {
            public $end_script = false;
            public function active_hook($hook) { return false; }
            public function call($hook) { return null; }
        });

        // Mock Channel_calendar class
        if (!class_exists('Channel_calendar')) {
            eval('class Channel_calendar { public function calendar() { return "calendar_output"; } }');
        }

        $result = $this->channel->calendar();

        $this->assertEquals('calendar_output', $result);
    }
}
