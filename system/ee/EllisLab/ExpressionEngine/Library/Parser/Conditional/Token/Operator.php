<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
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
