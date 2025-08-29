<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchResultsRequireShortcutTest extends Pro_searchTestBase
{
	public function testResultsRequireShortcutReturnsNoResults()
	{
		$this->setTemplateParams(['require_shortcut' => 'yes']);
		$this->setTemplateTagdata('x');
		$this->setParamsStub([]);
		$out = $this->pro->results();
		$this->assertSame('NO_RESULTS', $out);
	}
}


