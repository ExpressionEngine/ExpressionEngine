<?php

require_once __DIR__ . '/../ProSearchTestBase.php';

if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {}
    }
}

require_once __DIR__ . '/../../../../../Addons/pro_search/model.pro_search.php';
require_once __DIR__ . '/../../../../../Addons/pro_search/models/pro_search_word_model.php';
require_once __DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php';

class ProSearchWordModelTest extends ProSearchTestBase
{
    protected $model;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Multibyte (used in constructor/methods)
        $mb = new class {
            public function strlen($str) { return strlen($str); }
            public function substr($str, $start, $len = null) { return substr($str, $start, $len); }
        };
        $this->setMock('pro_multibyte', $mb);
        
        // Mock Config
        $config = $this->createMock('FakeConfig');
        $config->method('item')->willReturnMap([
            ['pro_search_first_letter_suggestions', false],
            ['pro_search_levenshtein_method', 'php'],
            ['site_id', 1]
        ]);
        $this->setMock('config', $config);
        
        // Mock Pro_search_words (used in get_dirty, get_sounds)
        // We need the class to exist to create mock
        if (!class_exists('Pro_search_words')) {
            // Mock class
            eval('class Pro_search_words { public function is_valid($str) { return true; } }');
        }
        $words = $this->createMock('Pro_search_words');
        $words->method('is_valid')->willReturn(true);
        $this->setMock('pro_search_words', $words);
        
        $this->model = new Pro_search_word_model();
    }

    public function testGetUnknown()
    {
        $rows = [['word' => 'known']];
        
        $db = $this->createMock('ProSearchDbMock');
        $db->method('select')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('where_in')->willReturnSelf();
        $db->method('get')->willReturn(new eeDbResultMock($rows));
        
        $this->setMock('db', $db);
        
        $unknown = $this->model->get_unknown(['known', 'unknown'], 'en', 1);
        $this->assertEquals(['unknown'], array_values($unknown));
    }
    
    public function testGetDirty()
    {
        $rows = [['word' => 'foo', 'clean' => 'foobar']];
        
        $db = $this->createMock('ProSearchDbMock');
        $db->method('select')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('where_in')->willReturnSelf();
        $db->method('get')->willReturn(new eeDbResultMock($rows));
        
        $this->setMock('db', $db);
        
        $result = $this->model->get_dirty(['foobar'], 'en', 1);
        $this->assertCount(1, $result);
    }
    
    public function testGetSuggestions()
    {
        // Mock DB for get_suggestions
        // It queries DB to get words, then calculates Levenshtein in PHP
        
        // Return words that are close to 'test'
        $rows = [['word' => 'text'], ['word' => 'tent'], ['word' => 'completely_different']];
        
        $db = $this->createMock('ProSearchDbMock');
        $db->method('select')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('where_in')->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->method('get')->willReturn(new eeDbResultMock($rows));
        
        $this->setMock('db', $db);
        
        $suggestions = $this->model->get_suggestions('test', 'en');
        // 'text' distance 1. 'tent' distance 1.
        $this->assertContains('text', $suggestions);
        $this->assertContains('tent', $suggestions);
        $this->assertNotContains('completely_different', $suggestions);
    }
    
    public function testGetSounds()
    {
        $rows = [['word' => 'test', 'sound' => 'T230']];
        
        $db = $this->createMock('ProSearchDbMock'); 
        $db->method('select')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('where_in')->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->method('get')->willReturn(new eeDbResultMock($rows));
        
        $this->setMock('db', $db);
        
        $result = $this->model->get_sounds(['test'], 'en', 1);
        $this->assertCount(1, $result);
    }
    
    public function testInsertIgnoreBatch()
    {
        $db = $this->createMock('ProSearchDbMock');
        $db->expects($this->once())->method('query')->with($this->stringContains('INSERT IGNORE INTO'));
        $db->method('escape_str')->will($this->returnCallback(function($str) { return $str; }));
        
        $this->setMock('db', $db);
        
        $this->model->insert_ignore_batch([['col' => 'val']]);
    }
    
    public function testReplaceBatch()
    {
        $db = $this->createMock('ProSearchDbMock');
        $db->expects($this->once())->method('query')->with($this->stringContains('REPLACE INTO'));
        $db->method('escape_str')->will($this->returnCallback(function($str) { return $str; }));
        
        $this->setMock('db', $db);
        
        $this->model->replace_batch([['col' => 'val']]);
    }
}

