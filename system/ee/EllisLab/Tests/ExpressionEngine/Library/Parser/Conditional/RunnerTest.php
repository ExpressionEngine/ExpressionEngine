<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\Runner;
use PHPUnit\Framework\TestCase;

class RunnerTest extends TestCase {

	public function setUp()
	{
		$this->runner = new Runner();
	}

	public function tearDown()
	{
		$this->runner = NULL;
	}

	protected function runConditionWithoutAnnotations($str, $vars = array(), $runner = NULL)
	{
		return preg_replace(
			"/\{!--.*?--\}/s",
			'',
			$this->runCondition($str, $vars, $runner)
		);
	}

	protected function runCondition($str, $vars = array(), $runner = NULL)
	{
		if ( ! isset($runner))
		{
			$runner = $this->runner;
		}

		return $runner->processConditionals($str, $vars);
	}

	protected function runConditionTest($description, $str_in, $expected, $vars = array())
	{
		if (strpos($expected, '{if') !== FALSE)
		{
			$result = $this->runConditionWithoutAnnotations($str_in, $vars);
		}
		else
		{
			$result = $this->runCondition($str_in, $vars);
		}

		$this->assertEquals($expected, $result, $description);
	}

	/**
	 * @dataProvider plainDataProvider
	 */
	public function testPlainConditionalsWithoutVariables($description, $str_in, $expected_out, $vars = array())
	{
		$this->runConditionTest($description, $str_in, $expected_out, $vars);
	}

	/**
	 * @dataProvider badDataProvider
	 */
	public function testBadConditionalsWithoutVariables($exception, $description, $str_in)
	{
		$this->setExpectedException($exception);
		$this->runConditionTest($description, $str_in, '');
	}

	/**
	 * @dataProvider safetyOnDataProvider
	 */
	public function testSafetyOn($description, $str_in, $expected_out)
	{
		$this->runner->safetyOn();
		$this->runConditionTest($description, $str_in, $expected_out);
	}

	public function testBasicVariableReplacement()
	{
		$runner = new Runner();

		// var1 is in there to prevent execution
		$string = '{if var1 && var2 == \'bob\'}yes{/if}';

		$this->assertEquals(
			'{if var1 && 3 == \'bob\'}yes{/if}',
			$this->runConditionWithoutAnnotations($string, array('var2' => 3), $runner),
			'Integer Variable Replacement'
		);

		$this->assertEquals(
			'{if var1 && \'mary\' == \'bob\'}yes{/if}',
			$this->runConditionWithoutAnnotations($string, array('var2' => 'mary'), $runner),
			'String Variable Replacement'
		);

		$this->assertEquals(
			'{if var1 && true == \'bob\'}yes{/if}',
			$this->runConditionWithoutAnnotations($string, array('var2' => TRUE), $runner),
			'Bool TRUE Variable Replacement'
		);

		$this->assertEquals(
			'{if var1 && false == \'bob\'}yes{/if}',
			$this->runConditionWithoutAnnotations($string, array('var2' => FALSE), $runner),
			'Bool FALSE Variable Replacement'
		);
	}

	public function testProgressiveConstruction()
	{
		$runner = new Runner();

		$inital = '{if var1 && var2 && var3 == \'bob\'}yes{if:else}no{/if}';

		$var2 = $this->runConditionWithoutAnnotations($inital, array('var1' => 3), $runner);

		$this->assertEquals(
			'{if 3 && var2 && var3 == \'bob\'}yes{if:else}no{/if}',
			$var2,
			'Integer Variable Replacement'
		);

		$var3 = $this->runConditionWithoutAnnotations($var2, array('var3' => 'bob'), $runner);

		$this->assertEquals(
			'{if 3 && var2 && \'bob\' == \'bob\'}yes{if:else}no{/if}',
			$var3,
			'String Variable Replacement'
		);

		// Adding var2 completes the conditional and causes evaluation
		$this->assertEquals(
			'yes',
			$this->runCondition($var3, array('var2' => 4), $runner),
			'Last variable triggers evaluation'
		);

		// Do it again with a falsey value to sanity check it
		$this->assertEquals(
			'no',
			$this->runCondition($var3, array('var2' => 0), $runner),
			'Last variable triggers evaluation to false'
		);
	}

