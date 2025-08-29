<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchCollectionsTest extends Pro_searchTestBase
{
	public function testCollectionsNoRowsReturnsNoResults()
	{
		ee()->setMock('pro_search_collection_model', new class {
			public function get_by_site($ids){ return []; }
			public function get_by_param($v, $rows){ return $rows; }
			public function get_by_language($v,$in,$rows){ return $rows; }
		});
		$this->setTemplateTagdata('x');
		$out = $this->pro->collections();
		$this->assertSame('NO_RESULTS', $out);
	}

	public function testCollectionsParsesRows()
	{
		ee()->setMock('pro_search_collection_model', new class {
			public function get_by_site($ids){ return [
				['collection_id'=>1,'collection_name'=>'main','language'=>'en','settings'=>[]]
			]; }
			public function get_by_param($v, $rows){ return $rows; }
			public function get_by_language($v,$in,$rows){ return $rows; }
		});
		$this->setTemplateTagdata('{collection_name}{collection_language}');
		$out = $this->pro->collections();
		$this->assertStringContainsString('mainen', $out);
	}
}


