<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchParamTest extends Pro_searchTestBase
{
	public function testParamWithGetTagParamReturnsFormattedValue()
	{
		$this->setTemplateParams(['get' => 'keywords']);
		$this->setParamsStub(['keywords' => 'test query']);

		$result = $this->pro->param();

		$this->assertSame('test query', $result);
	}

	public function testParamWithMethodArgumentReturnsFormattedValue()
	{
		$this->setTemplateParams([]);
		$this->setParamsStub(['keywords' => 'test query']);

		$result = $this->pro->param('keywords');

		$this->assertSame('test query', $result);
	}

	public function testParamWithMethodArgumentFallbackWhenNoGetTagParam()
	{
		$this->setTemplateParams([]);
		$this->setParamsStub(['keywords' => 'test query']);

		$result = $this->pro->param('keywords');

		$this->assertSame('test query', $result);
	}

	public function testParamWithEmptyValueReturnsEmptyString()
	{
		$this->setTemplateParams(['get' => 'empty_param']);
		$this->setParamsStub(['empty_param' => '']);

		$result = $this->pro->param();

		$this->assertSame('', $result);
	}

	public function testParamWithNullValueReturnsEmptyString()
	{
		$this->setTemplateParams(['get' => 'null_param']);
		$this->setParamsStub(['null_param' => null]);

		$result = $this->pro->param();

		$this->assertSame('', $result);
	}

	public function testParamWithNumericValueReturnsString()
	{
		$this->setTemplateParams(['get' => 'numeric_param']);
		$this->setParamsStub(['numeric_param' => 123]);

		$result = $this->pro->param();

		$this->assertSame('123', $result);
	}

	public function testParamWithFormatParameter()
	{
		$this->setTemplateParams(['get' => 'test_param', 'format' => 'url']);
		$this->setParamsStub(['test_param' => 'test value']);

		$result = $this->pro->param();

		$this->assertSame('test value', $result);
	}

	public function testParamWithAsParameterCreatesLoop()
	{
		$this->setTemplateParams(['get' => 'multi_param', 'as' => 'item']);
		$this->setTemplateTagdata('{item}');
		$this->setParamsStub(['multi_param' => 'value1|value2|value3']);

		$result = $this->pro->param();

		$this->assertSame('value1value2value3', $result);
	}

	public function testParamWithAsParameterAndFormat()
	{
		$this->setTemplateParams(['get' => 'multi_param', 'as' => 'item', 'format' => 'url']);
		$this->setTemplateTagdata('{item} ');
		$this->setParamsStub(['multi_param' => 'value1|value2']);

		$result = $this->pro->param();

		$this->assertSame('value1 value2 ', $result);
	}

	public function testParamWithAsParameterSingleValue()
	{
		$this->setTemplateParams(['get' => 'single_param', 'as' => 'item']);
		$this->setTemplateTagdata('[{item}]');
		$this->setParamsStub(['single_param' => 'single_value']);

		$result = $this->pro->param();

		$this->assertSame('[single_value]', $result);
	}

	public function testParamWithAsParameterEmptyValue()
	{
		$this->setTemplateParams(['get' => 'empty_multi', 'as' => 'item']);
		$this->setTemplateTagdata('{item}');
		$this->setParamsStub(['empty_multi' => '']);

		$result = $this->pro->param();

		$this->assertSame('', $result);
	}

	public function testParamWithAsParameterNullValue()
	{
		$this->setTemplateParams(['get' => 'null_multi', 'as' => 'item']);
		$this->setTemplateTagdata('{item}');
		$this->setParamsStub(['null_multi' => null]);

		$result = $this->pro->param();

		$this->assertSame('', $result);
	}

	public function testParamWithNonExistentParameterReturnsEmptyString()
	{
		$this->setTemplateParams(['get' => 'nonexistent']);
		$this->setParamsStub(['existing' => 'value']);

		$result = $this->pro->param();

		$this->assertSame('', $result);
	}

	public function testParamCallsParamsSet()
	{
		$this->setTemplateParams(['get' => 'test']);
		$this->setParamsStub(['test' => 'value']);

		// We can't directly test that params->set() was called, but we can verify
		// the method completes successfully with our stub
		$result = $this->pro->param();

		$this->assertSame('value', $result);
	}
}