	public function testBranchConditionRewritingAndPruning()
	{
		$runner = new Runner();

		$string = '{if 5 == var}yes{if:elseif 5 == 5}maybe{if:else}no{/if}';

		$this->assertEquals(
			'{if 5 == var}yes{if:else}maybe{/if}',
			$this->runConditionWithoutAnnotations($string, array(), $runner),
			'Elseif branch rewritten to else and old else pruned'
		);

		$string = '{if 5 == var}yes{if:elseif 5 == 6}maybe{if:else}no{/if}';

		$this->assertEquals(
			'{if 5 == var}yes{if:else}no{/if}',
			$this->runConditionWithoutAnnotations($string, array(), $runner),
			'Elseif branch evaluated to FALSE and is pruned'
		);

		$string = '{if 5 == 7}nope{if:elseif 5 == var}maybe{if:else}maybe2{/if}';

		$this->assertEquals(
			'{if 5 == var}maybe{if:else}maybe2{/if}',
			$this->runConditionWithoutAnnotations($string, array(), $runner),
			'If evaluated to false, if is pruned, elseif is promoted'
		);

		// HS ticket 87661
		$string = '{if nonexistent}nope';
		$string .= "{if:elseif test == 'true'}yep";
		$string .= '{if:elseif another_nonexistent}never';
		$string .= '{if:else}nope{/if}';

		$this->assertEquals(
			'{if nonexistent}nope{if:else}yep{/if}',
			$this->runConditionWithoutAnnotations($string, array('test' => 'true'), $runner),
			'Double elseif promotion, true rewriting, and branch pruning'
		);

		// now the big one
		$string = '{if 5 == 7}nope';
		$string .= '{if:elseif \'bob\' == \'mary\'}never';
		$string .= '{if:elseif 7 == var}maybe';
		$string .= '{if:elseif 7 == 7}quitepossibly';
		$string .= '{if:elseif 8 == 0}nah';
		$string .= '{if:else}nope{/if}';

		$this->assertEquals(
			'{if 7 == var}maybe{if:else}quitepossibly{/if}',
			$this->runConditionWithoutAnnotations($string, array(), $runner),
			'Double elseif promotion, true rewriting, and branch pruning'
		);
	}

	public function plainDataProvider()
	{
		// assemble all of the tests
		return array_merge(
			array(),
			$this->conditionals(),
			$this->nestedConditionals(),
			$this->whitespaceRewriting(),
			$this->basicMaths(),
			$this->negation(),
			$this->basicBranching(),
			$this->plainLogicOperatorTests(),
			$this->comparisonOperatorTests(),
			$this->concatenationTests(),
			$this->eeTagTests(),
			$this->stringTests(),
			$this->numberTests(),
			$this->variableTests(),
			$this->operatorPrecedenceTests(),
			$this->parenthesisTests(),
			$this->userGuideTestsBooleanValueComparisons(),

			// From the bug tracker
			$this->bug20323_variables_in_strings()
		);
	}

	public function badDataProvider()
	{
		$parser_exception = 'EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\ParserException';

		return array(
			array($parser_exception,	'Double float',						'{if 1.2.3 }out{/if}'),
			array($parser_exception,	'Comment Looks Like Math',			'{if 7 /* 5 }out{/if}'),
			array($parser_exception,	'Inline Comment Looks Like Math',	'{if 7 // 5 }out{/if}'),

		);
	}

	public function safetyOnDataProvider()
	{
		return array(
			array('Unparsed Tags to false',			'{if {tag} == FALSE}yes{if:else}no{/if}',		'yes'),
			array('Unparsed Tags to false 2',		'{if {tag} != FALSE}no{if:else}yes{/if}',		'yes'),
			array('Unparsed Tag with param',		'{if {tag a="b"} == FALSE}yes{if:else}no{/if}',	'yes'),
			array('Unparsed Tag with param 2',		'{if {tag b="c"} != FALSE}no{if:else}yes{/if}',	'yes'),
			array('Unparsed Vars to false',			'{if var1 == FALSE}yes{if:else}no{/if}',		'yes'),
			array('Unparsed Vars to false 2',		'{if var1 || var2}no{if:else}yes{/if}',			'yes'),
			array('Unparsed quoted to false',		'{if "{tag}" == FALSE}yes{if:else}no{/if}',		'yes'),
			array('Unparsed quoted to false 2',		'{if "{tag}" != FALSE}no{if:else}yes{/if}',		'yes'),
			array('Unparsed quoted with param',		'{if "{tag a=\"b\"}" == FALSE}yes{if:else}no{/if}',		'yes'),
			array('Unparsed quoted with param 2',	'{if "{tag a=\'c\'}" != FALSE}no{if:else}yes{/if}',		'yes'),
		);
	}

	protected function conditionals()
	{
		return array(
			// evaluation
			array('Evaluate If With Space',		 '{if 5 == 5}out{/if}',					'out'),
			array('Evaluate If With Tab',		 '{if	5 == 5}out{/if}',				'out'),
			array('Evaluate If With Newline',	 "{if\n5 == 5}out{/if}",				'out'),
			array('Evaluate If With CRLF',		 "{if\r\n5 == 5}out{/if}",				'out'),
			array('Evaluate If With Whitespace', "{if\n\t5 == 5\n}out{/if}",			'out'),
			array('Evaluate If With Comment',	 "{if {!-- cool --}5 == 5\n}out{/if}",	'out'),
		);
	}

