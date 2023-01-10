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

/**
 * String Literal Token
 */
class StringLiteral extends Token
{
    public function __construct($lexeme)
    {
        parent::__construct('STRING', $lexeme);

        // if there's a comment in the literal string, it needs to go
        $lexeme = preg_replace('/^\{!--.*?--\}$/', '', $lexeme);
        $this->value = preg_replace('/\s+/', ' ', $lexeme);
    }

    public function canEvaluate()
    {
        // Remove regex quantifiers like {2} or {2,4} so they're not considered
        // unparsed variables
        $value = preg_replace('/\{\d+,?\d*\}/', '', $this->value);

        return (stristr($value, LD) === false);
    }

    public function __toString()
    {
        return var_export($this->value, true);
    }
}

// EOF
