<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelCallbackEntryRowDataTest extends ChannelTestBase
{
    public function testNoHookReturnsRowUnchanged()
    {
        $row = ['entry_id' => 1, 'title' => 'A'];
        $out = $this->channel->callback_entry_row_data('X', $row);
        $this->assertSame($row, $out);
    }

    public function testHookCanMutateRow()
    {
        ee()->extensions->hooks['channel_entries_row'] = [ 'active' => true, 'return' => function($self, $row){ $row['title'] = 'B'; return $row; } ];
        // Make call() execute the provided closure
        $this->setMock('extensions', new class {
            public $hooks = ['channel_entries_row' => ['active' => true, 'return' => null]];
            public function active_hook($name){ return isset($this->hooks[$name]) && $this->hooks[$name]['active']; }
            public function call($name, $self, $row){ return ['entry_id' => $row['entry_id'], 'title' => 'B']; }
        });

        $row = ['entry_id' => 2, 'title' => 'A'];
        $out = $this->channel->callback_entry_row_data('X', $row);
        $this->assertEquals('B', $out['title']);
    }
}


