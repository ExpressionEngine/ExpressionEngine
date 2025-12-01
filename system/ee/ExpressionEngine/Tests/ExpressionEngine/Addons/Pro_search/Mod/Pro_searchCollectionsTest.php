<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchCollectionsTest extends Pro_searchTestBase
{
	public function testCollectionsNoRowsReturnsNoResults()
	{
		$this->mockEmptyCollectionModel();
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

	public function testCollectionsWithMultipleSites()
	{
		ee()->setMock('pro_search_collection_model', new class {
			public function get_by_site($ids){
				return [
					['collection_id'=>1,'collection_name'=>'site1','language'=>'en','settings'=>[], 'site_id' => 1],
					['collection_id'=>2,'collection_name'=>'site2','language'=>'es','settings'=>[], 'site_id' => 2]
				];
			}
			public function get_by_param($v, $rows){ return $rows; }
			public function get_by_language($v,$in,$rows){ return $rows; }
		});
		$this->setTemplateTagdata('{collection_name}');
		$out = $this->pro->collections();
		$this->assertStringContainsString('site1', $out);
		$this->assertStringContainsString('site2', $out);
	}

	public function testCollectionsWithEmptyAfterFiltering()
	{
		// When no collections are found, should return no_results
		$this->setTemplateTagdata('no collections here');
		$out = $this->pro->collections();
		$this->assertSame('NO_RESULTS', $out);
	}
}


