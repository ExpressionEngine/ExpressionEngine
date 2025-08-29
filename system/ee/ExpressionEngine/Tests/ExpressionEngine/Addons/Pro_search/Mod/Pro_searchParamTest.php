<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchParamTest extends Pro_searchTestBase
{
	public function testParamReturnsFormattedValue()
	{
		$this->setTemplateParams(['get' => 'keywords', 'format' => 'html']);
		$this->setParamsStub(['keywords' => 'dogs & cats']);
		$out = $this->pro->param();
		$this->assertSame('dogs & cats', $out);
	}

	public function testParamAsLoopParsesTagdata()
	{
		$this->setTemplateParams(['get' => 'colors', 'as' => 'c']);
		$this->setTemplateTagdata('{c}');
		$this->setParamsStub(['colors' => 'red|green|blue']);
		$out = $this->pro->param();
		$this->assertSame('redgreenblue', $out);
	}
}


