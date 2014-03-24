<?php

require_once APPPATH.'libraries/Functions.php';

class PrepConditionalsTest extends PHPUnit_Framework_TestCase {


	/**
	 * @dataProvider dataProvider
	 */
	public function testConditionalsSafetyYesPrefixBlank($description, $str_in, $expected_out, $vars = array(), $php_vars = array())
	{
		$fns = new FunctionsStub('randomstring');
		$this->assertEquals(
			$expected_out,
			$fns->prep_conditionals($str_in, $vars, $safety = 'y', $prefix = ''),
			$description
		);
	}

	/**
	 * @dataProvider dataProvider
	 */
	public function testConditionalsSafetyNoPrefixBlank($description, $str_in, $expected_out, $vars = array(), $php_vars = array())
	{
		$fns = new FunctionsStub('randomstring');
		$this->assertEquals(
			$expected_out,
			$fns->prep_conditionals($str_in, $vars, $safety = 'n', $prefix = ''),
			$description
		);
	}



	public function dataProvider()
	{
		$tests = array();

		// assemble all of the tests
		return array_merge(
			$tests,
			$this->simpleConditionalTests(),
			$this->simpleComparisonTests(),
			$this->simpleComparisonTestsNoWhitespace()
		);
	}

	protected function simpleConditionalTests()
	{
		$t = '{if xyz}out{/if}';

		return array(
			array('Simple TRUE Boolean',	$t,   '{if "1"}out{/if}',	array('xyz' => TRUE)),
			array('Simple FALSE Boolean',	$t,   '{if ""}out{/if}',	array('xyz' => FALSE)),
			array('Simple Zero Int',		$t,   '{if "0"}out{/if}',	array('xyz' => 0)),
			array('Simple Positive Int',	$t,   '{if "5"}out{/if}',	array('xyz' => 5)),
			array('Simple Negative Int',	$t,   '{if "-5"}out{/if}',	array('xyz' => -5)),
			array('Simple Empty String',	$t,   '{if ""}out{/if}',	array('xyz' => '')),
		);
	}

	protected function simpleComparisonTests()
	{
		return array(
			array('Simple == Integer',	'{if 5 == 5}out{/if}',	'{if 5 == 5}out{/if}'),
			array('Simple != Integer',	'{if 5 != 7}out{/if}',	'{if 5 != 7}out{/if}'),
			array('Simple > Integer',	'{if 7 > 5}out{/if}',	'{if 7 > 5}out{/if}'),
			array('Simple < Integer',	'{if 5 < 7}out{/if}',	'{if 5 < 7}out{/if}'),
			array('Simple <> Integer',	'{if 5 <> 7}out{/if}',	'{if 5 <> 7}out{/if}'),
		);
	}

	protected function simpleComparisonTestsNoWhitespace()
	{
		return array(
			array('Simple == Integer',	'{if 5==5}out{/if}',	'{if 5==5}out{/if}'),
			array('Simple != Integer',	'{if 5!=7}out{/if}',	'{if 5!=7}out{/if}'),
			array('Simple > Integer',	'{if 7>5}out{/if}',		'{if 7>5}out{/if}'),
			array('Simple < Integer',	'{if 5<7}out{/if}',		'{if 5<7}out{/if}'),
			array('Simple <> Integer',	'{if 5<>7}out{/if}',	'{if 5<>7}out{/if}'),
		);
	}


	protected function simpleOperatorTests()
	{
		$t = '{if xyz}out{/if}';

		return array(
			array('LessTha',	$t,   '{if "1"}out{/if}',	array('xyz' => TRUE)),
			array('Simple FALSE Boolean',	$t,   '{if ""}out{/if}',	array('xyz' => FALSE)),
			array('Simple Zero Int',		$t,   '{if "0"}out{/if}',	array('xyz' => 0)),
			array('Simple Positive Int',	$t,   '{if "5"}out{/if}',	array('xyz' => 5)),
			array('Simple Negative Int',	$t,   '{if "-5"}out{/if}',	array('xyz' => -5)),
			array('Simple Empty String',	$t,   '{if ""}out{/if}',	array('xyz' => '')),
		);
	}
}

class FunctionsStub extends EE_Functions {

	private $fixedRandomString = '';

	public function __construct($randomString)
	{
		$this->EE = new StdClass();
		$this->fixedRandomString = $randomString;
	}

	// remove the random element
	public function random($type = 'encrypt', $len = 8)
	{
		return $this->fixedRandomString;
	}
}

function unique_marker($ident)
{
	return 'randommarker'.$ident;
}