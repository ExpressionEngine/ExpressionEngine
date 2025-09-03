<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

// This allows both ee()-> singleton mocks to allow unit testing of methods that rely on it
// as well as ee('Foo') dependency container objects.
//
// Singleton:
// load/config/etc can be stub classes with stub methods
//
// App container:
// In your test, you must define the return value for requested object:
//
//      ee()->setMock('Encrypt', new Encrypt\Encrypt('ADefaultKey'));
//
// Then any calls from the application to ee('Encrypt') will return the object / return value you specified.
// In the test's tearDown() method, it should then reset the mocks so the next test does not inherit your mocks
//
//      ee()->resetMocks();
//
function ee($mock = '')
{
    return new eeSingletonMock($mock);
}

class eeSingletonMock
{
    public $load;
    public $config;
    public $session;
    public $logger;
    public $dbforge;
    public $db;
    public $input;
    public $lang;
    public $legacy_api;
    public $typography;

    protected $mock;
    protected static $mocks = [];

    public function __construct($mock = '')
    {
        $this->load = new eeSingletonLoadMock();
        $this->config = new eeSingletonConfigMock();
        $this->session = new eeSingletonSessionMock();
        $this->logger = new eeSingletonLoggerMock();
        $this->dbforge = new eeSingletonDBForgeMock();
        $this->input = new eeSingletonInputMock();
        $this->db = new eeDbArMock();
        $this->lang = new eeLangMock();
        $this->typography = new FakeTypography();
        require_once APPPATH . 'libraries/Api.php';
        $this->legacy_api = new \Api();
        $this->mock = $mock;

        // Override with static mocks if set
        $overridable = ['db', 'config', 'functions', 'TMPL', 'session', 'load', 'logger', 'dbforge', 'input', 'lang', 'typography'];
        foreach ($overridable as $prop) {
            if (array_key_exists($prop, self::$mocks)) {
                @$this->$prop = self::$mocks[$prop];
            }
        }
    }

    public function setMock($name, $return)
    {
        self::$mocks[$name] = $return;
    }

    public function resetMocks()
    {
        self::$mocks = [];
        $this->mock = '';
    }

    public function __get($name)
    {
        if (array_key_exists($this->mock, self::$mocks) && !is_null(self::$mocks[$this->mock]->$name)) {
            return self::$mocks[$this->mock]->$name;
        }
        if (array_key_exists($name, self::$mocks) && !is_null(self::$mocks[$name])) {
            return self::$mocks[$name];
        }
    }

    public function __call($name, $args)
    {
        if (array_key_exists($this->mock, self::$mocks) && method_exists(self::$mocks[$this->mock], $name)) {
            return call_user_func_array([self::$mocks[$this->mock], $name], $args);
        }
    }
}

class eeSingletonLoadMock
{
    public function helper()
    {
        return;
    }

    public function library()
    {
        return;
    }

    public function model()
    {
        return;
    }

    public function dbforge()
    {
        return;
    }
}

class eeSingletonConfigMock
{
    protected static $config = [];
    public $items = [];

    public function item($item, $index = '', $raw_value = false)
    {
        return (isset(self::$config[$item])) ? self::$config[$item] : false;
    }

    public function setItem($item, $value)
    {
        self::$config[$item] = $value;
    }

    public function resetConfig()
    {
        self::$config = [];
    }
}

class eeSingletonSessionMock
{
    public static $userdata = [];

    public function userdata($item, $default = false)
    {
        return (! isset(self::$userdata[$item])) ? $default : self::$userdata[$item];
    }

    public function setUserdata($item, $value)
    {
        self::$userdata[$item] = $value;
    }

    public function resetUserdata()
    {
        self::$userdata = [];
    }

    public function getMember()
    {
        return new class {
            public function getAssignedChannels() {
                return new class {
                    public function getDictionary($key, $value) {
                        return ['channel_1' => 'Channel One', 'channel_2' => 'Channel Two'];
                    }
                };
            }
        };
    }
}

