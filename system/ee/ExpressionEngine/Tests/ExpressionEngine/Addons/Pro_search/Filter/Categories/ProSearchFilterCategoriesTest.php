<?php

require_once __DIR__ . '/../../ProSearchTestBase.php';

if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {}
    }
}

require_once __DIR__ . '/../../../../../../Addons/pro_search/filter.pro_search.php';
require_once __DIR__ . '/../../../../../../Addons/pro_search/filters/categories/lsf.categories.php';
require_once __DIR__ . '/../../../../../../Addons/pro_search/helpers/pro_search_helper.php';

class ProSearchFilterCategoriesTest extends ProSearchTestBase
{
    protected $filter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Params
        $params = $this->createMock('stdClass');
        if (!class_exists('Pro_search_params')) {
             eval('class Pro_search_params { 
                 public $forget = [];
                 public function get($key=null, $default=null){} 
                 public function set($key, $val){} 
                 public function explode($str){ return [[$str], true]; } 
                 public function site_ids(){ return [1]; } 
                 public function get_prefixed($p, $s=false){ return []; } 
                 public function prep($key, $val){ return $val; }
             }');
        }
        $params = $this->createMock('Pro_search_params');
        $params->forget = [];
        
        // Default behavior for get_prefixed (no categories)
        $params->method('get_prefixed')->willReturn([]);
        
        $this->setMock('pro_search_params', $params);
        
        // Mock Fields (needed by base class)
        $fields = $this->createMock('stdClass');
        if (!class_exists('Pro_search_fields')) {
             eval('class Pro_search_fields { public function name($str){} public function table($str){} }');
        }
        $fields = $this->createMock('Pro_search_fields');
        $this->setMock('pro_search_fields', $fields);
        
        $this->filter = new Pro_search_filter_categories();
    }

    public function testFilterNoCategories()
    {
        $entry_ids = [1, 2, 3];
        $result = $this->filter->filter($entry_ids);
        $this->assertEquals($entry_ids, $result);
    }
    
    public function testFilterWithCategory()
    {
        // Setup Params to return category
        $params = $this->createMock('Pro_search_params');
        $params->method('get_prefixed')->willReturn(['category:1' => '10']); // group 1, cat id 10
        $params->method('prep')->willReturn('10');
        $params->method('explode')->willReturn([['10'], true]); // IDs, in=true
        $params->method('site_ids')->willReturn([1]);
        $params->forget = [];
        
        $this->setMock('pro_search_params', $params);
        
        // Setup DB
        $db = $this->createMock('ProSearchDbMock');
        $db->method('select')->willReturnSelf();
        $db->method('distinct')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('where_in')->willReturnSelf(); // entry_ids and cat_id
        
        $result = $this->createMock('eeDbResultMock');
        $result->method('result_array')->willReturn([['entry_id' => 1]]);
        
        $db->method('get')->willReturn($result);
        
        $this->setMock('db', $db);
        
        // Recreate filter with new mocks
        $this->filter = new Pro_search_filter_categories();
        
        $entry_ids = [1, 2, 3];
        $result = $this->filter->filter($entry_ids);
        
        $this->assertEquals([1], $result);
    }
}