	protected function nestedConditionals()
	{
		return array(
			// evaluation
			array('Nested in TRUE',			'{if 5 == 5}{if var == "foo"}out{/if}{/if}',								"{if var == 'foo'}out{/if}"),
			array('Nested in FALSE',		'{if 2 == 1}{if var2 == "foo"}no{/if}{if:else}out{/if}',					'out'),
			array('Nested in with comment',	'{if 2 == 1}{if var2 == "foo"}no{!-- never --}nope{/if}{if:else}out{/if}',	'out'),
		);
	}

	protected function whitespaceRewriting()
	{
		// rewriting is forced by the unparsed variable
		// the test here is mostly make sure whitespace isn't lost. The
		// beautification is merely a side-effect
		return array(
			array('Rewrite: If With Space',		 '{if 5 == 5 && var}out{/if}',			'{if 5 == 5 && var}out{/if}'),
			array('Rewrite: If With Tab',		 '{if	5 == 5 && var}out{/if}',		'{if 5 == 5 && var}out{/if}'),
			array('Rewrite: If With Newline',	 "{if\n5 == 5 && var}out{/if}",			'{if 5 == 5 && var}out{/if}'),
			array('Rewrite: If With CRLF',		 "{if\r\n5 == 5 && var}out{/if}",		'{if 5 == 5 && var}out{/if}'),
			array('Rewrite: If With Whitespace', "{if\n\t5 == 5\n&& var\n}out{/if}",	'{if 5 == 5 && var}out{/if}'),
		);
	}

	protected function basicMaths()
	{
		return array(
			array('Math plus',		'{if 5 + 5 == 10}yes{if:else}no{/if}',			'yes'),
			array('Math minus',		'{if 7 - 9 == -2}yes{if:else}no{/if}',			'yes'),
			array('Math star',		'{if 5 * 5 == 25}yes{if:else}no{/if}',			'yes'),
			array('Math slash',		'{if 12 / 4 == 3}yes{if:else}no{/if}',			'yes'),
			array('Math mod',		'{if 12 % 5 == 2}yes{if:else}no{/if}',			'yes'),
			array('Power hat',		'{if 2 ^ 3 ^ 2 == 512}yes{if:else}no{/if}',		'yes'),
			array('Power star',		'{if 2 ** 3 ** 2 == 512}yes{if:else}no{/if}',	'yes'),
			array('2-nd root',		'{if 9 ^ .5 == 3}yes{if:else}no{/if}',			'yes'),
			array('3-rd root',		'{if 27 ** (1/3) == 3}yes{if:else}no{/if}',		'yes'),
		);
	}

	protected function negation()
	{
		return array(
			array('Negate parens',		'{if 5 + -(5*3 - 0) == -10}yes{if:else}no{/if}',	'yes'),
			array('Negate exponent',	'{if 5 ^ -2 == .04}yes{if:else}no{/if}',			'yes'),
			array('Negate base',		'{if -5 ^ 2 == -25}yes{if:else}no{/if}',			'yes'),
			array('Square of negative',	'{if (-5) ^ 2 == 25}yes{if:else}no{/if}',			'yes'),
			array('Negate not zero',	'{if -!0 == -1}yes{if:else}no{/if}',				'yes'),
			array('Not negated zero',	'{if !-0 == TRUE}yes{if:else}no{/if}',				'yes'),

		);
	}

	protected function basicBranching()
	{
		return array(
			array('Evaluate Ifelseif, if true',	'{if 5 == 5}yes{if:elseif 5 == 5}no{/if}',	'yes'),
			array('Evaluate Ifelse, if true',	'{if 5 == 5}yes{if:else}no{/if}',			'yes'),
			array('Evaluate Ifelseif, if false','{if 5 == 6}no{if:elseif 5 == 5}yes{/if}',	'yes'),
			array('Evaluate Ifelse, if false',	'{if 5 == 6}no{if:else}yes{/if}',			'yes'),
		);
	}

	protected function plainLogicOperatorTests()
	{
		return array(
			array('Plain && Integer',		'{if 5 && 5}yes{if:else}no{/if}',	'yes'),
			array('Plain || Integer',		'{if 5 || 7}yes{if:else}no{/if}',	'yes'),
			array('Plain AND Integer',		'{if 7 AND 5}yes{if:else}no{/if}',	'yes'),
			array('Plain OR Integer',		'{if 5 OR 7}yes{if:else}no{/if}',	'yes'),
			array('Plain XOR Integer',		'{if 5 XOR 0}yes{if:else}no{/if}',	'yes'),
			array('Plain OR Lowercase',		'{if 5 or 7}yes{if:else}no{/if}',	'yes'),
			array('Plain AND Lowercase',	'{if 7 and 5}yes{if:else}no{/if}',	'yes'),
			array('Plain XOR Lowercase',	'{if 5 xor 0}yes{if:else}no{/if}',	'yes'),
			array('Plain ! Integer',		'{if ! 0}yes{if:else}no{/if}',		'yes'),

			// and now false
			array('Plain && False',		'{if 5 && 0}no{if:else}yes{/if}',	'yes'),
			array('Plain || False',		'{if 0 || 0}no{if:else}yes{/if}',	'yes'),
			array('Plain AND False',	'{if 7 AND 0}no{if:else}yes{/if}',	'yes'),
			array('Plain OR False',		'{if 0 OR 0}no{if:else}yes{/if}',	'yes'),
			array('Plain XOR False',	'{if 5 XOR 7}no{if:else}yes{/if}',	'yes'),
			array('Plain ! False',		'{if ! 7}no{if:else}yes{/if}',		'yes'),
		);
	}

