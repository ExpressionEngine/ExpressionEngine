<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFieldTest extends ChannelTestBase
{
    public function testNoParametersReturnsNoResults()
    {
        // No field_id/field_name provided
        $this->setTemplateParams([]);
        $result = $this->channel->field();
        $this->assertEquals('NO_RESULTS', $result);
    }

    public function testFieldNameResolvesAndOmitsFieldSettings()
    {
        // Stub TMPL to capture parse_variables output
        $this->setMock('TMPL', new class {
            public $tagdata = 'x';
            public $tagparams = ['field_name' => 'my_field'];
            public function fetch_param($k){ return $this->tagparams[$k] ?? null; }
            public function no_results(){ return 'NO_RESULTS'; }
            public function parse_variables($tagdata, $vars){ return json_encode($vars[0]); }
        });

        // Mock Model to return a field model
        $fieldModel = new class {
            public function getValues(){ return ['field_id'=>5,'field_name'=>'my_field','field_settings'=>['secret'=>'x']]; }
            public function getPossibleValuesForEvaluation(){ return ['A'=>'Alpha','B'=>'Bravo']; }
        };
        $this->setMock('Model', new class($fieldModel){ private $m; public function __construct($m){$this->m=$m;} public function get($n){ return new class($this->m){ private $m; public function __construct($m){$this->m=$m;} public function filter(){ return $this; } public function first(){ return $this->m; } }; } });

        $out = $this->channel->field();
        $data = json_decode($out, true);
        $this->assertArrayHasKey('field_id', $data);
        $this->assertArrayHasKey('field_name', $data);
        $this->assertArrayNotHasKey('field_settings', $data);
        $this->assertArrayHasKey('field_options', $data);
        $this->assertCount(2, $data['field_options']);
        $this->assertSame(['value'=>'A','label'=>'Alpha'], $data['field_options'][0]);
    }
}