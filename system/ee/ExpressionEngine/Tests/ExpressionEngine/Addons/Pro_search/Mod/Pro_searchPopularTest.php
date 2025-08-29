<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchPopularTest extends Pro_searchTestBase
{
	public function testPopularParsesRows()
	{
		$this->setTemplateParams(['limit' => 2]);
		$this->setTemplateTagdata('{keywords} {search_count}');

		ee()->setMock('pro_search_log_model', new class {
			public function get_popular_keywords(){
				return [
					['keywords' => 'alpha', 'search_count' => 5],
					['keywords' => 'beta', 'search_count' => 3]
				];
			}
			public function key(){ return 'k'; }
		});

		$out = $this->pro->popular();
		$this->assertStringContainsString('alpha 5', $out);
		$this->assertStringContainsString('beta 3', $out);
	}

	public function testPopularNoRowsReturnsNoResults()
	{
		$this->setTemplateTagdata('x');
		ee()->setMock('pro_search_log_model', new class { public function get_popular_keywords(){ return []; } public function key(){ return 'k'; } });
		$out = $this->pro->popular();
		$this->assertSame('NO_RESULTS', $out);
	}
}