// Mock EE_Session class for Channel_form_session tests
if (!class_exists('EE_Session')) {
    class EE_Session
    {
        public $userdata = [];

        public function __construct()
        {
            // Initialize with basic session data
            $this->userdata = [
                'member_id' => 0,
                'group_id' => 0,
                'ip_address' => '127.0.0.1'
            ];
        }

        public function userdata($key, $default = false)
        {
            return isset($this->userdata[$key]) ? $this->userdata[$key] : $default;
        }
    }
}

class eeSingletonLoggerMock
{
    public function developer()
    {
    }
}

class eeSingletonDBForgeMock
{
    public function add_field()
    {
    }

    public function add_key()
    {
    }
    public function create_table()
    {
    }
}

class eeSingletonInputMock
{
    public function get_post($item)
    {
    }

    public function get($item)
    {
        // Mock implementation - return null for most cases
        return null;
    }
}

class eeLangMock
{
    public function load($item)
    {
    }
    public function loadfile($name)
    {
    }
}

class eeDbArMock
{
    public $rows = [];
    private $whereConditions = [];
    private $limitValue = null;
    public function setRows(array $rows)
    {
        $this->rows = $rows;
        return $this;
    }
    public function where($field = null, $value = null)
    {
        if ($field !== null) {
            $this->whereConditions[$field] = $value;
        }
        return $this;
    }
    public function limit($value = null)
    {
        $this->limitValue = $value;
        return $this;
    }
    public function select()
    {
        return $this;
    }
    public function from()
    {
        return $this;
    }
    public function order()
    {
        return $this;
    }

    public function get()
    {
        $filtered = $this->rows;
        foreach ($this->whereConditions as $field => $value) {
            $filtered = array_values(array_filter($filtered, function ($row) use ($field, $value) {
                return isset($row[$field]) && $row[$field] == $value;
            }));
        }
        if (!is_null($this->limitValue)) {
            $filtered = array_slice($filtered, 0, $this->limitValue);
        }

        // reset conditions between calls
        $this->whereConditions = [];
        $this->limitValue = null;
        return new eeDbResultMock($filtered);
    }
    public function query($sql)
    {
        $this->last_query = $sql;



        // Return different mock data based on the SQL query
        if ((strpos($sql, 'exp_channel_titles') !== false || strpos($sql, 'FROM exp_channel_titles') !== false) &&
            (strpos($sql, 'year(FROM_UNIXTIME') !== false || strpos($sql, 'MONTH(FROM_UNIXTIME') !== false)) {
            // For month_links query, return year/month data
            return new eeDbResultMock([
                ['year' => '2024', 'month' => '1']
            ]);
        } elseif (strpos($sql, 'exp_channel_titles') !== false || strpos($sql, 'FROM exp_channel_titles') !== false) {
            // For other channel titles queries, return entry data
            return new eeDbResultMock([
                ['entry_id' => '123', 'url_title' => 'sample-article', 'channel_id' => '1']
            ]);
        } elseif (strpos($sql, 'exp_categories') !== false) {
            // For categories query, return category data
            return new eeDbResultMock([
                ['cat_id' => '5', 'cat_url_title' => 'sample-category']
            ]);
        } else {
            // Default fallback
            return new eeDbResultMock($this->rows);
        }
    }

    public function last_query()
    {
        return $this->last_query ?? '';
    }
}

class eeDbResultMock
{
    public $resultArray;
    public $rows = [];
    public function __construct($resultArray = [])
    {
        $this->resultArray = $resultArray;
        $this->rows = $resultArray;
    }
    public function result()
    {
        $result = [];
        foreach ($this->resultArray as $row) {
            $result[] = (object) $row;
        }
        return $result;
    }
    public function num_rows()
    {
        return count($this->rows);
    }
    public function result_array()
    {
        return $this->rows;
    }
    public function row($column = null)
    {
        if (empty($this->rows)) {
            return null;
        }
        $row = (object) $this->rows[0];
        if ($column !== null) {
            if (isset($row->$column)) {
                return $row->$column;
            } else {
                // Return null for missing columns instead of the entire row object
                return null;
            }
        }
        return $row;
    }
    public function row_array()
    {
        if (empty($this->rows)) {
            return [];
        }
        return $this->rows[0];
    }
    public function free_result()
    {
        // no-op for tests
    }
}

