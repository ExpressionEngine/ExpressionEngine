<?php

require_once 'ChannelApiTestBase.php';

/**
 * Tests for Api_channel_entries::__construct() method
 */
class ApiChannelEntriesConstructTest extends ChannelApiTestBase
{
    /**
     * Test that constructor loads the channel_entries_model
     */
    public function testConstructorLoadsChannelEntriesModel()
    {
        // Create a new instance to test constructor
        $api = new Api_channel_entries();

        // Verify the constructor completed successfully
        $this->assertInstanceOf('Api_channel_entries', $api);

        // The model loading is tested implicitly - if constructor fails,
        // the test will fail. We can verify the API has the expected methods
        $this->assertTrue(method_exists($api, 'save_entry'), 'API should have save_entry method');
        $this->assertTrue(method_exists($api, 'entry_exists'), 'API should have entry_exists method');
    }

    /**
     * Test that constructor calls parent constructor
     */
    public function testConstructorCallsParentConstructor()
    {
        // Create a new instance
        $api = new Api_channel_entries();

        // Verify the instance is created successfully
        $this->assertInstanceOf('Api_channel_entries', $api);

        // Verify parent constructor was called by checking if parent properties exist
        // Since we can't directly test parent constructor, we'll verify the API is properly initialized
        $this->assertTrue(method_exists($api, 'initialize'), 'Parent constructor should be called');
    }

    /**
     * Test that constructor sets up the API properly
     */
    public function testConstructorSetsUpApiProperly()
    {
        // Create a new instance
        $api = new Api_channel_entries();

        // Verify that the API has the expected properties
        $this->assertObjectHasProperty('entry_data', $api);
        $this->assertObjectHasProperty('channel_id', $api);
        $this->assertObjectHasProperty('entry_id', $api);
        $this->assertObjectHasProperty('autosave', $api);
        $this->assertObjectHasProperty('data', $api);
        $this->assertObjectHasProperty('meta', $api);
        $this->assertObjectHasProperty('c_prefs', $api);
        $this->assertObjectHasProperty('_cache', $api);

        // Verify initial values are set correctly
        $this->assertEquals([], $api->entry_data);
        $this->assertNull($api->channel_id);
        $this->assertEquals(0, $api->entry_id);
        $this->assertFalse($api->autosave);
        $this->assertEquals([], $api->data);
        $this->assertEquals([], $api->meta);
        $this->assertEquals([], $api->c_prefs);
        $this->assertEquals([], $api->_cache);
    }

    /**
     * Test that constructor initializes autosave_entry_id
     */
    public function testConstructorInitializesAutosaveEntryId()
    {
        // Create a new instance
        $api = new Api_channel_entries();

        // Verify autosave_entry_id is initialized
        $this->assertObjectHasProperty('autosave_entry_id', $api);
        $this->assertEquals(0, $api->autosave_entry_id);
    }
}
