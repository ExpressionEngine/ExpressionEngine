<?php

require_once __DIR__ . '/ApiChannelStructureTestBase.php';

/**
 * Channel Creation Tests for Api_channel_structure
 *
 * These tests verify the create_channel method including:
 * - Basic channel creation with valid data
 * - Extensive validation of all input parameters
 * - Template group creation integration
 * - Category group validation
 * - Duplicate name detection
 * - URL safety validation
 * - Error handling and recovery
 */
class ApiChannelStructureCreateTest extends ApiChannelStructureTestBase
{
    /**
     * Test create_channel with valid basic data
     */
    public function testCreateChannelWithValidBasicData()
    {
        // Use minimal data that we know works from debug test
        $channelData = [
            'channel_name' => 'test_basic',
            'channel_title' => 'Test Basic Channel'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Debug test: Check what happens with minimal valid data
     */
    public function testDebugChannelCreation()
    {
        // Test with minimal required data
        $minimalData = [
            'channel_name' => 'test',
            'channel_title' => 'Test Channel'
        ];

        $result = $this->apiChannelStructure->create_channel($minimalData);

        if ($result === false) {
            $this->fail('Even minimal channel creation failed. Error count: ' . $this->apiChannelStructure->error_count());
        }

        $this->assertIsInt($result);
    }

    /**
     * Test create_channel with empty data array
     */
    public function testCreateChannelWithEmptyDataArray()
    {
        $result = $this->apiChannelStructure->create_channel([]);

        $this->assertFalse($result);
    }

    /**
     * Test create_channel with null data
     */
    public function testCreateChannelWithNullData()
    {
        $result = $this->apiChannelStructure->create_channel(null);

        $this->assertFalse($result);
    }

    /**
     * Test create_channel with non-array data
     */
    public function testCreateChannelWithNonArrayData()
    {
        $result = $this->apiChannelStructure->create_channel('not_an_array');

        $this->assertFalse($result);
    }

    /**
     * Test create_channel with missing channel_title
     */
    public function testCreateChannelWithMissingChannelTitle()
    {
        $channelData = [
            'channel_name' => 'test_no_title',
            // channel_title is intentionally missing
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test create_channel with empty channel_title
     */
    public function testCreateChannelWithEmptyChannelTitle()
    {
        $channelData = [
            'channel_name' => 'test_empty_title',
            'channel_title' => '' // Empty title
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test create_channel with missing channel_name
     */
    public function testCreateChannelWithMissingChannelName()
    {
        $channelData = [
            'channel_title' => 'Test Missing Name Channel'
            // channel_name is intentionally missing
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test create_channel with empty channel_name
     */
    public function testCreateChannelWithEmptyChannelName()
    {
        $channelData = [
            'channel_name' => '', // Empty name
            'channel_title' => 'Test Empty Name Channel'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test create_channel with invalid channel_name (contains spaces)
     */
    public function testCreateChannelWithInvalidChannelName()
    {
        $channelData = [
            'channel_name' => 'invalid name with spaces',
            'channel_title' => 'Test Invalid Name Channel'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test create_channel with duplicate channel_name
     */
    public function testCreateChannelWithDuplicateChannelName()
    {
        // Mock existing channel with same name
        $this->mockSuperModelCount('channels', [
            'site_id' => 1,
            'channel_name' => 'duplicate_channel'
        ], 1);

        $channelData = [
            'channel_name' => 'duplicate_channel', // This name already exists
            'channel_title' => 'Duplicate Channel Test'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test create_channel with valid URL title prefix
     */
    public function testCreateChannelWithValidUrlTitlePrefix()
    {
        $channelData = [
            'channel_name' => 'test_prefix',
            'channel_title' => 'Test Prefix Channel',
            'url_title_prefix' => 'news'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test create_channel with invalid URL title prefix
     */
    public function testCreateChannelWithInvalidUrlTitlePrefix()
    {
        $channelData = [
            'channel_name' => 'invalid_prefix_channel',
            'channel_title' => 'Invalid Prefix Channel',
            'url_title_prefix' => 'invalid prefix with spaces'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test create_channel with missing site_id (should use default)
     */
    public function testCreateChannelWithMissingSiteId()
    {
        $channelData = [
            'channel_name' => 'test_no_site',
            'channel_title' => 'Test No Site Channel'
            // site_id is intentionally missing
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test create_channel with invalid site_id
     */
    public function testCreateChannelWithInvalidSiteId()
    {
        $channelData = [
            'channel_name' => 'test_invalid_site',
            'channel_title' => 'Test Invalid Site Channel',
            'site_id' => 'invalid'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result); // Should use default site_id
    }

    /**
     * Test create_channel with category groups
     */
    public function testCreateChannelWithCategoryGroups()
    {
        $channelData = [
            'channel_name' => 'test_cat_groups',
            'channel_title' => 'Test Category Groups Channel',
            'cat_group' => [1, 2, 3] // Pass as array as expected by API
        ];

        // Mock category groups as valid
        $this->mockSuperModelCount('category_groups', ['group_id' => [1, 2, 3]], 3);

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test create_channel with invalid category groups
     */
    public function testCreateChannelWithInvalidCategoryGroups()
    {
        $channelData = [
            'channel_name' => 'invalid_cat_channel',
            'channel_title' => 'Invalid Category Channel',
            'cat_group' => [1, 999] // 999 doesn't exist
        ];

        // Mock category groups - only 1 exists, not both
        $this->mockSuperModelCount('category_groups', ['group_id' => [1, 999]], 1);

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test create_channel with template creation
     */
    public function testCreateChannelWithTemplateCreation()
    {
        $this->mockTemplateApi();

        $channelData = [
            'channel_name' => 'template_channel',
            'channel_title' => 'Template Channel Test',
            'create_templates' => 'yes',
            'old_group_id' => 1,
            'group_name' => 'new_templates',
            'template_theme' => 'default'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test create_channel with duplicate template group name
     */
    public function testCreateChannelWithDuplicateTemplateGroupName()
    {
        $this->mockTemplateApi();

        // Mock duplicate template group name
        $this->mockSuperModelCount('template_groups', [
            'site_id' => 1,
            'group_name' => 'new_templates'
        ], 1);

        $channelData = [
            'channel_name' => 'duplicate_template_channel',
            'channel_title' => 'Duplicate Template Channel',
            'create_templates' => 'yes',
            'old_group_id' => 1,
            'group_name' => 'new_templates',
            'template_theme' => 'default'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test create_channel with reserved template group name
     */
    public function testCreateChannelWithReservedTemplateGroupName()
    {
        $this->mockTemplateApi();

        $channelData = [
            'channel_name' => 'reserved_template_channel',
            'channel_title' => 'Reserved Template Channel',
            'create_templates' => 'yes',
            'old_group_id' => 1,
            'group_name' => 'act', // Reserved name
            'template_theme' => 'default'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test create_channel with invalid template group name
     */
    public function testCreateChannelWithInvalidTemplateGroupName()
    {
        $this->mockTemplateApi();

        $channelData = [
            'channel_name' => 'invalid_group_channel',
            'channel_title' => 'Invalid Group Channel',
            'create_templates' => 'yes',
            'old_group_id' => 1,
            'group_name' => 'invalid name with spaces',
            'template_theme' => 'default'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test create_channel with dupe_id parameter
     */
    public function testCreateChannelWithDupeId()
    {
        // Setup source channel data
        $this->mockChannelData();

        $channelData = [
            'channel_name' => 'dupe_channel',
            'channel_title' => 'Dupe Channel Test',
            'dupe_id' => 1, // Duplicate from channel 1
            'create_templates' => 'no' // Must be set to avoid undefined variable
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test create_channel with invalid dupe_id
     *
     * Note: The current API behavior sets an error when dupe_id is invalid,
     * which causes channel creation to fail. This may be a bug in the API.
     */
    public function testCreateChannelWithInvalidDupeId()
    {
        $channelData = [
            'channel_name' => 'invalid_dupe_channel',
            'channel_title' => 'Invalid Dupe Channel Test',
            'dupe_id' => 999, // Non-existent channel
            'create_templates' => 'no'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        // Current API behavior: invalid dupe_id causes creation to fail
        $this->assertFalse($result);
        $this->assertGreaterThan(0, $this->apiChannelStructure->error_count());
    }

    /**
     * Test create_channel with comment expiration
     */
    public function testCreateChannelWithCommentExpiration()
    {
        $channelData = [
            'channel_name' => 'comment_expiration_channel',
            'channel_title' => 'Comment Expiration Channel',
            'comment_expiration' => 30 // 30 days
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test create_channel with invalid comment expiration
     */
    public function testCreateChannelWithInvalidCommentExpiration()
    {
        $channelData = [
            'channel_name' => 'invalid_expiration_channel',
            'channel_title' => 'Invalid Expiration Channel',
            'comment_expiration' => 'invalid'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result); // Should be set to 0 (default)
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test create_channel with field group assignment
     */
    public function testCreateChannelWithFieldGroup()
    {
        $channelData = [
            'channel_name' => 'field_group_channel',
            'channel_title' => 'Field Group Channel',
            'field_group' => 2
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test create_channel with auto-assigned field group
     */
    public function testCreateChannelWithAutoAssignedFieldGroup()
    {
        $channelData = [
            'channel_name' => 'auto_field_channel',
            'channel_title' => 'Auto Field Channel'
            // field_group is intentionally missing - should auto-assign
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test create_channel logs creation action
     */
    public function testCreateChannelLogsCreationAction()
    {
        $channelData = [
            'channel_name' => 'logging_test_channel',
            'channel_title' => 'Logging Test Channel'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        // Verify logging was called
        $this->assertNotEmpty(ee()->logger->logged_messages ?? []);
    }

    /**
     * Test create_channel with custom channel URL
     */
    public function testCreateChannelWithCustomChannelUrl()
    {
        $channelData = [
            'channel_name' => 'custom_url_channel',
            'channel_title' => 'Custom URL Channel',
            'channel_url' => 'https://custom.example.com/channel/'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test create_channel with custom comment URL
     */
    public function testCreateChannelWithCustomCommentUrl()
    {
        $channelData = [
            'channel_name' => 'custom_comment_channel',
            'channel_title' => 'Custom Comment Channel',
            'comment_url' => 'https://custom.example.com/comments/'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test create_channel with all comment settings
     */
    public function testCreateChannelWithAllCommentSettings()
    {
        $channelData = [
            'channel_name' => 'comment_settings_channel',
            'channel_title' => 'Comment Settings Channel',
            'comment_system_enabled' => 'y',
            'comment_require_membership' => 'n',
            'comment_moderate' => 'y',
            'comment_max_chars' => 1000,
            'comment_timelock' => 60,
            'comment_require_email' => 'y',
            'comment_text_formatting' => 'xhtml',
            'comment_html_formatting' => 'safe',
            'comment_allow_img_urls' => 'n',
            'comment_auto_link_urls' => 'y',
            'comment_notify' => 'y',
            'comment_notify_authors' => 'n'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test create_channel with search settings
     */
    public function testCreateChannelWithSearchSettings()
    {
        $channelData = [
            'channel_name' => 'search_settings_channel',
            'channel_title' => 'Search Settings Channel',
            'search_results_url' => 'https://example.com/search/results/',
            'rss_url' => 'https://example.com/rss/',
            'enable_versioning' => 'y',
            'max_revisions' => 20,
            'max_entries' => 100
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test create_channel returns channel ID
     */
    public function testCreateChannelReturnsChannelId()
    {
        $channelData = [
            'channel_name' => 'return_id_channel',
            'channel_title' => 'Return ID Channel'
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);

        // Verify the channel was actually created in the mock
        $createdChannel = ee()->channel_model->channels[$result] ?? null;
        $this->assertNotNull($createdChannel);
        $this->assertEquals('return_id_channel', $createdChannel['channel_name']);
    }

    /**
     * Test create_channel with multiple validation errors
     */
    public function testCreateChannelWithMultipleValidationErrors()
    {
        $channelData = [
            'channel_name' => '', // Missing name
            'channel_title' => '', // Missing title
            'site_id' => 1
        ];

        $result = $this->apiChannelStructure->create_channel($channelData);

        $this->assertFalse($result);
        // Should have multiple errors
        $this->assertGreaterThanOrEqual(2, $this->apiChannelStructure->error_count());
    }
}
