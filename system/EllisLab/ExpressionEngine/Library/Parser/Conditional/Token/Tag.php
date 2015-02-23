<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional\Token;

class Tag extends Token {

	public function __construct($lexeme)
	{
		parent::__construct('TAG', $lexeme);
	}

	public function canEvaluate()
	{
		return FALSE;
	}
}