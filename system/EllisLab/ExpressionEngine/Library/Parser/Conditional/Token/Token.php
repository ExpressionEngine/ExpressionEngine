<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional\Token;

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
 * ExpressionEngine Conditional Token Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Token {

	public $type;
	public $lexeme;	// as written in the template

	public $context;
	public $lineno;

	protected $value; // the real value

	public function __construct($type, $lexeme)
	{
		$this->type = $type;
		$this->lexeme = $lexeme;

		// for most tokens the template representation is their value
		$this->value = $lexeme;
	}

	public function canEvaluate()
	{
		return TRUE;
	}

	public function value()
	{
		return $this->value;
	}

	public function __toString()
	{
		return $this->lexeme;
	}

	public function toArray()
	{
		return array(
			$this->type,
			$this->lexeme
		);
	}
}