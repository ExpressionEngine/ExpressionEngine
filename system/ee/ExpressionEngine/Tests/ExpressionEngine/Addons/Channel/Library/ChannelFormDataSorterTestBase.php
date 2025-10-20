<?php

use PHPUnit\Framework\TestCase;

// Bootstrap minimal EE environment so we can include the real class
if (!defined('APP_VER')) {
    define('APP_VER', '7.5.14');
}
if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../../../../../../');
}
if (!defined('APPPATH')) {
    define('APPPATH', BASEPATH . 'ee/');
}

// Determine addons path relative to this file
$__addons = __DIR__ . '/../../../../../Addons/';
if (!defined('PATH_ADDONS')) {
    define('PATH_ADDONS', $__addons);
}
if (!defined('PATH_PRO_ADDONS')) {
    define('PATH_PRO_ADDONS', PATH_ADDONS);
}
if (!defined('PATH_MOD')) {
    define('PATH_MOD', PATH_ADDONS);
}
if (!defined('REQ')) {
    define('REQ', 'CP');
}

// Provide a global ee() accessor backed by a mutable stdClass
if (!function_exists('ee')) {
    function ee($mock = '') {
        global $__EE_TEST_ENV__;
        if ($mock && isset($__EE_TEST_ENV__->mocks[$mock])) {
            return $__EE_TEST_ENV__->mocks[$mock];
        }
        return $__EE_TEST_ENV__;
    }
}

// Include the real Channel_form_data_sorter class
require_once PATH_ADDONS . 'channel/libraries/channel_form/Channel_form_data_sorter.php';

// Define Fake classes for individual test runs
if (!class_exists('FakeTemplate')) {
    class FakeTemplate
    {
        public $map = [];
        public $tagdata = '';
        public $tagproper = '';
        public $site_ids = [1];
        public $cache_timestamp = '';

        public function setMap(array $map): void { $this->map = $map; }
        public function setTagdata(string $tagdata): void { $this->tagdata = $tagdata; }
        public function no_results() { return 'NO_RESULTS'; }
        public function fetch_param($key, $default = null) {
            return array_key_exists($key, $this->map) ? $this->map[$key] : $default;
        }
    }
}

if (!class_exists('FakeConfig')) {
    class FakeConfig
    {
        public $items = [];
        public function item($key) { return array_key_exists($key, $this->items) ? $this->items[$key] : null; }
    }
}

if (!class_exists('FakeFunctions')) {
    class FakeFunctions
    {
        public $template_type = 'webpage'; // Prevent dynamic property deprecation warnings
        public function fetch_site_index($a = 0, $b = 0) { return '/'; }
        public function create_url($path = '') {
            if ($path) {
                return 'https://example.com/' . ltrim($path, '/');
            }
            return 'https://example.com/';
        }
    }
}

if (!class_exists('FakeDb')) {
    class FakeDb
    {
        public $rows = [];
        private $whereConditions = [];
        private $whereInConditions = [];
        private $limitValue = null;
        private $tableName = null;

        public function setRows(array $rows): void { $this->rows = $rows; }
        public function query($sql) { return new eeDbResultMock($this->rows); }
        public function where($field, $value = null) {
            if ($value === null) {
                $this->whereConditions[] = $field;
            } else {
                $this->whereConditions[$field] = $value;
            }
            return $this;
        }
        public function where_in($field, $values) {
            $this->whereInConditions[$field] = (array) $values;
            return $this;
        }
        public function limit($value) {
            $this->limitValue = $value;
            return $this;
        }
        public function from($table) {
            $this->tableName = $table;
            return $this;
        }
        public function select($columns = '*') { return $this; }
        public function distinct() { return $this; }
        public function order_by($field, $direction = 'ASC') { return $this; }
        public function get($table = null, $limit = null, $offset = null) {
            if ($table) $this->tableName = $table;
            if ($limit) $this->limitValue = $limit;
            return new eeDbResultMock($this->rows);
        }
        public function insert($table, $data = []) { return true; }
        public function update($table, $data = [], $where = []) { return true; }
        public function delete($table, $where = []) { return true; }
        public function insert_id() { return 1; }
        public function affected_rows() { return 1; }
    }
}

if (!class_exists('eeDbResultMock')) {
    class eeDbResultMock
    {
        private $rows;
        public function __construct(array $rows = []) { $this->rows = $rows; }
        public function result_array() { return $this->rows; }
        public function row_array($n = 0) { return isset($this->rows[$n]) ? $this->rows[$n] : []; }
        public function num_rows() { return count($this->rows); }
        public function free_result() { /* no-op */ }
    }
}

