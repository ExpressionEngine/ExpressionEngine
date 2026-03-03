<?php

require_once __DIR__ . '/../ProSearchTestBase.php';

if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {}
    }
}

require_once __DIR__ . '/../../../../../Addons/pro_search/model.pro_search.php';
require_once __DIR__ . '/../../../../../Addons/pro_search/models/pro_search_log_model.php';
require_once __DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php';

class ProSearchLogModelTest extends ProSearchTestBase
{
    protected $model;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->model = new Pro_search_log_model();
    }

    public function testGetFilteredRows()
    {
        $rows = [['log_id' => 1, 'keywords' => 'foo']];
        
        $db = $this->createMock('ProSearchDbMock');
        $db->method('get')->willReturn(new eeDbResultMock($rows));
        
        // Expect like for keywords
        $db->expects($this->once())->method('like')->with('keywords', 'foo');
        
        $this->setMock('db', $db);
        
        $result = $this->model->get_filtered_rows(['keywords' => 'foo']);
        $this->assertCount(1, $result);
    }
    
    public function testGetFilteredCount()
    {
        $db = $this->createMock('ProSearchDbMock');
        $db->method('count_all_results')->willReturn(5);
        $this->setMock('db', $db);
        
        $count = $this->model->get_filtered_count(['site_id' => 1]);
        $this->assertEquals(5, $count);
    }
    
    public function testGetMemberIds()
    {
        $rows = [['member_id' => 1], ['member_id' => 2]];
        
        $db = $this->createMock('ProSearchDbMock');
        $db->method('select')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->method('get')->willReturn(new eeDbResultMock($rows));
        
        $this->setMock('db', $db);
        
        $ids = $this->model->get_member_ids();
        $this->assertEquals([1, 2], $ids);
    }
    
    public function testAddNumResults()
    {
        $db = $this->createMock('ProSearchDbMock');
        $db->expects($this->once())->method('query');
        
        $this->setMock('db', $db);
        
        $this->model->add_num_results(10, 123);
    }
    
    public function testPrune()
    {
        $rows = [['log_id' => 100]];
        
        $db = $this->createMock('ProSearchDbMock');
        $db->method('select')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->method('order_by')->willReturnSelf();
        $db->method('limit')->willReturnSelf();
        
        $result = $this->createMock('eeDbResultMock');
        $result->method('row')->willReturn(100);
        
        $db->method('get')->willReturn($result);
        
        // Expect deletion
        $db->expects($this->once())->method('delete');
        
        $this->setMock('db', $db);
        
        $this->model->prune(1, 50);
    }
}

