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
			// plain tests don't use variables
			$this->plainComparisonTests(),
			$this->plainComparisonTestsNoWhitespace(),
			$this->plainLogicOperatorTests(),
			$this->plainLogicOperatorTestsNoWhitespace(),
			$this->plainModuloTests(),

			// simple tests don't combine too many things
			$this->simpleVariableReplacementsTest(),

			// advanced tests are common combinations of lots of things
			$this->advancedAndsAndOrs(),
			$this->advancedParenthesisEqualizing(),

			// wonky tests parse despite createing php errors
			// we should try to invalidate all of these, so for our new conditional
			// parsing these tests should be rewriten as failing
			$this->wonkySpacelessStringLogicOperators(),
			$this->wonkyRepetitions()
			// $this->wonkyFalseChains(),

			// evil tests attempt to subvert parsing to get valid php code
			// to the eval stage. These should never ever work.
			/*
			$this->wonkyBackslashesInVariables(),
			$this->wonkyBackticksInVariables(),
			$this->wonkyBackticksInConditional(),
			$this->wonkyPHPCommentsInVariables(),
			$this->wonkyPHPCommentsInConditional(),
			$this->wonkyConditionalSplitWithComments(),
			$this->wonkyConditionalSplitWithBackticks(),
			*/
		);
	}

	protected function plainComparisonTests()
	{
		return array(
			array('Plain == Integer',	'{if 5 == 5}out{/if}',	'{if 5 == 5}out{/if}'),
			array('Plain != Integer',	'{if 5 != 7}out{/if}',	'{if 5 != 7}out{/if}'),
			array('Plain > Integer',	'{if 7 > 5}out{/if}',	'{if 7 > 5}out{/if}'),
			array('Plain < Integer',	'{if 5 < 7}out{/if}',	'{if 5 < 7}out{/if}'),
			array('Plain <> Integer',	'{if 5 <> 7}out{/if}',	'{if 5 <> 7}out{/if}'),
		);
	}

	protected function plainComparisonTestsNoWhitespace()
	{
		return array(
			array('Plain == Integer No Space',	'{if 5==5}out{/if}',	'{if 5==5}out{/if}'),
			array('Plain != Integer No Space',	'{if 5!=7}out{/if}',	'{if 5!=7}out{/if}'),
			array('Plain > Integer No Space',	'{if 7>5}out{/if}',		'{if 7>5}out{/if}'),
			array('Plain < Integer No Space',	'{if 5<7}out{/if}',		'{if 5<7}out{/if}'),
			array('Plain <> Integer No Space',	'{if 5<>7}out{/if}',	'{if 5<>7}out{/if}'),
		);
	}

	protected function plainLogicOperatorTests()
	{
		return array(
			array('Plain && Integer',	'{if 5 && 5}out{/if}',	'{if 5 && 5}out{/if}'),
			array('Plain || Integer',	'{if 5 || 7}out{/if}',	'{if 5 || 7}out{/if}'),
			array('Plain AND Integer',	'{if 7 AND 5}out{/if}',	'{if 7 AND 5}out{/if}'),
			array('Plain OR Integer',	'{if 5 OR 7}out{/if}',	'{if 5 OR 7}out{/if}'),
			array('Plain XOR Integer',	'{if 5 XOR 7}out{/if}',	'{if 5 XOR 7}out{/if}'),
		);
	}

	protected function plainLogicOperatorTestsNoWhitespace()
	{
		return array(
			array('Plain && Integer No Space',	'{if 5&&5}out{/if}',	'{if 5&&5}out{/if}'),
			array('Plain || Integer No Space',	'{if 5||7}out{/if}',	'{if 5||7}out{/if}'),
			// the string ones are in wonkySpacelessStringLogicOperators as they generate invalid php
		);
	}

	protected function plainModuloTests()
	{
		return array(
			array('Modulo Integers',				'{if 15 % 5}out{/if}',			'{if 15 % 5}out{/if}'),
			array('Modulo Strings',					'{if "foo" % "bar"}out{/if}',	'{if "foo" % "bar"}out{/if}'),
			array('Modulo Integers no Whitespace',	'{if 15%5}out{/if}',			'{if 15%5}out{/if}'),
			array('Modulo Strings no Whitespace',	'{if "foo"%"bar"}out{/if}',		'{if "foo"%"bar"}out{/if}'),
		);
	}

	protected function simpleVariableReplacementsTest()
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

	protected function advancedAndsAndOrs()
	{
		return array(
			array('All ANDs',			'{if "foo" && "bar" && 5 && 7&&"baz" AND "bat"}out{/if}',	'{if "foo" && "bar" && 5 && 7&&"baz" AND "bat"}out{/if}'),
			array('All ORs',			'{if "foo" || "bar" || 5 || 7||"baz" OR "bat"}out{/if}',	'{if "foo" || "bar" || 5 || 7||"baz" OR "bat"}out{/if}'),
			array('Mixed ORs and ANDs',	'{if "foo" OR "bar" && 5 || 7||"baz" AND "bat"}out{/if}',	'{if "foo" OR "bar" && 5 || 7||"baz" AND "bat"}out{/if}'),
		);
	}

	protected function advancedParenthesisEqualizing()
	{
		return array(
			array('Too Many Open Parentheses',		'{if (((5 && 6)}out{/if}',	'{if (((5 && 6)))}out{/if}'),
			array('Too Many Closing Parentheses',	'{if (5 && 6)))}out{/if}',	'{if (((5 && 6)))}out{/if}'),
		);
	}

	protected function wonkySpacelessStringLogicOperators()
	{
		return array(
			array('Wonky No Space AND',	'{if 7AND5}out{/if}',	'{if 7AND5}out{/if}'),
			array('Wonky No Space OR',	'{if 5OR7}out{/if}',	'{if 5OR7}out{/if}'),
			array('Wonky No Space XOR',	'{if 5XOR7}out{/if}',	'{if 5XOR7}out{/if}'),
		);
	}

	protected function wonkyRepetitions()
	{
		return array(
			array('Double Modulo',		 '{if 5 %% 7}out{/if}',		'{if 5 %% 7}out{/if}'),
			array('Double AND', 		 '{if 5 && AND 7}out{/if}',	'{if 5 && AND 7}out{/if}'),
			array('Double No Space AND', '{if 5 &&AND 7}out{/if}',	'{if 5 &&AND 7}out{/if}'),
			array('Double Comparison',	 '{if 5 > < 7}out{/if}',	'{if 5 > < 7}out{/if}'),
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