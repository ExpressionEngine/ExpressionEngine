<?php

namespace EllisLab\Tests\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\ConditionalRunner;

class ConditionalRunnerTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->runner = new ConditionalRunner();
	}

	protected function runCondition($description, $str_in, $expected, $vars = array())
	{
		$result = $this->runner->processConditionals($str_in, $vars);
		$this->assertEquals($expected, $result, $description);
	}

	/**
	 * @dataProvider plainDataProvider
	 */
	public function testPlainConditionalsWithoutVariables($description, $str_in, $expected_out, $vars = array())
	{
		$this->runner->disableProtectJavascript();
		$this->runCondition($description, $str_in, $expected_out, $vars);
	}

	/**
	 * @dataProvider badDataProvider
	 */
	public function testBadConditionalsWithoutVariables($exception, $description, $str_in)
	{
		$this->setExpectedException($exception);
		$this->runner->disableProtectJavascript();
		$this->runCondition($description, $str_in, '');
	}

	/**
	 * @dataProvider safetyOnDataProvider
	 */
	public function testSafetyOn($description, $str_in, $expected_out)
	{
		$this->runner->safetyOn();
		$this->runner->disableProtectJavascript();
		$this->runCondition($description, $str_in, $expected_out);
	}

	public function testBasicVariableReplacement()
	{
		$runner = new ConditionalRunner();
		$runner->disableProtectJavascript();

		// var1 is in there to prevent execution
		$string = '{if var1 && var2 == \'bob\'}yes{/if}';

		$this->assertEquals(
			$runner->processConditionals($string, array('var2' => 3)),
			'{if var1 && \'3\' == \'bob\'}yes{/if}',
			'Integer Variable Replacement'
		);

		$this->assertEquals(
			$runner->processConditionals($string, array('var2' => 'mary')),
			'{if var1 && \'mary\' == \'bob\'}yes{/if}',
			'String Variable Replacement'
		);

		$this->assertEquals(
			$runner->processConditionals($string, array('var2' => TRUE)),
			'{if var1 && \'1\' == \'bob\'}yes{/if}',
			'Bool TRUE Variable Replacement'
		);

		$this->assertEquals(
			$runner->processConditionals($string, array('var2' => FALSE)),
			'{if var1 && \'\' == \'bob\'}yes{/if}',
			'Bool FALSE Variable Replacement'
		);
	}

	public function testProgressiveConstruction()
	{
		$runner = new ConditionalRunner();
		$runner->disableProtectJavascript();

		$inital = '{if var1 && var2 && var3 == \'bob\'}yes{if:else}no{/if}';

		$var2 = $runner->processConditionals($inital, array('var1' => 3));

		$this->assertEquals(
			$var2,
			'{if \'3\' && var2 && var3 == \'bob\'}yes{if:else}no{/if}',
			'Integer Variable Replacement'
		);

		$var3 = $runner->processConditionals($var2, array('var3' => 'bob'));

		$this->assertEquals(
			$var3,
			'{if \'3\' && var2 && \'bob\' == \'bob\'}yes{if:else}no{/if}',
			'String Variable Replacement'
		);

		// Adding var2 completes the conditional and causes evaluation
		$this->assertEquals(
			$runner->processConditionals($var3, array('var2' => 4)),
			'yes',
			'Last variable triggers evaluation'
		);

		// Do it again with a falsey value to sanity check it
		$this->assertEquals(
			$runner->processConditionals($var3, array('var2' => 0)),
			'no',
			'Last variable triggers evaluation to false'
		);
	}

	public function testBranchConditionRewritingAndPruning()
	{
		$runner = new ConditionalRunner();
		$runner->disableProtectJavascript();

		$string = '{if 5 == var}yes{if:elseif 5 == 5}maybe{if:else}no{/if}';

		$this->assertEquals(
			$runner->processConditionals($string, array()),
			'{if 5 == var}yes{if:elseif TRUE}maybe{/if}',
			'Elseif branch rewritten to TRUE and else branch pruned'
		);

		$string = '{if 5 == var}yes{if:elseif 5 == 6}maybe{if:else}no{/if}';

		$this->assertEquals(
			$runner->processConditionals($string, array()),
			'{if 5 == var}yes{if:else}no{/if}',
			'Elseif branch evaluated to FALSE and is pruned'
		);

		$string = '{if 5 == 7}nope{if:elseif 5 == var}maybe{if:else}maybe2{/if}';

		$this->assertEquals(
			$runner->processConditionals($string, array()),
			'{if 5 == var}maybe{if:else}maybe2{/if}',
			'If evaluated to false, if is pruned, elseif is promoted'
		);

		// now the big one
		$string = '{if 5 == 7}nope';
		$string .= '{if:elseif \'bob\' == \'mary\'}never';
		$string .= '{if:elseif 7 == var}maybe';
		$string .= '{if:elseif 7 == 7}quitepossibly';
		$string .= '{if:elseif 8 == 0}nah';
		$string .= '{if:else}nope{/if}';

		$this->assertEquals(
			$runner->processConditionals($string, array()),
			'{if 7 == var}maybe{if:elseif TRUE}quitepossibly{/if}',
			'Double elseif promotion, true rewriting, and branch pruning'
		);
	}

	public function plainDataProvider()
	{
		// assemble all of the tests
		return array_merge(
			array(),
			$this->conditionals(),
			$this->whitespaceRewriting(),
			$this->basicMaths(),
			$this->basicBranching(),
			$this->plainLogicOperatorTests(),
			$this->comparisonOperatorTests(),
			$this->concatenationTests(),
			$this->eeTagTests(),
			$this->operatorPrecedenceTests(),
			$this->parenthesisTests()
		);
	}

	public function badDataProvider()
	{
		$parser_exception = 'EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\ConditionalParserException';
		$lexer_exception = 'EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\ConditionalLexerException';

		return array(
			array($parser_exception, 'Simple Backticks',				'{if `echo hello`}out{/if}'),
			array($parser_exception, 'Splitting Backticks',				'{if string.`echo hello #}out{/if}{if `== 0}out{/if}'),
			array($parser_exception, 'Simple Comments',					'{if php/* test == 5*/info(); }out{/if}'),
			array($parser_exception, 'Comment Looks Like Math',			'{if 7 /* 5 }out{/if}'),
			array($parser_exception, 'Inline Comment Looks Like Math',	'{if 7 // 5 }out{/if}'),
			array($parser_exception, 'Splitting Comments',				'{if string /* == 5 }out{/if}{if */phpinfo(); == 5}out{/if}'),
			array($lexer_exception,  'Unclosed String (single quotes)', "{if string == 'ee}out{/if}"),
			array($lexer_exception,  'Unclosed String (double quotes)', '{if string == "ee}out{/if}'),
			array($lexer_exception,  'Unclosed Conditional', 			'{if string == "ee"}out'),
			array($lexer_exception,  'Unterminated Conditional', 		'{if string == "ee"out{/if}'),
			array($lexer_exception,  'If as a Prefix', 					'{if:foo}'),
			array($lexer_exception,  'Ifelse duplicity', 				'{if 5 == 5}out{if:else:else}out{/if}'),
			array($lexer_exception,  'Ifelse Prefixing', 				'{if 5 == 5}out{if:elsebeth}out{/if}'),
			array($lexer_exception,  'Ifelseif Prefixing', 				'{if 5 == 5}out{if:elseiffy}out{/if}'),
			array($lexer_exception,  'NUMBER + :', 						'{if 1:2}out{/if}'),
			array($lexer_exception,  'OK + :',	 						'{if :foo}out{/if}'),
			array($lexer_exception,  'OK + :',	 						'{if "foo":bar}out{/if}'),
			array($lexer_exception,  'OK + :',	 						"{if 'foo':bar}out{/if}"),
			array($lexer_exception,  'FLOAT + .', 						'{if 1.2.3}out{/if}'),
			array($lexer_exception,  'FLOAT + :', 						'{if 1.2:3}out{/if}'),
		);
	}

	public function safetyOnDataProvider()
	{
		return array(
			array('Unparsed Tags to false',		'{if {tag} == FALSE}yes{if:else}no{/if}',	'yes'),
			array('Unparsed Tags to false 2',	'{if {tag} != FALSE}no{if:else}yes{/if}',	'yes'),
			array('Junk String to false',		'{if ` != FALSE}no{if:else}yes{/if}',		'yes'),
			array('Junk String to false 2',		'{if ` == FALSE}yes{if:else}no{/if}',		'yes'),
		);
	}

	protected function conditionals()
	{
		return array(
			// evaluation
			array('Evaluate If With Space',		 '{if 5 == 5}out{/if}',			'out'),
			array('Evaluate If With Tab',		 '{if	5 == 5}out{/if}',		'out'),
			array('Evaluate If With Newline',	 "{if\n5 == 5}out{/if}",		'out'),
			array('Evaluate If With CRLF',		 "{if\r\n5 == 5}out{/if}",		'out'),
			array('Evaluate If With Whitespace', "{if\n\t5 == 5\n}out{/if}",	'out'),
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
			array('Math plus',	'{if 5 + 5 == 10}yes{if:else}no{/if}', 'yes'),
			array('Math minus',	'{if 7 - 9 == -2}yes{if:else}no{/if}', 'yes'),
			array('Math star',	'{if 5 * 5 == 25}yes{if:else}no{/if}', 'yes'),
			array('Math slash',	'{if 12 / 4 == 3}yes{if:else}no{/if}', 'yes'),
			array('Math mod',	'{if 12 % 5 == 2}yes{if:else}no{/if}', 'yes'),
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
			array('Plain && Integer',	'{if 5 && 5}yes{if:else}no{/if}',	'yes'),
			array('Plain || Integer',	'{if 5 || 7}yes{if:else}no{/if}',	'yes'),
			array('Plain AND Integer',	'{if 7 AND 5}yes{if:else}no{/if}',	'yes'),
			array('Plain OR Integer',	'{if 5 OR 7}yes{if:else}no{/if}',	'yes'),
			array('Plain XOR Integer',	'{if 5 XOR 0}yes{if:else}no{/if}',	'yes'),
			array('Plain ! Integer',	'{if ! 0}yes{if:else}no{/if}',		'yes'),

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
			array('Plain == Integer',	'{if 5 == 5}yes{if:else}no{/if}',		'yes'),
			array('Plain != Integer',	'{if 3 != 6}yes{if:else}no{/if}',		'yes'),
			array('Plain == String',	'{if "a" == "a"}yes{if:else}no{/if}',	'yes'),
			array('Plain != String',	'{if "a" != "b"}yes{if:else}no{/if}',	'yes'),
			array('Plain <= Integer',	'{if 3 <= 5}yes{if:else}no{/if}',		'yes'),
			array('Plain <= Integer 2',	'{if 5 <= 5}yes{if:else}no{/if}',		'yes'),
			array('Plain >= Integer',	'{if 7 >= 5}yes{if:else}no{/if}',		'yes'),
			array('Plain >= Integer 2',	'{if 5 >= 5}yes{if:else}no{/if}',		'yes'),
			array('Plain <> Integer',	'{if 7 <> 5}yes{if:else}no{/if}',		'yes'),
			array('Plain > Integer',	'{if 7 > 5}yes{if:else}no{/if}',		'yes'),
			array('Plain < Integer',	'{if 5 < 7}yes{if:else}no{/if}',		'yes'),

			array('False == Integer',	'{if 5 == 2}no{if:else}yes{/if}',		'yes'),
			array('False != Integer',	'{if 6 != 6}no{if:else}yes{/if}',		'yes'),
			array('False == String',	'{if "a" == "b"}no{if:else}yes{/if}',	'yes'),
			array('False != String',	'{if "a" != "a"}no{if:else}yes{/if}',	'yes'),
			array('False <= Integer',	'{if 5 <= 3}no{if:else}yes{/if}',		'yes'),
			array('False >= Integer',	'{if 5 >= 7}no{if:else}yes{/if}',		'yes'),
			array('False <> Integer',	'{if 7 <> 7}no{if:else}yes{/if}',		'yes'),
			array('False > Integer',	'{if 5 > 7}no{if:else}yes{/if}',		'yes'),
			array('False < Integer',	'{if 7 < 5}no{if:else}yes{/if}',		'yes'),
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

	protected function stringTests()
	{

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
}