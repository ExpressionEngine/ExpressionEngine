<?php

namespace EllisLab\Tests\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\ConditionalRunner;

class ConditionalRunnerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider plainDataProvider
	 */
	public function testPlainConditionalsWithoutVariables($description, $problem, $result)
	{
		$runner = new ConditionalRunner();
		$runner->disableProtectJavascript();

		$out = $runner->processConditionals($problem, array());
		$this->assertEquals($result, $out, $description);
	}

	/**
	 * @dataProvider badDataProvider
	 */
	public function testBadConditionalsWithoutVariables($exception, $description, $str_in)
	{
		$this->setExpectedException($exception);

		$runner = new ConditionalRunner();
		$runner->disableProtectJavascript();

		$out = $runner->processConditionals($str_in, array());
		$this->assertEquals($result, '', $description);
	}

	public function testBasicVariableReplacement()
	{
		$runner = new ConditionalRunner();
		$runner->disableProtectJavascript();

		// var1 is in there to prevent execution
		$string = '{if var1 && var2 == "bob"}yes{/if}';

		$this->assertEquals(
			$runner->processConditionals($string, array('var2' => 3)),
			'{if var1 && "3" == "bob"}yes{/if}',
			'Integer Variable Replacement'
		);

		$this->assertEquals(
			$runner->processConditionals($string, array('var2' => "mary")),
			'{if var1 && "mary" == "bob"}yes{/if}',
			'String Variable Replacement'
		);

		$this->assertEquals(
			$runner->processConditionals($string, array('var2' => TRUE)),
			'{if var1 && "1" == "bob"}yes{/if}',
			'Bool TRUE Variable Replacement'
		);

		$this->assertEquals(
			$runner->processConditionals($string, array('var2' => FALSE)),
			'{if var1 && "" == "bob"}yes{/if}',
			'Bool FALSE Variable Replacement'
		);
	}

	public function testProgressiveConstruction()
	{
		$runner = new ConditionalRunner();
		$runner->disableProtectJavascript();

		$inital = '{if var1 && var2 && var3 == "bob"}yes{if:else}no{/if}';

		$var2 = $runner->processConditionals($inital, array('var1' => 3));

		$this->assertEquals(
			$var2,
			'{if "3" && var2 && var3 == "bob"}yes{if:else}no{/if}',
			'Integer Variable Replacement'
		);

		$var3 = $runner->processConditionals($var2, array('var3' => 'bob'));

		$this->assertEquals(
			$var3,
			'{if "3" && var2 && "bob" == "bob"}yes{if:else}no{/if}',
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

		// In this test the else can't be executed, but the elseif
		// will take on a valid value. Using that value we can intelligently
		// prune the other branches even before evaluating the whole thing.

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
	}

	public function testElseIfPromotion()
	{

	}

	public function testProgressivePruning()
	{
		$nested = '{if 5 == var}yes{if:else 5 == 5}maybe{if:else}definitelynot{/if}';

	}

	public function plainDataProvider()
	{
		// assemble all of the tests
		return array_merge(
			array(),
			$this->conditionals(),
			$this->basicMaths(),
			$this->plainLogicOperatorTests()
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


	protected function conditionals()
	{
		return array(
			array('If With Space',		'{if 5 == 5}out{/if}',						'out'),
			array('If With Tab',		'{if	5 == 5}out{/if}',					'out'),
			array('If With Newline',	"{if\n5 == 5}out{/if}",						"out"),
			array('If With CRLF',		"{if\r\n5 == 5}out{/if}",					"out"),
			array('If With Whitespace',	"{if\n\t5 == 5\n}out{/if}",					"out"),
			array('Ifelseif, if true',	'{if 5 == 5}yes{if:elseif 5 == 5}no{/if}',	'yes'),
			array('Ifelse, if true',	'{if 5 == 5}yes{if:else}no{/if}',			'yes'),
			array('Ifelseif, if false',	'{if 5 == 6}no{if:elseif 5 == 5}yes{/if}',	'yes'),
			array('Ifelse, if false',	'{if 5 == 6}no{if:else}yes{/if}',			'yes'),
		);
	}

	protected function basicMaths()
	{
		return array(
			array('Math plus', '{if 5 + 5 == 10}yes{if:else}no{/if}', 'yes'),
			array('Math minus', '{if 7 - 9 == -2}yes{if:else}no{/if}', 'yes'),
			array('Math star', '{if 5 * 5 == 25}yes{if:else}no{/if}', 'yes'),
			array('Math slash', '{if 12 / 4 == 3}yes{if:else}no{/if}', 'yes'),
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
}