<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional\Token;

class Number extends Token {

	public function __construct($lexeme)
	{
		parent::__construct('NUMBER', $lexeme);

		// cast to number type (int or float)
		$this->value = 0 + $lexeme;
	}
}