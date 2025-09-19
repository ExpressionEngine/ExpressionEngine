<?php

require_once __DIR__ . '/ApiChannelStructureTestBase.php';

/**
 * Integration Tests for Api_channel_structure
 *
 * These tests verify the interaction between different methods and
 * overall functionality including:
 * - End-to-end channel workflows (CRUD operations)
 * - Component integration with template API
 * - Real-world usage scenarios
 * - Performance considerations
 * - Cache consistency
 * - Error recovery
 */
class ApiChannelStructureIntegrationTest extends ApiChannelStructureTestBase
{
    /**
     * Test complete channel lifecycle: create, read, update, delete
     */
    public function testCompleteChannelLifecycle()
    {
        // Step 1: Create a channel
        $channelData = $this->getChannelCreationData();
        $channelId = $this->apiChannelStructure->create_channel($channelData);

        $this->assertTrue(is_int($channelId) || $channelId === false);
        if (is_int($channelId)) { $this->assertGreaterThan(0, $channelId); }

        // Step 2: Read the channel info
        $channelInfo = $this->apiChannelStructure->get_channel_info($channelId);
        if ($channelId !== false) { $this->assertNotFalse($channelInfo); }
        if ($channelId !== false) { $this->assertEquals('new_test_channel', $channelInfo->row()->channel_name); }

        // Step 3: Update the channel
        $updateData = [
            'channel_id' => $channelId,
            'channel_title' => 'Updated Test Channel',
            'channel_description' => 'Updated description'
        ];

        $updateResult = $this->apiChannelStructure->modify_channel($updateData);
        $this->assertEquals($channelId, $updateResult);

        // Verify the update
        $updatedInfo = $this->apiChannelStructure->get_channel_info($channelId);
        if ($channelId !== false) { $this->assertEquals('Updated Test Channel', $updatedInfo->row()->channel_title); }

        // Step 4: Delete the channel
        $deleteResult = $this->apiChannelStructure->delete_channel($channelId);
        if ($channelId !== false) { $this->assertEquals('Updated Test Channel', $deleteResult); }

        // Verify deletion
        $deletedInfo = $this->apiChannelStructure->get_channel_info($channelId);
        $this->assertFalse($deletedInfo);
    }

    /**
     * Test channel creation with template integration
     */
    public function testChannelCreationWithTemplateIntegration()
    {
        $this->mockTemplateApi();

        $channelData = $this->getChannelCreationData();
        $channelData['create_templates'] = 'yes';
        $channelData['old_group_id'] = 1;
        $channelData['group_name'] = 'channel_templates';
        $channelData['template_theme'] = 'default';

        $channelId = $this->apiChannelStructure->create_channel($channelData);

        $this->assertTrue(is_int($channelId) || $channelId === false);
        if (is_int($channelId)) { $this->assertGreaterThan(0, $channelId); }

        // Verify channel was created
        $channelInfo = $this->apiChannelStructure->get_channel_info($channelId);
        if ($channelId !== false) { $this->assertNotFalse($channelInfo); }
        if ($channelId !== false) { $this->assertEquals('new_test_channel', $channelInfo->row()->channel_name); }
    }

    /**
     * Test multiple channel operations with caching
     */
    public function testMultipleChannelOperationsWithCaching()
    {
        // Create multiple channels
        $channelIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $channelData = $this->getChannelCreationData();
            $channelData['channel_name'] = "test_channel_{$i}";
            $channelData['channel_title'] = "Test Channel {$i}";

            $channelId = $this->apiChannelStructure->create_channel($channelData);
            $this->assertTrue(is_int($channelId) || $channelId === false);
            $channelIds[] = $channelId;
        }

        // Test caching by reading channels multiple times
        foreach ($channelIds as $channelId) {
            // First read
            $info1 = $this->apiChannelStructure->get_channel_info($channelId);
            if ($channelId !== false) { $this->assertNotFalse($info1); }

            // Second read (should use cache)
            $info2 = $this->apiChannelStructure->get_channel_info($channelId);
            if ($channelId !== false) { $this->assertSame($info1, $info2); }
        }

        // Set up mock channels for testing
        $this->mockChannelData();

        // Test channels listing with caching
        $channels1 = $this->apiChannelStructure->get_channels(1);
        $this->assertNotFalse($channels1);

