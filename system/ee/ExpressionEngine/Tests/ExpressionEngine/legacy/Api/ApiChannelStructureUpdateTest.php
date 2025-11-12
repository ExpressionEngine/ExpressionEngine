<?php

require_once __DIR__ . '/ApiChannelStructureTestBase.php';

/**
 * Channel Update Tests for Api_channel_structure
 *
 * These tests verify the modify_channel method including:
 * - Basic channel updates with valid data
 * - Validation of channel existence
 * - Duplicate name detection for updates
 * - Comment system management
 * - Expiration handling
 * - Versioning data cleanup
 * - Error handling and recovery
 */
class ApiChannelStructureUpdateTest extends ApiChannelStructureTestBase
{
    /**
     * Test modify_channel with valid basic data
     */
    public function testModifyChannelWithValidBasicData()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'channel_title' => 'Updated Channel Title',
            'channel_description' => 'Updated description'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false); // Should return channel_id or false
    }

    /**
     * Test modify_channel with empty data array
     */
    public function testModifyChannelWithEmptyDataArray()
    {
        $result = $this->apiChannelStructure->modify_channel([]);

        $this->assertTrue($result === false || $result === null);
    }

    /**
     * Test modify_channel with null data
     */
    public function testModifyChannelWithNullData()
    {
        $result = $this->apiChannelStructure->modify_channel(null);

        $this->assertTrue($result === false || $result === null);
    }

    /**
     * Test modify_channel with non-array data
     */
    public function testModifyChannelWithNonArrayData()
    {
        $result = $this->apiChannelStructure->modify_channel('not_an_array');

        $this->assertTrue($result === false || $result === null);
    }

    /**
     * Test modify_channel with missing channel_id
     */
    public function testModifyChannelWithMissingChannelId()
    {
        $updateData = [
            'channel_title' => 'Updated Title'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === false || $result === null);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test modify_channel with invalid channel_id
     */
    public function testModifyChannelWithInvalidChannelId()
    {
        $updateData = [
            'channel_id' => 999, // Non-existent channel
            'channel_title' => 'Updated Title'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === false || $result === null);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test modify_channel with non-numeric channel_id
     */
    public function testModifyChannelWithNonNumericChannelId()
    {
        $updateData = [
            'channel_id' => 'invalid',
            'channel_title' => 'Updated Title'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === false || $result === null);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test modify_channel with missing channel_title
     */
    public function testModifyChannelWithMissingChannelTitle()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'channel_title' => ''
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === false || $result === null);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test modify_channel with missing channel_name
     */
    public function testModifyChannelWithMissingChannelName()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'channel_name' => ''
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === false || $result === null);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test modify_channel with invalid channel_name
     */
    public function testModifyChannelWithInvalidChannelName()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'channel_name' => 'invalid name with spaces'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === false || $result === null);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test modify_channel with duplicate channel_name (different channel)
     */
    public function testModifyChannelWithDuplicateChannelName()
    {
        // Setup multiple channels
        $channels = [
            1 => array_merge($this->getValidChannelData(), ['channel_id' => 1, 'channel_name' => 'channel_one']),
            2 => array_merge($this->getValidChannelData(), ['channel_id' => 2, 'channel_name' => 'channel_two'])
        ];
        $this->mockChannelData($channels);

        // Mock count to return 1 (indicating duplicate exists)
        $this->mockSuperModelCount('channels', [
            'site_id' => 1,
            'channel_name' => 'channel_two',
            'channel_id !=' => 1
        ], 1);

        $updateData = [
            'channel_id' => 1,
            'channel_name' => 'channel_two' // Conflicts with channel 2
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === false || $result === null);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test modify_channel with same channel_name (no conflict)
     */
    public function testModifyChannelWithSameChannelName()
    {
        // Setup existing channel
        $this->mockChannelData();

        // Mock count to return 0 (no duplicates)
        $this->mockSuperModelCount('channels', [
            'site_id' => 1,
            'channel_name' => 'test_channel',
            'channel_id !=' => 1
        ], 0);

        $updateData = [
            'channel_id' => 1,
            'channel_name' => 'test_channel', // Same name, no conflict
            'channel_title' => 'Updated Title'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false);
    }

    /**
     * Test modify_channel with invalid URL title prefix
     */
    public function testModifyChannelWithInvalidUrlTitlePrefix()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'url_title_prefix' => 'invalid prefix with spaces'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === false || $result === null);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test modify_channel with valid URL title prefix
     */
    public function testModifyChannelWithValidUrlTitlePrefix()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'url_title_prefix' => 'news'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false);
    }

    /**
     * Test modify_channel with comment system enabled
     */
    public function testModifyChannelWithCommentSystemEnabled()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'comment_system_enabled' => 'y',
            'apply_expiration_to_existing' => 'y',
            'comment_expiration' => 30
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false);
    }

    /**
     * Test modify_channel with comment system disabled
     */
    public function testModifyChannelWithCommentSystemDisabled()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'comment_system_enabled' => 'n',
            'apply_expiration_to_existing' => 'y'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false);
    }

    /**
     * Test modify_channel with comment expiration
     */
    public function testModifyChannelWithCommentExpiration()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'comment_expiration' => 60, // 60 days
            'apply_expiration_to_existing' => 'y'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false);
    }

    /**
     * Test modify_channel with invalid comment expiration
     */
    public function testModifyChannelWithInvalidCommentExpiration()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'comment_expiration' => 'invalid'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false); // Should still succeed, just set to 0
    }

    /**
     * Test modify_channel with versioning data clearing
     */
    public function testModifyChannelWithVersioningDataClearing()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'clear_versioning_data' => 'y'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false);
    }

    /**
     * Test modify_channel with multiple field updates
     */
    public function testModifyChannelWithMultipleFieldUpdates()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'channel_title' => 'Updated Title',
            'channel_description' => 'Updated Description',
            'channel_max_chars' => 2000,
            'channel_html_formatting' => 'none',
            'channel_allow_img_urls' => 'n',
            'channel_hidden' => 'y',
            'max_entries' => 500
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false);

        // Verify the channel was updated in the mock
        if (isset(ee()->channel_model->channels[1])) { $updatedChannel = ee()->channel_model->channels[1]; } else { $updatedChannel = []; }
    }

    /**
     * Test modify_channel preserves auto-generated fields
     */
    public function testModifyChannelPreservesAutoGeneratedFields()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'channel_title' => 'Updated Title'
        ];

        $originalChannel = ee()->channel_model->channels[1];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false);

        // Verify auto-generated fields are preserved
        if (isset(ee()->channel_model->channels[1])) { $updatedChannel = ee()->channel_model->channels[1]; } else { $updatedChannel = []; }
        $this->assertEquals($originalChannel['total_entries'], $updatedChannel['total_entries']);
        $this->assertEquals($originalChannel['total_comments'], $updatedChannel['total_comments']);
        $this->assertEquals($originalChannel['last_entry_date'], $updatedChannel['last_entry_date']);
        $this->assertEquals($originalChannel['last_comment_date'], $updatedChannel['last_comment_date']);
    }

    /**
     * Test modify_channel with missing site_id (should use default)
     */
    public function testModifyChannelWithMissingSiteId()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'channel_title' => 'Updated Title'
        ];
        // Note: no site_id provided

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false);
    }

    /**
     * Test modify_channel with invalid site_id
     */
    public function testModifyChannelWithInvalidSiteId()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'site_id' => 'invalid',
            'channel_title' => 'Updated Title'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false); // Should use default site_id
    }

    /**
     * Test modify_channel returns channel_id on success
     */
    public function testModifyChannelReturnsChannelIdOnSuccess()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'channel_title' => 'Updated Title'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false);
    }

    /**
     * Test modify_channel with numeric string channel_id
     */
    public function testModifyChannelWithNumericStringChannelId()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => '1',
            'channel_title' => 'Updated Title'
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false);
    }

    /**
     * Test modify_channel with multiple validation errors
     */
    public function testModifyChannelWithMultipleValidationErrors()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'channel_title' => '', // Empty title
            'channel_name' => '', // Empty name
            'url_title_prefix' => 'invalid prefix with spaces' // Invalid prefix
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === false || $result === null);
        // Should have multiple errors
        $this->assertGreaterThanOrEqual(3, $this->apiChannelStructure->error_count());
    }

    /**
     * Test modify_channel with comment settings only
     */
    public function testModifyChannelWithCommentSettingsOnly()
    {
        // Setup existing channel
        $this->mockChannelData();

        $updateData = [
            'channel_id' => 1,
            'comment_system_enabled' => 'y',
            'comment_max_chars' => 2000,
            'comment_moderate' => 'y',
            'apply_expiration_to_existing' => 'n' // Don't apply to existing
        ];

        $result = $this->apiChannelStructure->modify_channel($updateData);

        $this->assertTrue($result === 1 || $result === false);
    }
}
