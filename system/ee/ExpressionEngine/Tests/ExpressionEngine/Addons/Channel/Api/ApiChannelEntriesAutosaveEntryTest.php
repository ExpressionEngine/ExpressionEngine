<?php

require_once 'ChannelApiTestBase.php';

/**
 * Tests for Api_channel_entries::autosave_entry() method
 */
class ApiChannelEntriesAutosaveEntryTest extends ChannelApiTestBase
{
    /**
     * Test that autosave_entry sets autosave_entry_id from input data
     */
    public function testAutosaveEntrySetsAutosaveEntryId()
    {
        $autosaveId = 123;

        // Mock submit_new_entry to avoid actual processing
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['submit_new_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('submit_new_entry')
            ->willReturn(true);

        // Test data with autosave_entry_id
        $data = [
            'channel_id' => 1,
            'title' => 'Test Entry',
            'autosave_entry_id' => $autosaveId
        ];

        // Call autosave_entry
        $mockApi->autosave_entry($data);

        // Verify autosave_entry_id was set
        $this->assertEquals($autosaveId, $mockApi->autosave_entry_id);
    }

    /**
     * Test autosave_entry with new entry (no entry_id)
     */
    public function testAutosaveEntryNewEntry()
    {
        // Mock submit_new_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['submit_new_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('submit_new_entry')
            ->with(1, $this->anything(), true) // autosave = true
            ->willReturn(456);

        // Test data for new entry (no entry_id)
        $data = [
            'channel_id' => 1,
            'title' => 'New Autosave Entry'
        ];

        // Call autosave_entry
        $result = $mockApi->autosave_entry($data);

        // Verify result and that submit_new_entry was called
        $this->assertEquals(456, $result);
    }

    /**
     * Test autosave_entry with existing entry (has entry_id)
     */
    public function testAutosaveEntryExistingEntry()
    {
        $entryId = 789;

        // Mock save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with($this->anything(), null, $entryId, true) // autosave = true
            ->willReturn(999);

        // Test data for existing entry
        $data = [
            'channel_id' => 1,
            'entry_id' => $entryId,
            'title' => 'Existing Autosave Entry'
        ];

        // Call autosave_entry
        $result = $mockApi->autosave_entry($data);

        // Verify result and that save_entry was called
        $this->assertEquals(999, $result);
    }

    /**
     * Test autosave_entry with existing title is preserved
     */
    public function testAutosaveEntryPreservesExistingTitle()
    {
        $existingTitle = 'My Existing Title';

        // Mock submit_new_entry
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['submit_new_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('submit_new_entry')
            ->with(1, $this->callback(function($data) use ($existingTitle) {
                // Verify title was preserved
                return $data['title'] === $existingTitle;
            }), true)
            ->willReturn(456);

        // Test data with existing title
        $data = [
            'channel_id' => 1,
            'title' => $existingTitle
        ];

        // Call autosave_entry
        $result = $mockApi->autosave_entry($data);

        // Verify result
        $this->assertEquals(456, $result);
    }

    /**
     * Test autosave_entry with empty autosave_entry_id defaults to 0
     */
    public function testAutosaveEntryDefaultsAutosaveEntryIdToZero()
    {
        // Mock submit_new_entry
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['submit_new_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('submit_new_entry')
            ->willReturn(true);

        // Test data without autosave_entry_id
        $data = [
            'channel_id' => 1,
            'title' => 'Test Entry'
        ];

        // Call autosave_entry
        $mockApi->autosave_entry($data);

        // Verify autosave_entry_id defaults to 0
        $this->assertEquals(0, $mockApi->autosave_entry_id);
    }


    /**
     * Test autosave_entry with missing title key (potential PHP error)
     */
    public function testAutosaveEntryWithMissingTitleKey()
    {
        // Mock save_entry to avoid calling real autosave_entry logic
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->willReturn(456);

        // Test data with entry_id set (existing entry) but missing title key
        $data = [
            'channel_id' => 1,
            'entry_id' => 456
            // Note: no 'title' key - this could cause PHP error in real implementation
        ];

        // Call autosave_entry
        $result = $mockApi->autosave_entry($data);

        // Verify result
        $this->assertEquals(456, $result);
    }

    /**
     * Test autosave_entry with entry_id = 0 (should treat as new entry)
     */
    public function testAutosaveEntryWithEntryIdZero()
    {
        // Mock submit_new_entry
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['submit_new_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('submit_new_entry')
            ->with(1, $this->anything(), true)
            ->willReturn(789);

        // Test data with entry_id = 0 (should be treated as new)
        $data = [
            'channel_id' => 1,
            'entry_id' => 0, // Explicitly set to 0
            'title' => 'Entry with ID Zero'
        ];

        // Call autosave_entry
        $result = $mockApi->autosave_entry($data);

        // Verify result
        $this->assertEquals(789, $result);
    }

    /**
     * Test autosave_entry with entry_id as string
     */
    public function testAutosaveEntryWithStringEntryId()
    {
        $entryId = '123';

        // Mock save_entry
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with($this->anything(), null, (int)$entryId, true) // Should convert to int
            ->willReturn(555);

        // Test data with string entry_id
        $data = [
            'channel_id' => 1,
            'entry_id' => $entryId,
            'title' => 'Entry with String ID'
        ];

        // Call autosave_entry
        $result = $mockApi->autosave_entry($data);

        // Verify result
        $this->assertEquals(555, $result);
    }
}
