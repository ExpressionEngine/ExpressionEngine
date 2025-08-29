<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchFiltersTest extends Pro_searchTestBase
{
	public function testFiltersParsesTemplateVariablesFromParams()
	{
		$this->setTemplateParams([]);
		$this->setTemplateTagdata('{pro_search_keywords}{pro_search_keywords:raw}');

		// Provide params values
		$this->setParamsStub([
			'keywords' => 'test',
		]);

		$out = $this->pro->filters();
		$this->assertStringContainsString('test', $out);
	}
}


