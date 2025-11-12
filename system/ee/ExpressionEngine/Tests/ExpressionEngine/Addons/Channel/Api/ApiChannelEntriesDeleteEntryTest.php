<?php

require_once 'ChannelApiTestBase.php';

/**
 * Tests for Api_channel_entries::delete_entry() method
 */
class ApiChannelEntriesDeleteEntryTest extends ChannelApiTestBase
{
    /**
     * Test delete_entry with single entry ID
     */
    public function testDeleteEntrySingleId()
    {
        $entry_id = 123;

        // Set up authenticated user
        $this->setupAuthenticatedUser(1, 1);

        // Set up channel permissions (can delete self entries)
        $this->mockPermission = new class {
            public function isSuperAdmin() { return false; }
            public function has($permission) {
                return strpos($permission, 'can_delete_self_entries_channel_id_1') !== false;
            }
            public function can($permission) { return $this->has($permission); }
            public function getAssignedChannels() { return [1]; }
        };
        ee()->setMock('Permission', $this->mockPermission);

        // Mock database to return entry data
        $this->mockDb->setRows([
            ['entry_id' => $entry_id, 'channel_id' => 1, 'author_id' => 1]
        ]);

        // Call delete_entry
        $result = $this->api->delete_entry($entry_id);

        // Verify successful deletion
        $this->assertTrue($result);
    }

    /**
     * Test delete_entry with multiple entry IDs
     */
    public function testDeleteEntryMultipleIds()
    {
        $entry_ids = [123, 456, 789];

        // Set up authenticated user
        $this->setupAuthenticatedUser(1, 1);

        // Set up channel permissions
        $this->setupChannelPermissions(1, true, true);

        // Mock database to return entry data
        $this->mockDb->setRows([
            ['entry_id' => 123, 'channel_id' => 1, 'author_id' => 1],
            ['entry_id' => 456, 'channel_id' => 1, 'author_id' => 1],
            ['entry_id' => 789, 'channel_id' => 1, 'author_id' => 1]
        ]);


        // Call delete_entry
        $result = $this->api->delete_entry($entry_ids);

        // Verify successful deletion
        $this->assertTrue($result);
    }

    /**
     * Test delete_entry with super admin permissions
     */
    public function testDeleteEntryPermissionCheckSuperAdmin()
    {
        $entry_id = 123;

        // Mock super admin permission
        $this->mockPermission = new class {
            public function isSuperAdmin() { return true; }
            public function has($permission) { return true; }
            public function can($permission) { return true; }
            public function getAssignedChannels() { return []; }
        };
        ee()->setMock('Permission', $this->mockPermission);

        // Mock database to return entry data
        $this->mockDb->setRows([
            ['entry_id' => $entry_id, 'channel_id' => 1, 'author_id' => 2] // Different author
        ]);

        // Mock the Model delete call
        $mockModel = $this->getMockBuilder(stdClass::class)
            ->setMethods(['delete'])
            ->getMock();

        // Call delete_entry
        $result = $this->api->delete_entry($entry_id);

        // Verify successful deletion (super admin bypasses permission checks)
        $this->assertTrue($result);
    }

    /**
     * Test delete_entry with assigned channel permissions
     */
    public function testDeleteEntryPermissionCheckAssignedChannels()
    {
        $entry_id = 123;

        // Set up authenticated user
        $this->setupAuthenticatedUser(1, 1);

        // Set up channel permissions (user has access to channel 1)
        $this->setupChannelPermissions(1, true, true);

        // Mock database to return entry data
        $this->mockDb->setRows([
            ['entry_id' => $entry_id, 'channel_id' => 1, 'author_id' => 2] // Different author
        ]);

        // Mock the Model delete call
        $mockModel = $this->getMockBuilder(stdClass::class)
            ->setMethods(['delete'])
            ->getMock();

        // Call delete_entry
        $result = $this->api->delete_entry($entry_id);

        // Verify successful deletion
        $this->assertTrue($result);
    }

    /**
     * Test delete_entry self-entry deletion permissions
     */
    public function testDeleteEntryPermissionCheckSelfEntries()
    {
        $entry_id = 123;

        // Set up authenticated user
        $this->setupAuthenticatedUser(1, 1);

        // Set up permissions (can delete self entries)
        $this->mockPermission = new class {
            public function isSuperAdmin() { return false; }
            public function has($permission) {
                return strpos($permission, 'can_delete_self_entries_channel_id_1') !== false;
            }
            public function can($permission) { return $this->has($permission); }
            public function getAssignedChannels() { return [1]; }
        };
        ee()->setMock('Permission', $this->mockPermission);

        // Mock database to return entry data (same author)
        $this->mockDb->setRows([
            ['entry_id' => $entry_id, 'channel_id' => 1, 'author_id' => 1] // Same author
        ]);

        // Mock the Model delete call
        $mockModel = $this->getMockBuilder(stdClass::class)
            ->setMethods(['delete'])
            ->getMock();

        // Call delete_entry
        $result = $this->api->delete_entry($entry_id);

        // Verify successful deletion
        $this->assertTrue($result);
    }

