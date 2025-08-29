<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchUrlEdgeTest extends Pro_searchTestBase
{
	public function testUrlToggleAddsAndRemoves()
	{
		$this->setTemplateParams(['toggle:category' => '10']);
		$this->setParamsStub(['category' => '5|10']);
		$out = $this->pro->url();
		$this->assertStringContainsString('https://', $out);
	}

	public function testUrlRespectsForceProtocol()
	{
		$this->setTemplateParams(['force_protocol' => 'http']);
		$out = $this->pro->url();
		$this->assertStringStartsWith('http://', $out);
	}
}


