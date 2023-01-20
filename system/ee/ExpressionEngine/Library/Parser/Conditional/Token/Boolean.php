<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Parser\Conditional\Token;

use ExpressionEngine\Library\Parser\Conditional\Exception\LexerException;

/**
 * Boolean Token
 */
class Boolean extends Token
{
    public function __construct($lexeme)
    {
        parent::__construct('BOOL', $lexeme);

        if (is_bool($lexeme)) {
            $this->lexeme = $lexeme = $lexeme ? 'TRUE' : 'FALSE';
        }

        switch (strtoupper($lexeme)) {
            case 'TRUE':
                $this->value = true;

                break;
            case 'FALSE':
                $this->value = false;

                break;
            default:
                throw new LexerException('Invalid boolean value: ' . $lexeme);
        }
    }
}

// EOF
