<?php

use PHPUnit\Framework\TestCase;

// Bootstrap minimal EE environment so we can include the real class
if (!defined('APP_VER')) {
    define('APP_VER', '7.5.14');
}
if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
// Determine addons path relative to this file when core bootstrap is not used
$__addons = (defined('SYSPATH') ? (SYSPATH . 'ee/ExpressionEngine/Addons/') : (__DIR__ . '/../../../../Addons/'));
if (!defined('PATH_ADDONS')) {
    define('PATH_ADDONS', $__addons);
}
if (!defined('PATH_PRO_ADDONS')) {
    define('PATH_PRO_ADDONS', PATH_ADDONS);
}
if (!defined('PATH_MOD')) {
    define('PATH_MOD', PATH_ADDONS);
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

// Include the real Structure class (and its requires)
require_once rtrim(PATH_ADDONS, '/') . '/structure/mod.structure.php';

// Test environment class with proper method support
class TestEnvironment
{
    public $mocks = [];
    public $TMPL;
    public $config;
    public $functions;
    public $db;
    public $uri;
    
    public function setMock($name, $mock)
    {
        $this->mocks[$name] = $mock;
        // Also set as a direct property for ee()->uri access
        $this->$name = $mock;
    }
}

// Lightweight fakes to satisfy ee() dependencies
class FakeTemplate
{
    public $map = [];
    public $tagdata = '';
    
    public function setMap(array $map): void { $this->map = $map; }
    public function setTagdata(string $tagdata): void { $this->tagdata = $tagdata; }
    
    public function fetch_param($key, $default = null) {
        return array_key_exists($key, $this->map) ? $this->map[$key] : $default;
    }
    
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
        }
        // If no replacements were made, return the original tagdata
        if ($result === $tagdata) {
            return $tagdata;
        }
        return $result;
    }
}

class FakeConfig
{
    public $items = [];
    public function item($key) { return array_key_exists($key, $this->items) ? $this->items[$key] : null; }
}

class FakeFunctions
{
    public function fetch_site_index($a = 0, $b = 0) { return '/'; }
}

class FakeDbResult
{
    private $rows;
    public function __construct(array $rows) { $this->rows = $rows; }
    public function result_array() { return $this->rows; }
    public function free_result() { /* No-op for testing */ }
    public function num_rows() { return count($this->rows); }
    public function row() { return empty($this->rows) ? null : (object) $this->rows[0]; }
}

class FakeDb
{
    public $rows = [];
    private $whereConditions = [];
    private $limitValue = null;
    private $tableName = null;
    
    public function setRows(array $rows): void { $this->rows = $rows; }
    public function query($sql) { return new FakeDbResult($this->rows); }
    
    public function where($field, $value)
    {
        $this->whereConditions[$field] = $value;
        return $this;
    }
    
    public function limit($value)
    {
        $this->limitValue = $value;
        return $this;
    }
    
    public function get($table)
    {
        $this->tableName = $table;
        $filtered = $this->rows;
        
        foreach ($this->whereConditions as $field => $value) {
            $filtered = array_values(array_filter($filtered, function ($row) use ($field, $value) {
                return isset($row[$field]) && $row[$field] == $value;
            }));
        }
        
        if (!is_null($this->limitValue)) {
            $filtered = array_slice($filtered, 0, $this->limitValue);
        }
        
        // Reset conditions between calls
        $this->whereConditions = [];
        $this->limitValue = null;
        
        return new FakeDbResult($filtered);
    }
}

abstract class StructureTestBase extends TestCase
{
    protected $structure;

    protected function setUp(): void
    {
        // If running under core test bootstrap, use its ee() mock container
        if (function_exists('ee') && method_exists(ee(), 'setMock')) {
            // Template mock
            ee()->setMock('TMPL', new FakeTemplate());
            // Config mock (use provided mock's public items for convenience)
            ee()->setMock('config', new FakeConfig());
            ee()->config->items = [
                'site_id' => 1,
                'reserved_category_word' => 'category',
            ];
            // Functions mock
            ee()->setMock('functions', new FakeFunctions());
            // DB mock (core provides eeDbArMock with setRows, but ensure it's present)
            ee()->setMock('db', new FakeDb());
        } else {
            // Fallback minimal env when running outside core bootstrap
            global $__EE_TEST_ENV__;
            $__EE_TEST_ENV__ = new TestEnvironment();
            $__EE_TEST_ENV__->TMPL = new FakeTemplate();
            $__EE_TEST_ENV__->config = new FakeConfig();
            $__EE_TEST_ENV__->functions = new FakeFunctions();
            $__EE_TEST_ENV__->db = new FakeDb();
            $__EE_TEST_ENV__->config->items = [
                'site_id' => 1,
                'reserved_category_word' => 'category',
            ];
        }

        // Instantiate real class WITHOUT running its constructor
        $this->structure = (new ReflectionClass('Structure'))->newInstanceWithoutConstructor();
    }

    // Add tearDown to clean up mocks
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

    protected function setMock(string $name, $mock): void
    {
        if (function_exists('ee') && method_exists(ee(), 'setMock')) {
            ee()->setMock($name, $mock);
            return;
        }
        global $__EE_TEST_ENV__;
        $__EE_TEST_ENV__->mocks[$name] = $mock;
    }

    protected function setSqlStub($sitePages, string $uri = '', $customTitles = false, array $entryTitleMap = [], array $channelEntries = []): void
    {
        $sql = new class($sitePages, $uri, $customTitles, $entryTitleMap, $channelEntries) {
            private $sitePages; 
            private $uri; 
            private $customTitles; 
            private $entryTitleMap; 
            private $channelEntries;
            
            public function __construct($sitePages, $uri, $customTitles, $entryTitleMap, $channelEntries) {
                $this->sitePages = $sitePages; 
                $this->uri = $uri; 
                $this->customTitles = $customTitles; 
                $this->entryTitleMap = $entryTitleMap; 
                $this->channelEntries = $channelEntries;
            }
            
            public function get_site_pages() { return $this->sitePages; }
            public function get_uri() { return $this->uri; }
            public function create_custom_titles($flag = false) { return $this->customTitles; }
            public function get_entry_title($entryId) { return $this->entryTitleMap[$entryId] ?? 'Title ' . $entryId; }
            public function get_entries_by_channel($channelId) { return $this->channelEntries; }
            public function get_parent_id($entryId) { return 0; } // Default implementation
            public function get_home_page_id() { return 1; } // Default implementation
            public function get_selective_data($siteId, $entryId, $parentId, $type, $depth, $limit, $status, $include, $exclude, $showExpired, $showFuture, $showExpired2, $showFuture2) {
                return []; // Default implementation
            }
        };
        $this->structure->sql = $sql;
    }

    protected function setNsetStub(array $nodesByEntryId): void
    {
        $nset = new class($nodesByEntryId) {
            private $nodes;
            public function __construct($nodes) { $this->nodes = $nodes; }
            public function getNode($entryId) { return $this->nodes[$entryId] ?? false; }
        };
        $this->structure->nset = $nset;
    }

    protected function setTemplateParams(array $map): void
    {
        ee()->TMPL->setMap($map);
    }

    protected function setTemplateTagdata(string $tagdata): void
    {
        ee()->TMPL->setTagdata($tagdata);
    }

    protected function setDbRows(array $rows): void
    {
        ee()->db->setRows($rows);
    }
}




