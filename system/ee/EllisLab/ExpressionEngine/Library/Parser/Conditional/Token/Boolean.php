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

use EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\LexerException;

/**
 * Boolean Token
 */
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
