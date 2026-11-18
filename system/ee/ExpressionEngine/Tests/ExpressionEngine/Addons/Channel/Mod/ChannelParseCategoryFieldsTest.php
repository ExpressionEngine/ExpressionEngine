<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelParseCategoryFieldsTest extends ChannelTestBase
{
    public function testUsesTemplateVarSingleWhenVariablesEmpty()
    {
        $this->channel->catfields = [];
        // TMPL var_single contains tags to parse, but data lacks keys -> ignored
        ee()->TMPL->var_single = ['category_name'];
        // Ensure typography initialize is available and Variables/Parser exists
        $this->setMock('load', new class {
            public function library($name){
                if ($name === 'typography') {
                    ee()->setMock('typography', new class { public function initialize($c){ return true; } });
                }
                if ($name === 'api') {
                    ee()->legacy_api = new class { public function instantiate($n){} };
                }
            }
        });
        $this->setMock('Variables/Parser', new class { public function parseVariableProperties($tag){ return ['field_name' => $tag]; } });
        // api_channel_fields methods used in parseCategoryFields
        $this->setMock('api_channel_fields', new class {
            public $field_types = [];
            public $field_type = '';
            public function include_handler($name){ return true; }
            public function setup_handler($name, $bool){ return new class {}; }
        });

        $out = (new ReflectionClass('Channel'))
            ->getMethod('parseCategoryFields')
            ->invoke($this->channel, 10, ['category_name' => 'X'], 'Hello {category_name}', []);

        $this->assertIsString($out);
    }

    public function testFileFieldParsingIsInvoked()
    {
        $this->channel->catfields = [];
        $chunk = 'img {category_image}';
        // Ensure typography exists for initialize
        $this->setMock('typography', new class { public function initialize($c){ return true; } });
        // Ensure api_channel_fields supports include_handler/setup_handler
        $this->setMock('api_channel_fields', new class {
            public $field_types = [];
            public $field_type = '';
            public function include_handler($name){ return true; }
            public function setup_handler($name, $bool){ return new class {}; }
        });
        // Ensure loader installs our desired file_field when library() is called from code
        $this->setMock('load', new class {
            public function library($name){
                if ($name === 'file_field') {
                    ee()->setMock('file_field', new class { public function parse_field($s){ return $s . ' PARSED'; } public function parse_string($s){ return $s; } });
                }
                if ($name === 'typography') {
                    ee()->setMock('typography', new class { public function initialize($c){ return true; } });
                }
                if ($name === 'api') {
                    ee()->legacy_api = new class { public function instantiate($n){} };
                }
            }
        });

        // Provide var props with a non-empty modifier to trigger file field parsing
        $this->setMock('Variables/Parser', new class { public function parseVariableProperties($tag){ return ['field_name' => $tag, 'modifier' => 'x', 'all_modifiers' => []]; } });
        $out = (new ReflectionClass('Channel'))
            ->getMethod('parseCategoryFields')
            ->invoke($this->channel, 1, ['category_image' => 'foo.jpg'], $chunk, ['category_image']);
        $this->assertStringEndsWith('PARSED', $out);
    }
}

