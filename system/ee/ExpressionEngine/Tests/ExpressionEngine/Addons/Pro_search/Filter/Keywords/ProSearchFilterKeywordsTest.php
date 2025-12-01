<?php

require_once __DIR__ . '/../../ProSearchTestBase.php';

if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {}
    }
}

require_once __DIR__ . '/../../../../../../Addons/pro_search/filter.pro_search.php';
require_once __DIR__ . '/../../../../../../Addons/pro_search/filters/keywords/lsf.keywords.php';
require_once __DIR__ . '/../../../../../../Addons/pro_search/helpers/pro_search_helper.php';

class ProSearchFilterKeywordsTest extends ProSearchTestBase
{
    protected $filter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Params
        $params = $this->createMock('stdClass'); // Or specific mock if available
        // We need 'get' method
        if (!class_exists('Pro_search_params')) {
             eval('class Pro_search_params { public function get($key=null, $default=null){} public function set($key, $val){} public function explode($str){ return [[$str], true]; } public function site_ids(){ return [1]; } public function get_prefixed($p, $s=false){ return []; } }');
        }
        $params = $this->createMock('Pro_search_params');
        $params->method('get')->will($this->returnCallback(function($key, $default = null) {
            if ($key === 'keywords') return 'test';
            return $default;
        }));
        $params->method('site_ids')->willReturn([1]);
        $this->setMock('pro_search_params', $params);
        
        // Mock Fields
        $fields = $this->createMock('stdClass');
        if (!class_exists('Pro_search_fields')) {
             eval('class Pro_search_fields { public function name($str){} public function table($str){} }');
        }
        $fields = $this->createMock('Pro_search_fields');
        $this->setMock('pro_search_fields', $fields);
        
        // Mock Collection Model
        $colModel = $this->createMock('stdClass');
        if (!class_exists('Pro_search_collection_model')) {
             eval('class Pro_search_collection_model extends CI_Model { public function get_by_params(){} public function get_by_id(){} }');
        }
        $colModel = $this->createMock('Pro_search_collection_model');
        $colModel->method('get_by_params')->willReturn([['collection_id' => 1, 'modifier' => 1.0]]);
        $colModel->method('get_by_id')->willReturn([1 => ['collection_id' => 1, 'modifier' => 1.0]]);
        $this->setMock('pro_search_collection_model', $colModel);
        
        // Mock Index Model
        $idxModel = $this->createMock('stdClass');
        if (!class_exists('Pro_search_index_model')) {
             eval('class Pro_search_index_model extends CI_Model { public function table(){ return "exp_pro_search_indexes"; } }');
        }
        $idxModel = $this->createMock('Pro_search_index_model');
        $idxModel->method('table')->willReturn('exp_pro_search_indexes');
        $this->setMock('pro_search_index_model', $idxModel);
        
        // Mock Settings
        $settings = $this->createMock('stdClass');
        if (!class_exists('Pro_search_settings')) {
             eval('class Pro_search_settings { public function get($key){} public $prefix = "pro_search_"; }');
        }
        $settings = $this->createMock('Pro_search_settings');
        $settings->method('get')->willReturn('n');
        $settings->prefix = 'pro_search_';
        $this->setMock('pro_search_settings', $settings);
        
        // Mock Words
        $words = $this->createMock('stdClass');
        if (!class_exists('Pro_search_words')) {
             eval('class Pro_search_words { public function clean($s,$i=false){return $s;} public function remove_diacritics($s){return $s;} public function set_language($l){} }');
        }
        $words = $this->createMock('Pro_search_words');
        $words->method('clean')->will($this->returnArgument(0));
        $words->method('remove_diacritics')->will($this->returnArgument(0));
        $this->setMock('pro_search_words', $words);
        
        // Mock Multibyte
        $mb = new class {
            public function strlen($str) { return strlen($str); }
            public function substr($str, $start, $len = null) { return substr($str, $start, $len); }
            public function strtolower($str) { return strtolower($str); }
        };
        $this->setMock('pro_multibyte', $mb);
        
        // Mock Loader
        $loader = $this->createMock('stdClass');
        // ee()->load->library() is called in constructor.
        // We mocked 'load' in ProSearchTestBase as an object with __call or methods.
        // ProSearchTestBase sets 'load' to an object with library method.
        // So it should be fine.
        
        $this->filter = new Pro_search_filter_keywords();
    }

    public function testFilter()
    {
        $db = $this->createMock('ProSearchDbMock');
        $db->method('select')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('join')->willReturnSelf();
        $db->method('where_in')->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->method('having')->willReturnSelf();
        
        $result = $this->createMock('eeDbResultMock');
        $result->num_rows = 1;
        $result->method('result_array')->willReturn([
            ['entry_id' => 10, 'collection_id' => 1, 'score' => 5]
        ]);
        $result->method('result')->willReturn([
            (object)['entry_id' => 10, 'collection_id' => 1, 'score' => 5, 'index_text' => 'test content']
        ]);
        
        $db->method('get')->willReturn($result);
        
        $this->setMock('db', $db);
        
        $entry_ids = $this->filter->filter(null);
        
        $this->assertIsArray($entry_ids);
        $this->assertContains(10, $entry_ids);
    }
    
    public function testNoResults()
    {
        $db = $this->createMock('ProSearchDbMock');
        $db->method('select')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('where_in')->willReturnSelf();
        $db->method('where')->willReturnSelf();
        
        $result = $this->createMock('eeDbResultMock');
        $result->num_rows = 0; // No results
        $result->method('result_array')->willReturn([]);
        
        $db->method('get')->willReturn($result);
        
        $this->setMock('db', $db);
        
        $entry_ids = $this->filter->filter(null);
        
        $this->assertIsArray($entry_ids);
        $this->assertEmpty($entry_ids);
    }
}

