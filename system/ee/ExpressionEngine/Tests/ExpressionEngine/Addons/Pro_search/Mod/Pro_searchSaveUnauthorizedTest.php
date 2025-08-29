<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchSaveUnauthorizedTest extends Pro_searchTestBase
{
	public function testSaveReturnsNullWhenUnauthorized()
	{
		$this->setSettingsStub([
			'encode_query' => 'y',
			'default_result_page' => '/search/results',
			'can_manage_shortcuts' => [],
			'build_index_act_key' => 'secret'
		]);
		ee()->setMock('session', new class {
			public function userdata($k){ return $k==='group_id' ? 2 : 0; }
		});
		$out = $this->pro->save();
		$this->assertNull($out);
	}
}


