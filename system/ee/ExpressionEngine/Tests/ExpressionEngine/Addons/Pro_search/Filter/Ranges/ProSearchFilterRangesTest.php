<?php

require_once __DIR__ . '/../../ProSearchTestBase.php';

if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {}
    }
}

require_once __DIR__ . '/../../../../../../Addons/pro_search/filter.pro_search.php';
require_once __DIR__ . '/../../../../../../Addons/pro_search/filters/ranges/lsf.ranges.php';
require_once __DIR__ . '/../../../../../../Addons/pro_search/helpers/pro_search_helper.php';

// Ensure Pro_search_params class exists
if (!class_exists('Pro_search_params')) {
    require_once PATH_ADDONS . 'pro_search/libraries/Pro_search_params.php';
}

class ProSearchFilterRangesTest extends ProSearchTestBase
{
    protected $filter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Params
        $params = $this->createMock('Pro_search_params');
        $params->method('get_prefixed')->willReturn([]); // No range params by default
        $params->method('site_ids')->willReturn([1]);
        $params->method('prep')->willReturnArgument(1);
        $params->forget = [];
        
        $this->setMock('pro_search_params', $params);
        
        // Mock Fields (needed by base class)
        $fields = $this->getMockBuilder('stdClass')
            ->addMethods(['name', 'table', 'native_table', 'id', 'is_native', 'is_date', 'is_numeric'])
            ->getMock();
        $fields->method('native_table')->willReturn('channel_titles');
        $fields->method('id')->willReturn(null);
        $fields->method('is_native')->willReturn(false);
        $fields->method('is_date')->willReturn(false);
        $fields->method('is_numeric')->willReturn(false);
        
        $this->setMock('pro_search_fields', $fields);
        
        // Mock Localize (needed for date parsing)
        $localize = $this->getMockBuilder('stdClass')
            ->addMethods(['localize_month', 'now'])
            ->getMock();
        $localize->method('localize_month')->willReturn(['Jan', 'January']);
        $localize->now = time();
        ee()->setMock('localize', $localize);
        
        // Mock Loader
        $load = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $load->method('library')->willReturn(null);
        ee()->setMock('load', $load);
        
        // Mock Collection Model (needed for get_channel_ids)
        $collectionModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get_channel_ids'])
            ->getMock();
        $collectionModel->method('get_channel_ids')->willReturn([]);
        ee()->setMock('pro_search_collection_model', $collectionModel);
        
        $this->filter = new Pro_search_filter_ranges();
    }

    public function testFilterNoParams()
    {
        $entry_ids = [1, 2, 3];
        $result = $this->filter->filter($entry_ids);
        $this->assertEquals($entry_ids, $result);
    }
    
    public function testResults()
    {
        $query = ['some' => 'data'];
        $result = $this->filter->results($query);
        
        // Should return query unchanged
        $this->assertEquals($query, $result);
    }
}