abstract class ChannelFormDataSorterTestBase extends TestCase
{
    protected $dataSorter;

    /**
     * Sample test data for sorting and filtering tests
     */
    protected $sampleData = [
        ['id' => 1, 'name' => 'Alice', 'age' => 25, 'score' => 85.5, 'active' => true],
        ['id' => 2, 'name' => 'Bob', 'age' => 30, 'score' => 92.0, 'active' => false],
        ['id' => 3, 'name' => 'Charlie', 'age' => 25, 'score' => 78.5, 'active' => true],
        ['id' => 4, 'name' => 'David', 'age' => 35, 'score' => 88.0, 'active' => false],
        ['id' => 5, 'name' => 'Eve', 'age' => 28, 'score' => null, 'active' => true],
    ];

    /**
     * Simple test data with edge cases
     */
    protected $simpleData = [
        ['value' => 10],
        ['value' => 5],
        ['value' => 15],
        ['value' => null],
        ['value' => 5], // duplicate
    ];

    /**
     * Mixed data types for testing
     */
    protected $mixedTypeData = [
        ['field' => 'apple'],
        ['field' => 100],
        ['field' => null],
        ['field' => 'banana'],
        ['field' => 50],
    ];

    protected function setUp(): void
    {
        // If running under core test bootstrap, use its ee() mock container
        if (function_exists('ee') && ee() !== null && method_exists(ee(), 'setMock')) {
            // Basic mocks for minimal environment
            ee()->setMock('TMPL', new FakeTemplate());
            ee()->setMock('config', new FakeConfig());
            ee()->setMock('functions', new FakeFunctions());
            ee()->setMock('db', new FakeDb());
        } else {
            // Fallback minimal env when running outside core bootstrap
            global $__EE_TEST_ENV__;
            $__EE_TEST_ENV__ = new TestEnvironment();
            $__EE_TEST_ENV__->TMPL = new FakeTemplate();
            $__EE_TEST_ENV__->config = new FakeConfig();
            $__EE_TEST_ENV__->functions = new FakeFunctions();
            $__EE_TEST_ENV__->db = new FakeDb();
        }

        // Create fresh instance for each test
        $this->dataSorter = new Channel_form_data_sorter();
    }

    protected function tearDown(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        } else {
            global $__EE_TEST_ENV__;
            if (isset($__EE_TEST_ENV__)) {
                $__EE_TEST_ENV__->mocks = [];
            }
        }
        parent::tearDown();
    }

    /**
     * Helper method to create a fresh data sorter instance
     */
    protected function createDataSorter()
    {
        return new Channel_form_data_sorter();
    }

    /**
     * Helper method to assert array is sorted in ascending order by column
     */
    protected function assertArraySortedAscending(array $array, string $column)
    {
        $values = array_column($array, $column);
        $sorted = $values;
        sort($sorted);
        $this->assertEquals($sorted, $values, "Array should be sorted ascending by {$column}");
    }

    /**
     * Helper method to assert array is sorted in descending order by column
     */
    protected function assertArraySortedDescending(array $array, string $column)
    {
        $values = array_column($array, $column);
        $sorted = $values;
        rsort($sorted);
        $this->assertEquals($sorted, $values, "Array should be sorted descending by {$column}");
    }

    /**
     * Helper method to get filtered results using the data sorter
     */
    protected function getFilteredResults(array $array, string $column, $value, string $operator = '==')
    {
        $testArray = $array; // Copy to avoid modifying original
        $this->dataSorter->filter($testArray, $column, $value, $operator);
        return array_values($testArray); // Re-index the array for easier testing
    }

    /**
     * Helper method to get sorted results using the data sorter
     */
    protected function getSortedResults(array $array, string $column, string $direction = 'asc')
    {
        $testArray = $array; // Copy to avoid modifying original
        $this->dataSorter->sort($testArray, $column, $direction);
        return $testArray;
    }
}

// Test environment class for standalone testing
if (!class_exists('TestEnvironment')) {
    class TestEnvironment
    {
        public $mocks = [];
        public $TMPL;
        public $config;
        public $functions;
        public $db;
        public $uri;
        public $session;
        public $extensions;
        public $api_channel_fields;
        public $load;
        public $cache;
        public $input;
        public $lang;
        public $legacy_api;
        public $localize;

        public function setMock($name, $mock)
        {
            $this->mocks[$name] = $mock;
            // Also set as a direct property for ee()->uri access
            $this->$name = $mock;
        }

        public function set($name, $value)
        {
            $this->$name = $value;
        }

        public function remove($name)
        {
            if (isset($this->$name)) {
                unset($this->$name);
            }
        }
    }
}
