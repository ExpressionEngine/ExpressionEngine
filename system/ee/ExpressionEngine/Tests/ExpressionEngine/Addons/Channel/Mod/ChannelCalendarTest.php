<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelCalendarTest extends ChannelTestBase
{
    public function testCalendarDelegatesToChannelCalendarClass()
    {
        if (!class_exists('Channel_calendar')) {
            eval('class Channel_calendar { public function calendar(){ return "CAL_OK"; } }');
        }

        $out = $this->channel->calendar();
        $this->assertEquals('CAL_OK', $out);
    }

    public function testCalendarExtensionOverride()
    {
        // Provide extensions mock matching signature used by mod
        $this->setMock('extensions', new class {
            public $end_script = true;
            public function active_hook($name){ return $name === 'channel_module_calendar_start'; }
            public function call($name){ return 'EXT_OK'; }
        });
        $out = $this->channel->calendar();
        $this->assertEquals('EXT_OK', $out);
    }
}

class ChannelCalendarHookTest extends ChannelTestBase
{
    public function testCalendarReturnsEarlyWhenHookEndsScript()
    {
        $expectedResult = 'hook_result';

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
        $this->setMock('extensions', new class {
            public $end_script = false;
            public function active_hook($hook) { return true; }
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
}
