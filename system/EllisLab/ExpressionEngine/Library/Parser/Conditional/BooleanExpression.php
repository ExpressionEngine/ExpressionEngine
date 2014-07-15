<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\BooleanExpressionException;

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
		return $this->bool($this->evaluateRPN($rpn));
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
					throw new BooleanExpressionException('Invalid Condition: Not enough operands for operator "'.$token.'".');
				}

				$right = array_pop($evaluate_stack);

				array_push(
					$evaluate_stack,
					$this->evaluateUnary($token, $right)
				);
			}
			else
			{
				if (count($evaluate_stack) < 2)
				{
					throw new BooleanExpressionException('Invalid Condition: Not enough operands for operator "'.$token.'".');
				}

				$right = array_pop($evaluate_stack);
				$left = array_pop($evaluate_stack);

				array_push(
					$evaluate_stack,
					$this->evaluateBinary($token, $left, $right)
				);
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
		$operator_stack = array();

		$prev_token = NULL;

		foreach ($tokens as $i => $token)
		{
			if ($this->isOperator($token))
			{
				// unary operators need to be marked as such for the next step
				if ($this->inPrefixPosition($prev_token) &&
					$this->isValidUnaryOperator($token->value()))
				{
					$token->markAsUnary();
				}

				while (count($operator_stack) && $this->isOperator(end($operator_stack)))
				{
					$top_token = end($operator_stack);

					$precedence = $this->precedence($token, $top_token);
					$right_assoc = $this->isAssociative($token, self::RIGHT_ASSOC);

					if (
						(( ! $right_assoc && $precedence == 0) ||
						($precedence < 0))
						&&
						// unary operators can only pop other unary operators
						( ! $token->isUnary() || $top_token->isUnary())
					)
					{
						$output[] = array_pop($operator_stack);
						continue;
					}

					break;
				}

				array_push($operator_stack, $token);
			}
			elseif ($token->type == 'LP')
			{
				array_push($operator_stack, $token);
			}
			elseif ($token->type == 'RP')
			{
				while (count($operator_stack) && end($operator_stack)->type != 'LP')
				{
					$output[] = array_pop($operator_stack);
				}

				array_pop($operator_stack);
			}
			else
			{
				$output[] = $token;
			}

			$prev_token = $token;
		}

		while ($leftover = array_pop($operator_stack))
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
		return (
			$previous == NULL OR
			$previous->type == 'LP' OR
			$this->isOperator($previous)
		);
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

		if ( ! isset($this->binary_operators[$token->value()]))
		{
			throw new BooleanExpressionException('Invalid Binary Operator: '. $token);
		}

		return $this->binary_operators[$token->value()];
	}

	/**
	 * Cast to EE boolean
	 */
	private function bool($value)
	{
		// We do *not* follow the string 0 php casting rule.
		// {if field} should be true if the user entered
		// something. Doesn't matter what it is.
		if ($value === '0')
		{
			return TRUE;
		}

		return (bool) $value;
	}

	/**
	 * Compare equality with the '0' is not FALSE consideration.
	 */
	private function equals($left, $right)
	{
		// only empty strings are false
		// '0' == FALSE		-> no
		// '0' == TRUE		-> yes
		if ($left === '0' && is_bool($right))
		{
			return $right;
		}

		if ($right === '0' && is_bool($left))
		{
			return $left;
		}

		// 5 == "5anything" is definitely not true
		if (is_numeric($left) && is_string($right))
		{
			$left = (string) $left;
		}
		elseif (is_numeric($right) && is_string($left))
		{
			$right = (string) $right;
		}

		return $left == $right;
	}

	/**
	 * Evaluate a binary operator
	 */
	private function evaluateBinary($op_token, $left, $right)
	{
		switch (strtoupper($op_token->value()))
		{
			// numbers
			case '^':
			case '**':
				return pow($left, $right);
			case '*':
				return $left * $right;
			case '/':
				return $left / $right;
			case '%':
				return $left % $right;
			case '+':
				return $left + $right;
			case '-':
				return $left - $right;

			// comparisons
			case '<>':
			case '!=':
				return ! $this->equals($left, $right);
			case '==':
				return $this->equals($left, $right);
			case '<':
				return $left < $right;
			case '<=':
				return $left <= $right;
			case '>':
				return $left > $right;
			case '>=':
				return $left >= $right;

			// boolean logic
			case '&&':
				return $this->bool($left) && $this->bool($right);
			case '||':
				return $this->bool($left) || $this->bool($right);
			case 'AND':
				return $this->bool($left) AND $this->bool($right);
			case 'XOR':
				return $this->bool($left) XOR $this->bool($right);
			case 'OR':
				return $this->bool($left) OR $this->bool($right);

			// strings
			case '.':
				return $left . $right;
			case '^=':
				return strpos($left, (string) $right) === 0;
			case '*=':
				return strpos($left, (string) $right) !== FALSE;
			case '$=':
				$right = (string) $right;
				return substr($left, -strlen($right)) == $right;
			case '~':
				if (($value = @preg_match($right, $left)) === FALSE)
				{
					throw new BooleanExpressionException('Invalid Regular Expression: '.$right);
				}

				return ($value > 0);
		}

		throw new BooleanExpressionException('Invalid Binary Operator: '.$token);
	}

	/**
	 * Evaluate a unary operator
	 */
	private function evaluateUnary($token, $right)
	{
		switch ($token->value())
		{
			case '!':
				return ! $this->bool($right);
			case '+':
				return +$right;
			case '-':
				return -$right;
		}

		throw new BooleanExpressionException('Invalid Unary Operator: '.$token);
	}

	/**
	 * List of binary operators
	 */
	private function getBinaryOperators()
	{
		// http://php.net/manual/en/language.operators.precedence.php

		// operator => array(precedence, associativity, [cast])
		return array(
			'^'  => array(60, self::RIGHT_ASSOC),
			'**' => array(60, self::RIGHT_ASSOC),

			'*' => array(40, self::LEFT_ASSOC),
			'/' => array(40, self::LEFT_ASSOC),
			'%' => array(40, self::LEFT_ASSOC),

			'+' => array(30, self::LEFT_ASSOC),
			'-' => array(30, self::LEFT_ASSOC),
			'.' => array(30, self::LEFT_ASSOC),

			'<'  => array(20, self::NON_ASSOC),
			'<=' => array(20, self::NON_ASSOC),
			'>'  => array(20, self::NON_ASSOC),
			'>=' => array(20, self::NON_ASSOC),

			'^=' => array(20, self::NON_ASSOC),
			'*=' => array(20, self::NON_ASSOC),
			'$=' => array(20, self::NON_ASSOC),
			'~'  => array(20, self::NON_ASSOC),

			'<>' => array(10, self::NON_ASSOC),
			'==' => array(10, self::NON_ASSOC),
			'!=' => array(10, self::NON_ASSOC),

			'&&'  => array(6, self::NON_ASSOC),
			'||'  => array(5, self::NON_ASSOC),
			'AND' => array(4, self::NON_ASSOC),
			'XOR' => array(3, self::NON_ASSOC),
			'OR'  => array(2, self::NON_ASSOC),
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