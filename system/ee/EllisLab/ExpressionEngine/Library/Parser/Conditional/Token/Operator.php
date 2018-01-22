<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional\Token;

/**
 * Operator Token
 */
class Operator extends Token {

	protected $isUnary = FALSE;

	public function __construct($lexeme)
	{
		parent::__construct('OPERATOR', $lexeme);
	}

	public function markAsUnary()
	{
		$this->isUnary = TRUE;
	}

	public function isUnary()
	{
		return $this->isUnary;
	}

	public function __toString()
	{
		return ' '.$this->lexeme.' ';
	}
}
