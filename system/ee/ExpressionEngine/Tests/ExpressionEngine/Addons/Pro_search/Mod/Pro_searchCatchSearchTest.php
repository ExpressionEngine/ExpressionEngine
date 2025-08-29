<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchCatchSearchTest extends Pro_searchTestBase
{
	public function testCatchSearchRedirectsToResultUrl()
	{
		// input contains params and a keyword
		ee()->setMock('input', new class {
			public function post($k){ return null; }
			public function get_post($k){ return null; }
		});

		// Simulate POSTed params (encoded empty) and query data via superglobals
		$_POST = ['params' => base64_encode(json_encode(['result_page' => '/results'])), 'keywords' => 'alpha'];
		$_GET = [];

		$func = new class extends ProSearchFakeFunctions {
			public function redirect($url){ $this->lastRedirect = $url; }
		};
		ee()->setMock('functions', $func);

		$this->pro->catch_search();
		$this->assertNotNull($func->lastRedirect);
		$this->assertStringContainsString('/results', $func->lastRedirect);
	}
}


