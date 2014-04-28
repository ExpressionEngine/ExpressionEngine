<?php

require_once APPPATH.'libraries/Functions.php';

class PrepConditionalsTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider dataProvider
	 */
	public function testConditionalsSafetyYesPrefixBlank($description, $str_in, $expected_out, $vars = array())
	{
		$this->runConditionalTest($description, $str_in, $expected_out, $vars);
	}

	/**
	 * @dataProvider embeddedTags
	 */
	public function testEmbeddedTags($description, $str_in, $expected_out_safety_off, $expected_out_safety_on, $vars = array())
	{
		// variables called int and string are always available unless $vars was explicitly set to FALSE
		if ($vars !== FALSE)
		{
			$vars = array_merge(array(
				'int' => 5,
				'string' => 'ee'
			), $vars);
		}
		else
		{
			$vars = array();
		}

		$fns = new FunctionsStub('randomstring');

		// First pass: safety off
		$str = $fns->prep_conditionals($str_in, $vars, $safety = 'n', $prefix = '');
		$this->assertEquals(
			$expected_out_safety_off,
			$str,
			$description . " (safety off)"
		);

		// Second pass: safety on
		$this->assertEquals(
			$expected_out_safety_on,
			$fns->prep_conditionals($str_in, $vars, $safety = 'y', $prefix = ''),
			$description . " (safety on)"
		);

		// Third pass: saftey on fed with result of safety off
		$this->assertEquals(
			$expected_out_safety_on,
			$fns->prep_conditionals($str, $vars, $safety = 'y', $prefix = ''),
			$description . " (safety off + on)"
		);
	}

	/**
	 * @dataProvider badDataProvider
	 */
	public function testBadConditionalsWithVariables($exception, $description, $str_in)
	{
		$this->setExpectedException($exception);
		$this->runConditionalTest($description, $str_in, '');
	}

	/**
	 * @dataProvider badDataProvider
	 */
	public function testBadConditionalsWithoutVariables($exception, $description, $str_in)
	{
		$this->setExpectedException($exception);
		$this->runConditionalTest($description, $str_in, '', FALSE);
	}

	public function testMultipassVariable()
	{
		$str = '{if string == whatthefoxsay}out{/if}';
		// First pass is with safety off
		$fns = new FunctionsStub('randomstring');
		$str = $fns->prep_conditionals($str, array('whatthefoxsay' => 'Ring-ding-ding-ding-dingeringeding!'), $safety = 'n', $prefix = '');

		$this->assertEquals(
			'{if string == "Ring-ding-ding-ding-dingeringeding!"}out{/if}',
			$str,
			"Prep Conditionals with safetey off"
		);

		// Second pass is with safety on
		$str = 	$fns->prep_conditionals($str, array('string' => 'ee'), $safety = 'y', $prefix = '');

		$this->assertEquals(
			'{if "ee" == "Ring-ding-ding-ding-dingeringeding!"}out{/if}',
			$str,
			"Double Prep Variable Buildup"
		);
	}

	protected function runConditionalTest($description, $str_in, $expected_out, $vars = array())
	{
		// variables called int and string are always available unless $vars was explicitly set to FALSE
		if ($vars !== FALSE)
		{
			$vars = array_merge(array(
				'int' => 5,
				'string' => 'ee'
			), $vars);
		}
		else
		{
			$vars = array();
		}

		$fns = new FunctionsStub('randomstring');

		$this->assertEquals(
			$expected_out,
			$fns->prep_conditionals($str_in, $vars, $safety = 'y', $prefix = ''),
			$description
		);

		$str = $fns->prep_conditionals($str_in, $vars, $safety = 'n', $prefix = '');

		$this->assertEquals(
			$expected_out,
			$fns->prep_conditionals($str, $vars, $safety = 'y', $prefix = ''),
			"Double Prep with vars: ". $description
		);

		$str = $fns->prep_conditionals($str_in, array('whatthefoxsay' => 'Ring-ding-ding-ding-dingeringeding!'), $safety = 'n', $prefix = '');

		$this->assertEquals(
			$expected_out,
			$fns->prep_conditionals($str, $vars, $safety = 'y', $prefix = ''),
			"Double Prep without vars: ". $description
		);
	}

	public function badDataProvider()
	{
		return array(
			array('UnsafeConditionalException',  'Simple Backticks',				'{if `echo hello`}out{/if}'),
			array('UnsafeConditionalException',  'Splitting Backticks',				'{if string.`echo hello #}out{/if}{if `== 0}out{/if}'),
			array('UnsafeConditionalException',  'Simple Comments',					'{if php/* test == 5*/info(); }out{/if}'),
			array('UnsafeConditionalException',  'Splitting Comments',				'{if string /* == 5 }out{/if}{if */phpinfo(); == 5}out{/if}'),
			array('InvalidConditionalException', 'Unclosed String (single quotes)', "{if string == 'ee}out{/if}"),
			array('InvalidConditionalException', 'Unclosed String (double quotes)', '{if string == "ee}out{/if}'),
			array('InvalidConditionalException', 'Unclosed Conditional', 			'{if string == "ee"}out'),
			array('InvalidConditionalException', 'Unterminated Conditional', 		'{if string == "ee"out{/if}'),
			array('InvalidConditionalException', 'If as a Prefix', 					'{if:foo}'),
			array('InvalidConditionalException', 'Ifelse duplicity', 				'{if 5 == 5}out{if:else:else}out{/if}'),
			array('InvalidConditionalException', 'Ifelse Prefixing', 				'{if 5 == 5}out{if:elsebeth}out{/if}'),
			array('InvalidConditionalException', 'Ifelseif Prefixing', 				'{if 5 == 5}out{if:elseiffy}out{/if}'),
		);
	}

	public function dataProvider()
	{
		// assemble all of the tests
		return array_merge(
			array(),
			// tests for things that should not be parsed as conditionals
			$this->conditionals(),
			$this->notConditionals(),
			$this->multipleConditionals(),

			// plain tests don't use variables
			$this->plainComparisonTests(),
			$this->plainComparisonTestsNoWhitespace(),
			$this->plainLogicOperatorTests(),
			$this->plainLogicOperatorTestsNoWhitespace(),
			$this->plainModuloTests(),
			$this->plainUnparsedTurnsFalse(),

			// simple tests don't combine too many things
			$this->simpleVariableReplacementsTest(),
			$this->simpleVariableComparisonsTest(),

			// advanced tests are common combinations of lots of things
			$this->advancedAndsAndOrs(),
			$this->advancedParenthesisEqualizing(),

			// testing string protection
			$this->protectingStrings(),

			// testing that our safety cleanup does its job
			$this->safteyCleanup(),
			$this->safetyFalseCleanup(),

			// testing bug reports
			$this->bug20323(),

			// wonky tests parse despite createing php errors
			// we should try to invalidate all of these, so for our new conditional
			// parsing these tests should be rewriten as failing
			$this->wonkySpacelessStringLogicOperators(),
			$this->wonkyRepetitions(),
			$this->wonkyEmpty(),
			$this->wonkyMutableBooleans(),
			$this->wonkyDifferentBehaviorWithoutVariables(),
			$this->wonkyPhpOperatorsWorkOnlyWithWhitespace(),
			$this->wonkyComparisonOperators()

			// evil tests attempt to subvert parsing to get valid php code
			// to the eval stage. These should never ever work.
			/*
			$this->evilBackslashesInVariables(),
			$this->evilBackticksInVariables(),
			$this->evilPHPCommentsInVariables(),
			$this->evilPHPCommentsInConditional(),
			$this->evilConditionalSplitWithComments(),
			$this->evilConditionalSplitWithBackticks(),
			*/
		);
	}

	protected function conditionals()
	{
		return array(
			array('If With Space',		'{if 5 == 5}out{/if}',						'{if 5 == 5}out{/if}'),
			array('If With Tab',		'{if	5 == 5}out{/if}',					'{if 5 == 5}out{/if}'),
			array('If With Newline',	"{if\n5 == 5}out{/if}",						"{if 5 == 5}out{/if}"),
			array('If With CRLF',		"{if\r\n5 == 5}out{/if}",					"{if 5 == 5}out{/if}"),
			array('If With Whitespace',	"{if\n\t5 == 5\n}out{/if}",					"{if 5 == 5}out{/if}"),
			array('Ifelseif',			'{if 5 == 5}out{if:elseif 5 == 5}out{/if}',	'{if 5 == 5}out{if:elseif 5 == 5}out{/if}'),
			array('Ifelse',				'{if 5 == 5}out{if:else}out{/if}',			'{if 5 == 5}out{if:else}out{/if}'),
		);
	}

	protected function notConditionals()
	{
		return array(
			array('Just a Variable',	'{iffy}',	'{iffy}'),
			array('Too Many Spaces',	'{ if }',	'{ if }'),
			array("It's JavaScript",	'<script>function toddler(){if (true) return false}</script>', '<script>function toddler(){if (true) return false}</script>'),
		);
	}

	protected function multipleConditionals()
	{
		return array(
			array('Two conditionals', '{if 1 == 1}out{/if} {if 2 == 2}out{/if}', '{if 1 == 1}out{/if} {if 2 == 2}out{/if}'),
			array('Very long string', '{if "test"}out{/if} {if "long var_a46d7cbbeb2015d076399df72e0e63791 string"}out{/if}', '{if "test"}out{/if} {if "long var_a46d7cbbeb2015d076399df72e0e63791 string"}out{/if}'),
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

	protected function plainUnparsedTurnsFalse()
	{
		return array(
			array('Unparsed Plain',				'{if notset}out{/if}',			'{if FALSE}out{/if}'),
			array('Unparsed with Modifier',		'{if notset:modified}out{/if}',	'{if FALSE}out{/if}'),
			array('Unparsed variable tag',		'{if {notset}}out{/if}',		'{if FALSE}out{/if}'),
			array('Unparsed variable-variable',	'{if a{notset}b}out{/if}',		'{if FALSE}out{/if}'),
		);
	}

	protected function simpleVariableReplacementsTest()
	{
		return array(
			array('Simple TRUE Boolean',	'{if xyz}out{/if}',   '{if "1"}out{/if}',	array('xyz' => TRUE)),
			array('Simple FALSE Boolean',	'{if xyz}out{/if}',   '{if ""}out{/if}',	array('xyz' => FALSE)),
			array('Simple Zero Int',		'{if xyz}out{/if}',   '{if "0"}out{/if}',	array('xyz' => 0)),
			array('Simple Positive Int',	'{if xyz}out{/if}',   '{if "5"}out{/if}',	array('xyz' => 5)),
			array('Simple Negative Int',	'{if xyz}out{/if}',   '{if "-5"}out{/if}',	array('xyz' => -5)),
			array('Simple Empty String',	'{if xyz}out{/if}',   '{if ""}out{/if}',	array('xyz' => '')),
		);
	}

	protected function simpleVariableComparisonsTest()
	{
		return array(
			array('Compare FALSE Boolean',	'{if xyz > FALSE}out{/if}',		'{if "" > FALSE}out{/if}',		array('xyz' => FALSE)),
			array('Compare Zero Int',		'{if xyz < 0}out{/if}',			'{if "0" < 0}out{/if}',			array('xyz' => 0)),
			array('Compare Positive Int',	'{if xyz <> 5}out{/if}',		'{if "5" <> 5}out{/if}',		array('xyz' => 5)),
			array('Compare Negative Int',	'{if xyz>-5}out{/if}',			'{if "-5">-5}out{/if}',			array('xyz' => -5)),
			array('Compare Empty String',	'{if xyz<=""}out{/if}',			'{if ""<=""}out{/if}',			array('xyz' => '')),
			array('Compare FALSE Booleans',	'{if xyz == FALSE}out{/if}',	'{if "" == FALSE}out{/if}',		array('xyz' => FALSE)),
			array('Compare TRUE Booleans',	'{if xyz == TRUE}out{/if}',		'{if "1" == TRUE}out{/if}',		array('xyz' => TRUE)),
			array('Compare NoSpace Bools',	'{if FALSE!=TRUE}out{/if}',		'{if FALSE!=TRUE}out{/if}',		array('xyz' => TRUE)),
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
			array('Too Many Open Parentheses',				'{if (((5 && 6)}out{/if}',	'{if (((5 && 6)))}out{/if}'),
			array('Too Many Closing Parentheses',			'{if (5 && 6)))}out{/if}',	'{if (((5 && 6)))}out{/if}'),
			array('Difficult Missing Open Parentheses',		'{if ((5 || 7 == 8) AND (6 != 6)))}out{/if}',	'{if (((5 || 7 == 8) AND (6 != 6)))}out{/if}'),
			array('Difficult Missing Closing Parentheses',	'{if ((5 || 7 == 8) AND ((6 != 6))}out{/if}',	'{if ((5 || 7 == 8) AND ((6 != 6)))}out{/if}'),
			array('Ignore Quoted Parenthesis Mismatch',		'{if "(5 && 6)))"}out{/if}',	'{if "&#40;5 && 6&#41;&#41;&#41;"}out{/if}'),
		);
	}

	protected function protectingStrings()
	{
		$bs = '\\'; // NOTE: this is a _single_ backslash

		return array(
			array('Protecting Single Quotes',		'{if xyz == "\'"}out{/if}',			'{if "&#39;" == "&#39;"}out{/if}',					array('xyz' => "'")),
			array('Protecting Double Quotes',		"{if xyz == '\"'}out{/if}",			'{if "&#34;" == "&#34;"}out{/if}',					array('xyz' => '"')),
			array('Protecting Parentheses',			'{if xyz == "()"}out{/if}',			'{if "&#40;&#41;" == "&#40;&#41;"}out{/if}',		array('xyz' => "()")),
			array('Protecting Dollar Signs',		'{if xyz == "$"}out{/if}',			'{if "&#36;" == "&#36;"}out{/if}',					array('xyz' => "$")),
			array('Protecting Braces',				'{if xyz == "{}"}out{/if}',			'{if "&#123;&#125;" == "{}"}out{/if}',				array('xyz' => "{}")),
			array('Protecting New Lines',			"{if xyz == '\n'}out{/if}",			'{if "" == ""}out{/if}',							array('xyz' => "\n")),
			array('Protecting Carriage Returns',	"{if xyz == '\r'}out{/if}",			'{if "" == ""}out{/if}',							array('xyz' => "\r")),
			array('Protecting Backslashes',			"{if xyz == '{$bs}{$bs}'}out{/if}",	'{if "&#92;" == "&#92;"}out{/if}',					array('xyz' => $bs)),
			array('Allowing Escape Characters',		"{if xyz == '{$bs}''}out{/if}",		'{if "&#92;" == "&#39;"}out{/if}',					array('xyz' => $bs)),
			array('Nested Braces',					"{if xyz == '}great'}{/if}",		'{if "" == "&#125;great"}{/if}',					array('xyz' => '')),
		);
	}

	public function embeddedTags()
	{
		return array(
			array('Unqouted Embedded Tag',				'{if {exp:foo:bar}}out{/if}',	'{if {exp:foo:bar}}out{/if}', 				'{if FALSE}out{/if}'),
			array('Double Quoted Embedded Tag',			'{if "{exp:foo:bar}"}out{/if}',	'{if "{exp:foo:bar}"}out{/if}', 			'{if "&#123;exp:foo:bar&#125;"}out{/if}'),
			array('Single Quoted Embedded Tag',			"{if '{exp:foo:bar}'}out{/if}",	'{if "{exp:foo:bar}"}out{/if}', 			'{if "&#123;exp:foo:bar&#125;"}out{/if}'),
			array('Embedded Tag Before Conditional',	'{exp:foo:bar}{if 5}out{/if}',	'{exp:foo:bar}{if 5}out{/if}', 				'{exp:foo:bar}{if 5}out{/if}'),
			array('Embedded Tag After Conditional',		'{if 5}out{/if}{exp:foo:bar}',	'{if 5}out{/if}{exp:foo:bar}', 				'{if 5}out{/if}{exp:foo:bar}'),
			array('User Supplied Embedded Tag',			'{if baz}out{/if}',				'{if "&#123;exp:foo:bar&#125;"}out{/if}',	'{if "&#123;exp:foo:bar&#125;"}out{/if}',	array('baz' => '{exp:foo:bar}')),
		);
	}

	protected function safteyCleanup()
	{
		return array(
			array('Function Cleaning',				'{if phpinfo()}out{/if}',		'{if FALSE && ()}out{/if}'),
			array('Single Variable Cleaning',		'{if foo}out{/if}',				'{if FALSE}out{/if}'),
			array('Double Variable Cleaning',		'{if foo bar}out{/if}',			'{if FALSE}out{/if}'),
			array('Tripple Variable Cleaning',		'{if foo bar baz}out{/if}',		'{if FALSE}out{/if}'),
		);
	}

	protected function safetyFalseCleanup()
	{
		return array(
			array('FALSE ()',				'{if FALSE ()}out{/if}',			'{if FALSE && ()}out{/if}'),
			array('FALSE  FALSE',			'{if FALSE  FALSE}out{/if}',		'{if FALSE}out{/if}'),
			array('FALSE  FALSE  FALSE',	'{if FALSE  FALSE  FALSE}out{/if}',	'{if FALSE}out{/if}'),
		);
	}

	protected function wonkySpacelessStringLogicOperators()
	{
		return array(
			array('Wonky No Space AND',	'{if 7AND5}out{/if}',	'{if 7 FALSE}out{/if}'),
			array('Wonky No Space OR',	'{if 5OR7}out{/if}',	'{if 5 FALSE}out{/if}'),
			array('Wonky No Space XOR',	'{if 5XOR7}out{/if}',	'{if 5 FALSE}out{/if}'),
		);
	}

	protected function wonkyRepetitions()
	{
		return array(
			array('Double Modulo',		 '{if 5 %% 7}out{/if}',		'{if 5 %% 7}out{/if}'),
			array('Double AND', 		 '{if 5 && AND 7}out{/if}',	'{if 5 && AND 7}out{/if}'),
			array('Double No Space AND', '{if 5 &&AND 7}out{/if}',	'{if 5 &&AND 7}out{/if}'),
			array('Double Comparison',	 '{if 5 > < 7}out{/if}',	'{if 5 > < 7}out{/if}'),
			array('Shift by comparison', '{if 5 >>> 7}out{/if}',	'{if 5 FALSE > 7}out{/if}'),

		);
	}

	protected function wonkyEmpty()
	{
		return array(
			array('Totally Blank',		'{if }out{/if}',				'{if }out{/if}'),
			array('Blank Parentheses',	'{if ()}out{/if}',				'{if ()}out{/if}'),
			array('Compare To Blank',	'{if () == 5}out{/if}',			'{if () == 5}out{/if}'),
			array('Blank Logic',		'{if () AND 5 || ()}out{/if}',	'{if () AND 5 || ()}out{/if}'),
		);
	}

	protected function wonkyMutableBooleans()
	{
		return array(
			array('TRUE CANNOT be a variable',	'{if xyz == TRUE}out{/if}', '{if "1" == TRUE}out{/if}',	array('xyz' => TRUE, 'TRUE' => "baz")),
			array('FALSE CANNOT be a variable',	'{if xyz == FALSE}out{/if}', '{if "1" == FALSE}out{/if}',	array('xyz' => TRUE, 'FALSE' => "bat")),
			array('true can be a variable?!',	'{if xyz == true}out{/if}', '{if "1" == "baz"}out{/if}',	array('xyz' => TRUE, 'true' => "baz")),
			array('false can be a variable?!',	'{if xyz == false}out{/if}', '{if "1" == "bat"}out{/if}',	array('xyz' => TRUE, 'false' => "bat")),
			array('true can equal false',		'{if true == false}out{/if}', '{if "" == false}out{/if}',	array('xyz' => TRUE, 'true' => ""))
		);
	}

	protected function wonkyDifferentBehaviorWithoutVariables()
	{
		return array(
			array('Total Nonsense Allowed',		'{if fdsk&)(Ijf7)}out{/if}',	'{if fdsk&)(Ijf7)}out{/if}', FALSE),
			array('No Parenthesis Matching',	'{if (((5 && 6)}out{/if}',	'{if (((5 && 6)}out{/if}', FALSE),
		);
	}

	protected function wonkyPhpOperatorsWorkOnlyWithWhitespace()
	{
		return array(
			array('Addition works with spaces',				'{if int + int}out{/if}', '{if "5" + "5"}out{/if}'),
			array('Addition does not work without spaces',	'{if int+int}out{/if}', '{if FALSE + FALSE}out{/if}'),
			array('Concatenation with spaces',				'{if string . string}out{/if}', '{if "ee" . "ee"}out{/if}'),
			array('Concatenation without spaces',			'{if string.string}out{/if}', '{if FALSE . FALSE}out{/if}'),
			array('Subtract dash-words variable',			'{if a-number - int}out{/if}', '{if "15" - "5"}out{/if}', array('a-number' => 15)),
		);
	}

	protected function wonkyComparisonOperators()
	{
		return array(
			array('= Instead of ==',	'{if 5 = 5}out{/if}',	'{if 5 FALSE 5}out{/if}'),
			array('! Instead of !=',	'{if 5 ! 5}out{/if}',	'{if 5 FALSE 5}out{/if}'),
			array('& Instead of &&',	'{if 5 & 5}out{/if}',	'{if 5 FALSE 5}out{/if}'),
			array('| Instead of ||',	'{if 5 | 5}out{/if}',	'{if 5 FALSE 5}out{/if}'),
		);
	}

	// See: https://support.ellislab.com/bugs/detail/20323
	protected function bug20323()
	{
		$vars = array(
			'value' => 'Test with long caption title to test layout',
			'title' => 'Test article with captions'
		);

		return array(
			array('Variable in variable',	'{if value}out{/if}',											'{if "Test with long caption title to test layout"}out{/if}',	$vars),
			array('Variable in string',		'{if "Test with long caption title to test layout"}out{/if}',	'{if "Test with long caption title to test layout"}out{/if}',	$vars),
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
		static $i = 0;
		return $this->fixedRandomString.($i++);
	}

	public function conditional_is_unsafe($str)
	{
		$result = parent::conditional_is_unsafe($str);

		if ($result === TRUE)
		{
			throw new UnsafeConditionalException('Conditional is unsafe.');
		}

		return $result;
	}

	public function extract_conditionals($str, $vars)
	{
		$result = parent::extract_conditionals($str, $vars);

		if ($result === FALSE)
		{
			throw new InvalidConditionalException('Conditional is invalid.');
		}

		return $result;
	}
}

function surrounding_character($string)
{
	$first_char = substr($string, 0, 1);

	return ($first_char == substr($string, -1, 1)) ? $first_char : FALSE;
}

function unique_marker($ident)
{
	return 'randommarker'.$ident;
}

class UnsafeConditionalException extends Exception {}
class InvalidConditionalException extends Exception {}