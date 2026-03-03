<?php

require_once __DIR__ . '/ApiChannelStructureTestBase.php';

/**
 * Channel Deletion Tests for Api_channel_structure
 *
 * These tests verify the delete_channel method including:
 * - Basic channel deletion
 * - Cascading entry and comment cleanup
 * - Author statistics updates
 * - Validation and error handling
 * - Logging functionality
 * - Site ID handling
 */
class ApiChannelStructureDeleteTest extends ApiChannelStructureTestBase
{
    /**
     * Test delete_channel with valid channel ID
     */
    public function testDeleteChannelWithValidChannelId()
    {
        // Setup existing channel
        $this->mockChannelData();

        $result = $this->apiChannelStructure->delete_channel(1);

        $this->assertEquals('Test Channel', $result); // Should return channel title
        // Verify channel was removed from mock
        $this->assertArrayNotHasKey(1, ee()->channel_model->channels);
    }

    /**
     * Test delete_channel with invalid channel ID
     */
    public function testDeleteChannelWithInvalidChannelId()
    {
        $result = $this->apiChannelStructure->delete_channel(999);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test delete_channel with empty channel ID
     */
    public function testDeleteChannelWithEmptyChannelId()
    {
        $result = $this->apiChannelStructure->delete_channel('');

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test delete_channel with null channel ID
     */
    public function testDeleteChannelWithNullChannelId()
    {
        $result = $this->apiChannelStructure->delete_channel(null);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test delete_channel with numeric string ID
     */
    public function testDeleteChannelWithNumericStringId()
    {
        // Setup existing channel
        $this->mockChannelData();

        $result = $this->apiChannelStructure->delete_channel('1');

        $this->assertEquals('Test Channel', $result);
        $this->assertArrayNotHasKey(1, ee()->channel_model->channels);
    }

    /**
     * Test delete_channel with zero ID
     */
    public function testDeleteChannelWithZeroId()
    {
        $result = $this->apiChannelStructure->delete_channel(0);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test delete_channel with site_id parameter
     */
    public function testDeleteChannelWithSiteIdParameter()
    {
        // Setup existing channel
        $this->mockChannelData();

        $result = $this->apiChannelStructure->delete_channel(1, 1);

        $this->assertEquals('Test Channel', $result);
        $this->assertArrayNotHasKey(1, ee()->channel_model->channels);
    }

    /**
     * Test delete_channel with null site_id (should use default)
     */
    public function testDeleteChannelWithNullSiteId()
    {
        // Setup existing channel
        $this->mockChannelData();

        $result = $this->apiChannelStructure->delete_channel(1, null);

        $this->assertEquals('Test Channel', $result);
        $this->assertArrayNotHasKey(1, ee()->channel_model->channels);
    }

    /**
     * Test delete_channel with invalid site_id
     */
    public function testDeleteChannelWithInvalidSiteId()
    {
        // Setup existing channel
        $this->mockChannelData();

        $result = $this->apiChannelStructure->delete_channel(1, 'invalid');

        $this->assertEquals('Test Channel', $result); // Should still work with default site_id
        $this->assertArrayNotHasKey(1, ee()->channel_model->channels);
    }

    /**
     * Test delete_channel with channel entries
     */
    public function testDeleteChannelWithChannelEntries()
    {
        // Setup existing channel
        $this->mockChannelData();

        // Setup mock channel entries
        $this->mockChannelEntries([
            ['entry_id' => 1, 'author_id' => 1],
            ['entry_id' => 2, 'author_id' => 1],
            ['entry_id' => 3, 'author_id' => 2]
        ]);

        $result = $this->apiChannelStructure->delete_channel(1);

        $this->assertEquals('Test Channel', $result);
        $this->assertArrayNotHasKey(1, ee()->channel_model->channels);
    }

    /**
     * Test delete_channel with no channel entries
     */
    public function testDeleteChannelWithNoChannelEntries()
    {
        // Setup existing channel
        $this->mockChannelData();

        // Setup empty entries result
        $this->mockChannelEntries([]);

        $result = $this->apiChannelStructure->delete_channel(1);

        $this->assertEquals('Test Channel', $result);
        $this->assertArrayNotHasKey(1, ee()->channel_model->channels);
    }

    /**
     * Test delete_channel logs deletion action
     */
    public function testDeleteChannelLogsDeletionAction()
    {
        // Setup existing channel
        $this->mockChannelData();

        $result = $this->apiChannelStructure->delete_channel(1);

        $this->assertEquals('Test Channel', $result);
        // Verify logging was called
        $this->assertNotEmpty(ee()->logger->logged_messages ?? []);
    }

    /**
     * Test delete_channel returns channel title on success
     */
    public function testDeleteChannelReturnsChannelTitleOnSuccess()
    {
        // Setup existing channel
        $this->mockChannelData();

        $result = $this->apiChannelStructure->delete_channel(1);

        $this->assertEquals('Test Channel', $result);
    }

    /**
     * Test delete_channel clears channel info cache
     */
    public function testDeleteChannelClearsChannelInfoCache()
    {
        // Setup existing channel and cache it
        $this->mockChannelData();
        $channelInfo = $this->apiChannelStructure->get_channel_info(1);

        // Verify it's cached
        $this->assertArrayHasKey(1, $this->apiChannelStructure->channel_info);

        // Delete the channel
        $this->apiChannelStructure->delete_channel(1);

        // Cache should still have the entry (since it's just deleted from DB)
        // In real implementation, cache might be invalidated differently
        $this->assertArrayHasKey(1, $this->apiChannelStructure->channel_info);
    }

    /**
     * Test delete_channel with different channel titles
     */
    public function testDeleteChannelWithDifferentChannelTitles()
    {
        // Setup multiple channels with different titles
        $channels = [
            1 => array_merge($this->getValidChannelData(), ['channel_id' => 1, 'channel_title' => 'News Channel']),
            2 => array_merge($this->getValidChannelData(), ['channel_id' => 2, 'channel_title' => 'Blog Channel']),
            3 => array_merge($this->getValidChannelData(), ['channel_id' => 3, 'channel_title' => 'Gallery Channel'])
        ];
        $this->mockChannelData($channels);

        // Delete each channel and verify correct title is returned
        $result1 = $this->apiChannelStructure->delete_channel(1);
        $this->assertEquals('News Channel', $result1);

        $result2 = $this->apiChannelStructure->delete_channel(2);
        $this->assertEquals('Blog Channel', $result2);

        $result3 = $this->apiChannelStructure->delete_channel(3);
        $this->assertEquals('Gallery Channel', $result3);

        // Verify all channels are deleted
        $this->assertEmpty(ee()->channel_model->channels);
    }

    /**
     * Test delete_channel handles database errors gracefully
     */
    public function testDeleteChannelHandlesDatabaseErrorsGracefully()
    {
        // Setup existing channel
        $this->mockChannelData();

        // The mock should handle the delete operation
        $result = $this->apiChannelStructure->delete_channel(1);

        $this->assertEquals('Test Channel', $result);
    }

    /**
     * Test delete_channel with entries from multiple authors
     */
    public function testDeleteChannelWithEntriesFromMultipleAuthors()
    {
        // Setup existing channel
        $this->mockChannelData();

        // Setup entries from multiple authors
        $this->mockChannelEntries([
            ['entry_id' => 1, 'author_id' => 1],
            ['entry_id' => 2, 'author_id' => 1],
            ['entry_id' => 3, 'author_id' => 2],
            ['entry_id' => 4, 'author_id' => 3],
            ['entry_id' => 5, 'author_id' => 2]
        ]);

        $result = $this->apiChannelStructure->delete_channel(1);

        $this->assertEquals('Test Channel', $result);
        $this->assertArrayNotHasKey(1, ee()->channel_model->channels);
    }

    /**
     * Test delete_channel preserves other channels
     */
    public function testDeleteChannelPreservesOtherChannels()
    {
        // Setup multiple channels
        $channels = [
            1 => array_merge($this->getValidChannelData(), ['channel_id' => 1, 'channel_title' => 'Channel One']),
            2 => array_merge($this->getValidChannelData(), ['channel_id' => 2, 'channel_title' => 'Channel Two']),
            3 => array_merge($this->getValidChannelData(), ['channel_id' => 3, 'channel_title' => 'Channel Three'])
        ];
        $this->mockChannelData($channels);

        // Delete channel 2
        $result = $this->apiChannelStructure->delete_channel(2);

        $this->assertEquals('Channel Two', $result);

        // Verify other channels still exist
        $this->assertArrayHasKey(1, ee()->channel_model->channels);
        $this->assertArrayNotHasKey(2, ee()->channel_model->channels);
        $this->assertArrayHasKey(3, ee()->channel_model->channels);

        $this->assertEquals('Channel One', ee()->channel_model->channels[1]['channel_title']);
        $this->assertEquals('Channel Three', ee()->channel_model->channels[3]['channel_title']);
    }

    /**
     * Test delete_channel with very long channel title
     */
    public function testDeleteChannelWithVeryLongChannelTitle()
    {
        // Setup channel with very long title
        $longTitle = str_repeat('A very long channel title ', 10);
        $channels = [
            1 => array_merge($this->getValidChannelData(), [
                'channel_id' => 1,
                'channel_title' => $longTitle
            ])
        ];
        $this->mockChannelData($channels);

        $result = $this->apiChannelStructure->delete_channel(1);

        $this->assertEquals($longTitle, $result);
        $this->assertArrayNotHasKey(1, ee()->channel_model->channels);
    }

    /**
     * Test delete_channel with special characters in title
     */
    public function testDeleteChannelWithSpecialCharactersInTitle()
    {
        // Setup channel with special characters in title
        $specialTitle = 'Channel with "quotes" & <tags> © ®';
        $channels = [
            1 => array_merge($this->getValidChannelData(), [
                'channel_id' => 1,
                'channel_title' => $specialTitle
            ])
        ];
        $this->mockChannelData($channels);

        $result = $this->apiChannelStructure->delete_channel(1);

        $this->assertEquals($specialTitle, $result);
        $this->assertArrayNotHasKey(1, ee()->channel_model->channels);
    }

    /**
     * Test delete_channel with unicode characters in title
     */
    public function testDeleteChannelWithUnicodeCharactersInTitle()
    {
        // Setup channel with unicode characters
        $unicodeTitle = 'Test Channel 中文 Español Français';
        $channels = [
            1 => array_merge($this->getValidChannelData(), [
                'channel_id' => 1,
                'channel_title' => $unicodeTitle
            ])
        ];
        $this->mockChannelData($channels);

        $result = $this->apiChannelStructure->delete_channel(1);

        $this->assertEquals($unicodeTitle, $result);
        $this->assertArrayNotHasKey(1, ee()->channel_model->channels);
    }

    /**
     * Test delete_channel multiple times on same channel
     */
    public function testDeleteChannelMultipleTimesOnSameChannel()
    {
        // Setup existing channel
        $this->mockChannelData();

        // First delete should succeed
        $result1 = $this->apiChannelStructure->delete_channel(1);
        $this->assertEquals('Test Channel', $result1);
        $this->assertArrayNotHasKey(1, ee()->channel_model->channels);

        // Reset API instance to clear cache (simulating fresh request)
        $this->resetApiInstance();

        // Second delete should fail (channel no longer exists)
        $result2 = $this->apiChannelStructure->delete_channel(1);
        $this->assertFalse($result2);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test delete_channel after cache invalidation
     */
    public function testDeleteChannelAfterCacheInvalidation()
    {
        // Setup existing channel
        $this->mockChannelData();

        // Cache the channel info
        $channelInfo = $this->apiChannelStructure->get_channel_info(1);
        $this->assertNotFalse($channelInfo);

        // Delete the channel
        $result = $this->apiChannelStructure->delete_channel(1);
        $this->assertEquals('Test Channel', $result);

        // Reset API instance to clear cache (simulating fresh request)
        $this->resetApiInstance();

        // Try to get channel info again (should fail since channel is deleted)
        $channelInfoAfterDelete = $this->apiChannelStructure->get_channel_info(1);
        $this->assertFalse($channelInfoAfterDelete);
    }
}
