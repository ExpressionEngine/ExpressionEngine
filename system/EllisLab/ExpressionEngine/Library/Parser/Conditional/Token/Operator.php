<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional\Token;

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