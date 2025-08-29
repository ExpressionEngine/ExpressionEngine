<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchSaveSearchTest extends Pro_searchTestBase
{
	public function testSaveSearchInsertsAndRedirects()
	{
		// Allow manage shortcuts
		$this->setSettingsStub([
			'encode_query' => 'y',
			'default_result_page' => '/search/results',
			'can_manage_shortcuts' => [1],
			'build_index_act_key' => 'secret'
		]);

		// session mocks
		ee()->setMock('session', new class {
			public $userdata = ['group_id' => 1, 'member_id' => 10, 'ip_address' => '127.0.0.1'];
			public $flashdata = [];
			public function userdata($k){ return $this->userdata[$k] ?? null; }
			public function set_flashdata($k,$v){ $this->flashdata[$k] = $v; }
		});

		// input mock
		ee()->setMock('input', new class {
			private $post = [
				'group_id' => 2,
				'shortcut_name' => 'foo',
				'shortcut_label' => 'Foo',
				'params' => 'e30=' // base64 of {}
			];
			public function post($key){ return $this->post[$key] ?? null; }
			public function get_post($key){ return $this->post[$key] ?? null; }
		});

		// model mocks
		ee()->setMock('pro_search_shortcut_model', new class {
			public function validate($data){ return $data; }
			public function errors(){ return []; }
			public function table(){ return 'pro_search_shortcuts'; }
			public function insert($data){ return 55; }
		});

		// lang mock
		ee()->setMock('lang', new class { public function loadfile($p){} });

		// functions mock to capture redirect
		$func = new class extends ProSearchFakeFunctions {
			public function redirect($url){ $this->lastRedirect = $url; }
		};
		ee()->setMock('functions', $func);

		$this->pro->save_search();
		$this->assertNotNull($func->lastRedirect);
	}
}


