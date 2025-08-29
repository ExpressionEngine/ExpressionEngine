<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchParamMissingTest extends Pro_searchTestBase
{
	public function testParamNoGetReturnsNoResults()
	{
		$this->setTemplateParams([]);
		$out = $this->pro->param();
		$this->assertSame('NO_RESULTS', $out);
	}
}


