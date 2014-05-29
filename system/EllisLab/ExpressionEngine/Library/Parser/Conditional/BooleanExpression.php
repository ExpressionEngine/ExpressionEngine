<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\ParserException;

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

	private $tokens;
	private $unary_operators;
	private $binary_operators;

	private $can_eval = TRUE;

	public function __construct()
	{
		$this->unary_operators = $this->getUnaryOperators();
		$this->binary_operators = $this->getBinaryOperators();
	}

	/**
	 * Add a token for evaluation
	 */
	public function add($token)
	{
		if ( ! $token->canEvaluate())
		{
			$this->can_eval = FALSE;
		}

		$this->tokens[] = $token;
	}

	/**
	 * Evaluate the tokens
	 */
	public function evaluate()
	{
		$rpn = $this->convertToRPN($this->tokens);
		return ($this->evaluateRPN($rpn) != '');
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
		return implode('', $this->tokens);
	}

	/**
	 * Take the rpn form and make use of it
	 */
	protected function evaluateRPN($rpn_queue)
	{
		$evaluate_stack = array();

		while ($token = array_shift($rpn_queue))
		{
			if ( ! $this->isOperator($token))
			{
				$evaluate_stack[] = $token->value();
			}
			elseif ($token->isUnary())
			{
				if (count($evaluate_stack) < 1)
				{
					throw new ParserException('Invalid Boolean Expression');
				}

				$right = array_pop($evaluate_stack);

				switch ($token->value())
				{
					case '!': array_push($evaluate_stack, ! $right);
						break;
					case '+': array_push($evaluate_stack, +$right);
						break;
					case '-': array_push($evaluate_stack, -$right);
						break;
					default:
						throw new ParserException('Invalid Unary Operator: '.$token[1]);
				}
			}
			else
			{
				if (count($evaluate_stack) < 2)
				{
					throw new ParserException('Invalid Boolean Expression');
				}

				$right = array_pop($evaluate_stack);
				$left = array_pop($evaluate_stack);

				switch (strtoupper($token->value()))
				{
					case '^':
					case '**': array_push($evaluate_stack, pow($left, $right));
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
					case '^=': array_push($evaluate_stack, (strpos($left, $right) === 0));
						break;
					case '*=': array_push($evaluate_stack, (strpos($left, $right) !== FALSE));
						break;
					case '$=': array_push($evaluate_stack, (substr($left, -strlen($right)) == $right));
						break;
					case '~':
						if (($value = @preg_match($right, $left)) === FALSE)
						{
							throw new ParserException('Invalid Regular Expression: '.$right);
						}
						array_push($evaluate_stack, ($value > 0));
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
					default:
						throw new ParserException('Invalid Binary Operator: '.$token[1]);
				}
			}
		}

		return array_pop($evaluate_stack);
	}

	/**
	 * Shunting yard algorithm to convert to RPN
	 *
	 * Will drop parentheses (RPN does not require them) and
	 * also marks all unary operators so we can treat them
	 * correctly in the evaluation phase.
	 *
	 * @param Array $tokens List of tokens in the expression
	 * @return Array of tokens in RPN format.
	 */
	protected function convertToRPN($tokens)
	{
		$output = array();
		$stack = array();

		$prev_token = NULL;

		foreach ($tokens as $i => $token)
		{
			if ($this->isOperator($token))
			{
				// unary operators need to be marked as such for the next step
				if ($this->inPrefixPosition($prev_token) && $this->isValidUnaryOperator($token->value()))
				{
					$token->markAsUnary();
				}

				while (count($stack) && $this->isOperator(end($stack)))
				{
					$top_token = end($stack);

					if ((
						(
							($this->isAssociative($token, self::LEFT_ASSOC) ||
							 $this->isAssociative($token, self::NON_ASSOC)) &&
							$this->precedence($token, $top_token) <= 0
						) ||
						(
							$this->isAssociative($token, self::RIGHT_ASSOC) &&
						 	$this->precedence($token, $top_token) < 0)
						)
						// unary operators can only pop other unary operators
						&& ( ! $token->isUnary() || $top_token->isUnary())
					)
					{
						$output[] = array_pop($stack);
						continue;
					}

					break;
				}

				array_push($stack, $token);
			}
			elseif ($token->type == 'LP')
			{
				array_push($stack, $token);
			}
			elseif ($token->type == 'RP')
			{
				while (count($stack) && end($stack)->type != 'LP')
				{
					$output[] = array_pop($stack);
				}

				array_pop($stack);
			}
			else
			{
				$output[] = $token;
			}

			$prev_token = $token;
		}

		while ($leftover = array_pop($stack))
		{
			$output[] = $leftover;
		}

		return $output;
	}

	/**
	 * Unary operators?
	 *
	 * Decides based on the symbol if the token *can* be used as a
	 * unary operator. Does not mean it must be used as such.
	 */
	private function isValidUnaryOperator($value)
	{
		return array_key_exists($value, $this->unary_operators);
	}

	/**
	 * Determine if the current operator could be a prefix.
	 *
	 * This is the case if there is no previous token, the previous
	 * token is another operator, or the previous token is a left
	 * parenthesis.
	 */
	private function inPrefixPosition($previous)
	{
		return ($previous == NULL || $previous->type == 'LP' || $this->isOperator($previous));
	}

	/**
	 * Precendence check
	 */
	private function precedence($first, $second)
	{
		$first_operator = $this->getOperator($first);
		$second_operator = $this->getOperator($second);
		return ($first_operator[0] - $second_operator[0]);
	}

	/**
	 * Associativeness check
	 */
	private function isAssociative($token, $dir)
	{
		$operator = $this->getOperator($token);
		return ($operator[1] == $dir);
	}

	/**
	 * Operator check
	 */
	private function isOperator($token)
	{
		return ($token->type == 'OPERATOR');
	}

	/**
	 * Get an operator based on a token
	 *
	 * Decides based on the token flag if the token is unary.
	 */
	private function getOperator($token)
	{
		if ($token->isUnary())
		{
			return $this->unary_operators[$token->value()];
		}

		return $this->binary_operators[$token->value()];
	}

	/**
	 * List of binary operators
	 */
	private function getBinaryOperators()
	{
		// http://php.net/manual/en/language.operators.precedence.php

		return array(
			'^' => array(60, self::RIGHT_ASSOC),
			'**' => array(60, self::RIGHT_ASSOC),

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

			'^=' => array(20, self::NON_ASSOC),
			'*=' => array(20, self::NON_ASSOC),
			'$=' => array(20, self::NON_ASSOC),
			'~' => array(20, self::NON_ASSOC),

			'<>' => array(10, self::NON_ASSOC),
			'==' => array(10, self::NON_ASSOC),
			'!=' => array(10, self::NON_ASSOC),

			'&&' => array(6, self::NON_ASSOC),
			'||' => array(5, self::NON_ASSOC),
			'AND' => array(4, self::NON_ASSOC),
			'XOR' => array(3, self::NON_ASSOC),
			'OR' => array(2, self::NON_ASSOC),
		);
	}

	/**
	 * List of unary operators
	 */
	private function getUnaryOperators()
	{
		return array(
			'-' => array(50, self::RIGHT_ASSOC),
			'+' => array(50, self::RIGHT_ASSOC),
			'!' => array(50, self::RIGHT_ASSOC),
		);
	}
}