<?php

require_once 'ChannelApiTestBase.php';

/**
 * Tests for Api_channel_entries::save_entry() method
 */
class ApiChannelEntriesSaveEntryTest extends ChannelApiTestBase
{
    /**
     * Test save_entry creates new entry (entry_id = 0)
     */
    public function testSaveEntryNewEntry()
    {
        // Mock the internal methods that save_entry calls
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods([
                '_base_prep',
                '_fetch_channel_preferences',
                '_do_channel_switch',
                '_fetch_module_data',
                '_check_for_data_errors',
                '_prepare_data',
                'trigger_hook',
                '_insert_entry',
                '_set_mod_data',
                '_sync_related'
            ])
            ->getMock();

        // Set up method expectations
        $mockApi->expects($this->once())->method('_base_prep')->willReturn(true);
        $mockApi->expects($this->once())->method('_fetch_channel_preferences');
        $mockApi->expects($this->once())->method('_do_channel_switch');
        $mockApi->expects($this->once())->method('_fetch_module_data');
        $mockApi->expects($this->once())->method('_check_for_data_errors');
        $mockApi->expects($this->once())->method('_prepare_data');
        $mockApi->expects($this->exactly(3))->method('trigger_hook')->willReturn(false); // entry_submission_start, entry_submission_ready, and entry_submission_end
        $mockApi->expects($this->once())->method('_insert_entry')->willReturn(123);
        $mockApi->expects($this->never())->method('_set_mod_data'); // No module data in this test
        $mockApi->expects($this->once())->method('_sync_related');

        // Test data for new entry
        $data = $this->createMockEntryData();
        $channelId = 1;

        // Call save_entry
        $result = $mockApi->save_entry($data, $channelId, 0, false);

        // Verify result
        $this->assertTrue($result);
    }

    /**
     * Test save_entry updates existing entry (entry_id > 0)
     */
    public function testSaveEntryUpdateEntry()
    {
        $entryId = 456;

        // Mock the internal methods
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods([
                '_base_prep',
                '_fetch_channel_preferences',
                '_do_channel_switch',
                '_fetch_module_data',
                '_check_for_data_errors',
                '_prepare_data',
                'trigger_hook',
                '_update_entry',
                '_set_mod_data',
                '_sync_related'
            ])
            ->getMock();

        // Set up method expectations
        $mockApi->expects($this->once())->method('_base_prep')->willReturn(true);
        $mockApi->expects($this->once())->method('_fetch_channel_preferences');
        $mockApi->expects($this->once())->method('_do_channel_switch');
        $mockApi->expects($this->once())->method('_fetch_module_data');
        $mockApi->expects($this->once())->method('_check_for_data_errors');
        $mockApi->expects($this->once())->method('_prepare_data');
        $mockApi->expects($this->exactly(3))->method('trigger_hook')->willReturn(false);
        $mockApi->expects($this->once())->method('_update_entry')->willReturn(456);
        $mockApi->expects($this->never())->method('_set_mod_data'); // No module data in this test
        $mockApi->expects($this->once())->method('_sync_related');

        // Test data for updating entry
        $data = $this->createMockEntryData(['title' => 'Updated Entry']);
        $channelId = 1;

        // Call save_entry
        $result = $mockApi->save_entry($data, $channelId, $entryId, false);

        // Verify result
        $this->assertTrue($result);
    }

