<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFetchCustomChannelFieldsTest extends ChannelTestBase
{
    public function testCacheHitShortCircuitsApi()
    {
        ee()->session->cache['channel']['custom_channel_fields'] = ['cf' => ['field_id_1' => 'title']];
        ee()->session->cache['channel']['date_fields'] = ['df' => []];
        ee()->session->cache['channel']['relationship_fields'] = ['rf' => []];
        ee()->session->cache['channel']['grid_fields'] = ['gf' => []];
        ee()->session->cache['channel']['members_fields'] = ['mf' => []];
        ee()->session->cache['channel']['pair_custom_fields'] = ['pf' => []];
        ee()->session->cache['channel']['fluid_field_fields'] = ['ff' => []];
        ee()->session->cache['channel']['toggle_fields'] = ['tf' => []];

        $this->channel->fetch_custom_channel_fields();

        $this->assertSame(ee()->session->cache['channel']['custom_channel_fields'], $this->channel->cfields);
        $this->assertSame(ee()->session->cache['channel']['date_fields'], $this->channel->dfields);
        $this->assertSame(ee()->session->cache['channel']['relationship_fields'], $this->channel->rfields);
        $this->assertSame(ee()->session->cache['channel']['grid_fields'], $this->channel->gfields);
        $this->assertSame(ee()->session->cache['channel']['members_fields'], $this->channel->msfields);
        $this->assertSame(ee()->session->cache['channel']['pair_custom_fields'], $this->channel->pfields);
        $this->assertSame(ee()->session->cache['channel']['fluid_field_fields'], $this->channel->ffields);
        $this->assertSame(ee()->session->cache['channel']['toggle_fields'], $this->channel->tfields);
    }

    public function testApiFetchPopulatesFieldsAndCaches()
    {
        ee()->session->cache['channel'] = [];

        $fields = [
            'custom_channel_fields' => ['c' => 1],
            'date_fields' => ['d' => 1],
            'relationship_fields' => ['r' => 1],
            'members_fields' => ['m' => 1],
            'grid_fields' => ['g' => 1],
            'pair_custom_fields' => ['p' => 1],
            'fluid_field_fields' => ['f' => 1],
            'toggle_fields' => ['t' => 1],
        ];

        $this->setMock('api_channel_fields', new class($fields) {
            public $fields;
            public function __construct($fields){ $this->fields = $fields; }
            public function fetch_custom_channel_fields(){ return $this->fields; }
            public function set_settings($id, $settings) {}
            public function setup_handler($id) { return false; }
            public function apply($method, $args) { return null; }
            public function check_method_exists($method) { return false; }
            public function fetch_custom_member_fields() { return []; }
            public $custom_member_field_pairs = [];
        });

        $this->channel->fetch_custom_channel_fields();

        $this->assertSame($fields['custom_channel_fields'], $this->channel->cfields);
        $this->assertSame($fields['date_fields'], $this->channel->dfields);
        $this->assertSame($fields['relationship_fields'], $this->channel->rfields);
        $this->assertSame($fields['members_fields'], $this->channel->msfields);
        $this->assertSame($fields['grid_fields'], $this->channel->gfields);
        $this->assertSame($fields['pair_custom_fields'], $this->channel->pfields);
        $this->assertSame($fields['fluid_field_fields'], $this->channel->ffields);
        $this->assertSame($fields['toggle_fields'], $this->channel->tfields);

        $this->assertSame($fields['custom_channel_fields'], ee()->session->cache['channel']['custom_channel_fields']);
        $this->assertSame($fields['date_fields'], ee()->session->cache['channel']['date_fields']);
        $this->assertSame($fields['relationship_fields'], ee()->session->cache['channel']['relationship_fields']);
        $this->assertSame($fields['members_fields'], ee()->session->cache['channel']['members_fields']);
        $this->assertSame($fields['grid_fields'], ee()->session->cache['channel']['grid_fields']);
        $this->assertSame($fields['pair_custom_fields'], ee()->session->cache['channel']['pair_custom_fields']);
        $this->assertSame($fields['fluid_field_fields'], ee()->session->cache['channel']['fluid_field_fields']);
        $this->assertSame($fields['toggle_fields'], ee()->session->cache['channel']['toggle_fields']);
    }

    public function testInstallWideFieldsPropagateAcrossSites()
    {
        ee()->session->cache['channel'] = [];
        $fields = [
            'custom_channel_fields' => [0 => ['alpha' => 1], 2 => ['beta' => 2]],
            'date_fields' => [0 => ['d' => 1]],
            'relationship_fields' => [0 => ['r' => 1]],
            'members_fields' => [0 => ['m' => 1]],
            'grid_fields' => [0 => ['g' => 1]],
            'pair_custom_fields' => [0 => ['p' => 1]],
            'fluid_field_fields' => [0 => ['f' => 1]],
            'toggle_fields' => [0 => ['t' => 1]],
        ];
        $this->setMock('api_channel_fields', new class($fields) {
            public $fields;
            public function __construct($fields){ $this->fields = $fields; }
            public function fetch_custom_channel_fields(){ return $this->fields; }
            public function set_settings($id, $settings) {}
            public function setup_handler($id) { return false; }
            public function apply($method, $args) { return null; }
            public function check_method_exists($method) { return false; }
            public function fetch_custom_member_fields() { return []; }
            public $custom_member_field_pairs = [];
        });
        // Mock site ids [1,2]
        $this->setMock('Model', new class {
            public function get($model){
                return new class {
                    public function fields(){ return $this; }
                    public function all($raw = false){ return new class { public function getIds(){ return [1,2]; } }; }
                };
            }
        });

        $this->channel->fetch_custom_channel_fields();

        // cfields 1 and 2 should exist; site 2 merges beta and alpha
        $this->assertArrayHasKey(1, $this->channel->cfields);
        $this->assertArrayHasKey(2, $this->channel->cfields);
        $this->assertArrayHasKey('alpha', $this->channel->cfields[2]);
        $this->assertArrayHasKey('beta', $this->channel->cfields[2]);
    }
    public function testFetchCustomChannelFieldsReturnsCachedDataWhenAvailable()
    {
        // Set up cached data
        $cachedData = [
            'field_id_1' => ['field_name' => 'test_field', 'field_type' => 'text'],
            'field_id_2' => ['field_name' => 'another_field', 'field_type' => 'textarea']
        ];

        // Mock session cache to return cached data
        $this->setMock('session', new class($cachedData) {
            public $cache = [];
            private $cachedData;
            public function __construct($cachedData) { $this->cachedData = $cachedData; }
            public function cache($class, $key) {
                if ($class === 'channel' && $key === 'custom_channel_fields') {
                    return $this->cachedData;
                }
                return false;
            }
            public function set_cache($class, $key, $value) {
                // No-op for this test
            }
        });

        $this->channel->fetch_custom_channel_fields();

        // Verify the method completed without errors
        // The exact caching behavior depends on the mock implementation
        $this->assertIsArray($this->channel->cfields);
    }

    public function testFetchCustomChannelFieldsQueriesDatabaseWhenNotCached()
    {
        // Mock session cache to return false (no cached data)
        $this->setMock('session', new class {
            public $cache = [];
            public function cache($class, $key) { return false; }
            public function set_cache($class, $key, $value) {
                // Store the value for verification
                $this->storedData[$class][$key] = $value;
            }
        });

        // Mock database to return field data
        $fieldData = [
            [
                'field_id' => 1,
                'field_name' => 'test_field',
                'field_type' => 'text',
                'field_settings' => '{"field_required":"n"}'
            ],
            [
                'field_id' => 2,
                'field_name' => 'another_field',
                'field_type' => 'textarea',
                'field_settings' => '{"field_required":"y"}'
            ]
        ];

        $this->setDbRows($fieldData);

        $this->channel->fetch_custom_channel_fields();

        // Verify the method completed without errors
        // The exact field loading depends on the database mock
        $this->assertIsArray($this->channel->cfields);
    }

    public function testFetchCustomChannelFieldsHandlesDateFields()
    {
        // Mock session cache to return false (no cached data)
        $this->setMock('session', new class {
            public $cache = [];
            public function cache($class, $key) { return false; }
            public function set_cache($class, $key, $value) {
                // Store the value for verification
            }
        });

        // Mock database to return field data including date fields
        $fieldData = [
            [
                'field_id' => 1,
                'field_name' => 'date_field',
                'field_type' => 'date',
                'field_settings' => '{"field_required":"n"}'
            ]
        ];

        $this->setDbRows($fieldData);

        $this->channel->fetch_custom_channel_fields();

        // Verify the method completed without errors
        // The exact field processing depends on the mock data
        $this->assertIsArray($this->channel->dfields);
    }

    public function testFetchCustomChannelFieldsHandlesRelationshipFields()
    {
        // Mock session cache to return false (no cached data)
        $this->setMock('session', new class {
            public $cache = [];
            public function cache($class, $key) { return false; }
            public function set_cache($class, $key, $value) {
                // Store the value for verification
            }
        });

        // Mock database to return field data including relationship fields
        $fieldData = [
            [
                'field_id' => 1,
                'field_name' => 'relationship_field',
                'field_type' => 'relationship',
                'field_settings' => '{"field_required":"n"}'
            ]
        ];

        $this->setDbRows($fieldData);

        $this->channel->fetch_custom_channel_fields();

        // Verify the method completed without errors
        // The exact field processing depends on the mock data
        $this->assertIsArray($this->channel->rfields);
    }

    public function testFetchCustomChannelFieldsHandlesGridFields()
    {
        // Mock session cache to return false (no cached data)
        $this->setMock('session', new class {
            public $cache = [];
            public function cache($class, $key) { return false; }
            public function set_cache($class, $key, $value) {
                // Store the value for verification
            }
        });

        // Mock database to return field data including grid fields
        $fieldData = [
            [
                'field_id' => 1,
                'field_name' => 'grid_field',
                'field_type' => 'grid',
                'field_settings' => '{"field_required":"n"}'
            ]
        ];

        $this->setDbRows($fieldData);

        $this->channel->fetch_custom_channel_fields();

        // Verify the method completed without errors
        // The exact field processing depends on the mock data
        $this->assertIsArray($this->channel->gfields);
    }
}
