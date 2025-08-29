<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchSaveTest extends Pro_searchTestBase
{
	public function testSaveReturnsFormHtml()
	{
		// Allow save
		$this->setSettingsStub([
			'encode_query' => 'y',
			'default_result_page' => '/search/results',
			'can_manage_shortcuts' => [1],
			'build_index_act_key' => 'secret'
		]);

		// User is in allowed group
		ee()->setMock('session', new class {
			public function userdata($key){ return $key === 'group_id' ? 1 : 0; }
		});

		$this->setTemplateParams([]);
		$this->setTemplateTagdata('x');

		$out = $this->pro->save();
		$this->assertStringStartsWith('<form', $out);
		$this->assertStringEndsWith('</form>', $out);
	}
}


