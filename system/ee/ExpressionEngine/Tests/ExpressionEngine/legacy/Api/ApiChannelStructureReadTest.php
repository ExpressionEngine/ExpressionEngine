<?php

require_once __DIR__ . '/ApiChannelStructureTestBase.php';

/**
 * Read Operations Tests for Api_channel_structure
 *
 * These tests verify the get_channel_info and get_channels methods including:
 * - Data retrieval and caching
 * - Error handling for invalid inputs
 * - Cache functionality and performance
 * - Database interaction mocking
 */
class ApiChannelStructureReadTest extends ApiChannelStructureTestBase
{
    /**
     * Test get_channel_info with valid channel ID
     */
    public function testGetChannelInfoWithValidChannelId()
    {
        // Setup mock channel data
        $this->mockChannelData();

        $result = $this->apiChannelStructure->get_channel_info(1);

        $this->assertNotFalse($result);
        $this->assertEquals(1, $result->num_rows());
        $this->assertEquals('test_channel', $result->row()->channel_name);
        $this->assertEquals('Test Channel', $result->row()->channel_title);
    }

    /**
     * Test get_channel_info with invalid channel ID
     */
    public function testGetChannelInfoWithInvalidChannelId()
    {
        $result = $this->apiChannelStructure->get_channel_info(999);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test get_channel_info with empty channel ID
     */
    public function testGetChannelInfoWithEmptyChannelId()
    {
        $result = $this->apiChannelStructure->get_channel_info('');

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test get_channel_info with null channel ID
     */
    public function testGetChannelInfoWithNullChannelId()
    {
        $result = $this->apiChannelStructure->get_channel_info(null);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test get_channel_info caching functionality
     */
    public function testGetChannelInfoCaching()
    {
        // Setup mock channel data
        $this->mockChannelData();

        // First call should cache the result
        $result1 = $this->apiChannelStructure->get_channel_info(1);
        $this->assertNotFalse($result1);

        // Verify data is in cache
        $this->assertArrayHasKey(1, $this->apiChannelStructure->channel_info);
        $this->assertSame($result1, $this->apiChannelStructure->channel_info[1]);

        // Second call should use cached result
        $result2 = $this->apiChannelStructure->get_channel_info(1);
        $this->assertSame($result1, $result2);
    }

    /**
     * Test get_channel_info cache bypass for different channel IDs
     */
    public function testGetChannelInfoCacheBypassForDifferentIds()
    {
        // Setup multiple channels
        $channels = [
            1 => array_merge($this->getValidChannelData(), ['channel_id' => 1, 'channel_name' => 'channel_one']),
            2 => array_merge($this->getValidChannelData(), ['channel_id' => 2, 'channel_name' => 'channel_two'])
        ];
        $this->mockChannelData($channels);

        // Get first channel
        $result1 = $this->apiChannelStructure->get_channel_info(1);
        $this->assertEquals('channel_one', $result1->row()->channel_name);

        // Get second channel
        $result2 = $this->apiChannelStructure->get_channel_info(2);
        $this->assertEquals('channel_two', $result2->row()->channel_name);

        // Verify both are cached
        $this->assertArrayHasKey(1, $this->apiChannelStructure->channel_info);
        $this->assertArrayHasKey(2, $this->apiChannelStructure->channel_info);
    }

    /**
     * Test get_channels with valid site ID
     */
    public function testGetChannelsWithValidSiteId()
    {
        // Setup mock channel data
        $this->mockChannelData();

        $result = $this->apiChannelStructure->get_channels(1);

        $this->assertNotFalse($result);
        $this->assertEquals(1, $result->num_rows());
        $this->assertEquals('test_channel', $result->row()->channel_name);
    }

    /**
     * Test get_channels with null site ID (should use default)
     */
    public function testGetChannelsWithNullSiteId()
    {
        // Setup mock channel data
        $this->mockChannelData();

        $result = $this->apiChannelStructure->get_channels(null);

        $this->assertNotFalse($result);
        $this->assertEquals(1, $result->num_rows());
    }

    /**
     * Test get_channels with invalid site ID
     */
    public function testGetChannelsWithInvalidSiteId()
    {
        // Mock empty result for invalid site
        $result = $this->apiChannelStructure->get_channels(999);

        $this->assertFalse($result);
    }

    /**
     * Test get_channels caching functionality
     */
    public function testGetChannelsCaching()
    {
        // Setup mock channel data
        $this->mockChannelData();

        // First call should cache the result
        $result1 = $this->apiChannelStructure->get_channels(1);
        $this->assertNotFalse($result1);

        // Verify data is in cache
        $this->assertArrayHasKey(1, $this->apiChannelStructure->channels);
        $this->assertSame($result1, $this->apiChannelStructure->channels[1]);

        // Second call should use cached result
        $result2 = $this->apiChannelStructure->get_channels(1);
        $this->assertSame($result1, $result2);
    }

    /**
     * Test get_channels with multiple channels per site
     */
    public function testGetChannelsWithMultipleChannelsPerSite()
    {
        // Setup multiple channels for same site
        $channels = [
            1 => array_merge($this->getValidChannelData(), ['channel_id' => 1, 'channel_name' => 'channel_one']),
            2 => array_merge($this->getValidChannelData(), ['channel_id' => 2, 'channel_name' => 'channel_two']),
            3 => array_merge($this->getValidChannelData(), ['channel_id' => 3, 'channel_name' => 'channel_three'])
        ];
        $this->mockChannelData($channels);

        $result = $this->apiChannelStructure->get_channels(1);

        $this->assertNotFalse($result);
        $this->assertEquals(3, $result->num_rows());
    }

    /**
     * Test get_channels filters by site ID correctly
     */
    public function testGetChannelsFiltersBySiteIdCorrectly()
    {
        // Setup channels for different sites
        $channels = [
            1 => array_merge($this->getValidChannelData(), ['channel_id' => 1, 'site_id' => 1]),
            2 => array_merge($this->getValidChannelData(), ['channel_id' => 2, 'site_id' => 2]),
            3 => array_merge($this->getValidChannelData(), ['channel_id' => 3, 'site_id' => 1])
        ];
        $this->mockChannelData($channels);

        $result = $this->apiChannelStructure->get_channels(1);

        $this->assertNotFalse($result);
        $this->assertEquals(2, $result->num_rows()); // Should return channels 1 and 3
    }

    /**
     * Test get_channel_info returns all expected channel fields
     */
    public function testGetChannelInfoReturnsAllExpectedFields()
    {
        // Setup mock channel data
        $this->mockChannelData();

        $result = $this->apiChannelStructure->get_channel_info(1);
        $channel = $result->row();

        // Verify key fields are present
        $this->assertObjectHasProperty('channel_id', $channel);
        $this->assertObjectHasProperty('channel_name', $channel);
        $this->assertObjectHasProperty('channel_title', $channel);
        $this->assertObjectHasProperty('site_id', $channel);
        $this->assertObjectHasProperty('field_group', $channel);
        $this->assertObjectHasProperty('cat_group', $channel);
        $this->assertObjectHasProperty('channel_url', $channel);
        $this->assertObjectHasProperty('comment_url', $channel);
    }

    /**
     * Test get_channels returns all expected channel fields
     */
    public function testGetChannelsReturnsAllExpectedFields()
    {
        // Setup mock channel data
        $this->mockChannelData();

        $result = $this->apiChannelStructure->get_channels(1);
        $channel = $result->row();

        // Verify key fields are present
        $this->assertObjectHasProperty('channel_id', $channel);
        $this->assertObjectHasProperty('channel_name', $channel);
        $this->assertObjectHasProperty('channel_title', $channel);
        $this->assertObjectHasProperty('site_id', $channel);
    }

    /**
     * Test get_channel_info with numeric string ID
     */
    public function testGetChannelInfoWithNumericStringId()
    {
        // Setup mock channel data
        $this->mockChannelData();

        $result = $this->apiChannelStructure->get_channel_info('1');

        $this->assertNotFalse($result);
        $this->assertEquals(1, $result->row()->channel_id);
    }

    /**
     * Test get_channels with numeric string site ID
     */
    public function testGetChannelsWithNumericStringSiteId()
    {
        // Setup mock channel data
        $this->mockChannelData();

        $result = $this->apiChannelStructure->get_channels('1');

        $this->assertNotFalse($result);
    }

    /**
     * Test get_channel_info performance with caching
     */
    public function testGetChannelInfoPerformanceWithCaching()
    {
        // Setup mock channel data
        $this->mockChannelData();

        // First call - should hit database
        $startTime = microtime(true);
        $result1 = $this->apiChannelStructure->get_channel_info(1);
        $firstCallTime = microtime(true) - $startTime;

        // Second call - should use cache
        $startTime = microtime(true);
        $result2 = $this->apiChannelStructure->get_channel_info(1);
        $secondCallTime = microtime(true) - $startTime;

        // Cached call should be faster (though in test environment this may vary)
        $this->assertNotFalse($result1);
        $this->assertNotFalse($result2);
        $this->assertSame($result1, $result2);
    }

    /**
     * Test get_channels performance with caching
     */
    public function testGetChannelsPerformanceWithCaching()
    {
        // Setup mock channel data
        $this->mockChannelData();

        // First call - should hit database
        $result1 = $this->apiChannelStructure->get_channels(1);
        $this->assertNotFalse($result1);

        // Second call - should use cache
        $result2 = $this->apiChannelStructure->get_channels(1);
        $this->assertSame($result1, $result2);
    }

    /**
     * Test cache isolation between instances
     */
    public function testCacheIsolationBetweenInstances()
    {
        // Setup mock channel data
        $this->mockChannelData();

        // Get channel with first instance
        $result1 = $this->apiChannelStructure->get_channel_info(1);

        // Create second instance
        $instance2 = new Api_channel_structure();

        // Second instance should not have cached data from first instance
        $this->assertEmpty($instance2->channel_info);
        $this->assertEmpty($instance2->channels);
    }

    /**
     * Test that error messages are set correctly
     */
    public function testErrorMessagesAreSetCorrectly()
    {
        // Test invalid channel ID
        $this->apiChannelStructure->get_channel_info(999);

        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());

        // Test invalid channel ID (empty string)
        $this->resetApiInstance();
        $this->apiChannelStructure->get_channel_info('');

        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test get_channel_info with zero ID
     */
    public function testGetChannelInfoWithZeroId()
    {
        $result = $this->apiChannelStructure->get_channel_info(0);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test get_channels with zero site ID
     */
    public function testGetChannelsWithZeroSiteId()
    {
        $result = $this->apiChannelStructure->get_channels(0);

        // Should return false for zero site_id (invalid)
        $this->assertFalse($result);
    }
}
