<?php

namespace EllisLab\ExpressionEngine\Library\Parser;

use SplStack;

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
 * ExpressionEngine Core Abstract Lexer Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class AbstractLexer {

	/**
	 * The string being worked on
	 */
	protected $str;

	/**
	 * A stack for convenience
	 */
	protected $stack;

	abstract public function tokenize($str);

	public function __construct()
	{
		$this->stack = new SplStack();
	}

	/**
	 * Peek ahead n characters without moving
	 *
	 * @param int $n The number of characters to peek ahead
	 * @return string The next $n characters
	 */
	protected function peek($n = 1)
	{
		if ($n == 1)
		{
			return $this->str[0];
		}
		return substr($this->str, 0, $n);
	}

	/**
	 * Peek ahead on an anchored regex
	 *
	 * @param string $regex A regular expression
	 * @return array|string The result of the match or an empty string
	 */
	protected function peekRegex($regex)
	{
		if (preg_match('/^'.$regex.'/us', $this->str, $matches))
		{
			return $matches[0];
		}

		return NULL;
	}

	/**
	 * Seek to the first character in char mask
	 *
	 * @param string $charMask The characters we are seeking
	 * @return string The characters lost in the move
	 */
	protected function seekTo($char_mask)
	{
		$n = strcspn($this->str, $char_mask);
		return $this->move($n);
	}

	/**
	 * Move to the next character
	 *
	 * @return string The consumed character
	 */
	protected function next()
	{
		return $this->move(1);
	}

	/**
	 * Move ahead n characters in the string, returning the consumed bit
	 *
	 * @param int $n The number of characters to move ahead
	 * @return string The characters lost in the move
	 */
	protected function move($n)
	{
		$buffer = substr($this->str, 0, $n);
		$this->str = substr($this->str, $n);

		return $buffer;
	}

	/**
	 * Add a state to the stack
	 *
	 * @param String $state state to add
	 */
	protected function pushState($state)
	{
		$this->stack->push($state);
	}

	/**
	 * Remove a state from the stack
	 *
	 * @return The removed state
	 */
	protected function popState()
	{
		return $this->stack->pop();
	}

	/**
	 * Check the top of the stack
	 *
	 * @return The current state
	 */
	protected function topState()
	{
		return $this->stack->top();
	}
}