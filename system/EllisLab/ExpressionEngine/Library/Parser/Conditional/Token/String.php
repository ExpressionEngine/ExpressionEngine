<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional\Token;

class String extends Token {

	public function __construct($lexeme)
	{
		parent::__construct('STRING', $lexeme);

		$this->value = preg_replace('/\s+/', ' ', $lexeme);
	}

	public function canEvaluate()
	{
		return (stristr($this->value, LD) === FALSE);
	}

	public function __toString()
	{
		return var_export($this->value, TRUE);
	}
}