<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelCloseUlAndComboLoaderTest extends ChannelTestBase
{
    public function testCloseUlAppendsClosingWhenNoChildren()
    {
        $this->channel->temp_array = [ [2,0], [3,2] ]; // none with parent_id=5
        $this->channel->category_list = [];
        $this->channel->close_ul(5, 2);
        $this->assertNotEmpty($this->channel->category_list);
        $this->assertStringContainsString("\t\t</ul>", end($this->channel->category_list));
    }

    public function testCloseUlDoesNothingWhenChildrenExist()
    {
        $this->channel->temp_array = [ [5,0], [6,5] ]; // has child with parent_id=5
        $this->channel->category_list = [];
        $this->channel->close_ul(5, 1);
        $this->assertEmpty($this->channel->category_list);
    }

    public function testComboLoaderCssMissingFileDoesNotFatal()
    {
        // Simulate CSS request but file missing
        $this->setMock('input', new class {
            public function get($key){ return $key==='type' ? 'css' : null; }
            public function get_post($key){ return $key==='file' ? 'nope' : null; }
        });
        $this->setMock('config', new class {
            public function item($k){ return 'y'; }
            public function slash_item($k){ return '/emoticons/'; }
        });
        // Mock output to avoid headers
        $this->setMock('output', new class {
            public $out_type; public $final_output;
            public function enable_profiler($b){}
            public function send_cache_headers($a,$b,$c){}
            public function set_output($s){ $this->final_output = $s; }
        });

        // Should return null/void cleanly
        $this->channel->combo_loader();
        $this->assertTrue(true);
    }
}


