<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional\Token;

class Comment extends Token {

	public $conditional_annotation = FALSE;

	public function __construct($lexeme)
	{
		parent::__construct('COMMENT', $lexeme);

		$this->value = trim(preg_replace('/^\{!--(.*?)--\}$/', '$1', $lexeme));
	}

	public function canEvaluate()
	{
		return TRUE;
	}
}