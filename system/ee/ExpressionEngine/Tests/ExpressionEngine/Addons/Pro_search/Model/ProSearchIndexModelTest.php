<?php

require_once __DIR__ . '/../ProSearchTestBase.php';

if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {}
    }
}

require_once __DIR__ . '/../../../../../Addons/pro_search/model.pro_search.php';
require_once __DIR__ . '/../../../../../Addons/pro_search/models/pro_search_index_model.php';
require_once __DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php';

class ProSearchIndexModelTest extends ProSearchTestBase
{
    protected $model;

    protected function setUp(): void
    {
        parent::setUp();
        
        $config = $this->createMock('FakeConfig');
        $config->method('item')->willReturn(1); // site_id
        $this->setMock('config', $config);
        
        $this->model = new Pro_search_index_model();
    }

    public function testReplace()
    {
        $db = $this->createMock('ProSearchDbMock');
        $db->expects($this->once())->method('insert_string')->willReturn("INSERT INTO table (col) VALUES ('val')");
        $db->expects($this->once())->method('query')->with($this->stringContains('REPLACE INTO'));
        
        $this->setMock('db', $db);
        
        $this->model->replace(['col' => 'val']);
    }
    
    public function testReplaceBatch()
    {
        $db = $this->createMock('ProSearchDbMock');
        $db->expects($this->once())->method('query')->with($this->stringContains('REPLACE INTO'));
        $db->method('escape_str')->will($this->returnCallback(function($str) { return $str; }));
        
        $this->setMock('db', $db);
        
        $this->model->replace_batch([['col' => 'val']]);
    }
    
    public function testGetOldestIndex()
    {
        $rows = [['collection_id' => 1, 'index_date' => 123456]];
        
        $db = $this->createMock('ProSearchDbMock');
        $db->method('select')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->method('group_by')->willReturnSelf();
        $db->method('get')->willReturn(new eeDbResultMock($rows));
        
        $this->setMock('db', $db);
        
        $result = $this->model->get_oldest_index();
        $this->assertEquals([1 => 123456], $result);
    }
    
    public function testOptimize()
    {
        $db = $this->createMock('ProSearchDbMock');
        $db->expects($this->once())->method('query')->with($this->stringContains('OPTIMIZE TABLE'));
        
        $this->setMock('db', $db);
        
        $this->model->optimize();
    }
}

