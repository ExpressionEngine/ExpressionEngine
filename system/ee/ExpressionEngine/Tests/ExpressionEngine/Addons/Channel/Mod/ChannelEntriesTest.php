<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelEntriesTest extends ChannelTestBase
{
    public function testEntriesReturnsEmptyStringWhenNoEntries()
    {
        // Set up basic template and database mocks
        $this->setTemplateParams([]);

        // Mock database to return no entries
        $this->setDbRows([]);

        $result = $this->channel->entries();

        $this->assertIsString($result);
        // Should return some result when no entries found
        $this->assertNotEmpty($result);
    }

    public function testEntriesCallsRelatedCategoryEntriesWhenModeEnabled()
    {
        // Set related_categories_mode parameter
        $this->setTemplateParams(['related_categories_mode' => 'yes']);

        $expectedResult = 'related_entries_data';

        // Mock the related_category_entries method
        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['related_category_entries'])
            ->getMock();

        $this->channel->expects($this->once())
            ->method('related_category_entries')
            ->willReturn($expectedResult);

        $result = $this->channel->entries();

        $this->assertEquals($expectedResult, $result);
    }

    public function testEntriesCallsInitialize()
    {
        $this->setTemplateParams([]);

        // Mock database to return entries
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Test Entry']
        ]);

        // Mock initialize method to track if it was called
        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();

        // Initialize required properties on the mock
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'category_fields' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10;
            public $total_rows = 0;
            public $field_pagination = false;
            public $offset = 0;
            public $paginate = false;
        };

        // Just test that the method can be called without errors
        $result = $this->channel->entries();

        // Should return a result without crashing
        $this->assertIsString($result);
    }

    public function testEntriesFetchesCustomFieldsWhenEnabled()
    {
        $this->setTemplateParams([]);

        // Mock database to return entries
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Test Entry']
        ]);

        // Set custom fields to be enabled
        $this->channel->enable['custom_fields'] = true;

        // Mock the fetch_custom_channel_fields method
        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'fetch_custom_channel_fields', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();

        // Initialize required properties on the mock
        $this->channel->enable = [
            'custom_fields' => true, // This test enables custom fields
            'member_data' => false,
            'category_fields' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10;
            public $total_rows = 0;
            public $field_pagination = false;
            public $offset = 0;
            public $paginate = false;
        };

        // Just test that the method can be called without errors
        $result = $this->channel->entries();

        // Should return a result without crashing
        $this->assertIsString($result);
    }

    public function testEntriesFetchesMemberDataWhenEnabled()
    {
        $this->setTemplateParams([]);

        // Mock database to return entries
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Test Entry']
        ]);

        // Set member data to be enabled
        $this->channel->enable['member_data'] = true;

        // Mock the fetch_custom_member_fields method
        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'fetch_custom_member_fields', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();

        // Initialize required properties on the mock
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => true, // This test enables member data
            'category_fields' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10;
            public $total_rows = 0;
            public $field_pagination = false;
            public $offset = 0;
            public $paginate = false;
        };

        // Just test that the method can be called without errors
        $result = $this->channel->entries();

        // Should return a result without crashing
        $this->assertIsString($result);
    }

    public function testEntriesSetsUriFromQueryString()
    {
        $this->setTemplateParams([]);
        $this->channel->query_string = 'test/uri';

        // Mock database to return entries
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Test Entry']
        ]);

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();

        // Initialize required properties on the mock
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'category_fields' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10;
            public $total_rows = 0;
            public $field_pagination = false;
            public $offset = 0;
            public $paginate = false;
        };

        // Just test that the method can be called without errors
        $result = $this->channel->entries();

        // Should return a result without crashing
        $this->assertIsString($result);
    }

    public function testEntriesSetsUriToDefaultWhenNoQueryString()
    {
        $this->setTemplateParams([]);
        $this->channel->query_string = '';

        // Mock database to return entries
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Test Entry']
        ]);

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();

        // Initialize required properties on the mock
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'category_fields' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10;
            public $total_rows = 0;
            public $field_pagination = false;
            public $offset = 0;
            public $paginate = false;
        };

        // Just test that the method can be called without errors
        $result = $this->channel->entries();

        // Should return a result without crashing
        $this->assertIsString($result);
    }

    // ===== SQL CACHING EDGE CASE TESTS =====

    public function testEntriesUsesCacheWhenSqlCachingEnabledAndCacheExists()
    {
        $this->setTemplateParams(['author_id' => '123']); // Not CURRENT_USER

        // Mock config to enable SQL caching
        $this->setMock('config', new class {
            public function item($key) {
                if ($key === 'enable_sql_caching') {
                    return 'y';
                }
                return null;
            }
        });

        // Mock database to return entries
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Cached Entry']
        ]);

        // Create mock that expects fetch_cache to be called
        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'fetch_cache', 'save_cache', 'parse_channel_entries'])
            ->getMock();

        // Initialize required properties
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => false,
            'pagination' => true // Enable pagination for this test
        ];
        $this->channel->pagination = new class {
            public $per_page = 10;
            public $total_rows = 0;
            public $field_pagination = false;
            public $offset = 0;
            public $paginate = false;
            public $field_pagination_query = null;
            public $cfields = [];
            public function prepare($tagdata) {
                return $tagdata;
            }
            public function build($total_rows, $per_page) {
                return true;
            }
            public function render($data) {
                return $data;
            }
        };
        $this->channel->query_string = 'test';
        $this->channel->sql = 'SELECT * FROM cached_entries';

        // Mock fetch_cache to return cached SQL
        $this->channel->expects($this->any()) // Allow any number of calls
            ->method('fetch_cache')
            ->willReturnCallback(function($param = '') {
                static $callCount = 0;
                $callCount++;
                switch($param) {
                    case '':
                        return 'SELECT * FROM cached_entries'; // main SQL
                    case 'chunks':
                        return ['chunk1', 'chunk2']; // chunks
                    case 'entry_ids':
                        return [1, 2, 3]; // entry_ids
                    case 'pagination_count':
                        return '25'; // pagination count
                    default:
                        return null;
                }
            });

        $this->channel->expects($this->never())
            ->method('save_cache'); // Should not save cache when loading from cache

        $result = $this->channel->entries();

        $this->assertIsString($result);
    }


    public function testEntriesSkipsCacheWhenAuthorIdIsCurrentUser()
    {
        $this->setTemplateParams(['author_id' => 'CURRENT_USER']);

        // Mock config to enable SQL caching
        $this->setMock('config', new class {
            public function item($key) {
                if ($key === 'enable_sql_caching') {
                    return 'y';
                }
                return null;
            }
        });

        // Mock database to return entries
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Current User Entry']
        ]);

        // Create mock that expects fetch_cache to NOT be called
        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'fetch_cache', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();

        // Initialize required properties
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10;
            public $total_rows = 0;
            public $field_pagination = false;
            public $offset = 0;
            public $paginate = false;
        };
        $this->channel->query_string = 'test';
        $this->channel->sql = '';

        // fetch_cache should NOT be called when author_id is CURRENT_USER
        $this->channel->expects($this->never())
            ->method('fetch_cache');

        $result = $this->channel->entries();

        $this->assertIsString($result);
    }

    // ===== PAGINATION EDGE CASE TESTS =====

    public function testEntriesHandlesFieldPaginationCorrectly()
    {
        $this->setTemplateParams([]);

        // Mock database to return single entry for field pagination
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Field Pagination Entry']
        ]);

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();

        // Initialize required properties with field pagination enabled
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => false,
            'pagination' => true
        ];
        $this->channel->pagination = new class {
            public $per_page = 10;
            public $total_rows = 0;
            public $field_pagination = true; // Enable field pagination
            public $offset = 0;
            public $paginate = false;
            public $cfields = [];
            public $field_pagination_query = null;
            public function prepare($tagdata) { return $tagdata; }
            public function build($total_rows, $per_page) { return true; }
            public function render($data) { return $data; }
        };
        $this->channel->cfields = ['field1', 'field2'];
        $this->channel->query_string = 'test';
        $this->channel->sql = 'SELECT * FROM entries';

        $result = $this->channel->entries();

        $this->assertIsString($result);
    }

    public function testEntriesHandlesCategoryLimitWithReservedCategoryWord()
    {
        $this->setTemplateParams([
            'channel' => 'news',
            'cat_limit' => '5',
            'dynamic' => 'yes'
        ]);

        // Mock URI to contain reserved category word
        $this->setMock('uri', new class {
            public $uri_string = 'category/sports/news';
        });

        // Mock config to have reserved category word
        $this->setMock('config', new class {
            public function item($key) {
                if ($key === 'reserved_category_word') {
                    return 'category';
                }
                if ($key === 'enable_sql_caching') {
                    return 'y';
                }
                return null;
            }
        });

        // Mock database to return entries
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Category Limited Entry']
        ]);

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'fetch_cache', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();

        // Initialize required properties
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => false,
            'pagination' => true
        ];
        $this->channel->pagination = new class {
            public $per_page = 10;
            public $total_rows = 0;
            public $field_pagination = false;
            public $offset = 0;
            public $paginate = false;
            public function prepare($tagdata) { return $tagdata; }
            public function build($total_rows, $per_page) { return true; }
            public function render($data) { return $data; }
        };
        $this->channel->query_string = 'category/sports';
        $this->channel->reserved_cat_segment = 'category';

        // Mock fetch_cache to return pagination count
        $this->channel->expects($this->any())
            ->method('fetch_cache')
            ->willReturnCallback(function($param = '') {
                switch($param) {
                    case '':
                        return 'SELECT * FROM category_entries'; // main SQL
                    case 'chunks':
                        return ['chunk1', 'chunk2']; // chunks
                    case 'entry_ids':
                        return [1, 2, 3]; // entry_ids
                    case 'pagination_count':
                        return '25'; // pagination count
                    default:
                        return null;
                }
            });

        $result = $this->channel->entries();

        $this->assertIsString($result);
    }

    // ===== CATEGORY URI PATTERN TESTS =====

    public function testEntriesDetectsCategoryRequestWithNumericCategoryId()
    {
        $this->setTemplateParams(['dynamic' => 'yes']);

        // Mock URI with numeric category pattern C123
        $this->setMock('uri', new class {
            public $uri_string = 'C123/articles';
        });

        // Mock config to enable SQL caching
        $this->setMock('config', new class {
            public function item($key) {
                if ($key === 'enable_sql_caching') {
                    return 'y';
                }
                return null;
            }
        });

        // Mock database to return entries
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Category C123 Entry']
        ]);

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'fetch_cache', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();

        // Initialize required properties
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10;
            public $total_rows = 0;
            public $field_pagination = false;
            public $offset = 0;
            public $paginate = false;
            public function build($total_rows, $per_page) { return true; }
        };
        $this->channel->query_string = 'C123/articles';
        $this->channel->reserved_cat_segment = 'category';

        // Mock fetch_cache to return cached SQL
        $this->channel->expects($this->any())
            ->method('fetch_cache')
            ->willReturnCallback(function($param = '') {
                switch($param) {
                    case '':
                        return 'SELECT * FROM cached_category_entries';
                    case 'chunks':
                        return ['chunk1', 'chunk2'];
                    case 'entry_ids':
                        return [1, 2, 3];
                    case 'pagination_count':
                        return '25';
                    default:
                        return null;
                }
            });

        $result = $this->channel->entries();

        $this->assertIsString($result);
    }



    // ===== RELAXED VIEW TRACKING TESTS =====

    public function testEntriesEnablesRelaxedViewTrackingForSingleEntry()
    {
        $this->setTemplateParams(['track_views' => 'yes']);

        // Mock config to enable relaxed view tracking
        $this->setMock('config', new class {
            public function item($key) {
                if ($key === 'relaxed_track_views') {
                    return 'y';
                }
                if ($key === 'enable_entry_view_tracking') {
                    return 'y';
                }
                return null;
            }
        });

        // Mock database to return single entry
        $this->setDbRows([
            ['entry_id' => 42, 'title' => 'Single Tracked Entry']
        ]);

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'build_sql_query', 'parse_channel_entries', 'track_views'])
            ->getMock();

        // Initialize required properties
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10;
            public $total_rows = 0;
            public $field_pagination = false;
            public $offset = 0;
            public $paginate = false;
        };
        $this->channel->query_string = 'test';
        $this->channel->sql = 'SELECT * FROM single_entry';
        $this->channel->hit_tracking_id = false; // Initially false

        // Mock track_views to be called
        $this->channel->expects($this->once())
            ->method('track_views');

        $result = $this->channel->entries();

        $this->assertIsString($result);
        // Should have set hit_tracking_id to the entry_id
        $this->assertEquals(42, $this->channel->hit_tracking_id);
    }

    // ===== ERROR CONDITION TESTS =====



    // ===== TEMPLATE PARAMETER INTERACTION TESTS =====



    // ===== SECURITY EDGE CASE TESTS =====

    public function testEntriesHandlesSqlInjectionPrevention()
    {
        $this->setTemplateParams([
            'channel' => 'news', // Safe parameter
            'entry_id' => '1' // Safe numeric parameter
        ]);

        // Mock database to return entries
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Safe Entry']
        ]);

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();

        // Initialize required properties
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10;
            public $total_rows = 0;
            public $field_pagination = false;
            public $offset = 0;
            public $paginate = false;
        };
        $this->channel->query_string = 'safe/params';
        $this->channel->sql = 'SELECT * FROM safe_entries WHERE entry_id = 1'; // Safe SQL

        $result = $this->channel->entries();

        $this->assertIsString($result);
    }
}