// Enhanced fake classes for broader test compatibility
class FakeTemplate
{
    public $map = [];
    public $tagdata = '';
    public $tagproper = '';
    public $site_ids = [1];
    public $cache_timestamp = '';
    public $var_single = [];

    public function setMap(array $map): void { $this->map = $map; }
    public function setTagdata(string $tagdata): void { $this->tagdata = $tagdata; }
    public function no_results() { return 'NO_RESULTS'; }
    public function fetch_param($key, $default = null) {
        return array_key_exists($key, $this->map) ? $this->map[$key] : $default;
    }
    public function set_data($data) { /* no-op */ }
    public function parse_variables($tagdata, $vars) {
        // Simple variable replacement for testing
        $result = $tagdata;
        foreach ($vars as $var) {
            if (isset($var['prev']) && is_array($var['prev'])) {
                foreach ($var['prev'] as $prev) {
                    $result = str_replace('{prev_title}', $prev['title'], $result);
                    $result = str_replace('{prev_url}', $prev['url'], $result);
                    $result = str_replace('{prev_entry_id}', $prev['entry_id'], $result);
                }
            }
            if (isset($var['next']) && is_array($var['next'])) {
                foreach ($var['next'] as $next) {
                    $result = str_replace('{next_title}', $next['title'], $result);
                    $result = str_replace('{next_url}', $next['url'], $result);
                    $result = str_replace('{next_entry_id}', $next['entry_id'], $result);
                }
            }

            // Handle simple variables like {title}
            if (isset($var['title'])) {
                $result = str_replace('{title}', $var['title'], $result);
            }
            if (isset($var['entry_id'])) {
                $result = str_replace('{entry_id}', $var['entry_id'], $result);
            }
            if (isset($var['url_title'])) {
                $result = str_replace('{url_title}', $var['url_title'], $result);
            }
            if (isset($var['channel_short_name'])) {
                $result = str_replace('{channel_short_name}', $var['channel_short_name'], $result);
            }
            if (isset($var['channel'])) {
                $result = str_replace('{channel}', $var['channel'], $result);
            }
            if (isset($var['channel_url'])) {
                $result = str_replace('{channel_url}', $var['channel_url'], $result);
            }
        }
        return $result;
    }

    public function log_item($str)
    {
        // Simple no-op for logging in tests
        return true;
    }

    public function swap_var_single($variable, $replacement, $source)
    {
        // Handle both braced and unbraced variables
        if (strpos($variable, '{') === 0 && strrpos($variable, '}') === strlen($variable) - 1) {
            // Variable already has braces, use as-is
            return str_replace($variable, $replacement, $source);
        } else {
            // Variable doesn't have braces, add them
            return str_replace('{' . $variable . '}', $replacement, $source);
        }
    }

    public function delete_var_pairs($opening, $closing, $source)
    {
        // Simple implementation - remove the variable pair tags
        $pattern = '/' . preg_quote($opening, '/') . '.*?' . preg_quote($closing, '/') . '/s';
        return preg_replace($pattern, '', $source);
    }

    public function swap_var_pairs($opening, $closing, $source)
    {
        // Simple implementation - for testing, just return the source
        return $source;
    }

    public $var_pair = [];
}

class FakeConfig
{
    public $items = [];
    public function item($key) { return array_key_exists($key, $this->items) ? $this->items[$key] : null; }
    public function get_cached_site_prefs($site_id = 1) {
        // Return default site preferences for testing
        return [
            'site_name' => 'Test Site',
            'site_url' => 'https://example.com/',
            'site_index' => '',
            'template_group' => 'default',
            'template' => 'index'
        ];
    }
}

class FakeTypography
{
    public function format_characters($str) {
        // Simple mock implementation - just return the string as-is
        return $str;
    }

    public function formatTitle($str) {
        // Simple mock implementation - just return the string as-is
        return $str;
    }

    public function initialize($config = []) {
        // Simple mock implementation - do nothing
        return true;
    }
}