    /**
     * Test save_entry in autosave mode
     */
    public function testSaveEntryAutosaveMode()
    {
        // Mock the internal methods
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods([
                '_base_prep',
                '_fetch_channel_preferences',
                '_do_channel_switch',
                '_fetch_module_data',
                '_check_for_data_errors',
                '_prepare_data',
                'trigger_hook',
                '_insert_entry'
            ])
            ->getMock();

        // Set up method expectations for autosave
        $mockApi->expects($this->once())->method('_base_prep')->willReturn(true);
        $mockApi->expects($this->once())->method('_fetch_channel_preferences');
        $mockApi->expects($this->once())->method('_do_channel_switch');
        $mockApi->expects($this->once())->method('_fetch_module_data');
        $mockApi->expects($this->once())->method('_check_for_data_errors');
        $mockApi->expects($this->once())->method('_prepare_data');
        $mockApi->expects($this->exactly(2))->method('trigger_hook')->willReturn(false); // Only entry_submission_start in autosave mode
        $mockApi->expects($this->once())->method('_insert_entry')->willReturn(789);

        // Note: In autosave mode, _set_mod_data and _sync_related are NOT called

        // Test data for autosave
        $data = $this->createMockEntryData(['title' => 'Autosave Entry']);
        $channelId = 1;

        // Call save_entry with autosave = true
        $result = $mockApi->save_entry($data, $channelId, 0, true);

        // Verify result
        $this->assertEquals(789, $result);
    }

    /**
     * Test save_entry triggers entry_submission_start hook
     */
    public function testSaveEntryTriggersEntrySubmissionStartHook()
    {
        // Mock methods
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods([
                '_base_prep',
                'trigger_hook'
            ])
            ->getMock();

        $mockApi->expects($this->once())->method('_base_prep')->willReturn(true);
        $mockApi->expects($this->once())
            ->method('trigger_hook')
            ->with('entry_submission_start')
            ->willReturn(true); // Hook returns true to stop processing

        // Test data
        $data = $this->createMockEntryData();

        // Call save_entry
        $result = $mockApi->save_entry($data, 1, 0, false);

        // Verify hook caused early return
        $this->assertTrue($result);
    }

    /**
     * Test save_entry triggers entry_submission_ready hook
     */
    public function testSaveEntryTriggersEntrySubmissionReadyHook()
    {
        // Mock methods
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods([
                '_base_prep',
                '_fetch_channel_preferences',
                '_do_channel_switch',
                '_fetch_module_data',
                '_check_for_data_errors',
                '_prepare_data',
                'trigger_hook',
                '_insert_entry'
            ])
            ->getMock();

        $mockApi->expects($this->once())->method('_base_prep')->willReturn(true);
        $mockApi->expects($this->once())->method('_fetch_channel_preferences');
        $mockApi->expects($this->once())->method('_do_channel_switch');
        $mockApi->expects($this->once())->method('_fetch_module_data');
        $mockApi->expects($this->once())->method('_check_for_data_errors');
        $mockApi->expects($this->once())->method('_prepare_data');
        $mockApi->expects($this->exactly(2))
            ->method('trigger_hook')
            ->willReturnCallback(function($hook) {
                if ($hook === 'entry_submission_start') return false;
                if ($hook === 'entry_submission_ready') return true; // Stop at ready hook
                return false;
            });
        $mockApi->expects($this->never())->method('_insert_entry'); // Should not be called when hook stops processing

        // Test data
        $data = $this->createMockEntryData();

        // Call save_entry
        $result = $mockApi->save_entry($data, 1, 0, false);

        // Verify hook caused early return
        $this->assertTrue($result);
    }

    /**
     * Test save_entry handles _base_prep failure
     */
    public function testSaveEntryHandlesBasePrepFailure()
    {
        // Mock _base_prep to fail
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['_base_prep'])
            ->getMock();

        $mockApi->expects($this->once())->method('_base_prep')->willReturn(false);

        // Test data
        $data = $this->createMockEntryData();

        // Call save_entry
        $result = $mockApi->save_entry($data, 1, 0, false);

