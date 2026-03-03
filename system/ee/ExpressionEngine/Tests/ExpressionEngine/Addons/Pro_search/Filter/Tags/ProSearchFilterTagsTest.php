<?php

require_once __DIR__ . '/../../ProSearchTestBase.php';

if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {}
    }
}

require_once __DIR__ . '/../../../../../../Addons/pro_search/filter.pro_search.php';
require_once __DIR__ . '/../../../../../../Addons/pro_search/filters/tags/lsf.tags.php';
require_once __DIR__ . '/../../../../../../Addons/pro_search/helpers/pro_search_helper.php';

class ProSearchFilterTagsTest extends ProSearchTestBase
{
    protected $filter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Addons library (needed to check for tag/tagger packages)
        $addons = $this->getMockBuilder('stdClass')
            ->addMethods(['is_package'])
            ->getMock();
        $addons->method('is_package')->willReturn(false); // Default: no tag packages
        ee()->setMock('addons', $addons);
        
        // Mock Loader to return addons
        $load = ee()->load;
        if (!method_exists($load, 'library')) {
            $load = $this->createMock('stdClass');
            $load->method('library')->willReturn(null);
        }
        ee()->setMock('load', $load);
        
        // Mock Params - use test class to avoid dynamic property deprecation
        $params = $this->getMockBuilder('Pro_search_params_test')
            ->onlyMethods(['get_prefixed', 'get', 'set', 'explode', 'site_ids', 'prep'])
            ->getMock();
        // Default behavior for get_prefixed (no tags)
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
        
        // Mock Collection Model (needed for get_channel_ids)
        $collectionModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get_channel_ids'])
            ->getMock();
        $collectionModel->method('get_channel_ids')->willReturn([]);
        ee()->setMock('pro_search_collection_model', $collectionModel);
        
