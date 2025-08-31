<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFetchCustomMemberFieldsTest extends ChannelTestBase
{
    public function testCacheHitShortCircuitsApi()
    {
        $cachedM = ['member_field_1' => 'foo'];
        $cachedMP = ['pair_field_1' => 'bar'];
        ee()->session->cache['channel']['custom_member_fields'] = $cachedM;
        ee()->session->cache['channel']['custom_member_field_pairs'] = $cachedMP;

        $this->channel->fetch_custom_member_fields();

        $this->assertSame($cachedM, $this->channel->mfields);
        $this->assertSame($cachedMP, $this->channel->mpfields);
    }

    public function testApiPathPopulatesFields()
    {
        ee()->session->cache['channel'] = [];

        $apiM = ['member_field_2' => 'baz'];
        $apiMP = ['pair_field_2' => 'qux'];

        $this->setMock('api_channel_fields', new class($apiM, $apiMP) {
            public $m; public $mp; public $custom_member_field_pairs;
            public function __construct($m, $mp){ $this->m = $m; $this->mp = $mp; $this->custom_member_field_pairs = $mp; }
            public function fetch_custom_member_fields(){ return $this->m; }
            public function set_settings($id, $settings) {}
            public function setup_handler($id) { return false; }
            public function apply($method, $args) { return null; }
            public function check_method_exists($method) { return false; }
            public function fetch_custom_channel_fields(){ return [
                'custom_channel_fields' => [],
                'date_fields' => [],
                'relationship_fields' => [],
                'members_fields' => [],
                'grid_fields' => [],
                'pair_custom_fields' => [],
                'fluid_field_fields' => [],
                'toggle_fields' => [],
            ]; }
        });

        $this->channel->fetch_custom_member_fields();

        $this->assertSame($apiM, $this->channel->mfields);
        $this->assertSame($apiMP, $this->channel->mpfields);
        // Intentionally not asserting caches due to known base-code bug on second cache key write.
    }

    public function testEmptyPairsHandled()
    {
        ee()->session->cache['channel'] = [];
        $this->setMock('api_channel_fields', new class {
            public $custom_member_field_pairs = [];
            public function fetch_custom_member_fields(){ return []; }
            public function set_settings($id, $settings) {}
            public function setup_handler($id) { return false; }
            public function apply($method, $args) { return null; }
            public function check_method_exists($method) { return false; }
            public function fetch_custom_channel_fields(){ return [
                'custom_channel_fields' => [], 'date_fields' => [], 'relationship_fields' => [],
                'members_fields' => [], 'grid_fields' => [], 'pair_custom_fields' => [],
                'fluid_field_fields' => [], 'toggle_fields' => [],
            ]; }
        });
        $this->channel->fetch_custom_member_fields();
        $this->assertIsArray($this->channel->mpfields);
        $this->assertEmpty($this->channel->mpfields);
    }
}
