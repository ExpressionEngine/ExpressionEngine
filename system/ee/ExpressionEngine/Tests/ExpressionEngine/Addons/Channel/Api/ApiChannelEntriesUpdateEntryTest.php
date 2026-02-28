<?php

require_once 'ChannelApiTestBase.php';

/**
 * Tests for Api_channel_entries::update_entry() method
 */
class ApiChannelEntriesUpdateEntryTest extends ChannelApiTestBase
{
    /**
     * Test that update_entry calls save_entry with correct parameters
     */
    public function testUpdateEntryCallsSaveEntry()
    {
        $entry_id = 123;
        $data = $this->createMockEntryData(['title' => 'Updated Entry']);

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with($data, null, $entry_id, false)
            ->willReturn(true);

        // Replace the api instance
        $this->api = $mockApi;

        // Call update_entry
        $result = $this->api->update_entry($entry_id, $data);

        // Verify the result is returned
        $this->assertTrue($result);
    }

    /**
     * Test that update_entry returns the result from save_entry
     */
    public function testUpdateEntryReturnsSaveEntryResult()
    {
        $entry_id = 456;
        $data = $this->createMockEntryData(['title' => 'Updated Title']);
        $expected_result = 'update_successful';

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->willReturn($expected_result);

        // Replace the api instance
        $this->api = $mockApi;

        // Call update_entry
        $result = $this->api->update_entry($entry_id, $data);

        // Verify the result matches expected
        $this->assertEquals($expected_result, $result);
    }

    /**
     * Test update_entry with valid data
     */
    public function testUpdateEntryWithValidData()
    {
        $entry_id = 789;
        $data = $this->createMockEntryData([
            'title' => 'Updated Test Entry',
            'url_title' => 'updated-test-entry',
            'status' => 'closed'
        ]);

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with($data, null, $entry_id, false)
            ->willReturn(789); // Mock updated entry ID return

        // Replace the api instance
        $this->api = $mockApi;

        // Call update_entry
        $result = $this->api->update_entry($entry_id, $data);

        // Verify successful update
        $this->assertEquals(789, $result);
    }

    /**
     * Test update_entry with minimal required data
     */
    public function testUpdateEntryWithMinimalData()
    {
        $entry_id = 101;
        $data = [
            'channel_id' => 1,
            'title' => 'Minimal Update',
            'url_title' => 'minimal-update'
        ];

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with($data, null, $entry_id, false)
            ->willReturn(101);

        // Replace the api instance
        $this->api = $mockApi;

        // Call update_entry
        $result = $this->api->update_entry($entry_id, $data);

        // Verify successful update
        $this->assertEquals(101, $result);
    }

    /**
     * Test update_entry with different entry IDs
     */
    public function testUpdateEntryWithDifferentEntryIds()
    {
        $test_cases = [
            ['entry_id' => 1, 'expected' => 'result_1'],
            ['entry_id' => 999, 'expected' => 'result_999'],
            ['entry_id' => 0, 'expected' => 'result_0']
        ];

        foreach ($test_cases as $test_case) {
            $entry_id = $test_case['entry_id'];
            $expected = $test_case['expected'];
            $data = $this->createMockEntryData(['title' => 'Test Entry ' . $entry_id]);

            // Mock the save_entry method
            $mockApi = $this->getMockBuilder(Api_channel_entries::class)
                ->onlyMethods(['save_entry'])
                ->getMock();

            $mockApi->expects($this->once())
                ->method('save_entry')
                ->with($data, null, $entry_id, false)
                ->willReturn($expected);

            // Replace the api instance
            $this->api = $mockApi;

            // Call update_entry
            $result = $this->api->update_entry($entry_id, $data);

            // Verify the result
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test update_entry with autosave data
     */
    public function testUpdateEntryWithAutosaveData()
    {
        $entry_id = 222;
        $data = $this->createMockEntryData([
            'autosave_entry_id' => 333,
            'title' => 'Autosave Update Entry'
        ]);

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with($data, null, $entry_id, false) // autosave is always false for update_entry
            ->willReturn(222);

        // Replace the api instance
        $this->api = $mockApi;

        // Call update_entry
        $result = $this->api->update_entry($entry_id, $data);

        // Verify successful update
        $this->assertEquals(222, $result);
    }

    /**
     * Test update_entry with empty data array
     */
    public function testUpdateEntryWithEmptyData()
    {
        $entry_id = 444;
        $data = [];

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with($data, null, $entry_id, false)
            ->willReturn(false); // Mock failure

        // Replace the api instance
        $this->api = $mockApi;

        // Call update_entry
        $result = $this->api->update_entry($entry_id, $data);

        // Verify the result (should be false due to empty data)
        $this->assertFalse($result);
    }

    /**
     * Test update_entry parameter passing
     */
    public function testUpdateEntryParameterOrder()
    {
        $entry_id = 555;
        $data = $this->createMockEntryData(['custom_update_field' => 'updated_value']);

        // Mock the save_entry method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['save_entry'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('save_entry')
            ->with(
                $this->equalTo($data),     // First param: data
                $this->equalTo(null),      // Second param: channel_id (always null)
                $this->equalTo($entry_id), // Third param: entry_id
                $this->equalTo(false)      // Fourth param: autosave (always false)
            )
            ->willReturn('update_complete');

        // Replace the api instance
        $this->api = $mockApi;

        // Call update_entry
        $result = $this->api->update_entry($entry_id, $data);

        // Verify the result
        $this->assertEquals('update_complete', $result);
    }
}