	protected function comparisonOperatorTests()
	{
		return array(
			array('Plain == Integer',			'{if 5 == 5}yes{if:else}no{/if}',					'yes'),
			array('Plain != Integer',			'{if 3 != 6}yes{if:else}no{/if}',					'yes'),
			array('Plain == String',			'{if "a" == "a"}yes{if:else}no{/if}',				'yes'),
			array('Plain != String',			'{if "a" != "b"}yes{if:else}no{/if}',				'yes'),
			array('Plain <= Integer',			'{if 3 <= 5}yes{if:else}no{/if}',					'yes'),
			array('Plain <= Integer 2',			'{if 5 <= 5}yes{if:else}no{/if}',					'yes'),
			array('Plain >= Integer',			'{if 7 >= 5}yes{if:else}no{/if}',					'yes'),
			array('Plain >= Integer 2',			'{if 5 >= 5}yes{if:else}no{/if}',					'yes'),
			array('Plain <> Integer',			'{if 7 <> 5}yes{if:else}no{/if}',					'yes'),
			array('Plain > Integer',			'{if 7 > 5}yes{if:else}no{/if}',					'yes'),
			array('Plain < Integer',			'{if 5 < 7}yes{if:else}no{/if}',					'yes'),

			array('String Begins With',			'{if "testing" ^= "test"}yes{if:else}no{/if}',		'yes'),
			array('Integer Begins With',		'{if 123456 ^= 123}yes{if:else}no{/if}',			'yes'),
			array('Float Begins With',			'{if 42.7 ^= 42}yes{if:else}no{/if}',				'yes'),
			array('String Contains',			'{if "testing" *= "sti"}yes{if:else}no{/if}',		'yes'),
			array('Integer Contains',			'{if 123456 *= 345}yes{if:else}no{/if}',			'yes'),
			array('Float Contains',				'{if 42.7 *= 42}yes{if:else}no{/if}',				'yes'),
			array('Mix Contains',				'{if 42.7 *= "42"}yes{if:else}no{/if}',				'yes'),
			array('Mix Contains 2',				'{if "42.7" *= 42}yes{if:else}no{/if}',				'yes'),
			array('Contains Period',			'{if 42.7 *= "."}yes{if:else}no{/if}',				'yes'),
			array('String Ends With',			'{if "testing" $= "ing"}yes{if:else}no{/if}',		'yes'),
			array('Integer Ends With',			'{if 123456 $= 456}yes{if:else}no{/if}',			'yes'),
			array('Float Ends With',			'{if 42.7 $= ".7"}yes{if:else}no{/if}',				'yes'),
			array('String Regex Compare',		'{if "P25" ~ "/^P[0-9]+/"}yes{if:else}no{/if}',		'yes'),
			array('String Regex Quanifier',		'{if "P25" ~ "/^P[0-9]{2}/"}yes{if:else}no{/if}',	'yes'),
			array('String Regex Quanifier 2',	'{if "P25" ~ "/^P[0-9]{2,4}/"}yes{if:else}no{/if}',	'yes'),
			array('Integer Regex Compare',		'{if 1234 ~ "/\d+/"}yes{if:else}no{/if}',			'yes'),
			array('Float Regex Compare',		'{if 42.7 ~ "/\d+\.\d/"}yes{if:else}no{/if}',		'yes'),

			array('False String Begins With',	'{if "testing" ^= "ing"}no{if:else}yes{/if}',		'yes'),
			array('False Integer Begins With',	'{if 123456 ^= 456}no{if:else}yes{/if}',			'yes'),
			array('False Float Begins With',	'{if 42.7 ^= ".7"}no{if:else}yes{/if}',				'yes'),
			array('False String Contains',		'{if "testing" *= "hello"}no{if:else}yes{/if}',		'yes'),
			array('False Integer Contains',		'{if 123456 *= 321}no{if:else}yes{/if}',			'yes'),
			array('False Float Contains',		'{if 42.7 *= 24}no{if:else}yes{/if}',				'yes'),
			array('False Mix Contains',			'{if 42.7 *= "24"}no{if:else}yes{/if}',				'yes'),
			array('False Mix Contains 2',		'{if "42.7" *= 24}no{if:else}yes{/if}',				'yes'),
			array('False Contains Period',		'{if 42 *= "."}no{if:else}yes{/if}',				'yes'),
			array('False String Ends With',		'{if "testing" $= "test"}no{if:else}yes{/if}',		'yes'),
			array('False Integer Ends With',	'{if 123456 $= 123}no{if:else}yes{/if}',			'yes'),
			array('False Float Ends With',		'{if 42.7 $= 42}no{if:else}yes{/if}',				'yes'),
			array('False String Regex',			'{if "C25" ~ "/^P[0-9]+/"}no{if:else}yes{/if}',		'yes'),
			array('False String Quanifier',		'{if "C25" ~ "/^P[0-9]{2}+/"}no{if:else}yes{/if}',	'yes'),
			array('False String Quanifier 2',	'{if "C25" ~ "/^P[0-9]{2,4}+/"}no{if:else}yes{/if}','yes'),
			array('False Integer Regex',		'{if 1234 ~ "/[^\d]+/"}no{if:else}yes{/if}',		'yes'),
			array('False Float Regex',			'{if 42.7 ~ "/[^\d+\.\d]/"}no{if:else}yes{/if}',	'yes'),

			array('False == Integer',			'{if 5 == 2}no{if:else}yes{/if}',					'yes'),
			array('False != Integer',			'{if 6 != 6}no{if:else}yes{/if}',					'yes'),
			array('False == String',			'{if "a" == "b"}no{if:else}yes{/if}',				'yes'),
			array('False != String',			'{if "a" != "a"}no{if:else}yes{/if}',				'yes'),
			array('False <= Integer',			'{if 5 <= 3}no{if:else}yes{/if}',					'yes'),
			array('False >= Integer',			'{if 5 >= 7}no{if:else}yes{/if}',					'yes'),
			array('False <> Integer',			'{if 7 <> 7}no{if:else}yes{/if}',					'yes'),
			array('False > Integer',			'{if 5 > 7}no{if:else}yes{/if}',					'yes'),
			array('False < Integer',			'{if 7 < 5}no{if:else}yes{/if}',					'yes'),
		);
	}

