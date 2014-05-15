<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\ConditionalParserException;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.9.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Boolean Expression Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class BooleanExpression {

	const NON_ASSOC = 0;
	const LEFT_ASSOC = 1;
	const RIGHT_ASSOC = 2;

	private $operators;
	private $tokens;
	private $can_eval = TRUE;

	public function __construct()
	{
		$this->operators = $this->getOperators();
	}

	/**
	 * Add a token for evaluation
	 */
	public function add($type, $value, $eval = TRUE, $quote = FALSE)
	{
		if ($eval == FALSE)
		{
			$this->can_eval = FALSE;
		}

		$this->tokens[] = array($type, $value, $eval, $quote);
	}

	/**
	 * Evaluate the tokens
	 */
	public function evaluate()
	{
		$rpn = $this->convertToRPN($this->tokens);
		return $this->evaluateRPN($rpn);
	}

	/**
	 * Evaluate-able?
	 */
	public function canEvaluate()
	{
		return $this->can_eval;
	}

	/**
	 * Can't use it? Turn it back into a string conditional
	 */
	public function stringify()
	{
		$str = '';

		while ($token = array_shift($this->tokens))
		{
			if ($token[3] === TRUE)
			{
				$value = (string) $token[1];
				$str .= var_export($value, TRUE);
			}
			elseif ($token[0] == 'OPERATOR')
			{
				$str .= ' '.$token[1].' ';
			}
			else
			{
				$str .= $token[1];
			}
		}

		return $str;
	}

	/**
	 * Take the rpn form and make use of it
	 */
	protected function evaluateRPN($rpn_queue)
	{
		$evaluate_stack = array();

		// may want to split these out entirely, but we currently
		// only have one so I don't see the point.
		$unary_operators = array('!');

		while ($token = array_shift($rpn_queue))
		{
			if ( ! $this->isOperator($token))
			{
				$evaluate_stack[] = $token[1];
			}
			elseif (in_array($token[1], $unary_operators))
			{
				if (count($evaluate_stack) < 1)
				{
					throw new ConditionalParserException('Invalid Boolean Expression');
				}

				$right = array_pop($evaluate_stack);

				switch ($token[1])
				{
					case '!': array_push($evaluate_stack, ! $right);
						break;
				}
			}
			else
			{
				if (count($evaluate_stack) < 2)
				{
					throw new ConditionalParserException('Invalid Boolean Expression');
				}

				$right = array_pop($evaluate_stack);
				$left = array_pop($evaluate_stack);

				switch ($token[1])
				{
					case '^':
					case '**': array_push($evaluate_stack, pow($left, $right));
						break;
					case '*': array_push($evaluate_stack, $left * $right);
						break;
					case '*': array_push($evaluate_stack, $left * $right);
						break;
					case '/': array_push($evaluate_stack, $left / $right);
						break;
					case '%': array_push($evaluate_stack, $left % $right);
						break;
					case '+': array_push($evaluate_stack, $left + $right);
						break;
					case '-': array_push($evaluate_stack, $left - $right);
						break;
					case '.': array_push($evaluate_stack, $left . $right);
						break;
					case '<': array_push($evaluate_stack, $left < $right);
						break;
					case '<=': array_push($evaluate_stack, $left <= $right);
						break;
					case '>': array_push($evaluate_stack, $left > $right);
						break;
					case '>=': array_push($evaluate_stack, $left >= $right);
						break;
					case '<>': array_push($evaluate_stack, $left <> $right);
						break;
					case '==': array_push($evaluate_stack, $left == $right);
						break;
					case '!=': array_push($evaluate_stack, $left != $right);
						break;
					case '&&': array_push($evaluate_stack, $left && $right);
						break;
					case '||': array_push($evaluate_stack, $left || $right);
						break;
					case 'AND': array_push($evaluate_stack, $left AND $right);
						break;
					case 'XOR': array_push($evaluate_stack, $left XOR $right);
						break;
					case 'OR': array_push($evaluate_stack, $left OR $right);
						break;
				}
			}
		}

		return array_pop($evaluate_stack);
	}

	/**
	 * Shunting yard algorithm to convert to RPN
	 *
	 * @param Array $tokens List of tokens in the expression
	 */
	protected function convertToRPN($tokens)
	{
		$output = array();
		$stack = array();

		foreach ($tokens as $token)
		{
			if ($this->isOperator($token))
			{
				while (count($stack) && $this->isOperator(end($stack)))
				{
					if (
						(
							($this->isAssociative($token, self::LEFT_ASSOC) ||
							 $this->isAssociative($token, self::NON_ASSOC)) &&
							$this->precedence($token, end($stack)) <= 0
						) ||
						(
							$this->isAssociative($token, self::RIGHT_ASSOC) &&
						 	$this->precedence($token, end($stack)) < 0)
						)
					{
						$output[] = array_pop($stack);
						continue;
					}

					break;
				}

				array_push($stack, $token);
			}
			elseif ($token[0] == 'LP')
			{
				array_push($stack, $token);
			}
			elseif ($token[0] == 'RP')
			{
				while (count($stack) && $stack[count($stack) - 1][0] != 'LP')
				{
					$output[] = array_pop($stack);
				}

				array_pop($stack);
			}
			else
			{
				$output[] = $token;
			}
		}

		while ($leftover = array_pop($stack))
		{
			$output[] = $leftover;
		}

		return $output;
	}

	/**
	 * Precendence check
	 */
	private function precedence($first, $second)
	{
		return ($this->operators[$first[1]][0] - $this->operators[$second[1]][0]);
	}

	/**
	 * Associateiveness check
	 */
	private function isAssociative($token, $dir)
	{
		return ($this->operators[$token[1]][1] == $dir);
	}

	/**
	 * Operator check
	 */
	private function isOperator($token)
	{
		return ($token[0] == 'OPERATOR');
	}

	/**
	 * List of operators
	 */
	private function getOperators()
	{
		// http://php.net/manual/en/language.operators.precedence.php

		return array(
			'^' => array(60, self::RIGHT_ASSOC),
			'**' => array(60, self::RIGHT_ASSOC),

			'!' => array(50, self::RIGHT_ASSOC),

			'*' => array(40, self::LEFT_ASSOC),
			'/' => array(40, self::LEFT_ASSOC),
			'%' => array(40, self::LEFT_ASSOC),

			'+' => array(30, self::LEFT_ASSOC),
			'-' => array(30, self::LEFT_ASSOC),
			'.' => array(30, self::LEFT_ASSOC),

			'<' => array(20, self::NON_ASSOC),
			'<=' => array(20, self::NON_ASSOC),
			'>' => array(20, self::NON_ASSOC),
			'>=' => array(20, self::NON_ASSOC),

			'==' => array(10, self::NON_ASSOC),
			'!=' => array(10, self::NON_ASSOC),

			'&&' => array(6, self::NON_ASSOC),
			'||' => array(5, self::NON_ASSOC),
			'AND' => array(4, self::NON_ASSOC),
			'XOR' => array(3, self::NON_ASSOC),
			'OR' => array(2, self::NON_ASSOC),
		);
	}
}