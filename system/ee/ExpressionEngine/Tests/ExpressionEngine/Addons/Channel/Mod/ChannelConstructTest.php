<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelConstructTest extends ChannelTestBase
{
    public function testConstructorInitializesBasicProperties()
    {
        // Create a new Channel instance with constructor
        $channel = new Channel();

        // Test that basic properties are initialized
        $this->assertEquals('100', $channel->limit);
        $this->assertIsArray($channel->cfields);
        $this->assertIsArray($channel->mfields);
        $this->assertIsArray($channel->categories);
        $this->assertIsArray($channel->channel_name);
        $this->assertIsArray($channel->channels_array);
        $this->assertEquals('', $channel->return_data);
    }

    public function testConstructorSetsDynamicParameters()
    {
        $channel = new Channel();

        // Check that dynamic parameters are set using reflection
        $reflection = new ReflectionClass($channel);
        $property = $reflection->getProperty('_dynamic_parameters');
        TestReflectionHelper::makePropertyAccessible($property);

        $expectedParams = array('channel', 'entry_id', 'category', 'orderby',
            'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from',
            'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month',
            'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'primary_role_id', 'cat_limit',
            'month_limit', 'offset', 'author_id', 'url_title');

        $this->assertEquals($expectedParams, $property->getValue($channel));
    }

    public function testConstructorInitializesPagination()
    {
        $channel = new Channel();

        // Check that pagination is created
        $this->assertObjectHasProperty('pagination', $channel);
        $this->assertNotNull($channel->pagination);
    }

    public function testConstructorSetsQueryString()
    {
        $channel = new Channel();

        // Check that query string is set from URI
        $this->assertEquals(ee()->uri->page_query_string ?: ee()->uri->query_string, $channel->query_string);
    }
}