	protected function concatenationTests()
	{
		return array(
			array('Basic Concatenation',	'{if "te"."st" == "test"}yes{if:else}no{/if}',		'yes'),
			array('Space Concatenation',	'{if "te" . "st" == "test"}yes{if:else}no{/if}',	'yes'),
			array('Single Variable Concat',	'{if var . "st" == "test"}yes{if:else}no{/if}',		'yes', array('var' => 'te')),
			array('Two Variable Concat',	'{if var . iable == "test"}yes{if:else}no{/if}',	'yes', array('var' => 'te', 'iable' => 'st')),
			array('Three String Concat',	'{if "te"."st"."s" == "tests"}yes{if:else}no{/if}',	'yes'),
			array('Integer Concat',			'{if "te". 12 == "te12"}yes{if:else}no{/if}',		'yes'),
			array('Float Concat',			'{if "te". 1.2 == "te1.2"}yes{if:else}no{/if}',		'yes'),
		);
	}

	protected function eeTagTests()
	{
		return array(
			array('Raw ee tag',			'{if {tag1} == {tag2}"yup"{tag3}}yes{if:else}no{/if}', '{if {tag1} == {tag2}\'yup\'{tag3}}yes{if:else}no{/if}'),
			array('Raw exp: tag',		'{if {exp:plugin}{tag}{/exp:plugin} == "yup"}yes{if:else}no{/if}', '{if {exp:plugin}{tag}{/exp:plugin} == \'yup\'}yes{if:else}no{/if}'),
			array('Raw ee w/ params',	'{if {tag1 foo="bar"} == {tag2}"yup"{tag3 baz="bat" dog="cat"}}yes{if:else}no{/if}', '{if {tag1 foo="bar"} == {tag2}\'yup\'{tag3 baz="bat" dog="cat"}}yes{if:else}no{/if}'),
			array('Raw exp: w/ params',	'{if {exp:plugin a="b" c="d" d="{nested}"}{tag}{/exp:plugin} == "yup"}yes{if:else}no{/if}', '{if {exp:plugin a="b" c="d" d="{nested}"}{tag}{/exp:plugin} == \'yup\'}yes{if:else}no{/if}'),

			array('Quoted tag',				'{if "{tag1}"."{tag2}" == "dog"}yes{if:else}no{/if}', '{if \'{tag1}\' . \'{tag2}\' == \'dog\'}yes{if:else}no{/if}'),
			array('Quoted with params',		'{if "{tag param=\'quotes\'}"}yes{if:else}no{/if}', '{if \'{tag param=\\\'quotes\\\'}\'}yes{if:else}no{/if}'),
		);
	}

