<?php

namespace EllisLab\Tests\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\ConditionalLexer;

class ConditionalLexerTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->lexer = new ConditionalLexer();
	}

	protected function runLexer($description, $str_in, $expected)
	{
		$result = $this->lexer->tokenize($str_in);
		$this->assertEquals($expected, $result, $description);
	}

	/**
	 * @dataProvider goodDataProvider
	 */
	public function testGoodDataProvider($description, $str_in, $expected)
	{
		$this->runLexer($description, $str_in, $expected);
	}

	public function goodDataProvider()
	{
		// assemble all of the tests
		return array_merge(
			array(),
			$this->validOperatorsWithSpaces(),
			$this->validOperatorsWithoutSpaces()
		);
	}

	protected function validOperatorsWithSpaces()
	{
		$return = array();

		// Template
		$expected = array(
			array('IF',					'{if '),
			array('NUMBER',				'5'),
			array('WHITESPACE',			' '),
			array('OPERATOR',			''),
			array('WHITESPACE',			' '),
			array('NUMBER',				'7'),
			array('ENDCOND',			'}'),
			array('TEMPLATE_STRING',	'out'),
			array('ENDIF',				'{/if}'),
			array('EOS',				TRUE)
		);

		$operators = array(
			'||', '&&', '**',
			'==', '!=', '<=', '>=', '<>', '<', '>',
			'%', '+', '-', '*', '/',
			'.', '!', '^'
		);

		foreach ($operators as $operator)
		{
			$expected[3][1] = $operator;
			$return[] = array(
				"{$operator} Operator",
				"{if 5 {$operator} 7}out{/if}",
				$expected
			);
		}

		return $return;
	}

	protected function validOperatorsWithoutSpaces()
	{
		$return = array();

		// Template
		$expected = array(
			array('IF',					'{if '),
			array('NUMBER',				'5'),
			array('OPERATOR',			''),
			array('NUMBER',				'7'),
			array('ENDCOND',			'}'),
			array('TEMPLATE_STRING',	'out'),
			array('ENDIF',				'{/if}'),
			array('EOS',				TRUE)
		);

		$operators = array(
			'||', '&&', '**',
			'==', '!=', '<=', '>=', '<>', '<', '>',
			'%', '+', '-', '*', '/',
			'.', '!', '^'
		);

		foreach ($operators as $operator)
		{
			$expected[2][1] = $operator;
			$return[] = array(
				"{$operator} Operator",
				"{if 5{$operator}7}out{/if}",
				$expected
			);
		}

		return $return;
	}
}