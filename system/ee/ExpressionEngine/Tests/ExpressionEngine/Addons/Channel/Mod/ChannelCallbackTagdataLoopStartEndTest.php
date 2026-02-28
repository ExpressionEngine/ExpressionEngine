<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelCallbackTagdataLoopStartEndTest extends ChannelTestBase
{
    public function testLoopStartNoHookReturnsTagdata()
    {
        $out = $this->channel->callback_tagdata_loop_start('ABC', ['entry_id' => 1]);
        $this->assertEquals('ABC', $out);
    }

    public function testLoopStartHookMutatesTagdata()
    {
        $this->setMock('extensions', new class {
            public $hooks = ['channel_entries_tagdata' => ['active' => true]];
            public function active_hook($name){ return isset($this->hooks[$name]) && $this->hooks[$name]['active']; }
            public function call($name, $tagdata, $row, $self){ return $tagdata . '|S'; }
        });

        $out = $this->channel->callback_tagdata_loop_start('ABC', ['entry_id' => 1]);
        $this->assertEquals('ABC|S', $out);
    }

    public function testLoopEndNoHookReturnsTagdata()
    {
        $out = $this->channel->callback_tagdata_loop_end('XYZ', ['entry_id' => 1]);
        $this->assertEquals('XYZ', $out);
    }

    public function testLoopEndHookMutatesTagdata()
    {
        $this->setMock('extensions', new class {
            public $hooks = ['channel_entries_tagdata_end' => ['active' => true]];
            public function active_hook($name){ return isset($this->hooks[$name]) && $this->hooks[$name]['active']; }
            public function call($name, $tagdata, $row, $self){ return $tagdata . '|E'; }
        });

        $out = $this->channel->callback_tagdata_loop_end('XYZ', ['entry_id' => 1]);
        $this->assertEquals('XYZ|E', $out);
    }
}


