<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelCategorySubtreeTest extends ChannelTestBase
{
    public function testCategorySubtreeWithInvalidStartHasNoOutput()
    {
        $this->channel->cat_array = [];
        $open = $this->channel->category_subtree(['parent_id' => 999, 'template' => '', 'path' => []]);
        $this->assertEquals(0, $open);
    }

    public function testCategorySubtreeDepthIncrementAndCounts()
    {
        // Minimal fake cat_array: [parent_id, name, image, desc, url_title]
        $this->channel->cat_array = [
            1 => [0, 'Root', '', '', 'root'],
            2 => [1, 'Child', '', '', 'child']
        ];
        $this->channel->catfields = [];
        // Ensure typography is available for format_characters and initialize
        $this->setMock('typography', new class {
            public function format_characters($s){ return $s; }
            public function initialize($cfg){ return true; }
        });
        // Stub loader so load->library() and load->helper() calls do nothing
        $this->setMock('load', new class {
            public function library($name) { /* no-op */ }
            public function helper($name) { /* no-op */ }
        });
        // Provide legacy API and channel fields mocks used by parseCategoryFields
        $this->setMock('legacy_api', new class { public function instantiate($what){ return true; } });
        $this->setMock('api_channel_fields', new class {
            public $field_types = [];
            public function include_handler($name){ return true; }
            public function setup_handler($name, $bool){ return new class {}; }
        });
        // File field mock for parse_string
        $this->setMock('file_field', new class { public function parse_string($s){ return $s; } });
        // Template mock with parse_switch and empty var_single
        $this->setMock('TMPL', new class { public $var_single = []; public function parse_switch($chunk, $i){ return $chunk; } });
        // Provide functions mock for encode_ee_tags and prep_conditionals
        $this->setMock('functions', new class {
            public function encode_ee_tags($s){ return $s; }
            public function prep_conditionals($str, $vars){ return $str; }
        });
        $open = $this->channel->category_subtree(['parent_id' => 0, 'template' => '{category_name}', 'path' => []]);
        $this->assertEquals(1, $open);
        $this->assertGreaterThan(0, count($this->channel->category_list));
    }
}


