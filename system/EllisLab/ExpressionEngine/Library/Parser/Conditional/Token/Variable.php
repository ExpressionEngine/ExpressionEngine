<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional\Token;

class Variable extends Token {

	protected $has_value = FALSE;

	public function __construct($lexeme)
	{
		parent::__construct('VARIABLE', $lexeme);
	}

	public function canEvaluate()
	{
		return $this->has_value;
	}

	public function setValue($value)
	{
		$this->value = $value;
		$this->has_value = TRUE;
	}

	public function value()
	{
		// in this case the parent assumption is wrong
		// our value is definitely *not* the template string
		if ( ! $this->has_value)
		{
			return NULL;
		}

		return $this->value;
	}

	public function __toString()
	{
		if ($this->has_value)
		{
			return var_export($this->value, TRUE);
		}

		return $this->lexeme;
	}
}