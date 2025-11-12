<?php

require_once 'ChannelApiTestBase.php';

/**
 * Tests for Api_channel_entries::submit_new_entry() method
 */
class ApiChannelEntriesSubmitNewEntryTest extends ChannelApiTestBase
{
    /**
     * Test that submit_new_entry calls save_entry with correct parameters
     */
    public function testSubmitNewEntryCallsSaveEntry()
    {
        $channel_id = 1;
        $data = $this->createMockEntryData();

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with($data, $channel_id, null, false)
            ->willReturn(true);

        // Replace the api instance
        $this->api = $mockApi;

        // Call submit_new_entry
        $result = $this->api->submit_new_entry($channel_id, $data);

        // Verify the result is returned
        $this->assertTrue($result);
    }

    /**
     * Test that submit_new_entry returns the result from save_entry
     */
    public function testSubmitNewEntryReturnsSaveEntryResult()
    {
        $channel_id = 2;
        $data = $this->createMockEntryData(['title' => 'Test Return Value']);
        $expected_result = 'expected_return_value';

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->willReturn($expected_result);

        // Replace the api instance
        $this->api = $mockApi;

        // Call submit_new_entry
        $result = $this->api->submit_new_entry($channel_id, $data);

        // Verify the result matches expected
        $this->assertEquals($expected_result, $result);
    }

    /**
     * Test submit_new_entry with valid data
     */
    public function testSubmitNewEntryWithValidData()
    {
        $channel_id = 1;
        $data = $this->createMockEntryData([
            'title' => 'Valid Test Entry',
            'url_title' => 'valid-test-entry',
            'status' => 'open'
        ]);

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with($data, $channel_id, null, false)
            ->willReturn(123); // Mock entry ID return

        // Replace the api instance
        $this->api = $mockApi;

        // Call submit_new_entry
        $result = $this->api->submit_new_entry($channel_id, $data);

        // Verify successful submission
        $this->assertEquals(123, $result);
    }

    /**
     * Test submit_new_entry with minimal required data
     */
    public function testSubmitNewEntryWithMinimalData()
    {
        $channel_id = 3;
        $data = [
            'channel_id' => $channel_id,
            'title' => 'Minimal Entry',
            'url_title' => 'minimal-entry'
        ];

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with($data, $channel_id, null, false)
            ->willReturn(456);

        // Replace the api instance
        $this->api = $mockApi;

        // Call submit_new_entry
        $result = $this->api->submit_new_entry($channel_id, $data);

        // Verify successful submission
        $this->assertEquals(456, $result);
    }

    /**
     * Test submit_new_entry with autosave parameter
     */
    public function testSubmitNewEntryWithAutosaveData()
    {
        $channel_id = 1;
        $data = $this->createMockEntryData([
            'autosave_entry_id' => 789,
            'title' => 'Autosave Test Entry'
        ]);

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with($data, $channel_id, null, false) // autosave is always false for submit_new_entry
            ->willReturn(789);

        // Replace the api instance
        $this->api = $mockApi;

        // Call submit_new_entry
        $result = $this->api->submit_new_entry($channel_id, $data);

        // Verify successful submission
        $this->assertEquals(789, $result);
    }

    /**
     * Test submit_new_entry with empty data array
     */
    public function testSubmitNewEntryWithEmptyData()
    {
        $channel_id = 1;
        $data = [];

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with($data, $channel_id, null, false)
            ->willReturn(false); // Mock failure

        // Replace the api instance
        $this->api = $mockApi;

        // Call submit_new_entry
        $result = $this->api->submit_new_entry($channel_id, $data);

        // Verify the result (should be false due to empty data)
        $this->assertFalse($result);
    }

    /**
     * Test submit_new_entry parameter passing
     */
    public function testSubmitNewEntryParameterOrder()
    {
        $channel_id = 5;
        $data = $this->createMockEntryData(['custom_field' => 'value']);

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with(
                $this->equalTo($data),      // First param: data
                $this->equalTo($channel_id), // Second param: channel_id
                $this->equalTo(null),       // Third param: entry_id (always null)
                $this->equalTo(false)       // Fourth param: autosave (always false)
            )
            ->willReturn('success');

        // Replace the api instance
        $this->api = $mockApi;

        // Call submit_new_entry
        $result = $this->api->submit_new_entry($channel_id, $data);

        // Verify the result
        $this->assertEquals('success', $result);
    }
}
