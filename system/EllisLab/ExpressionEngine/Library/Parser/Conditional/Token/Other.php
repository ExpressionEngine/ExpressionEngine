<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional\Token;

class Other extends Token {

	public function __construct($lexeme)
	{
		parent::__construct('MISC', $lexeme);

		// always encode misc
		$this->value = str_replace(
			array('{', '}',),
			array('&#123;', '&#125;'),
			$lexeme
		);
	}

	public function canEvaluate()
	{
		return FALSE;
	}
}