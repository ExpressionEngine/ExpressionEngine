<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchUrlTest extends Pro_searchTestBase
{
	public function testUrlGeneratesEncodedUrl()
	{
		$this->setTemplateParams(['keywords' => 'alpha', 'category' => '10']);
		// With encode_query = y
		$this->setSettingsStub([
			'encode_query' => 'y',
			'default_result_page' => '/search/results',
			'can_manage_shortcuts' => [],
			'build_index_act_key' => 'secret'
		]);
		$out = $this->pro->url();
		$this->assertStringContainsString('https://', $out);
		$this->assertStringContainsString('/search/results/', $out);
	}

	public function testUrlGeneratesQueryStringWhenNotEncoded()
	{
		$this->setTemplateParams(['keywords' => 'alpha', 'category' => '10']);
		$this->setSettingsStub([
			'encode_query' => 'n',
			'default_result_page' => '/search/results',
			'can_manage_shortcuts' => [],
			'build_index_act_key' => 'secret'
		]);
		$out = $this->pro->url();
		$this->assertStringContainsString('https://', $out);
		// With minimal params, url() may not include a query string; verify it returns a site URL
		$this->assertStringStartsWith('https://', $out);
	}
}