class FakeFunctions
{
    public function fetch_site_index($a = 0, $b = 0) { return '/'; }
    public function create_url($path = '') {
        // Handle dynamic path generation for path variables
        if ($path) {
            return 'https://example.com/' . ltrim($path, '/');
        }
        return 'https://example.com/';
    }
    public function sql_andor_string($str, $field, $prefix = '', $null_check = true) {
        // Simple mock implementation for AND/OR SQL generation
        if (strpos($str, '|') !== false) {
            $parts = explode('|', $str);
            $conditions = [];
            foreach ($parts as $part) {
                $conditions[] = "$field = '$part'";
            }
            return ' AND (' . implode(' OR ', $conditions) . ')';
        } else {
            return " AND $field = '$str'";
        }
    }

    public function ar_andor_string($str, $field, $prefix = '', $null_check = true) {
        // Alias for sql_andor_string
        return $this->sql_andor_string($str, $field, $prefix, $null_check);
    }

    public function assign_parameters($param_string)
    {
        $params = [];
        $param_string = trim($param_string);
        if ($param_string === '') {
            return $params;
        }
        // Very simple key="value" parser
        preg_match_all('/(\w+)\s*=\s*"([^"]*)"/', $param_string, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $params[$m[1]] = $m[2];
        }
        return $params;
    }
    public function prep_conditionals($str, $vars = [])
    {
        // Simple mock implementation - just return the string
        return $str;
    }



    public function extract_path($variable)
    {
        // Simple mock implementation for path extraction
        if (strpos($variable, ':') !== false) {
            return str_replace('path:', '', $variable);
        }
        return $variable;
    }

    public function trim_slashes($str)
    {
        // Simple mock implementation of trim_slashes function
        return trim($str, '/');
    }
}

class FakeDb
{
    public $rows = [];
    private $whereConditions = [];
    private $whereInConditions = [];
    private $limitValue = null;
    private $tableName = null;

    public function setRows(array $rows): void { $this->rows = $rows; }
    public function query($sql) { return new eeDbResultMock($this->rows); }

    public function where($field, $value = null)
    {
        if ($value === null) {
            // Handle single argument case - treat $field as a condition
            $this->whereConditions[] = $field;
        } else {
            $this->whereConditions[$field] = $value;
        }
        return $this;
    }

    public function where_in($field, $values)
    {
        $this->whereInConditions[$field] = (array) $values;
        return $this;
    }

    public function join($table, $condition, $type = '')
    {
        return $this;
    }

    public function limit($value)
    {
        $this->limitValue = $value;
        return $this;
    }

    public function select($fields = '*')
    {
        return $this;
    }

    public function from($table)
    {
        $this->tableName = $table;
        return $this;
    }

    public function order_by($field, $direction = '')
    {
        return $this;
    }

    public function escape_str($str)
    {
        return addslashes($str);
    }

    public function get($table = null)
    {
        if ($table) {
            $this->tableName = $table;
        }
        $filtered = $this->rows;

        foreach ($this->whereConditions as $field => $value) {
            // Handle table prefixes (e.g., "t.entry_id" should match "entry_id")
            $fieldWithoutPrefix = strpos($field, '.') !== false ? substr($field, strpos($field, '.') + 1) : $field;
            $filtered = array_values(array_filter($filtered, function ($row) use ($fieldWithoutPrefix, $value) {
                return isset($row[$fieldWithoutPrefix]) && $row[$fieldWithoutPrefix] == $value;
            }));
        }
        foreach ($this->whereInConditions as $field => $values) {
            // Handle table prefixes (e.g., "w.site_id" should match "site_id")
            $fieldWithoutPrefix = strpos($field, '.') !== false ? substr($field, strpos($field, '.') + 1) : $field;
            $filtered = array_values(array_filter($filtered, function ($row) use ($fieldWithoutPrefix, $values) {
                return isset($row[$fieldWithoutPrefix]) && in_array($row[$fieldWithoutPrefix], $values);
            }));
        }

        if (!is_null($this->limitValue)) {
            $filtered = array_slice($filtered, 0, $this->limitValue);
        }

        // Reset conditions between calls
        $this->whereConditions = [];
        $this->whereInConditions = [];
        $this->limitValue = null;

        return new eeDbResultMock($filtered);
    }
}

// Test environment class with proper method support
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
        @$this->$name = $mock;
    }

    public function set($name, $value)
    {
        @$this->$name = $value;
    }

    public function remove($name)
    {
        if (isset($this->$name)) {
            unset($this->$name);
        }
    }
}