<?php

require_once __DIR__ . '/../../ProSearchTestBase.php';

if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {}
    }
}

require_once __DIR__ . '/../../../../../../Addons/pro_search/filter.pro_search.php';
require_once __DIR__ . '/../../../../../../Addons/pro_search/filters/relationships/lsf.relationships.php';
require_once __DIR__ . '/../../../../../../Addons/pro_search/helpers/pro_search_helper.php';

// Ensure Pro_search_params class exists
if (!class_exists('Pro_search_params')) {
    require_once PATH_ADDONS . 'pro_search/libraries/Pro_search_params.php';
}

class ProSearchFilterRelationshipsTest extends ProSearchTestBase
{
    protected $filter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Params
        $params = $this->createMock('Pro_search_params');
        $params->method('get_prefixed')->willReturn([]); // No relationship params by default
        $params->method('site_ids')->willReturn([1]);
        $params->method('explode')->willReturn([[], true]);
        $params->forget = [];
        
        $this->setMock('pro_search_params', $params);
        
        // Mock Fields (needed by base class)
        $fields = $this->getMockBuilder('stdClass')
            ->addMethods(['id', 'is_rel', 'is_grid', 'grid_col_id', 'grid_col_type', 'is_matrix', 'matrix_col_id', 'matrix_col_type', 'is_playa', 'is_playa_grid'])
            ->getMock();
        $fields->method('id')->willReturn(null);
        $fields->method('is_rel')->willReturn(false);
        $fields->method('is_grid')->willReturn(false);
        $fields->method('is_matrix')->willReturn(false);
        $fields->method('is_playa')->willReturn(false);
        
        $this->setMock('pro_search_fields', $fields);
        
        // Mock Collection Model (needed for get_channel_ids)
        $collectionModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get_channel_ids'])
            ->getMock();
        $collectionModel->method('get_channel_ids')->willReturn([]);
        ee()->setMock('pro_search_collection_model', $collectionModel);
        
        $this->filter = new Pro_search_filter_relationships();
    }

    public function testFilterNoParams()
    {
        $entry_ids = [1, 2, 3];
        $result = $this->filter->filter($entry_ids);
        $this->assertEquals($entry_ids, $result);
    }
    
    public function testExclude()
    {
        // Test exclude method
        $result = $this->filter->exclude();
        // Should return null by default
        $this->assertNull($result);
    }
}

