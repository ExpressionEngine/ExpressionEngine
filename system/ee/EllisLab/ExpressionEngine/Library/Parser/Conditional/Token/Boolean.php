<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional\Token;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\LexerException;

class Boolean extends Token {

	public function __construct($lexeme)
	{
		parent::__construct('BOOL', $lexeme);

		if (is_bool($lexeme))
		{
			$this->lexeme = $lexeme = $lexeme ? 'TRUE' : 'FALSE';
		}

		switch (strtoupper($lexeme))
		{
			case 'TRUE':
				$this->value = TRUE;
				break;
			case 'FALSE':
				$this->value = FALSE;
				break;
			default:
				throw new LexerException('Invalid boolean value: '.$lexeme);
		}
	}
}

// EOF