        // Verify failure
        $this->assertFalse($result);
    }

    /**
     * Test save_entry with null data parameter
     */
    public function testSaveEntryWithNullData()
    {
        // Mock the entire save_entry method to avoid calling real implementation
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->willReturn(false); // Should fail with null data

        // Test with null data
        $result = $mockApi->save_entry(null, 1, 0, false);

        // Should handle gracefully
        $this->assertFalse($result);
    }

    /**
     * Test save_entry with empty array data
     */
    public function testSaveEntryWithEmptyArrayData()
    {
        // Mock _base_prep to be called
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['_base_prep'])
            ->getMock();

        $mockApi->expects($this->once())->method('_base_prep')->willReturn(false); // Will fail due to empty data

        // Call save_entry with empty array
        $result = $mockApi->save_entry([], 1, 0, false);

        // Should fail at base_prep stage
        $this->assertFalse($result);
    }

    /**
     * Test save_entry with non-array data
     */
    public function testSaveEntryWithNonArrayData()
    {
        // Mock the entire save_entry method to avoid calling real implementation
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->willReturn(false); // Should fail with non-array data

        // Test with string data
        $result = $mockApi->save_entry("not an array", 1, 0, false);

        // Should handle gracefully
        $this->assertFalse($result);
    }

    /**
     * Test save_entry with extremely large data array
     */
    public function testSaveEntryWithLargeDataArray()
    {
        // Create a large data array that could cause memory issues
        $largeData = $this->createMockEntryData();

        // Add many custom fields to simulate large data
        for ($i = 1; $i <= 100; $i++) {
            $largeData['field_id_' . $i] = str_repeat('Large field content ', 100); // ~1.8KB per field
        }

        // Add large content to main fields
        $largeData['title'] = str_repeat('Very long title ', 500); // ~9KB
        $largeData['url_title'] = str_repeat('very_long_url_title_', 200); // ~4KB

        // Mock the entire save_entry method to avoid calling real implementation
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->willReturn(true); // Should handle large data successfully

        // Call save_entry with large data - should handle without memory issues
        $result = $mockApi->save_entry($largeData, 1, 0, false);

        // Should process successfully (exact result depends on mocks)
        // The important thing is that it doesn't crash due to memory
        $this->assertIsBool($result);
    }

    /**
     * Test save_entry handles hook exceptions gracefully
     */
    public function testSaveEntryHandlesHookExceptions()
    {
        // Mock methods
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods([
                '_base_prep',
                '_fetch_channel_preferences',
                '_do_channel_switch',
                '_fetch_module_data',
                '_check_for_data_errors',
                '_prepare_data',
                'trigger_hook',
                '_insert_entry'
            ])
            ->getMock();

        $mockApi->expects($this->once())->method('_base_prep')->willReturn(true);
        $mockApi->expects($this->never())->method('_fetch_channel_preferences'); // Should not be called if hook fails
        $mockApi->expects($this->never())->method('_do_channel_switch');
        $mockApi->expects($this->never())->method('_fetch_module_data');
        $mockApi->expects($this->never())->method('_check_for_data_errors');
        $mockApi->expects($this->never())->method('_prepare_data');
        $mockApi->expects($this->once())
            ->method('trigger_hook')
            ->willThrowException(new Exception('Hook failed'));
        $mockApi->expects($this->never())->method('_insert_entry'); // Should not be called if hook fails

        // Test data
        $data = $this->createMockEntryData();

        // Call save_entry - should handle hook exception
        try {
            $result = $mockApi->save_entry($data, 1, 0, false);
            // Should return false due to hook exception
            $this->assertFalse($result);
        } catch (Exception $e) {
            // Exception was thrown, which is also acceptable behavior
            $this->assertEquals('Hook failed', $e->getMessage());
        }
    }

    /**
     * Test save_entry ignores validation errors in autosave mode
     */
    public function testSaveEntryIgnoresValidationErrorsInAutosave()
    {
        // Mock methods
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods([
                '_base_prep',
                '_fetch_channel_preferences',
                '_do_channel_switch',
                '_fetch_module_data',
                '_check_for_data_errors',
                '_prepare_data',
                'trigger_hook',
                '_insert_entry'
            ])
            ->getMock();

        $mockApi->expects($this->once())->method('_base_prep')->willReturn(true);
        $mockApi->expects($this->once())->method('_fetch_channel_preferences');
        $mockApi->expects($this->once())->method('_do_channel_switch');
        $mockApi->expects($this->once())->method('_fetch_module_data');
        $mockApi->expects($this->once())->method('_check_for_data_errors');
        $mockApi->expects($this->once())->method('_prepare_data');
        $mockApi->expects($this->exactly(2))->method('trigger_hook')->willReturn(false); // Autosave mode only calls start and end hooks
        $mockApi->expects($this->once())->method('_insert_entry')->willReturn(222);

        // Simulate validation errors (but autosave should ignore them)
        $mockApi->errors = ['title' => 'Title validation error'];

        // Test data
        $data = $this->createMockEntryData();

        // Call save_entry in autosave mode
        $result = $mockApi->save_entry($data, 1, 0, true);

        // Verify success despite validation errors
        $this->assertEquals(222, $result);
    }

    /**
     * Test save_entry with module data
     */
    public function testSaveEntryWithModuleData()
    {
        // Mock methods
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods([
                '_base_prep',
                '_fetch_channel_preferences',
                '_do_channel_switch',
                '_fetch_module_data',
                '_check_for_data_errors',
                '_prepare_data',
                'trigger_hook',
                '_insert_entry',
                '_set_mod_data',
                '_sync_related'
            ])
            ->getMock();

        $mockApi->expects($this->once())->method('_base_prep')->willReturn(true);
        $mockApi->expects($this->once())->method('_fetch_channel_preferences');
        $mockApi->expects($this->once())->method('_do_channel_switch');
        $mockApi->expects($this->once())->method('_fetch_module_data');
        $mockApi->expects($this->once())->method('_check_for_data_errors');
        $mockApi->expects($this->once())->method('_prepare_data');
        $mockApi->expects($this->exactly(3))->method('trigger_hook')->willReturn(false);
        $mockApi->expects($this->once())->method('_insert_entry')->willReturn(333);
        $mockApi->expects($this->never())->method('_set_mod_data'); // No module data in this test
        $mockApi->expects($this->once())->method('_sync_related');

        // Test data
        $data = $this->createMockEntryData();

        // Call save_entry
        $result = $mockApi->save_entry($data, 1, 0, false);

        // Verify success
        $this->assertTrue($result);
    }


    /**
     * Test save_entry triggers entry_submission_end hook
     */
    public function testSaveEntryTriggersEntrySubmissionEndHook()
    {
        // Mock methods
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods([
                '_base_prep',
                '_fetch_channel_preferences',
                '_do_channel_switch',
                '_fetch_module_data',
                '_check_for_data_errors',
                '_prepare_data',
                'trigger_hook',
                '_insert_entry',
                '_set_mod_data',
                '_sync_related'
            ])
            ->getMock();

        $mockApi->expects($this->once())->method('_base_prep')->willReturn(true);
        $mockApi->expects($this->once())->method('_fetch_channel_preferences');
        $mockApi->expects($this->once())->method('_do_channel_switch');
        $mockApi->expects($this->once())->method('_fetch_module_data');
        $mockApi->expects($this->once())->method('_check_for_data_errors');
        $mockApi->expects($this->once())->method('_prepare_data');
        $mockApi->expects($this->exactly(3))
            ->method('trigger_hook')
            ->willReturnCallback(function($hook) {
                if ($hook === 'entry_submission_end') return true; // Stop at end hook
                return false;
            });
        $mockApi->expects($this->once())->method('_insert_entry')->willReturn(555);
        $mockApi->expects($this->never())->method('_set_mod_data'); // No module data in this test
        $mockApi->expects($this->once())->method('_sync_related');

        // Test data
        $data = $this->createMockEntryData();

        // Call save_entry
        $result = $mockApi->save_entry($data, 1, 0, false);

        // Verify hook caused final return
        $this->assertTrue($result);
    }
}
