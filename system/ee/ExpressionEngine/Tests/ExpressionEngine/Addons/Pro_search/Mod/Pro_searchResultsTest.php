<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchResultsTest extends Pro_searchTestBase
{
	public function testResultsReturnsNoResultsWhenQueryRequired()
	{
		$this->setTemplateParams(['require_query' => 'yes']);
		$this->setTemplateTagdata('x');
		$this->setParamsStub([]); // query_given() => false
		$out = $this->pro->results();
		$this->assertSame('NO_RESULTS', $out);
	}

	public function testResultsUsesExtensionProvidedTagdata()
	{
		// Filters lib stub
		ee()->setMock('pro_search_filters', new class {
			public function filter(){}
			public function entry_ids(){ return null; }
			public function fixed_order(){ return false; }
			public function set_entry_ids($ids){}
			public function exclude(){ return null; }
		});

		// Extensions mock returns early tagdata
		ee()->setMock('extensions', new class {
			public $end_script = false;
			public function active_hook($name){ return $name === 'pro_search_channel_entries'; }
			public function call($name){ return 'HELLO'; }
		});

		$this->setTemplateParams([]);
		$this->setTemplateTagdata('ignored');
		$this->setParamsStub([]);

		$out = $this->pro->results();
		$this->assertSame('HELLO', $out);
	}
}


