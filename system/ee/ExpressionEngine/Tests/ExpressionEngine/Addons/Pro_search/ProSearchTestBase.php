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
        public function where_in($field = null, $values = null, $escape = null) { return $this; }
        public function where_not_in($field = null, $values = null, $escape = null) { return $this; }
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

class ProSearchTestBase extends TestCase
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
            public function server($index = '', $xss_clean = false) {
                return '';
            }
            public function post($index = '', $xss_clean = false) {
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
        $settings = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
        $settings->method('get')->willReturn('');
        $settings->prefix = 'pro_search_';
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