	protected function stringTests()
	{
		$bs = '\\'; // SINGLE backslash
		$sq = "'"; // single quote
		$dq = '"'; // dairy queen

		return array(
			array('Zero string is true',			'{if "0" == TRUE}yes{if:else}no{/if}',							'yes'),
			array('Zero string is true',			'{if TRUE == "0"}yes{if:else}no{/if}',							'yes'),
			array('Zero string var is true',		'{if var == TRUE}yes{if:else}no{/if}',							'yes', array('var' => '0')),
			array('Empty string is false',			'{if "" == FALSE}yes{if:else}no{/if}',							'yes'),
			array('Strings with #s are not #s',		'{if 5 == "5yep"}no{if:else}yes{/if}',							'yes'),
			array('Strings with #s are not #s',		'{if "5yep" == 5}no{if:else}yes{/if}',							'yes'),
			array('Esc Single quote in double',		'{if "ee'.$bs.$sq.'s parser" == var}yes{if:else}no{/if}',		'yes', array('var' => "ee's parser")),
			array('Esc Double quote in double',		'{if "ee'.$bs.$dq.'s parser" == var}yes{if:else}no{/if}',		'yes', array('var' => 'ee"s parser')),
			array('Esc Single quote in single',		"{if 'ee".$bs.$sq."s parser' == var}yes{if:else}no{/if}",		'yes', array('var' => "ee's parser")),
			array('Esc Double qutote in single',	"{if 'ee".$bs.$dq."s parser' == var}yes{if:else}no{/if}",		'yes', array('var' => 'ee"s parser')),
			array('Double backslash',				"{if 'this is ".$bs.$bs." the end' == var}yes{if:else}no{/if}",	'yes', array('var' => 'this is '.$bs.' the end')),
			array('Letters not escapable',			'{if "/'.$bs.'d+/" == var}yes{if:else}no{/if}',						'yes', array('var' => '/'.$bs.'d+/')),
		);
	}

	protected function numberTests()
	{
		return array(
			array('Zero int is false',		'{if 0 == FALSE}yes{if:else}no{/if}',			'yes'),
			array('Zero float is false',	'{if 0.0 == FALSE}yes{if:else}no{/if}',			'yes'),
			array('Zero int var is false',	'{if var == FALSE}yes{if:else}no{/if}',			'yes', array('var' => 0)),
		);
	}

	protected function variableTests()
	{
		return array(
			array('prefixed',	'{if foo:bar == 7}out{/if}',	'out', array('foo:bar' => TRUE))
		);
	}

	protected function operatorPrecedenceTests()
	{
		// expressions in this array should be written in such a way that
		// a precdence reversal would result in a different result.
		return array(
			array('== before math', '{if 2 + 5 == 9 - 2}yes{if:else}no{/if}',		'yes'),

			// same precedence -> left to right
			array('* and / ltr',	'{if 5/5 * 2 == 2}yes{if:else}no{/if}',		'yes'),
			array('/ and * ltr',	'{if 5 * 2 / 5 == 2}yes{if:else}no{/if}',	'yes'),
			array('/ and % ltr',	'{if 5 * 2 % 6 == 4}yes{if:else}no{/if}',	'yes'),

			// basic math precendence
			array('* before +',		'{if 5 + 5 * 2 == 15}yes{if:else}no{/if}',	'yes'),
			array('/ before -',		'{if 12 - 4 / 2 == 10}yes{if:else}no{/if}',	'yes'),

			// ! has the highest precedence we support
			array('! before all',	'{if ! 5 + 5 == 5}yes{if:else}no{/if}',		'yes'),
			array('! before all 2',	'{if 5 + ! 5 == 5}yes{if:else}no{/if}',		'yes'),
			array('! before all 3',	'{if 5 - 5 * 1 == ! 5}yes{if:else}no{/if}',	'yes'),

			// comparisons before boolean logic
			array('== before &&',	'{if FALSE == TRUE && TRUE == FALSE}no{if:else}yes{/if}',	'yes'),
		);
	}

	protected function parenthesisTests()
	{
		return array(
			array('Single Parentheses', '{if (5 + 5) * 2 == 20}yes{if:else}no{/if}', 'yes'),
			array('Double Parentheses', '{if ((5 + 5) * 3 * (2 + 2)) == 120}yes{if:else}no{/if}', 'yes'),
		);
	}

