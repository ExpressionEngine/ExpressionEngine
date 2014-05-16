<?php

namespace EllisLab\Tests\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\ConditionalLexer;

class ConditionalLexerTest extends \PHPUnit_Framework_TestCase {

	protected $valueTypes = array(
		'bool'				=> array('token' => array('BOOL', 'TRUE'),							'value' => 'TRUE'),
		'int'				=> array('token' => array('NUMBER', '5'),							'value' => 5),
		'negative'			=> array('token' => array('NUMBER', '-5'),							'value' => -5),
		'bigfloat'			=> array('token' => array('NUMBER', '5.1'),							'value' => 5.1),
		'littlefloat'		=> array('token' => array('NUMBER', '.1'),							'value' => .1),
		'string'			=> array('token' => array('STRING', 'string'),						'value' => '"string"'),
		'dash-string'		=> array('token' => array('STRING', 'dash-string'),					'value' => '"dash-string"'),
		'dot.string'		=> array('token' => array('STRING', 'dot.string'),					'value' => '"dot.string"'),
		'intstring'			=> array('token' => array('STRING', '5'),							'value' => '"5"'),
		'variable'			=> array('token' => array('VARIABLE', 'variable'),					'value' => 'variable'),
		'dash-variable'		=> array('token' => array('VARIABLE', 'dash-variable'),				'value' => 'dash-variable'),
		'simpletag'			=> array('token' => array('TAG', '{simpletag}'),					'value' => '{simpletag}'),
		'moduletag'			=> array('token' => array('TAG', '{exp:foo:bar}'),					'value' => '{exp:foo:bar}'),
		'tag_with_params'	=> array('token' => array('TAG', '{exp:foo:bar param="value"}'),	'value' => '{exp:foo:bar param="value"}')
	);

	protected $commonTokens = array(
		'start' => array(
			array('IF', '{if ')
		),
		'end'   => array(
			array('ENDCOND',			'}'),
			array('TEMPLATE_STRING',	'out'),
			array('ENDIF',				'{/if}'),
			array('EOS',				TRUE)
		)
	);

	public function setUp()
	{
		$this->lexer = new ConditionalLexer();
	}

	public function tearDown()
	{
		$this->lexer = NULL;
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

	protected function assembleCommonCondition($expression)
	{
		return "{if ".$expression."}out{/if}";
	}

	protected function assembleCommonTokens($tokens)
	{
		return array_merge(
			$this->commonTokens['start'],
			$tokens,
			$this->commonTokens['end']
		);
	}

	public function goodDataProvider()
	{
		// assemble all of the tests
		return array_merge(
			array(),
			$this->validOperatorsWithSpaces(),
			$this->validOperatorsWithoutSpaces(),
			$this->invalidOperatorsWithSpaces()
		);
	}

	protected function validOperatorsWithSpaces()
	{
		$return = array();

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
				$expected = array(
					$value['token'],
					array('WHITESPACE',	' '),
					array('OPERATOR',	$operator),
					array('WHITESPACE',	' '),
					$value['token']
				);

				$return[] = array(
					"The \"{$operator}\" operator with {$type} values",
					$this->assembleCommonCondition($value['value']." ".$operator." ".$value['value']),
					$this->assembleCommonTokens($expected)
				);
			}
		}

		return $return;
	}

	// Things change without spaces around the operator
	protected function validOperatorsWithoutSpaces()
	{
		$return = array();

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
				// Some exceptions for exceptional operators
				if ($operator == '-')
				{
					if ($type == 'bool' || $type == 'variable' || $type == 'dash-variable')
					{
						continue;
					}
				}
				elseif ($operator == '.')
				{
					if ($type == 'int' || $type == 'bigfloat' || $type == 'littlefloat')
					{
						continue;
					}
				}

				$expected = array(
					$value['token'],
					array('OPERATOR', $operator),
					$value['token']
				);

				$return[] = array(
					"The \"{$operator}\" operator with {$type} values",
					$this->assembleCommonCondition($value['value'].$operator.$value['value']),
					$this->assembleCommonTokens($expected)
				);
			}
		}

		// Manual tests for the '.' operator's exceptions
		$operator = '.';

		// int.int -> NUMBER
		$value = $this->valueTypes['int']['value'];
		$expected = array(
			array('NUMBER',	$value.'.'.$value)
		);
		$return[] = array(
			"The \"{$operator}\" operator with int values",
			$this->assembleCommonCondition($value.$operator.$value),
			$this->assembleCommonTokens($expected)
		);

		// int.negative -> OPERATOR
		$int = $this->valueTypes['int'];
		$negative = $this->valueTypes['negative'];
		$expected = array(
			$int['token'],
			array('OPERATOR', '.'),
			$negative['token'],
		);
		$return[] = array(
			"The \"{$operator}\" operator with int and negative values",
			$this->assembleCommonCondition($int['value'].$operator.$negative['value']),
			$this->assembleCommonTokens($expected)
		);

		// negative.int -> NUMBER
		$int = $this->valueTypes['int'];
		$negative = $this->valueTypes['negative'];
		$expected = array(
			array('NUMBER',	$negative['value'].'.'.$int['value'])
		);
		$return[] = array(
			"The \"{$operator}\" operator with int and negative values",
			$this->assembleCommonCondition($negative['value'].$operator.$int['value']),
			$this->assembleCommonTokens($expected)
		);

		// *float.* -> EXCEPTION (this is covered in our exceptions test)

		// Manual tests for the '-' operator's exceptions
		$operator = '-';

		// bool-bool -> variable
		// variable-variable -> variable
		// dash-variable-dash-variable -> variable
		foreach (array('bool', 'variable', 'dash-variable') as $type)
		{
			$value = $this->valueTypes[$type]['value'];
			$expected = array(
				array('VARIABLE', $value.'-'.$value)
			);
			$return[] = array(
				"The \"{$operator}\" operator with {$type} values",
				$this->assembleCommonCondition($value.$operator.$value),
				$this->assembleCommonTokens($expected)
			);
		}

		return $return;
	}

	protected function invalidOperatorsWithSpaces()
	{
		$return = array();

		$valid_operators = array(
			'||', '&&', '**',
			'==', '!=', '<=', '>=', '<>', '<', '>',
			'%', '+', '-', '*', '/',
			'.', '!', '^'
		);

		// Manual invalid operator assignments
		$invalid_operators = array(
			'===', '!=='
		);

		$edge_cases = array();

		// Build out some combinations
		foreach ($valid_operators as $first)
		{
			foreach ($valid_operators as $second)
			{
				$operator = $first.$second;

				if (in_array($operator, $valid_operators))
				{
					continue;
				}

				// We'll be handling these edge cases later
				if ($second == '.' || $second == '-')
				{
					$edge_cases[] = $operator;
				}
				else
				{
					$invalid_operators[] = $operator;
				}
			}
		}

		$invalid_operators = array_unique($invalid_operators);
		$edge_cases = array_unique($edge_cases);

		foreach ($invalid_operators as $operator)
		{
			// Testing our common value types for edge-cases.
			// We don't need to care about permutations here just combinations
			// because we need to ensure that these value types are found
			// on both sides of an operator.
			foreach ($this->valueTypes as $type => $value)
			{
				$expected = array(
					$value['token'],
					array('WHITESPACE',	' '),
					array('MISC',	$operator),
					array('WHITESPACE',	' '),
					$value['token']
				);

				$return[] = array(
					"The \"{$operator}\" operator with {$type} values",
					$this->assembleCommonCondition($value['value']." ".$operator." ".$value['value']),
					$this->assembleCommonTokens($expected)
				);
			}
		}

		return $return;
	}

}