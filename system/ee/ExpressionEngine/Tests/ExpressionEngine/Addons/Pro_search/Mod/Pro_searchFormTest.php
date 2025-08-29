<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchFormTest extends Pro_searchTestBase
{
	public function testFormWrapsFiltersOutput()
	{
		$this->setTemplateParams(['form_class' => 'x']);
		$this->setTemplateTagdata('inside');

		$this->setParamsStub(['keywords' => 'abc']);

		$out = $this->pro->form();
		$this->assertStringStartsWith('<form', $out);
		$this->assertStringContainsString('inside', $out);
		$this->assertStringEndsWith('</form>', $out);
	}
}


