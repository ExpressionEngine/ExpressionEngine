<?php

require_once __DIR__ . '/../ProSearchTestBase.php';

if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {}
    }
}

require_once __DIR__ . '/../../../../../Addons/pro_search/model.pro_search.php';
require_once __DIR__ . '/../../../../../Addons/pro_search/models/pro_search_group_model.php';
require_once __DIR__ . '/../../../../../Addons/pro_search/models/pro_search_replace_log_model.php';
require_once __DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php';

class ProSearchMiscModelsTest extends ProSearchTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Config mock needed for site_id in model constructor
        $config = $this->createMock('FakeConfig');
        $config->method('item')->willReturn(1);
        $this->setMock('config', $config);
    }

    public function testGroupGetBySite()
    {
        $model = new Pro_search_group_model();
        
        $rows = [['group_id' => 1, 'group_label' => 'A Group']];
        
        $db = $this->createMock('eeDbArMock');
        $db->method('get')->willReturn(new eeDbResultMock($rows));
        $db->method('where')->willReturnSelf();
        $db->method('order_by')->willReturnSelf();
        
        $this->setMock('db', $db);
        
        $result = $model->get_by_site(1);
        $this->assertCount(1, $result);
    }
    
    public function testReplaceLogGetSiteCount()
    {
        $model = new Pro_search_replace_log_model();
        
        $db = $this->createMock('eeDbArMock');
        $db->method('count_all_results')->willReturn(10);
        $db->method('where')->willReturnSelf();
        
        $this->setMock('db', $db);
        
        $this->assertEquals(10, $model->get_site_count());
    }
    
    public function testReplaceLogPrune()
    {
        $model = new Pro_search_replace_log_model();
        
        $db = $this->createMock('eeDbArMock');
        $db->method('select')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->method('order_by')->willReturnSelf();
        $db->method('limit')->willReturnSelf();
        
        $result = $this->createMock('eeDbResultMock');
        $result->method('row')->willReturn(600); // Return ID 600
        
        $db->method('get')->willReturn($result);
        
        // Expect deletion because 600 > 500 (default keep)
        $db->expects($this->once())->method('delete');
        
        $this->setMock('db', $db);
        
        $model->prune(1, 500);
    }
}