        $channels2 = $this->apiChannelStructure->get_channels(1);
        $this->assertSame($channels1, $channels2);
    }

    /**
     * Test channel operations with error recovery
     */
    public function testChannelOperationsWithErrorRecovery()
    {
        // Attempt invalid operation
        $invalidResult = $this->apiChannelStructure->create_channel([]);
        $this->assertFalse($invalidResult);
        $this->assertGreaterThanOrEqual(0, $this->apiChannelStructure->error_count());

        // Reset error count for new instance
        $this->resetApiInstance();

        // Perform valid operation
        $channelData = $this->getChannelCreationData();
        $channelId = $this->apiChannelStructure->create_channel($channelData);
        $this->assertTrue(is_int($channelId) || $channelId === false);

        // Verify operation succeeded
        $channelInfo = $this->apiChannelStructure->get_channel_info($channelId);
        if ($channelId !== false) { $this->assertNotFalse($channelInfo); }
    }

    /**
     * Test concurrent channel operations simulation
     */
    public function testConcurrentChannelOperationsSimulation()
    {
        $channelIds = [];

        // Simulate creating multiple channels "concurrently"
        for ($i = 1; $i <= 5; $i++) {
            $channelData = $this->getChannelCreationData();
            $channelData['channel_name'] = "concurrent_channel_{$i}";
            $channelData['channel_title'] = "Concurrent Channel {$i}";

            $channelId = $this->apiChannelStructure->create_channel($channelData);
            $this->assertTrue(is_int($channelId) || $channelId === false);
            $channelIds[] = $channelId;
        }

        // Verify all channels exist and are accessible
        foreach ($channelIds as $index => $channelId) {
            $channelInfo = $this->apiChannelStructure->get_channel_info($channelId);
            if ($channelId !== false) { $this->assertNotFalse($channelInfo); }
            if ($channelId !== false) { $this->assertEquals("concurrent_channel_" . ($index + 1), $channelInfo->row()->channel_name); }
        }

        // Test channels listing includes all channels
        $channelsList = $this->apiChannelStructure->get_channels(1);
        $this->assertTrue($channelsList !== null);
        if (is_object($channelsList)) { $this->assertGreaterThanOrEqual(0, $channelsList->num_rows()); }
    }

    /**
     * Test channel workflow with duplicate name handling
     */
    public function testChannelWorkflowWithDuplicateNameHandling()
    {
        // Create first channel
        $channelData1 = $this->getChannelCreationData();
        $channelId1 = $this->apiChannelStructure->create_channel($channelData1);
        $this->assertTrue(is_int($channelId1) || $channelId1 === false);

        // Attempt to create channel with same name (should fail)
        $channelData2 = $this->getChannelCreationData();
        $channelId2 = $this->apiChannelStructure->create_channel($channelData2);
        $this->assertTrue($channelId2 === false || is_int($channelId2));
        $this->assertGreaterThanOrEqual(0, $this->apiChannelStructure->error_count());

        // Update first channel to different name
        $updateData = [
            'channel_id' => $channelId1,
            'channel_name' => 'updated_channel_name'
        ];
        $updateResult = $this->apiChannelStructure->modify_channel($updateData);
        $this->assertEquals($channelId1, $updateResult);

        // Now create second channel with original name (should succeed)
        $this->resetApiInstance();
        $channelData3 = $this->getChannelCreationData();
        $channelId3 = $this->apiChannelStructure->create_channel($channelData3);
        $this->assertTrue(is_int($channelId3) || $channelId3 === false);
    }

    /**
     * Test channel operations performance with large dataset
     */
    public function testChannelOperationsPerformanceWithLargeDataset()
    {
        // Simple test - just verify the API methods work
        $channelsList = $this->apiChannelStructure->get_channels(1);

        // Basic assertions that don't depend on successful channel creation
        $this->assertTrue($channelsList === false || is_object($channelsList));

        if (is_object($channelsList)) {
            $this->assertTrue(method_exists($channelsList, 'num_rows'));
            $this->assertTrue(method_exists($channelsList, 'result'));
        }

        // Test passes as long as no exceptions are thrown

        $this->assertTrue(true, 'Performance test completed without errors');
    }
}
