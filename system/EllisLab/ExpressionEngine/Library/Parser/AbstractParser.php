<?php

namespace EllisLab\ExpressionEngine\Library\Parser;

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
 * ExpressionEngine Core Abstract Parser Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 *
 * THIRD PARTY DEVS: Extend at your own risk before it's documented
 */
abstract class AbstractParser {

	/**
	 * Current token
	 */
	protected $token;

	/**
	 * All tokens
	 */
	protected $tokens;

	/**
	 * @param Array $tokens List of Token's
	 */
	public function __construct($tokens)
	{
		$this->tokens = $tokens;
	}

	/**
	 * All parsers must implement a parse method
	 *
	 * @return String parsed string
	 */
	abstract public function parse();

	/**
	 * Compare the current token to a given type
	 *
	 * @param String $type The type to check against
	 * @return bool  Current token is of type $type.
	 */
	protected function is($type)
	{
		if ($this->valid())
		{
			return ($this->token->type == $type);
		}

		return FALSE;
	}

	/**
	 * Get the current token value
	 *
	 * @return mixed
	 */
	protected function value()
	{
		return $this->token->lexeme;
	}

	/**
	 * Compare the current token to a type, and advance if it matches.
	 *
	 * @param String $type The type to check against
	 * @return Bool  Token was accepted
	 */
	protected function accept($type)
	{
		if ($this->is($type))
		{
			$this->next();
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Enforce an expected token.
	 *
	 * @param String $type The name to check against
	 * @return Bool  Expected token was found
	 */
	protected function expect($type)
	{
		if ($this->accept($type))
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Move to the next token.
	 *
	 * @return void
	 */
	protected function next()
	{
		$this->token = array_shift($this->tokens);
	}

	/**
	 * Current token is valid.
	 *
	 * Used to stop the parser when we run out of tokens
	 *
	 * @return bool Token exists?
	 */
	protected function valid()
	{
		return ($this->token !== NULL);
	}
}