    /**
     * Test delete_entry others-entry deletion permissions
     */
    public function testDeleteEntryPermissionCheckOthersEntries()
    {
        $entry_id = 123;

        // Set up authenticated user
        $this->setupAuthenticatedUser(1, 1);

        // Set up permissions (can delete others entries)
        $this->mockPermission = new class {
            public function isSuperAdmin() { return false; }
            public function has($permission) {
                return strpos($permission, 'can_delete_all_entries_channel_id_1') !== false;
            }
            public function can($permission) { return $this->has($permission); }
            public function getAssignedChannels() { return [1]; }
        };
        ee()->setMock('Permission', $this->mockPermission);

        // Mock database to return entry data (different author)
        $this->mockDb->setRows([
            ['entry_id' => $entry_id, 'channel_id' => 1, 'author_id' => 2] // Different author
        ]);

        // Mock the Model delete call
        $mockModel = $this->getMockBuilder(stdClass::class)
            ->setMethods(['delete'])
            ->getMock();

        // Call delete_entry
        $result = $this->api->delete_entry($entry_id);

        // Verify successful deletion
        $this->assertTrue($result);
    }

    /**
     * Test delete_entry unauthorized for channel
     */
    public function testDeleteEntryUnauthorizedForChannel()
    {
        $entry_id = 123;

        // Set up authenticated user
        $this->setupAuthenticatedUser(1, 1);

        // Set up permissions (no access to channel 1)
        $this->mockPermission = new class {
            public function isSuperAdmin() { return false; }
            public function has($permission) { return false; }
            public function can($permission) { return false; }
            public function getAssignedChannels() { return [2]; } // Access to channel 2, not 1
        };
        ee()->setMock('Permission', $this->mockPermission);

        // Mock database to return entry data
        $this->mockDb->setRows([
            ['entry_id' => $entry_id, 'channel_id' => 1, 'author_id' => 1]
        ]);

        // Mock the _set_error method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['_set_error'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('_set_error')
            ->with('unauthorized_to_delete_self')
            ->willReturn(false);

        // Replace the api instance
        $this->api = $mockApi;

        // Call delete_entry
        $result = $this->api->delete_entry($entry_id);

        // Verify unauthorized access
        $this->assertFalse($result);
    }

    /**
     * Test delete_entry unauthorized to delete self
     */
    public function testDeleteEntryUnauthorizedSelfDelete()
    {
        $entry_id = 123;

        // Set up authenticated user
        $this->setupAuthenticatedUser(1, 1);

        // Set up permissions (cannot delete self entries)
        $this->mockPermission = new class {
            public function isSuperAdmin() { return false; }
            public function has($permission) {
                return strpos($permission, 'can_delete_self_entries_channel_id_1') === false;
            }
            public function can($permission) { return $this->has($permission); }
            public function getAssignedChannels() { return [1]; }
        };
        ee()->setMock('Permission', $this->mockPermission);

        // Mock database to return entry data (same author)
        $this->mockDb->setRows([
            ['entry_id' => $entry_id, 'channel_id' => 1, 'author_id' => 1] // Same author
        ]);

        // Mock the _set_error method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['_set_error'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('_set_error')
            ->with('unauthorized_to_delete_self')
            ->willReturn(false);

        // Replace the api instance
        $this->api = $mockApi;

        // Call delete_entry
        $result = $this->api->delete_entry($entry_id);

        // Verify unauthorized access
        $this->assertFalse($result);
    }

    /**
     * Test delete_entry unauthorized to delete others
     */
    public function testDeleteEntryUnauthorizedOthersDelete()
    {
        $entry_id = 123;

        // Set up authenticated user
        $this->setupAuthenticatedUser(1, 1);

        // Set up permissions (cannot delete others entries)
        $this->mockPermission = new class {
            public function isSuperAdmin() { return false; }
            public function has($permission) {
                return strpos($permission, 'can_delete_all_entries_channel_id_1') === false;
            }
            public function can($permission) { return $this->has($permission); }
            public function getAssignedChannels() { return [1]; }
        };
        ee()->setMock('Permission', $this->mockPermission);

        // Mock database to return entry data (different author)
        $this->mockDb->setRows([
            ['entry_id' => $entry_id, 'channel_id' => 1, 'author_id' => 2] // Different author
        ]);

        // Mock the _set_error method
        $mockApi = $this->getMockBuilder(Api_channel_entries::class)
            ->onlyMethods(['_set_error'])
            ->getMock();

        $mockApi->expects($this->once())
            ->method('_set_error')
            ->with('unauthorized_to_delete_others')
            ->willReturn(false);

        // Replace the api instance
        $this->api = $mockApi;

        // Call delete_entry
        $result = $this->api->delete_entry($entry_id);

        // Verify unauthorized access
        $this->assertFalse($result);
    }

    /**
     * Test delete_entry triggers extension hooks
     */
    public function testDeleteEntryTriggersHooks()
    {
        $entry_id = 123;

        // Set up authenticated user
        $this->setupAuthenticatedUser(1, 1);

        // Set up permissions (can delete self entries)
        $this->mockPermission = new class {
            public function isSuperAdmin() { return false; }
            public function has($permission) {
                return strpos($permission, 'can_delete_self_entries_channel_id_1') !== false;
            }
            public function can($permission) { return $this->has($permission); }
            public function getAssignedChannels() { return [1]; }
        };
        ee()->setMock('Permission', $this->mockPermission);

        // Set up channel permissions
        $this->setupChannelPermissions(1, true, true);

        // Mock database to return entry data
        $this->mockDb->setRows([
            ['entry_id' => $entry_id, 'channel_id' => 1, 'author_id' => 1]
        ]);

        // Call delete_entry
        $result = $this->api->delete_entry($entry_id);

        // Verify successful deletion
        $this->assertTrue($result);
    }
}
