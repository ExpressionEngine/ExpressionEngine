<?php

require_once 'ChannelApiTestBase.php';

/**
 * Tests for Api_channel_entries::entry_exists() method
 */
class ApiChannelEntriesEntryExistsTest extends ChannelApiTestBase
{
    /**
     * Test entry_exists with non-numeric entry_id returns false
     */
    public function testEntryExistsWithNonNumericId()
    {
        // Test various non-numeric values
        $nonNumericIds = ['abc', '123abc', null, [], new stdClass()];

        foreach ($nonNumericIds as $entryId) {
            // Call entry_exists with non-numeric ID
            $result = $this->api->entry_exists($entryId);

            // Verify returns false
            $this->assertFalse($result, "entry_exists should return false for non-numeric ID: " . var_export($entryId, true));
        }
    }

    /**
     * Test entry_exists when entry does not exist
     */
    public function testEntryExistsWhenEntryDoesNotExist()
    {
        $entryId = 999;

        // Mock channel_entries_model to return no results
        $this->setupChannelEntriesModel(false, 1);

        // Mock the channel_entries_model on the ee() object
        $mockModel = new class {
            public function get_entry($entry_id) {
                return new class {
                    public function num_rows() { return 0; }
                    public function row($field) { return null; }
                };
            }
        };
        ee()->setMock('channel_entries_model', $mockModel);

        // Call entry_exists
        $result = $this->api->entry_exists($entryId);

        // Verify returns false
        $this->assertFalse($result);
    }

    /**
     * Test entry_exists when entry exists
     */
    public function testEntryExistsWhenEntryExists()
    {
        $entryId = 123;
        $authorId = 456;

        // Mock channel_entries_model to return existing entry
        $this->setupChannelEntriesModel(true, $authorId);

        // Mock the channel_entries_model on the ee() object
        $mockModel = new class($authorId) {
            private $authorId;
            public function __construct($authorId) { $this->authorId = $authorId; }
            public function get_entry($entry_id) {
                return new class($this->authorId) {
                    private $authorId;
                    public function __construct($authorId) { $this->authorId = $authorId; }
                    public function num_rows() { return 1; }
                    public function row($field) { return $this->authorId; }
                };
            }
        };
        ee()->setMock('channel_entries_model', $mockModel);

        // Call entry_exists
        $result = $this->api->entry_exists($entryId);

        // Verify returns true
        $this->assertTrue($result);
    }

    /**
     * Test entry_exists caches author_id when entry exists
     */
    public function testEntryExistsCachesAuthorId()
    {
        $entryId = 789;
        $authorId = 101112;

        // Mock the channel_entries_model on the ee() object
        $mockModel = new class($authorId) {
            private $authorId;
            public function __construct($authorId) { $this->authorId = $authorId; }
            public function get_entry($entry_id) {
                return new class($this->authorId) {
                    private $authorId;
                    public function __construct($authorId) { $this->authorId = $authorId; }
                    public function num_rows() { return 1; }
                    public function row($field) { return $this->authorId; }
                };
            }
        };
        ee()->setMock('channel_entries_model', $mockModel);

        // Ensure cache is initially empty
        $this->assertEquals([], $this->api->_cache);

        // Call entry_exists
        $result = $this->api->entry_exists($entryId);

        // Verify entry exists
        $this->assertTrue($result);

        // Verify author_id was cached
        $this->assertEquals(['orig_author_id' => $authorId], $this->api->_cache);
    }

    /**
     * Test entry_exists with numeric string ID
     */
    public function testEntryExistsWithNumericStringId()
    {
        $entryId = '456';
        $authorId = 789;

        // Mock channel_entries_model to return existing entry
        $this->setupChannelEntriesModel(true, $authorId);

        // Call entry_exists with string ID
        $result = $this->api->entry_exists($entryId);

        // Verify returns true (string numeric IDs should work)
        $this->assertTrue($result);
    }

    /**
     * Test entry_exists with zero as entry_id
     */
    public function testEntryExistsWithZeroId()
    {
        $entryId = 0;

        // Mock the channel_entries_model to return no results for ID 0
        $mockModel = new class {
            public function get_entry($entry_id) {
                return new class {
                    public function num_rows() { return 0; }
                    public function row($field) { return null; }
                };
            }
        };
        ee()->setMock('channel_entries_model', $mockModel);

        // Call entry_exists with zero
        $result = $this->api->entry_exists($entryId);

        // Verify returns false (zero is numeric but likely not a valid entry ID)
        $this->assertFalse($result);
    }

    /**
     * Test entry_exists with negative entry_id
     */
    public function testEntryExistsWithNegativeId()
    {
        $entryId = -123;

        // Call entry_exists with negative ID
        $result = $this->api->entry_exists($entryId);

        // Verify returns false (negative IDs are numeric but invalid)
        $this->assertFalse($result);
    }

