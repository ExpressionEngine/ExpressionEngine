<?php

require_once 'ChannelApiTestBase.php';

/**
 * Tests for Api_channel_entries::initialize() method
 */
class ApiChannelEntriesInitializeTest extends ChannelApiTestBase
{
    /**
     * Test that initialize resets cache arrays
     */
    public function testInitializeResetsCacheArrays()
    {
        // Set up some initial cache data
        $this->api->c_prefs = ['test' => 'value'];
        $this->api->_cache = ['some_key' => 'some_value'];

        // Call initialize using reflection
        $reflection = new ReflectionClass($this->api);
        $method = $reflection->getMethod('initialize');
        TestReflectionHelper::makeMethodAccessible($method);
        $method->invoke($this->api, []);

        // Verify arrays are reset
        $this->assertEquals([], $this->api->c_prefs);
        $this->assertEquals([], $this->api->_cache);
    }

    /**
     * Test that initialize preserves orig_author_id in cache
     */
    public function testInitializePreservesOrigAuthorId()
    {
        // Set up cache with orig_author_id
        $this->api->_cache = [
            'orig_author_id' => 123,
            'some_other_key' => 'some_value'
        ];

        // Call initialize using reflection
        $reflection = new ReflectionClass($this->api);
        $method = $reflection->getMethod('initialize');
        TestReflectionHelper::makeMethodAccessible($method);
        $method->invoke($this->api, []);

        // Verify orig_author_id is preserved but other keys are cleared
        $this->assertEquals(['orig_author_id' => 123], $this->api->_cache);
    }

    /**
     * Test initialize with empty parameters
     */
    public function testInitializeWithEmptyParams()
    {
        // Set up some initial state
        $this->api->c_prefs = ['existing' => 'data'];
        $this->api->_cache = ['existing' => 'cache'];

        // Call initialize using reflection
        $reflection = new ReflectionClass($this->api);
        $method = $reflection->getMethod('initialize');
        TestReflectionHelper::makeMethodAccessible($method);
        $method->invoke($this->api, []);

        // Verify reset occurred
        $this->assertEquals([], $this->api->c_prefs);
        $this->assertEquals([], $this->api->_cache);
    }

    /**
     * Test initialize with custom parameters
     */
    public function testInitializeWithCustomParams()
    {
        // Set up some initial state
        $this->api->c_prefs = ['existing' => 'data'];
        $this->api->_cache = ['existing' => 'cache'];

        // Call initialize using reflection
        $reflection = new ReflectionClass($this->api);
        $method = $reflection->getMethod('initialize');
        TestReflectionHelper::makeMethodAccessible($method);
        $params = ['entry_id' => 456, 'channel_id' => 789];
        $method->invoke($this->api, [$params]);

        // Verify reset occurred
        $this->assertEquals([], $this->api->c_prefs);
        $this->assertEquals([], $this->api->_cache);

        // Verify the API object has the parameters set (this would be done by parent class)
        // Note: Since we're not calling the real parent, we can't test this directly
        // But we can verify the method doesn't throw errors
        $this->assertTrue(true);
    }

    /**
     * Test initialize preserves orig_author_id when other cache data exists
     */
    public function testInitializePreservesOrigAuthorIdWithOtherData()
    {
        // Set up cache with multiple keys including orig_author_id
        $this->api->_cache = [
            'orig_author_id' => 999,
            'channel_id' => 1,
            'entry_id' => 123,
            'some_temp_data' => 'temp'
        ];

        // Call initialize using reflection
        $reflection = new ReflectionClass($this->api);
        $method = $reflection->getMethod('initialize');
        TestReflectionHelper::makeMethodAccessible($method);
        $method->invoke($this->api, []);

        // Only orig_author_id should remain
        $this->assertEquals(['orig_author_id' => 999], $this->api->_cache);
        $this->assertArrayNotHasKey('channel_id', $this->api->_cache);
        $this->assertArrayNotHasKey('entry_id', $this->api->_cache);
        $this->assertArrayNotHasKey('some_temp_data', $this->api->_cache);
    }

    /**
     * Test initialize when cache is initially empty
     */
    public function testInitializeWithEmptyCache()
    {
        // Ensure cache is empty initially
        $this->api->_cache = [];
        $this->api->c_prefs = [];

        // Call initialize using reflection
        $reflection = new ReflectionClass($this->api);
        $method = $reflection->getMethod('initialize');
        TestReflectionHelper::makeMethodAccessible($method);
        $method->invoke($this->api, []);

        // Verify arrays remain empty
        $this->assertEquals([], $this->api->c_prefs);
        $this->assertEquals([], $this->api->_cache);
    }

    /**
     * Test initialize resets c_prefs completely
     */
    public function testInitializeResetsChannelPreferences()
    {
        // Set up channel preferences
        $this->api->c_prefs = [
            'channel_url' => 'http://example.com',
            'deft_status' => 'open',
            'channel_title' => 'Test Channel',
            'enable_versioning' => 'y'
        ];

        // Call initialize using reflection
        $reflection = new ReflectionClass($this->api);
        $method = $reflection->getMethod('initialize');
        TestReflectionHelper::makeMethodAccessible($method);
        $method->invoke($this->api, []);

        // Verify all preferences are cleared
        $this->assertEquals([], $this->api->c_prefs);
        $this->assertArrayNotHasKey('channel_url', $this->api->c_prefs);
        $this->assertArrayNotHasKey('deft_status', $this->api->c_prefs);
        $this->assertArrayNotHasKey('channel_title', $this->api->c_prefs);
        $this->assertArrayNotHasKey('enable_versioning', $this->api->c_prefs);
    }

    /**
     * Test initialize with null parameters
     */
    public function testInitializeWithNullParams()
    {
        // Set up some initial state
        $this->api->c_prefs = ['test' => 'value'];
        $this->api->_cache = ['test' => 'cache'];

        // Call initialize using reflection
        $reflection = new ReflectionClass($this->api);
        $method = $reflection->getMethod('initialize');
        TestReflectionHelper::makeMethodAccessible($method);
        $method->invoke($this->api, [null]);

        // Verify reset occurred
        $this->assertEquals([], $this->api->c_prefs);
        $this->assertEquals([], $this->api->_cache);
    }
}
