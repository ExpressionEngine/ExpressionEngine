<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureCreateUriTest extends StructureTestBase
{
	public function testCreateUriUsesUrlTitleWhenUriEmpty()
	{
		$result = $this->structure->create_uri('', 'my-title');
		$this->assertSame('my-title', $result);
	}

	public function testCreateUriStripsInvalidCharacters()
	{
		$result = $this->structure->create_uri('Hello World!@#$', 'fallback');
		$this->assertSame('HelloWorld', $result);
	}

	public function testCreateUriTrimsLeadingAndTrailingUnderscores()
	{
		$result = $this->structure->create_uri('_hello_world_', 'x');
		$this->assertSame('hello_world', $result);
	}

	public function testCreateUriBothParamsEmptyReturnsEmptyString()
	{
		$result = $this->structure->create_uri('', '');
		$this->assertSame('', $result);
	}

	public function testCreateUriPreservesCaseAndMiddleUnderscores()
	{
		$result = $this->structure->create_uri('Hello___World', 'fallback');
		$this->assertSame('Hello___World', $result);
	}

	public function testCreateUriStripsUnicodeAccentsWithoutTransliteration()
	{
		$result = $this->structure->create_uri('Málaga', '');
		$this->assertSame('Mlaga', $result);
	}

	public function testCreateUriOnlySymbolsResultsInEmpty()
	{
		$result = $this->structure->create_uri('@#$%^&*()', '');
		$this->assertSame('', $result);
	}

	public function testCreateUriOnlyNumericsAllowed()
	{
		$result = $this->structure->create_uri('123456', '');
		$this->assertSame('123456', $result);
	}

	public function testCreateUriAllowedPunctuationAndUnderscore()
	{
		$result = $this->structure->create_uri('a-b.c_d', '');
		$this->assertSame('a-b.c_d', $result);
	}

	public function testCreateUriOnlyUnderscoresTrimsToEmpty()
	{
		$result = $this->structure->create_uri('____', '');
		$this->assertSame('', $result);
	}

	public function testCreateUriLeadingDotIsAllowed()
	{
		$result = $this->structure->create_uri('.hidden', '');
		$this->assertSame('.hidden', $result);
	}

	public function testCreateUriMixedSeparatorsAreSanitized()
	{
		$result = $this->structure->create_uri('a b_c-d.e', '');
		$this->assertSame('ab_c-d.e', $result);
	}
}