        $this->filter = new Pro_search_filter_tags();
    }

    public function testFilterNoTags()
    {
        $entry_ids = [1, 2, 3];
        $result = $this->filter->filter($entry_ids);
        $this->assertEquals($entry_ids, $result);
    }
    
    public function testFilterNoTagPackage()
    {
        // No tag package installed, should return entry_ids unchanged
        $entry_ids = [1, 2, 3];
        $result = $this->filter->filter($entry_ids);
        $this->assertEquals($entry_ids, $result);
    }
    
    public function testFilterWithTagIds()
    {
        // Mock Tag package
        $addons = $this->getMockBuilder('stdClass')
            ->addMethods(['is_package'])
            ->getMock();
        $addons->method('is_package')->will($this->returnCallback(function($pkg) {
            return $pkg === 'tag';
        }));
        ee()->setMock('addons', $addons);
        
        // Mock Loader
        $load = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $load->method('library')->willReturn(null);
        ee()->setMock('load', $load);
        
        // Setup Params to return tag_id - use test class to avoid dynamic property deprecation
        $params = $this->getMockBuilder('Pro_search_params_test')
            ->onlyMethods(['get_prefixed', 'prep', 'explode', 'site_ids'])
            ->getMock();
        $params->method('get_prefixed')->will($this->returnCallback(function($prefix) {
            if ($prefix === 'tag_id') {
                return ['tag_id:1' => '10|20']; // Two tag IDs
            }
            return [];
        }));
        $params->method('prep')->willReturn('10|20');
        $params->method('explode')->willReturn([['10', '20'], true]); // IDs, in=true
        $params->method('site_ids')->willReturn([1]);
        
        $this->setMock('pro_search_params', $params);
        
        // Setup DB
        $db = $this->createMock('ProSearchDbMock');
        $db->method('select')->willReturnSelf();
        $db->method('distinct')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('where_in')->willReturnSelf(); // entry_ids, site_id, tag_id
        
        $result = $this->createMock('eeDbResultMock');
        $result->method('result_array')->willReturn([['entry_id' => 1], ['entry_id' => 2]]);
        
        $db->method('get')->willReturn($result);
        
        $this->setMock('db', $db);
        
        // Recreate filter with new mocks
        $this->filter = new Pro_search_filter_tags();
        
        $entry_ids = [1, 2, 3];
        $result = $this->filter->filter($entry_ids);
        
        $this->assertEquals([1, 2], $result);
    }
    
    public function testFilterWithTagNames()
    {
        // Mock Tag package
        $addons = $this->getMockBuilder('stdClass')
            ->addMethods(['is_package'])
            ->getMock();
        $addons->method('is_package')->will($this->returnCallback(function($pkg) {
            return $pkg === 'tag';
        }));
        ee()->setMock('addons', $addons);
        
        // Mock Loader
        $load = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $load->method('library')->willReturn(null);
        ee()->setMock('load', $load);
        
        // Setup Params to return tag_name - use test class to avoid dynamic property deprecation
        $params = $this->getMockBuilder('Pro_search_params_test')
            ->onlyMethods(['get_prefixed', 'get', 'explode', 'site_ids', 'prep'])
            ->getMock();
        $params->method('get_prefixed')->will($this->returnCallback(function($prefix) {
            if ($prefix === 'tag_name') {
                return ['tag_name:1' => 'news|blog']; // Two tag names
            }
            return [];
        }));
        $params->method('get')->willReturn('+'); // websafe_separator
        $params->method('explode')->will($this->returnCallback(function($str) {
            if ($str === 'news|blog') {
                return [['news', 'blog'], true];
            }
            return [[$str], true];
        }));
        $params->method('site_ids')->willReturn([1]);
        $params->method('prep')->willReturnArgument(1);
        
        $this->setMock('pro_search_params', $params);
        
        // Setup DB for tag name lookup
        $db = $this->createMock('ProSearchDbMock');
        
        // First query: lookup tag names to get tag IDs
        $tagLookupResult = $this->createMock('eeDbResultMock');
        $tagLookupResult->method('result_array')->willReturn([
            ['tag_id' => 10, 'tag_name' => 'news'],
            ['tag_id' => 20, 'tag_name' => 'blog']
        ]);
        
        // Second query: get entry IDs from tag_entries
        $entryResult = $this->createMock('eeDbResultMock');
        $entryResult->method('result_array')->willReturn([['entry_id' => 1]]);
        
        $db->method('select')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('where_in')->willReturnSelf();
        $db->method('distinct')->willReturnSelf();
        
        // Return different results based on table
        $db->method('get')->will($this->returnCallback(function() use ($tagLookupResult, $entryResult) {
            // Check which table we're querying by checking the last from() call
            // For simplicity, return tag lookup on first call, entry result on second
            static $callCount = 0;
            $callCount++;
            return $callCount === 1 ? $tagLookupResult : $entryResult;
        }));
        
        $this->setMock('db', $db);
        
        // Mock Collection Model
        $collectionModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get_channel_ids'])
            ->getMock();
        $collectionModel->method('get_channel_ids')->willReturn([]);
        ee()->setMock('pro_search_collection_model', $collectionModel);
        
        // Recreate filter with new mocks
        $this->filter = new Pro_search_filter_tags();
        
        $entry_ids = [1, 2, 3];
        $result = $this->filter->filter($entry_ids);
        
        $this->assertEquals([1], $result);
    }
    
    public function testFilterWithTaggerPackage()
    {
        // Mock Tagger package (different table names)
        $addons = $this->getMockBuilder('stdClass')
            ->addMethods(['is_package'])
            ->getMock();
        $addons->method('is_package')->will($this->returnCallback(function($pkg) {
            return $pkg === 'tagger';
        }));
        ee()->setMock('addons', $addons);
        
        // Mock Loader
        $load = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $load->method('library')->willReturn(null);
        ee()->setMock('load', $load);
        
        // Setup Params to return tag_id - use test class to avoid dynamic property deprecation
        $params = $this->getMockBuilder('Pro_search_params_test')
            ->onlyMethods(['get_prefixed', 'prep', 'explode', 'site_ids'])
            ->getMock();
        $params->method('get_prefixed')->will($this->returnCallback(function($prefix) {
            if ($prefix === 'tag_id') {
                return ['tag_id:1' => '10'];
            }
            return [];
        }));
        $params->method('prep')->willReturn('10');
        $params->method('explode')->willReturn([['10'], true]);
        $params->method('site_ids')->willReturn([1]);
        
        $this->setMock('pro_search_params', $params);
        
        // Setup DB (should use tagger tables)
        $db = $this->createMock('ProSearchDbMock');
        $db->method('select')->willReturnSelf();
        $db->method('distinct')->willReturnSelf();
        $db->method('from')->willReturnSelf();
        $db->method('where_in')->willReturnSelf();
        
        $result = $this->createMock('eeDbResultMock');
        $result->method('result_array')->willReturn([['entry_id' => 5]]);
        
        $db->method('get')->willReturn($result);
        
        $this->setMock('db', $db);
        
        // Mock Collection Model
        $collectionModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get_channel_ids'])
            ->getMock();
        $collectionModel->method('get_channel_ids')->willReturn([]);
        ee()->setMock('pro_search_collection_model', $collectionModel);
        
        // Recreate filter with new mocks
        $this->filter = new Pro_search_filter_tags();
        
        $entry_ids = [1, 2, 3];
        $result = $this->filter->filter($entry_ids);
        
        $this->assertEquals([5], $result);
    }
    
    public function testResults()
    {
        // Mock TMPL to have rogue vars
        ee()->TMPL->var_single = [
            'pro_search_tag_id:1' => 'test',
            'pro_search_tag_name:1' => 'test',
            'other_var' => 'keep'
        ];
        
        $query = ['some' => 'data'];
        $result = $this->filter->results($query);
        
        // Should return query unchanged
        $this->assertEquals($query, $result);
        
        // Rogue vars should be removed (checking via reflection if needed)
        // The _remove_rogue_vars method is protected, so we can't directly test it
        // but we can verify the method executes without error
    }
}

