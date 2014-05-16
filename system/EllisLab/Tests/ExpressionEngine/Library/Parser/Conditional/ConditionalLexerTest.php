<?php

namespace EllisLab\Tests\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\ConditionalLexer;

class ConditionalLexerTest extends \PHPUnit_Framework_TestCase {

	protected $valueTypes = array(
		'bool'			=> array('token' => array('BOOL', 'TRUE'),				'value' => 'TRUE'),
		'int'			=> array('token' => array('NUMBER', '5'),				'value' => 5),
		'negative'		=> array('token' => array('NUMBER', '-5'),				'value' => -5),
		'bigfloat'		=> array('token' => array('NUMBER', '5.1'),				'value' => 5.1),
		'littlefloat'	=> array('token' => array('NUMBER', '.1'),				'value' => .1),
		'string'		=> array('token' => array('STRING', 'string'),			'value' => '"string"'),
		'dash-string'	=> array('token' => array('STRING', 'dash-string'),		'value' => '"dash-string"'),
		'dot.string'	=> array('token' => array('STRING', 'dot.string'),		'value' => '"dot.string"'),
		'intstring'		=> array('token' => array('STRING', '5'),				'value' => '"5"'),
		'variable'		=> array('token' => array('VARIABLE', 'variable'),		'value' => 'variable'),
		'dash-variable'	=> array('token' => array('VARIABLE', 'dash-variable'),	'value' => 'dash-variable'),
	);

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
			array('NUMBER',				'5'),
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

		// Test each operator (duh)
		foreach ($operators as $operator)
		{
			// Testing our common value types for edge-cases.
			// We don't need to care about permutations here just combinations
			// because we need to ensure that these value types are found
			// on both sides of an operator.
			foreach ($this->valueTypes as $type => $value)
			{
				$expected[1] = $value['token'];
				$expected[3][1] = $operator;
				$expected[5] = $value['token'];
				$return[] = array(
					"The \"{$operator}\" operator with {$type} values",
					"{if {$value['value']} {$operator} {$value['value']}}out{/if}",
					$expected
				);
			}
		}

		return $return;
	}

	// Things change without spaces around the operator
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
			'%', '+', '*', '/',
			'!', '^'
		);

		// Test each operator (duh)
		foreach ($operators as $operator)
		{
			// Testing our common value types for edge-cases.
			// We don't need to care about permutations here just combinations
			// because we need to ensure that these value types are found
			// on both sides of an operator.
			foreach ($this->valueTypes as $type => $value)
			{
				$expected[1] = $value['token'];
				$expected[2][1] = $operator;
				$expected[3] = $value['token'];
				$return[] = array(
					"The \"{$operator}\" operator with {$type} values",
					"{if {$value['value']}{$operator}{$value['value']}}out{/if}",
					$expected
				);
			}
		}

		// Manual tests for the '.' operator
		// TODO: build all the tests....
		$operator = '.';
		$return[] = array(
			"{$operator} Operator",
			"{if 5{$operator}7}out{/if}",
			array(
				array('IF',					'{if '),
				array('NUMBER',				'5.7'),
				array('ENDCOND',			'}'),
				array('TEMPLATE_STRING',	'out'),
				array('ENDIF',				'{/if}'),
				array('EOS',				TRUE)
			)
		);

		// Manual tests for the '.' operator
		// TODO: build all the tests....
		$operator = '-';

		return $return;
	}
}