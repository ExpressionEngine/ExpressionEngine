<?php

require_once __DIR__ . '/../../ProSearchTestBase.php';

if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {}
    }
}

require_once __DIR__ . '/../../../../../../Addons/pro_search/filter.pro_search.php';
require_once __DIR__ . '/../../../../../../Addons/pro_search/filters/field_search/lsf.field_search.php';
require_once __DIR__ . '/../../../../../../Addons/pro_search/helpers/pro_search_helper.php';

// Ensure Pro_search_params class exists
if (!class_exists('Pro_search_params')) {
    require_once PATH_ADDONS . 'pro_search/libraries/Pro_search_params.php';
}

class ProSearchFilterFieldSearchTest extends ProSearchTestBase
{
    protected $filter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Params
        $params = $this->createMock('Pro_search_params');
        $params->method('get_prefixed')->willReturn([]); // No search params by default
        $params->method('site_ids')->willReturn([1]);
        $params->method('prep')->willReturnArgument(1);
        $params->method('get')->willReturn('no'); // smart_field_search
        $params->forget = [];
        
        $this->setMock('pro_search_params', $params);
        
        // Mock Fields (needed by base class)
        $fields = $this->getMockBuilder('stdClass')
            ->addMethods(['name', 'table', 'native_table', 'is_native', 'sql', 'id', 'ids', 'get', 'is_grid', 'grid_col_id', 'is_matrix', 'matrix_col_id'])
            ->getMock();
        $fields->method('native_table')->willReturn('channel_titles');
        $fields->method('is_native')->willReturn(false);
        $fields->method('id')->willReturn(null);
        $fields->method('ids')->willReturn([]);
        $fields->method('sql')->willReturn('1=1');
        
        $this->setMock('pro_search_fields', $fields);
        
        // Mock Collection Model (needed for get_channel_ids)
        $collectionModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get_channel_ids'])
            ->getMock();
        $collectionModel->method('get_channel_ids')->willReturn([]);
        ee()->setMock('pro_search_collection_model', $collectionModel);
        
        $this->filter = new Pro_search_filter_field_search();
    }

    public function testFilterNoParams()
    {
        $entry_ids = [1, 2, 3];
        $result = $this->filter->filter($entry_ids);
        $this->assertEquals($entry_ids, $result);
    }
    
    public function testFilterWithNativeField()
    {
        // Setup Params with search:title
        $params = $this->createMock('Pro_search_params');
        $params->method('get_prefixed')->willReturn(['search:title' => 'test']);
        $params->method('site_ids')->willReturn([1]);
        $params->method('prep')->willReturn('test');
        $params->method('get')->willReturn('no');
        $params->forget = [];
        
        $this->setMock('pro_search_params', $params);
        
        // Mock Fields to return native field
        $fields = $this->getMockBuilder('stdClass')
            ->addMethods(['name', 'table', 'native_table', 'is_native', 'sql', 'id', 'ids', 'get', 'is_grid', 'grid_col_id', 'is_matrix', 'matrix_col_id'])
            ->getMock();
        $fields->method('native_table')->willReturn('channel_titles');
        $fields->method('is_native')->will($this->returnCallback(function($field) {
            return $field === 'title';
        }));
        $fields->method('sql')->willReturn('channel_titles.title LIKE "%test%"');
        
        $this->setMock('pro_search_fields', $fields);
        
        // Setup DB
        $db = $this->createMock('ProSearchDbMock');
        $db->method('select')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('join')->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->method('where_in')->willReturnSelf();
        
        $result = $this->createMock('eeDbResultMock');
        $result->method('result_array')->willReturn([['entry_id' => 1]]);
        
        $db->method('get')->willReturn($result);
        
        $this->setMock('db', $db);
        
        // Recreate filter with new mocks
        $this->filter = new Pro_search_filter_field_search();
        
        $entry_ids = [1, 2, 3];
        $result = $this->filter->filter($entry_ids);
        
        $this->assertIsArray($result);
        $this->assertContains(1, $result);
    }
    
    public function testResults()
    {
        $query = ['some' => 'data'];
        $result = $this->filter->results($query);
        
        // Should return query unchanged
        $this->assertEquals($query, $result);
    }
}