	/**
	 * These comparisons are documented in our user guide. Any changes here
	 * also need to be reflected in the user guide.
	 */
	protected function userGuideTestsBooleanValueComparisons()
	{
		return array(
			array('Boolean value comparison: TRUE == TRUE',		'{if TRUE == TRUE}yes{if:else}no{/if}',		'yes'),
			array('Boolean value comparison: TRUE == FALSE',	'{if TRUE == FALSE}yes{if:else}no{/if}',	'no'),
			array('Boolean value comparison: TRUE == 1',		'{if TRUE == 1}yes{if:else}no{/if}',		'yes'),
			array('Boolean value comparison: TRUE == 0',		'{if TRUE == 0}yes{if:else}no{/if}',		'no'),
			array('Boolean value comparison: TRUE == -1',		'{if TRUE == -1}yes{if:else}no{/if}',		'yes'),
			array('Boolean value comparison: TRUE == "1"',		'{if TRUE == "1"}yes{if:else}no{/if}',		'yes'),
			array('Boolean value comparison: TRUE == "0"',		'{if TRUE == "0"}yes{if:else}no{/if}',		'yes'),
			array('Boolean value comparison: TRUE == "-1"',		'{if TRUE == "-1"}yes{if:else}no{/if}',		'yes'),

			array('Boolean value comparison: FALSE == TRUE',	'{if FALSE == TRUE}yes{if:else}no{/if}',	'no'),
			array('Boolean value comparison: FALSE == FALSE',	'{if FALSE == FALSE}yes{if:else}no{/if}',	'yes'),
			array('Boolean value comparison: FALSE == 1',		'{if FALSE == 1}yes{if:else}no{/if}',		'no'),
			array('Boolean value comparison: FALSE == 0',		'{if FALSE == 0}yes{if:else}no{/if}',		'yes'),
			array('Boolean value comparison: FALSE == -1',		'{if FALSE == -1}yes{if:else}no{/if}',		'no'),
			array('Boolean value comparison: FALSE == "1"',		'{if FALSE == "1"}yes{if:else}no{/if}',		'no'),
			array('Boolean value comparison: FALSE == "0"',		'{if FALSE == "0"}yes{if:else}no{/if}',		'no'),
			array('Boolean value comparison: FALSE == "-1"',	'{if FALSE == "-1"}yes{if:else}no{/if}',	'no'),

			array('Boolean value comparison: 1 == TRUE',		'{if 1 == TRUE}yes{if:else}no{/if}',   		'yes'),
			array('Boolean value comparison: 1 == FALSE',		'{if 1 == FALSE}yes{if:else}no{/if}',  		'no'),
			array('Boolean value comparison: 1 == 1',			'{if 1 == 1}yes{if:else}no{/if}',	   		'yes'),
			array('Boolean value comparison: 1 == 0',			'{if 1 == 0}yes{if:else}no{/if}',	   		'no'),
			array('Boolean value comparison: 1 == -1',			'{if 1 == -1}yes{if:else}no{/if}',	   		'no'),
			array('Boolean value comparison: 1 == "1"',			'{if 1 == "1"}yes{if:else}no{/if}',	   		'yes'),
			array('Boolean value comparison: 1 == "0"',			'{if 1 == "0"}yes{if:else}no{/if}',	   		'no'),
			array('Boolean value comparison: 1 == "-1"',		'{if 1 == "-1"}yes{if:else}no{/if}',   		'no'),

			array('Boolean value comparison: 0 == TRUE',		'{if 0 == TRUE}yes{if:else}no{/if}',   		'no'),
			array('Boolean value comparison: 0 == FALSE',		'{if 0 == FALSE}yes{if:else}no{/if}',  		'yes'),
			array('Boolean value comparison: 0 == 1',			'{if 0 == 1}yes{if:else}no{/if}',	   		'no'),
			array('Boolean value comparison: 0 == 0',			'{if 0 == 0}yes{if:else}no{/if}',	   		'yes'),
			array('Boolean value comparison: 0 == -1',			'{if 0 == -1}yes{if:else}no{/if}',	   		'no'),
			array('Boolean value comparison: 0 == "1"',			'{if 0 == "1"}yes{if:else}no{/if}',	   		'no'),
			array('Boolean value comparison: 0 == "0"',			'{if 0 == "0"}yes{if:else}no{/if}',	   		'yes'),
			array('Boolean value comparison: 0 == "-1"',		'{if 0 == "-1"}yes{if:else}no{/if}',   		'no'),

			array('Boolean value comparison: -1 == TRUE',		'{if -1 == TRUE}yes{if:else}no{/if}',  		'yes'),
			array('Boolean value comparison: -1 == FALSE',		'{if -1 == FALSE}yes{if:else}no{/if}', 		'no'),
			array('Boolean value comparison: -1 == 1',			'{if -1 == 1}yes{if:else}no{/if}',	   		'no'),
			array('Boolean value comparison: -1 == 0',			'{if -1 == 0}yes{if:else}no{/if}',	   		'no'),
			array('Boolean value comparison: -1 == -1',			'{if -1 == -1}yes{if:else}no{/if}',	   		'yes'),
			array('Boolean value comparison: -1 == "1"',		'{if -1 == "1"}yes{if:else}no{/if}',   		'no'),
			array('Boolean value comparison: -1 == "0"',		'{if -1 == "0"}yes{if:else}no{/if}',   		'no'),
			array('Boolean value comparison: -1 == "-1"',		'{if -1 == "-1"}yes{if:else}no{/if}',  		'yes'),

			array('Boolean value comparison: "1" == TRUE',		'{if "1" == TRUE}yes{if:else}no{/if}', 		'yes'),
			array('Boolean value comparison: "1" == FALSE',		'{if "1" == FALSE}yes{if:else}no{/if}',		'no'),
			array('Boolean value comparison: "1" == 1',			'{if "1" == 1}yes{if:else}no{/if}',	   		'yes'),
			array('Boolean value comparison: "1" == 0',			'{if "1" == 0}yes{if:else}no{/if}',	   		'no'),
			array('Boolean value comparison: "1" == -1',		'{if "1" == -1}yes{if:else}no{/if}',   		'no'),
			array('Boolean value comparison: "1" == "1"',		'{if "1" == "1"}yes{if:else}no{/if}',  		'yes'),
			array('Boolean value comparison: "1" == "0"',		'{if "1" == "0"}yes{if:else}no{/if}',  		'no'),
			array('Boolean value comparison: "1" == "-1"',		'{if "1" == "-1"}yes{if:else}no{/if}', 		'no'),

			array('Boolean value comparison: "0" == TRUE',		'{if "0" == TRUE}yes{if:else}no{/if}', 		'yes'),
			array('Boolean value comparison: "0" == FALSE',		'{if "0" == FALSE}yes{if:else}no{/if}',		'no'),
			array('Boolean value comparison: "0" == 1',			'{if "0" == 1}yes{if:else}no{/if}',	   		'no'),
			array('Boolean value comparison: "0" == 0',			'{if "0" == 0}yes{if:else}no{/if}',	   		'yes'),
			array('Boolean value comparison: "0" == -1',		'{if "0" == -1}yes{if:else}no{/if}',   		'no'),
			array('Boolean value comparison: "0" == "1"',		'{if "0" == "1"}yes{if:else}no{/if}',  		'no'),
			array('Boolean value comparison: "0" == "0"',		'{if "0" == "0"}yes{if:else}no{/if}',  		'yes'),
			array('Boolean value comparison: "0" == "-1"',		'{if "0" == "-1"}yes{if:else}no{/if}', 		'no'),

			array('Boolean value comparison: "-1" == TRUE',		'{if "-1" == TRUE}yes{if:else}no{/if}',		'yes'),
			array('Boolean value comparison: "-1" == FALSE',	'{if "-1" == FALSE}yes{if:else}no{/if}',	'no'),
			array('Boolean value comparison: "-1" == 1',		'{if "-1" == 1}yes{if:else}no{/if}',		'no'),
			array('Boolean value comparison: "-1" == 0',		'{if "-1" == 0}yes{if:else}no{/if}',		'no'),
			array('Boolean value comparison: "-1" == -1',		'{if "-1" == -1}yes{if:else}no{/if}',		'yes'),
			array('Boolean value comparison: "-1" == "1"',		'{if "-1" == "1"}yes{if:else}no{/if}',		'no'),
			array('Boolean value comparison: "-1" == "0"',		'{if "-1" == "0"}yes{if:else}no{/if}',		'no'),
			array('Boolean value comparison: "-1" == "-1"',		'{if "-1" == "-1"}yes{if:else}no{/if}',		'yes'),
		);
	}

