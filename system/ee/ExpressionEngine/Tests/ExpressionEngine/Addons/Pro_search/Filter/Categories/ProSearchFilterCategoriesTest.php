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
        
        // Mock Params - use test class to avoid dynamic property deprecation
        $params = $this->getMockBuilder('Pro_search_params_test')
            ->onlyMethods(['get_prefixed', 'get', 'set', 'explode', 'site_ids', 'prep'])
            ->getMock();
        // Default behavior for get_prefixed (no categories)
        $params->method('get_prefixed')->willReturn([]);
        $params->method('get')->willReturn(null);
        $params->method('set')->willReturn(null);
        $params->method('explode')->willReturn([[], true]);
        $params->method('site_ids')->willReturn([1]);
        $params->method('prep')->willReturnArgument(1);
        
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
        // Setup Params to return category - use test class to avoid dynamic property deprecation
        $params = $this->getMockBuilder('Pro_search_params_test')
            ->onlyMethods(['get_prefixed', 'prep', 'explode', 'site_ids'])
            ->getMock();
        $params->method('get_prefixed')->willReturn(['category:1' => '10']); // group 1, cat id 10
        $params->method('prep')->willReturn('10');
        $params->method('explode')->willReturn([['10'], true]); // IDs, in=true
        $params->method('site_ids')->willReturn([1]);
        
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

