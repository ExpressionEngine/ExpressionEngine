<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional;

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
	public $lexeme;

	public function __construct($type, $lexeme)
	{
		$this->type = $type;
		$this->lexeme = $lexeme;
	}

	public function __toString()
	{
		return $this->type.' ('.$this->lexeme.')';
	}

	public function toArray()
	{
		return array(
			$this->type,
			$this->lexeme
		);
	}
}