	// See: https://support.ellislab.com/bugs/detail/20323
	protected function bug20323_variables_in_strings()
	{
		$vars = array(
			'value' => 'Test with long caption title to test layout',
			'title' => 'Test article with captions'
		);

		return array(
			array('Variable in variable',	'{if value == "' . $vars['value'] . '"}yes{if:else}no{/if}',											'yes',	$vars),
			array('Variable in string',		'{if "Test with long caption title to test layout" == "' . $vars['value'] . '"}yes{if:else}no{/if}',	'yes',	$vars),
		);
	}

	// See: https://support.ellislab.com/bugs/detail/20767
	public function testBug20767_automatic_brace_encoding()
	{
		$runner = new Runner();

		// helper to force a rewrite to the safety pass
		$force_defer = '{unparsable} string';

		// test value and its correct encoding
		$value = 'Unparsed {variable}';
		$encoded = 'Unparsed &#123;variable&#125;';

		$template = "{if '{$force_defer}' || value == '{$encoded}'}yes{if:else}no{/if}";

		$out = $this->runConditionWithoutAnnotations($template, compact('value'), $runner);

		$this->assertEquals(
			"{if '{$force_defer}' || '{$encoded}' == '{$encoded}'}yes{if:else}no{/if}",
			$out,
			'Braced string encoded rewrite'
		);
	}
}

// EOF
