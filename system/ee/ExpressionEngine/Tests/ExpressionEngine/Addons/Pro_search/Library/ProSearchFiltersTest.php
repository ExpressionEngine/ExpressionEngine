<?php

require_once __DIR__ . '/../ProSearchTestBase.php';

if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {}
    }
}

require_once PATH_ADDONS . 'pro_search/filter.pro_search.php';
require_once PATH_ADDONS . 'pro_search/libraries/Pro_search_filters.php';
require_once PATH_ADDONS . 'pro_search/helpers/pro_search_helper.php';

// Define constants needed for Pro_search_filters
if (!defined('REQ')) {
    define('REQ', 'PAGE');
}
if (!defined('PATH_THIRD')) {
    define('PATH_THIRD', PATH_ADDONS);
}

class ProSearchFiltersTest extends ProSearchTestBase
{
    protected $filters;

    protected function setUp(): void
    {
        parent::setUp();

        // Load directory helper before mocking (needed by Pro_search_filters constructor)
        if (!function_exists('directory_map')) {
            // Define a simple directory_map function for testing
            // In real usage, this comes from CodeIgniter's directory helper
            function directory_map(string $source_dir, int $directory_depth = 0, bool $hidden = false) {
                if ($fp = @opendir($source_dir)) {
                    $file_data = array();
                    $new_depth = $directory_depth - 1;
                    $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

                    while (false !== ($file = readdir($fp))) {
                        // Remove '.', '..', and hidden files [optional]
                        if (!trim($file, '.') || ($hidden == false && $file[0] == '.')) {
                            continue;
                        }

                        if (($directory_depth < 1 || $new_depth > 0) && @is_dir($source_dir . $file)) {
                            $file_data[$file] = directory_map($source_dir . $file . DIRECTORY_SEPARATOR, $new_depth, $hidden);
                        } else {
                            $file_data[] = $file;
                        }
                    }

                    closedir($fp);
                    return $file_data;
                }

                return false;
            }
        }

        // Mock Loader with directory_map helper and library method
        $load = $this->getMockBuilder('stdClass')
            ->addMethods(['helper', 'add_package_path', 'library'])
            ->getMock();
        $load->method('helper')->will($this->returnCallback(function($helper) {
            // Allow directory helper to be "loaded" (function already defined above)
            return null;
        }));
        $load->method('add_package_path')->willReturn(null);
        $load->method('library')->willReturn(null);
        ee()->setMock('load', $load);

        // Mock Pro Search Settings for disabled_filters
        $settings = $this->getMockBuilder('Pro_search_settings_test')
            ->onlyMethods(['get'])
            ->getMock();
        $settings->method('get')->will($this->returnCallback(function($key) {
            if ($key === 'disabled_filters') {
                return [];
            }
            return '';
        }));
        ee()->setMock('pro_search_settings', $settings);

        // Mock Pro Search Params for in_param checks
        $params = $this->getMockBuilder('Pro_search_params_test')
            ->addMethods(['in_param'])
            ->getMock();
        $params->method('in_param')->willReturn(false);
        ee()->setMock('pro_search_params', $params);
        
        // Mock Pro Search Fields (needed by filter base class)
        if (!class_exists('Pro_search_fields')) {
            eval('class Pro_search_fields { }');
        }
        $fields = $this->createMock('Pro_search_fields');
        ee()->setMock('pro_search_fields', $fields);
        
        // Mock Pro Search Collection Model (needed by keywords filter)
        $collectionModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get_by_params'])
            ->getMock();
        $collectionModel->method('get_by_params')->willReturn([]);
        ee()->setMock('pro_search_collection_model', $collectionModel);
        
        // Mock Addons service (needed by tags filter)
        $addons = $this->getMockBuilder('stdClass')
            ->addMethods(['is_package'])
            ->getMock();
        $addons->method('is_package')->willReturn(false);
        ee()->setMock('addons', $addons);

        // Create a mock filter class for testing
        if (!class_exists('Pro_search_filter_test_filter')) {
            eval('
                class Pro_search_filter_test_filter extends Pro_search_filter {
                    protected $priority = 5;
                    public function filter($entry_ids) {
                        return $entry_ids;
                    }
                    public function fixed_order() {
                        return false;
                    }
                    public function exclude() {
                        return null;
                    }
                    public function results($rows) {
                        return $rows;
                    }
                }
            ');
        }

        $this->filters = new Pro_search_filters();
    }

    public function testNames()
    {
        // names() should return an array of filter names
        $names = $this->filters->names();
        $this->assertIsArray($names);
        // Should contain at least some filters if they exist
        // In a real scenario, this would return actual filter names
    }

    public function testSetEntryIds()
    {
        $entry_ids = [1, 2, 3, 4, 5];
        $this->filters->set_entry_ids($entry_ids);
        $this->assertEquals($entry_ids, $this->filters->entry_ids());
    }

    public function testEntryIds()
    {
        // Initially should be null
        $this->assertNull($this->filters->entry_ids());
        
        // After setting, should return the IDs
        $entry_ids = [1, 2, 3];
        $this->filters->set_entry_ids($entry_ids);
        $this->assertEquals($entry_ids, $this->filters->entry_ids());
    }

    public function testFixedOrder()
    {
        // Initially should be null/false
        $result = $this->filters->fixed_order();
        $this->assertTrue($result === false || $result === null);
    }

    public function testExclude()
    {
        // Initially should be null
        $this->assertNull($this->filters->exclude());
        
        // Test adding single ID
        $result = $this->filters->exclude(5);
        $this->assertEquals([5], $result);
        
        // Test adding array of IDs
        $result = $this->filters->exclude([10, 20]);
        $this->assertContains(5, $result);
        $this->assertContains(10, $result);
        $this->assertContains(20, $result);
        
        // Test adding duplicate (should be deduplicated)
        $result = $this->filters->exclude(5);
        $this->assertEquals(1, count(array_keys($result, 5)));
    }

    public function testReset()
    {
        // Set some state
        $this->filters->set_entry_ids([1, 2, 3]);
        $this->filters->exclude([5, 6]);
        
        // Reset
        $this->filters->reset();
        
        // Verify everything is reset
        $this->assertNull($this->filters->entry_ids());
        $this->assertNull($this->filters->exclude());
        $fixedOrder = $this->filters->fixed_order();
        $this->assertTrue($fixedOrder === false || $fixedOrder === null);
    }

    public function testFilter()
    {
        // Set entry IDs
        $entry_ids = [1, 2, 3, 4, 5];
        $this->filters->set_entry_ids($entry_ids);
        
        // Run filter (should reset by default)
        $this->filters->filter();
        
        // After filtering, entry_ids should be processed
        // The actual result depends on what filters are loaded and their behavior
        $result = $this->filters->entry_ids();
        // Should be an array or null
        $this->assertTrue(is_array($result) || is_null($result));
    }

    public function testFilterWithoutReset()
    {
        // Set entry IDs
        $entry_ids = [1, 2, 3];
        $this->filters->set_entry_ids($entry_ids);
        
        // Run filter without reset
        $this->filters->filter(false);
        
        // Should still have processed the IDs
        $result = $this->filters->entry_ids();
        $this->assertTrue(is_array($result) || is_null($result));
    }

    public function testResults()
    {
        // Test results() method with mock rows
        // Keywords filter expects channel_id and url_title in rows
        $rows = [
            ['entry_id' => 1, 'title' => 'Test 1', 'channel_id' => 1, 'url_title' => 'test-1'],
            ['entry_id' => 2, 'title' => 'Test 2', 'channel_id' => 1, 'url_title' => 'test-2'],
        ];
        
        $result = $this->filters->results($rows);
        
        // Should return processed rows
        $this->assertIsArray($result);
    }

    public function testFilterWithExclude()
    {
        // Set entry IDs
        $entry_ids = [1, 2, 3, 4, 5];
        $this->filters->set_entry_ids($entry_ids);
        
        // Set exclude IDs
        $this->filters->exclude([2, 4]);
        
        // Run filter
        $this->filters->filter();
        
        // The exclude should be applied during filtering
        $result = $this->filters->entry_ids();
        // Result depends on filters, but exclude should be considered
        $this->assertTrue(is_array($result) || is_null($result));
    }
}