    /**
     * Test entry_exists preserves existing cache data
     */
    public function testEntryExistsPreservesExistingCacheData()
    {
        $entryId = 111;
        $authorId = 222;
        $existingCacheData = [
            'some_other_key' => 'some_value',
            'another_key' => 123
        ];

        // Set up existing cache data
        $this->api->_cache = $existingCacheData;

        // Mock the channel_entries_model on the ee() object
        $mockModel = new class($authorId) {
            private $authorId;
            public function __construct($authorId) { $this->authorId = $authorId; }
            public function get_entry($entry_id) {
                return new class($this->authorId) {
                    private $authorId;
                    public function __construct($authorId) { $this->authorId = $authorId; }
                    public function num_rows() { return 1; }
                    public function row($field) { return $this->authorId; }
                };
            }
        };
        ee()->setMock('channel_entries_model', $mockModel);

        // Call entry_exists
        $result = $this->api->entry_exists($entryId);

        // Verify returns true
        $this->assertTrue($result);

        // Verify existing cache data is preserved and orig_author_id is added
        $expectedCache = array_merge($existingCacheData, ['orig_author_id' => $authorId]);
        $this->assertEquals($expectedCache, $this->api->_cache);
    }

    /**
     * Test entry_exists with large numeric ID
     */
    public function testEntryExistsWithLargeNumericId()
    {
        $entryId = 999999;
        $authorId = 123;

        // Mock the channel_entries_model on the ee() object
        $mockModel = new class($authorId) {
            private $authorId;
            public function __construct($authorId) { $this->authorId = $authorId; }
            public function get_entry($entry_id) {
                return new class($this->authorId) {
                    private $authorId;
                    public function __construct($authorId) { $this->authorId = $authorId; }
                    public function num_rows() { return 1; }
                    public function row($field) { return $this->authorId; }
                };
            }
        };
        ee()->setMock('channel_entries_model', $mockModel);

        // Call entry_exists with large ID
        $result = $this->api->entry_exists($entryId);

        // Verify returns true
        $this->assertTrue($result);

        // Verify author_id was cached
        $this->assertEquals(['orig_author_id' => $authorId], $this->api->_cache);
    }

    /**
     * Test entry_exists with float entry_id (should be treated as non-numeric)
     */
    public function testEntryExistsWithFloatId()
    {
        $entryId = 123.45;

        // Mock the channel_entries_model to return no results for float ID
        $mockModel = new class {
            public function get_entry($entry_id) {
                return new class {
                    public function num_rows() { return 0; }
                    public function row($field) { return null; }
                };
            }
        };
        ee()->setMock('channel_entries_model', $mockModel);

        // Call entry_exists with float
        $result = $this->api->entry_exists($entryId);

        // Verify returns false (floats are numeric but likely not a valid entry ID)
        $this->assertFalse($result);
    }

    /**
     * Test entry_exists handles database query failure
     */
    public function testEntryExistsHandlesDatabaseFailure()
    {
        $entryId = 123;

        // Mock channel_entries_model to throw exception
        $mockModel = new class {
            public function get_entry($entry_id) {
                throw new Exception('Database connection failed');
            }
        };
        ee()->setMock('channel_entries_model', $mockModel);

        // Call entry_exists - should handle database failure gracefully
        try {
            $result = $this->api->entry_exists($entryId);
            // Should return false on database failure
            $this->assertFalse($result);
        } catch (Exception $e) {
            // Exception was thrown, which is also acceptable behavior
            $this->assertEquals('Database connection failed', $e->getMessage());
        }
    }

    /**
     * Test entry_exists with null author_id from database
     */
    public function testEntryExistsWithNullAuthorId()
    {
        $entryId = 456;

        // Mock channel_entries_model to return null author_id
        $mockModel = new class {
            public function get_entry($entry_id) {
                return new class {
                    public function num_rows() { return 1; }
                    public function row($field) { return null; } // Null author_id
                };
            }
        };
        ee()->setMock('channel_entries_model', $mockModel);

        // Call entry_exists
        $result = $this->api->entry_exists($entryId);

        // Should return true even with null author_id
        $this->assertTrue($result);

        // Should cache null author_id
        $this->assertEquals(['orig_author_id' => null], $this->api->_cache);
    }

    /**
     * Test multiple calls to entry_exists cache author_id correctly
     */
    public function testEntryExistsMultipleCallsCacheCorrectly()
    {
        $entryId1 = 111;
        $entryId2 = 222;
        $authorId1 = 333;
        $authorId2 = 444;

        // Mock for first entry
        $mockModel1 = new class($authorId1) {
            private $authorId;
            public function __construct($authorId) { $this->authorId = $authorId; }
            public function get_entry($entry_id) {
                return new class($this->authorId) {
                    private $authorId;
                    public function __construct($authorId) { $this->authorId = $authorId; }
                    public function num_rows() { return 1; }
                    public function row($field) { return $this->authorId; }
                };
            }
        };
        ee()->setMock('channel_entries_model', $mockModel1);

        // First call
        $result1 = $this->api->entry_exists($entryId1);
        $this->assertTrue($result1);
        $this->assertEquals(['orig_author_id' => $authorId1], $this->api->_cache);

        // Mock for second entry
        $mockModel2 = new class($authorId2) {
            private $authorId;
            public function __construct($authorId) { $this->authorId = $authorId; }
            public function get_entry($entry_id) {
                return new class($this->authorId) {
                    private $authorId;
                    public function __construct($authorId) { $this->authorId = $authorId; }
                    public function num_rows() { return 1; }
                    public function row($field) { return $this->authorId; }
                };
            }
        };
        ee()->setMock('channel_entries_model', $mockModel2);

        // Second call should overwrite the cache
        $result2 = $this->api->entry_exists($entryId2);
        $this->assertTrue($result2);
        $this->assertEquals(['orig_author_id' => $authorId2], $this->api->_cache);
    }
}
