<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchBuildIndexBatchTest extends Pro_searchTestBase
{
	public function testBuildIndexBatchForSingleCollection()
	{
		if (!defined('REQ')) {
			define('REQ', 'ACTION');
		}
		$this->setSettingsStub([
			'encode_query' => 'y',
			'default_result_page' => '/search/results',
			'can_manage_shortcuts' => [],
			'build_index_act_key' => 'secret'
		]);
		ee()->setMock('input', new class {
			public function get_post($k){
				if ($k==='key') return 'secret';
				if ($k==='collection_id') return '7';
				if ($k==='start') return '0';
				if ($k==='rebuild') return 'yes';
				return null;
			}
		});
		$indexer = new class {
			public $batchCalled = false;
			public function build_batch($colId, $start){ $this->batchCalled = ($colId==7 && $start===0); return true; }
		};
		ee()->setMock('pro_search_index', $indexer);
		ee()->setMock('pro_search_index_model', new class { public function delete($id,$field){} public function optimize(){} });
		$this->pro->build_index();
		$this->assertTrue($indexer->batchCalled);
	}
}


