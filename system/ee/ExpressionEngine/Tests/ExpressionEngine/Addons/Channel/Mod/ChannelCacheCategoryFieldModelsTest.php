<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelCacheCategoryFieldModelsTest extends ChannelTestBase
{
    public function testCacheHitShortCircuitsModelFetch()
    {
        // Prepare catfields and var_single to reference field by name
        $this->channel->catfields = [
            ['field_id' => 11, 'field_name' => 'cat_one']
        ];
        ee()->TMPL->var_single = ['cat_one' => 'cat_one'];

        // Minimal Variables/Parser mock
        $this->setMock('Variables/Parser', new class { public function parseVariableProperties($tag){ return ['field_name' => $tag]; } });

        // Session cache already has models
        ee()->session->set_cache('Channel', 'cat_field_models', [11 => (object)['field_id' => 11]]);

        // Mock Model service to detect any fetch attempts
        $this->setMock('Model', new class {
            public function get($name) { throw new Exception('Model fetch should not be called on cache hit'); }
        });

        // Call private method via reflection
        $ref = new ReflectionClass('Channel');
        $m = $ref->getMethod('cacheCategoryFieldModels');
        \TestReflectionHelper::makeMethodAccessible($m);
        $m->invoke($this->channel);

        $prop = (new ReflectionClass('Channel'))->getProperty('cat_field_models');
        \TestReflectionHelper::makePropertyAccessible($prop);
        $models = $prop->getValue($this->channel);
        $this->assertArrayHasKey(11, $models);
    }

    public function testCacheMissFetchesModelsAndCaches()
    {
        $this->channel->catfields = [
            ['field_id' => 21, 'field_name' => 'cat_two'],
            ['field_id' => 22, 'field_name' => 'cat_three'],
        ];
        ee()->TMPL->var_single = ['cat_two' => 'cat_two', 'cat_three' => 'cat_three'];

        // Clear session cache
        ee()->session->set_cache('Channel', 'cat_field_models', []);

        // Minimal Variables/Parser mock so parseVariableProperties returns field_name
        $this->setMock('Variables/Parser', new class { public function parseVariableProperties($tag){ return ['field_name' => $tag]; } });

        // Mock Model service to return models indexed by field_id
        $this->setMock('Model', new class {
            public function get($name, $ids){ return new class($ids){ private $ids; public function __construct($ids){ $this->ids = $ids; }
                public function all(){ return $this; }
                public function indexBy($key){ $out = []; foreach (array_unique($this->ids) as $id){ $obj = (object)['field_id' => $id]; $out[$id] = $obj; } return $out; }
            }; }
        });

        $ref = new ReflectionClass('Channel');
        $m = $ref->getMethod('cacheCategoryFieldModels');
        \TestReflectionHelper::makeMethodAccessible($m);
        $m->invoke($this->channel);

        $prop = (new ReflectionClass('Channel'))->getProperty('cat_field_models');
        \TestReflectionHelper::makePropertyAccessible($prop);
        $models = $prop->getValue($this->channel);
        $this->assertArrayHasKey(21, $models);
        $this->assertArrayHasKey(22, $models);
        $this->assertIsArray(ee()->session->cache('Channel', 'cat_field_models'));
    }
}


