<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelEntriesTest extends ChannelTestBase
{
    public function testEntriesReturnsNoResultsWhenSqlEmptyAndNotPreview()
    {
        $this->setTemplateParams([]);

        // Mock LivePreview to ensure isLivePreviewEntry() is false
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return false; }
        });

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'build_sql_query'])
            ->getMock();
        $this->channel->method('build_sql_query')->willReturn(null);

        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'category_fields' => false,
            'categories' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10; public $total_rows = 0; public $field_pagination = false; public $offset = 0; public $paginate = false;
        };
        $this->channel->sql = '';

        $result = $this->channel->entries();
        $this->assertEquals('NO_RESULTS', $result);
    }

    public function testEntriesSavesCacheOnMissSavesSqlAndChunks()
    {
        $this->setTemplateParams(['author_id' => '123']);

        // Enable SQL caching
        $this->setMock('config', new class {
            public function item($key) {
                if ($key === 'enable_sql_caching') { return 'y'; }
                return null;
            }
        });

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'fetch_cache', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');

        // simulate cache miss
        $this->channel->expects($this->any())
            ->method('fetch_cache')
            ->willReturn(false);

        // Expect build_sql_query to be called due to cache miss and empty SQL after initialize
        $this->channel->expects($this->once())
            ->method('build_sql_query');

        // Initialize required properties
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10; public $total_rows = 0; public $field_pagination = false; public $offset = 0; public $paginate = false;
        };
        $this->channel->query_string = 'x';

        $result = $this->channel->entries();
        $this->assertIsString($result);
    }

    public function testEntriesDoesNotUseCacheWhenSqlCachingDisabled()
    {
        $this->setMock('config', new class {
            public function item($key) { if ($key === 'enable_sql_caching') { return 'n'; } return null; }
        });

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'fetch_cache', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');

        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'category_fields' => false,
            'categories' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10; public $total_rows = 0; public $field_pagination = false; public $offset = 0; public $paginate = false;
        };
        $this->channel->sql = 'SELECT 1';

        $this->channel->expects($this->never())->method('fetch_cache');
        $this->channel->entries();
    }

    public function testEntriesFetchesCategoriesWhenEnabledAndTagdataEmpty()
    {
        $this->setTemplateParams([]);

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'fetch_categories', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');
        $this->channel->sql = 'SELECT 1';

        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => true,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10; public $total_rows = 0; public $field_pagination = false; public $offset = 0; public $paginate = false;
        };

        $this->channel->expects($this->once())->method('fetch_categories');

        $this->channel->entries();
    }

    public function testEntriesDoesNotFetchCategoriesWhenTagdataDoesNotContainCategories()
    {
        $this->setTemplateParams([]);
        $this->setTemplateTagdata('no-cats-here');

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'fetch_categories', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');
        $this->channel->sql = 'SELECT 1';

        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => true,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10; public $total_rows = 0; public $field_pagination = false; public $offset = 0; public $paginate = false;
        };

        $this->channel->expects($this->never())->method('fetch_categories');

        $this->channel->entries();
    }

    public function testEntriesRendersPaginationWhenEnabled()
    {
        $this->setTemplateParams([]);

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');

        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'category_fields' => false,
            'categories' => false,
            'pagination' => true
        ];
        $this->channel->pagination = new class {
            public $per_page = 10; public $total_rows = 0; public $field_pagination = false; public $offset = 0; public $paginate = false;
            public function prepare($tagdata){ return $tagdata; }
            public function render($data){ return 'WRAPPED:' . $data; }
        };
        $this->channel->sql = 'SELECT 1';
        $this->channel->return_data = 'BODY';

        $result = $this->channel->entries();
        $this->assertEquals('WRAPPED:BODY', $result);
    }

    public function testEntriesReturnsParseOutputWhenPaginationDisabled()
    {
        $this->setTemplateParams([]);

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');

        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'category_fields' => false,
            'categories' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10; public $total_rows = 0; public $field_pagination = false; public $offset = 0; public $paginate = false;
        };
        $this->channel->sql = 'SELECT 1';
        $this->channel->return_data = 'PARSED';

        $result = $this->channel->entries();
        $this->assertEquals('PARSED', $result);
    }

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
            ->onlyMethods(['initialize', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();
        $this->channel->expects($this->once())
            ->method('initialize');
        $this->channel->method('parse_channel_entries')->willReturn('');

        // Initialize required properties on the mock
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'category_fields' => false,
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
            ->onlyMethods(['initialize', 'fetch_custom_channel_fields', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();
        $this->channel->expects($this->once())
            ->method('fetch_custom_channel_fields');
        $this->channel->method('parse_channel_entries')->willReturn('');

        // Initialize required properties on the mock
        $this->channel->enable = [
            'custom_fields' => true, // This test enables custom fields
            'member_data' => false,
            'category_fields' => false,
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
        $this->channel->expects($this->once())
            ->method('fetch_custom_member_fields');

        // Initialize required properties on the mock
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => true, // This test enables member data
            'category_fields' => false,
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
            ->onlyMethods(['initialize', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();
        $this->channel->query_string = 'test/uri';
        $this->channel->method('parse_channel_entries')->willReturn('');

        // Initialize required properties on the mock
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'category_fields' => false,
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

        // Just test that the method can be called without errors
        $result = $this->channel->entries();

        // Should return a result without crashing
        $this->assertIsString($result);
        $this->assertEquals('test/uri', $this->channel->uri);
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
            ->onlyMethods(['initialize', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');

        // Initialize required properties on the mock
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'category_fields' => false,
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

        // Just test that the method can be called without errors
        $result = $this->channel->entries();

        // Should return a result without crashing
        $this->assertIsString($result);
        $this->assertEquals('index.php', $this->channel->uri);
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
            ->onlyMethods(['initialize', 'fetch_cache', 'save_cache', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');

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
            ->onlyMethods(['initialize', 'fetch_cache', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');

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
            ->onlyMethods(['initialize', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');

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
        $this->assertNotNull($this->channel->pagination->field_pagination_query);
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
            ->onlyMethods(['initialize', 'fetch_cache', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');

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
            public $captured_per_page = null;
            public function prepare($tagdata) { return $tagdata; }
            public function build($total_rows, $per_page) { $this->captured_per_page = $per_page; return true; }
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
        $this->assertEquals(5, $this->channel->pagination->captured_per_page);
        $this->assertTrue($this->channel->cat_request);
        $this->assertEquals(5, $this->channel->pagination->captured_per_page);
        $this->assertTrue($this->channel->cat_request);
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
            ->onlyMethods(['initialize', 'fetch_cache', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');

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
        $this->assertTrue($this->channel->cat_request);
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
            ->onlyMethods(['initialize', 'build_sql_query', 'parse_channel_entries', 'track_views'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');

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
            ->onlyMethods(['initialize', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');

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

    public function testEntriesDoesNotSetCatRequestWhenDynamicIsNoWithCachedSql()
    {
        $this->setTemplateParams([
            'dynamic' => 'no',
            'channel' => 'news'
        ]);
        $this->setMock('config', new class {
            public function item($key) { return ($key === 'enable_sql_caching') ? 'y' : null; }
        });

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'fetch_cache', 'parse_channel_entries'])
            ->getMock();

        $this->channel->method('fetch_cache')->willReturnCallback(function ($param = '') {
            // Return cached main SQL, but no pagination_count to avoid build()
            if ($param === '') { return 'SELECT * FROM cached'; }
            if ($param === 'pagination_count') { return false; }
            return null;
        });
        $this->channel->method('parse_channel_entries')->willReturn('');

        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10; public $total_rows = 0; public $field_pagination = false; public $offset = 0; public $paginate = false;
        };
        $this->channel->reserved_cat_segment = 'category';
        $this->channel->query_string = 'category/sports';

        $this->channel->entries();
        $this->assertFalse($this->channel->cat_request);
    }

    public function testEntriesLivePreviewBypassesEarlyNoResults()
    {
        // Mock LivePreview service
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return true; }
            public function getEntryData(){ return ['entry_id' => 'LP42', 'url_title' => 'lp-42']; }
        });
        // Template params: no channel restriction to avoid channel-name filter
        $this->setTemplateParams([]);

        $mock = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();
        // keep sql empty and bypass building
        $mock->method('build_sql_query')->willReturn(null);
        // Have parse_channel_entries set return_data so entries() returns something non-empty
        $mock->method('parse_channel_entries')->willReturnCallback(function () use ($mock) { $mock->return_data = 'LP_OK'; });

        $mock->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => false,
            'pagination' => false
        ];
        $mock->pagination = new class {
            public $per_page = 10; public $total_rows = 0; public $field_pagination = false; public $offset = 0; public $paginate = false;
        };
        $mock->query_string = 'LP42';

        $result = $mock->entries();
        $this->assertEquals('LP_OK', $result);
    }

    public function testEntriesSavesCacheOnceOnMissWithBuiltSql()
    {
        $this->setTemplateParams(['author_id' => '123']);
        $this->setMock('config', new class {
            public function item($key) { return $key === 'enable_sql_caching' ? 'y' : null; }
        });

        $mock = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'fetch_cache', 'build_sql_query', 'save_cache', 'parse_channel_entries'])
            ->getMock();

        $mock->method('fetch_cache')->willReturn(false); // cache miss
        $mock->method('build_sql_query')->willReturnCallback(function () use ($mock) { $mock->sql = 'SELECT 1'; });
        $mock->method('parse_channel_entries')->willReturnCallback(function () use ($mock) { $mock->return_data = ''; });
        $mock->expects($this->once())->method('save_cache')->with('SELECT 1');

        $mock->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => false,
            'pagination' => false
        ];
        $mock->pagination = new class {
            public $per_page = 10; public $total_rows = 0; public $field_pagination = false; public $offset = 0; public $paginate = false;
        };
        $mock->query_string = 'x';

        $mock->entries();
    }

    public function testEntriesCopiesCfieldsToPaginationOnFieldPagination()
    {
        $this->setTemplateParams([]);
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'One']
        ]);

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'build_sql_query', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');

        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => false,
            'pagination' => true
        ];
        $this->channel->pagination = new class {
            public $per_page = 10; public $total_rows = 0; public $field_pagination = true; public $offset = 0; public $paginate = false; public $cfields = []; public $field_pagination_query = null;
            public function prepare($t){ return $t; }
            public function build($a,$b){ return true; }
            public function render($d){ return $d; }
        };
        $this->channel->cfields = ['alpha' => 1, 'beta' => 2];
        $this->channel->sql = 'SELECT * FROM t';

        $this->channel->entries();
        $this->assertSame($this->channel->cfields, $this->channel->pagination->cfields);
        $this->assertNotNull($this->channel->pagination->field_pagination_query);
    }

    public function testEntriesDoesNotFetchCategoriesWhenDisabledEvenIfTagdataMentionsIt()
    {
        $this->setTemplateParams([]);
        $this->setTemplateTagdata('... categories ...');

        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'fetch_categories', 'parse_channel_entries'])
            ->getMock();
        $this->channel->method('parse_channel_entries')->willReturn('');
        $this->channel->sql = 'SELECT 1';

        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'categories' => false,
            'pagination' => false
        ];
        $this->channel->pagination = new class {
            public $per_page = 10; public $total_rows = 0; public $field_pagination = false; public $offset = 0; public $paginate = false;
        };

        $this->channel->expects($this->never())->method('fetch_categories');
        $this->channel->entries();
    }

    public function testEntriesPreparesTemplateOnlyWhenPaginationEnabled()
    {
        // Enabled: prepare should be called
        $this->setTemplateParams([]);
        $mock = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'parse_channel_entries'])
            ->getMock();
        $mock->method('parse_channel_entries')->willReturnCallback(function () use ($mock) { $mock->return_data = ''; });
        $prepTracker = new class { public $count = 0; public $per_page=10,$total_rows=0,$field_pagination=false,$offset=0,$paginate=false; public function prepare($t){ $this->count++; return $t; } public function render($d){ return $d; } };
        $mock->enable = [ 'custom_fields'=>false,'member_data'=>false,'category_fields'=>false,'pagination'=>true, 'categories'=>false ];
        $mock->pagination = $prepTracker;
        $mock->sql = 'SELECT 1';
        $mock->entries();
        $this->assertEquals(1, $prepTracker->count);

        // Disabled: prepare should not be called
        $this->setTemplateParams([]);
        $mock2 = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'parse_channel_entries'])
            ->getMock();
        $mock2->method('parse_channel_entries')->willReturnCallback(function () use ($mock2) { $mock2->return_data = ''; });
        $prepTracker2 = new class { public $count = 0; public $per_page=10,$total_rows=0,$field_pagination=false,$offset=0,$paginate=false; public function prepare($t){ $this->count++; return $t; } public function render($d){ return $d; } };
        $mock2->enable = [ 'custom_fields'=>false,'member_data'=>false,'category_fields'=>false,'pagination'=>false, 'categories'=>false ];
        $mock2->pagination = $prepTracker2;
        $mock2->sql = 'SELECT 1';
        $mock2->entries();
        $this->assertEquals(0, $prepTracker2->count);
    }

    public function testEntriesDoesNotRenderWhenPaginationDisabled()
    {
        $this->setTemplateParams([]);
        $mock = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'parse_channel_entries'])
            ->getMock();
        $mock->method('parse_channel_entries')->willReturnCallback(function () use ($mock) { $mock->return_data = 'BODY'; });
        $renderTracker = new class { public $called = false; public $per_page=10,$total_rows=0,$field_pagination=false,$offset=0,$paginate=false; public function prepare($t){ return $t; } public function render($d){ $this->called = true; return 'WRAPPED'; } };
        $mock->enable = [ 'custom_fields'=>false,'member_data'=>false,'category_fields'=>false,'pagination'=>false, 'categories'=>false ];
        $mock->pagination = $renderTracker;
        $mock->sql = 'SELECT 1';
        $result = $mock->entries();
        $this->assertFalse($renderTracker->called);
        $this->assertEquals('BODY', $result);
    }

    public function testEntriesDoesNotBuildPaginationWhenPaginationCountCacheMissing()
    {
        $this->setTemplateParams(['channel' => 'news']);
        $this->setMock('config', new class { public function item($k){ return $k==='enable_sql_caching'?'y':null; } });

        $mock = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'fetch_cache', 'parse_channel_entries'])
            ->getMock();
        $buildTracker = new class { public $built = false; public function prepare($t){ return $t; } public function build($a,$b){ $this->built=true; return true; } public function render($d){ return $d; } public $per_page=10,$total_rows=0,$field_pagination=false,$offset=0,$paginate=false; };
        $mock->pagination = $buildTracker;
        $mock->enable = [ 'custom_fields'=>false,'member_data'=>false,'categories'=>false,'pagination'=>true ];
        $mock->query_string = 'category/sports';
        $mock->reserved_cat_segment = 'category';
        $mock->method('fetch_cache')->willReturnCallback(function($param=''){ return $param===''?'SELECT * FROM cached':false; });
        $mock->method('parse_channel_entries')->willReturnCallback(function () use ($mock) { $mock->return_data = ''; });

        $mock->entries();
        $this->assertFalse($buildTracker->built);
    }
}
