<?php

use PHPUnit\Framework\TestCase;

if (!defined('APP_VER')) {
    define('APP_VER', '7.0.0');
}
if (!defined('BASEPATH')) {
    define('BASEPATH', '/path/to/system/');
}
if (!defined('PATH_ADDONS')) {
    define('PATH_ADDONS', __DIR__ . '/../../../../Addons/');
}
if (!defined('PATH_PRO_ADDONS')) {
    define('PATH_PRO_ADDONS', PATH_ADDONS);
}
if (!defined('PATH_MOD')) {
    define('PATH_MOD', PATH_ADDONS);
}
if (!defined('NL')) {
    define('NL', "\n");
}

if (!class_exists('CI_Model')) {
    class CI_Model
    {
        public function __construct() {}
    }
}

// Pre-define eeDbResultMock to support num_rows property
if (!class_exists('eeDbResultMock')) {
    class eeDbResultMock
    {
        public $resultArray;
        public $rows = [];
        public $num_rows = 0;

        public function __construct($resultArray = [])
        {
            $this->resultArray = $resultArray;
            $this->rows = $resultArray;
            $this->num_rows = count($resultArray);
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
        public function row(?string $column = null)
        {
            if (empty($this->rows)) {
                return null;
            }
            $row = (object) $this->rows[0];
            if ($column !== null) {
                if (isset($row->$column)) {
                    return $row->$column;
                } else {
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
            return (array) $this->rows[0];
        }
        public function free_result()
        {
        }
    }
}

// Include the EE mock object
require_once __DIR__ . '/../../../eeObjectMock.php';

// Helper classes to avoid dynamic property deprecations in PHP 8.2+
if (!class_exists('Pro_search_params_test')) {
    class Pro_search_params_test
    {
        public $forget = [];
        public function get(?string $key = null, $default = null) { return $default; }
        public function set($key, $val) {}
        public function explode($str) { return [[$str], true]; }
        public function site_ids() { return [1]; }
        public function get_prefixed($p, $s = false) { return []; }
        public function prep($key, $val) { return $val; }
        public function implode($arr) { return implode('|', $arr); }
        public function merge($arr) { return []; }
        public function overwrite($arr) {}
        public function reset() {}
    }
}

if (!class_exists('Pro_search_settings_test')) {
    class Pro_search_settings_test
    {
        public $prefix = 'pro_search_';
        public function get($key) { return ''; }
        public function stop_words() { return []; }
        public function ignore_words() { return []; }
    }
}

if (!class_exists('Uri_test')) {
    class Uri_test
    {
        public $page_query_string = '';
        public $query_string = '';
        public $uri_string = '';
        public function uri_string() { return $this->uri_string; }
    }
}

if (!class_exists('Pagination_test')) {
    class Pagination_test
    {
        public $paginate = false;
        public $uri_string = '';
        public $field_pagination = false;
        public $per_page = 10;
        public $offset = 0;
        public function create() { return $this; }
        public function prepare($str) { return $str; }
    }
}

// Custom FakeDb to return result with num_rows property
class ProSearchFakeDb extends FakeDb
{
    public $dbprefix = 'exp_';

    public function get($table = null)
    {
        $result = parent::get($table);
        return new ProSearchDbResult($result->result_array());
    }

    public function query($sql)
    {
        $result = parent::query($sql);
        return new ProSearchDbResult($result->result_array());
    }
    
    public function escape_like_str($str)
    {
        return addcslashes($str, '%_');
    }
}

class ProSearchDbResult extends eeDbResultMock
{
    public $num_rows = 0;

    public function __construct($resultArray = [])
    {
        parent::__construct($resultArray);
        $this->num_rows = count($resultArray);
    }
}

if (!class_exists('ProSearchDbMock')) {
    class ProSearchDbMock extends eeDbArMock
    {
        public $dbprefix = 'exp_';
        
        public function like($field, $match = '', $side = 'both') { return $this; }
        public function or_like($field, $match = '', $side = 'both') { return $this; }
        public function where_in(?string $field = null, $values = null, $escape = null) { return $this; }
        public function where_not_in(?string $field = null, $values = null, $escape = null) { return $this; }
        public function distinct($val = true) { return $this; }
        public function group_by($by) { return $this; }
        public function insert_string($table, $data) { return "INSERT INTO $table ..."; }
            public function escape_str($str, $like = false) { return $str; }
            public function escape_like_str($str) { return addcslashes($str, '%_'); }
            public function join($table, $cond, $type = '') { return $this; }
        public function having($key, $val = '', $escape = true) { return $this; }
        public function field_exists($field, $table) { return true; }
        
        public function get($table = null)
        {
            $result = parent::get($table);
            return new ProSearchDbResult($result->result_array());
        }

        public function query($sql)
        {
            $result = parent::query($sql);
            return new ProSearchDbResult($result->result_array());
        }
    }
}

// Helper loading might be needed if not loaded by the class under test
if (!function_exists('pro_search_decode')) {
    require_once PATH_ADDONS . 'pro_search/helpers/pro_search_helper.php';
}

abstract class ProSearchTestBase extends TestCase
{
    protected function setUp(): void
    {
        // Reset mocks
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }

        // Mock TMPL with Pro Search specific extensions
        $tmpl = new class extends FakeTemplate {
            public $search_fields = [];
            public $tagparts = ['exp', 'pro_search', 'results'];
            public $no_results = 'NO_RESULTS';
            
            public function parse_variables_row($tagdata, $vars) {
                $result = $tagdata;
                if (is_array($vars)) {
                    foreach ($vars as $key => $val) {
                        $valStr = is_scalar($val) ? (string)$val : '';
                        $result = str_replace('{' . $key . '}', $valStr, $result);
                    }
                }
                return $result;
            }
            
            public function parse_variables($tagdata, $vars) {
                return parent::parse_variables($tagdata, $vars);
            }
        };
        ee()->setMock('TMPL', $tmpl);

        // Mock Config
        $config = new FakeConfig();
        ee()->setMock('config', $config);

        // Mock DB using our custom class
        $db = new ProSearchFakeDb();
        ee()->setMock('db', $db);

        // Mock Input with additional methods
        $input = new class extends eeSingletonInputMock {
            public function server(string $index = '', bool $xss_clean = false) {
                return '';
            }
            public function post(string $index = '', bool $xss_clean = false) {
                return null;
            }
        };
        ee()->setMock('input', $input);

        // Mock Loader with additional methods needed by Pro Search
        $load = $this->getMockBuilder('eeSingletonLoadMock')
            ->addMethods(['add_package_path'])
            ->getMock();
        $load->method('add_package_path')->willReturn(null);
        ee()->setMock('load', $load);

        // Mock Extensions
        $extensions = $this->getMockBuilder('stdClass')
            ->addMethods(['active_hook', 'call'])
            ->getMock();
        $extensions->method('active_hook')->willReturn(false);
        $extensions->method('call')->willReturn(null);
        ee()->setMock('extensions', $extensions);

        // Mock Pro Search Settings (often used)
        // Ensure stop_words() and ignore_words() return arrays for PHP 7.4 compatibility
        // Use test class with prefix property to avoid dynamic property deprecation in PHP 8.2+
        $settings = $this->getMockBuilder('Pro_search_settings_test')
            ->onlyMethods(['get', 'stop_words', 'ignore_words'])
            ->getMock();
        $settings->method('get')->willReturn('');
        $settings->method('stop_words')->willReturn([]); // Return empty array for PHP 7.4 compatibility
        $settings->method('ignore_words')->willReturn([]); // Return empty array for PHP 7.4 compatibility
        ee()->setMock('pro_search_settings', $settings);

        // Mock Pro Multibyte (often used)
        $multibyte = $this->getMockBuilder('stdClass')
            ->addMethods(['strpos', 'substr', 'strlen', 'strtolower', 'strtoupper'])
            ->getMock();
        
        $multibyte->method('strpos')->will($this->returnCallback('mb_strpos'));
        $multibyte->method('substr')->will($this->returnCallback('mb_substr'));
        $multibyte->method('strlen')->will($this->returnCallback('mb_strlen'));
        $multibyte->method('strtolower')->will($this->returnCallback('mb_strtolower'));
        $multibyte->method('strtoupper')->will($this->returnCallback('mb_strtoupper'));

        ee()->setMock('pro_multibyte', $multibyte);
        
        // Mock Pro Search Words (needed by helpers)
        $words = $this->getMockBuilder('stdClass')
            ->addMethods(['clean', 'remove_diacritics'])
            ->getMock();
        $words->method('clean')->will($this->returnArgument(0));
        $words->method('remove_diacritics')->will($this->returnArgument(0));
        ee()->setMock('pro_search_words', $words);
        
        // Mock Pro Search Params (needed by many models)
        $params = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'explode', 'site_ids'])
            ->getMock();
        $params->method('explode')->will($this->returnCallback(function($str) {
             return [explode('|', $str), true];
        }));
        ee()->setMock('pro_search_params', $params);
    }

    protected function tearDown(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
    }

    protected function setMock(string $name, $mock): void
    {
        if (function_exists('ee') && method_exists(ee(), 'setMock')) {
            ee()->setMock($name, $mock);
        }
    }
}
