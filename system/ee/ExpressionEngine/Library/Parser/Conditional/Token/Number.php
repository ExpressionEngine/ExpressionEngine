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
 * Number Token
 */
class Number extends Token
{
    public function __construct($lexeme)
    {
        parent::__construct('NUMBER', $lexeme);

        // cast to number type (int or float)
        $this->value = 0 + $lexeme;
    }
}
