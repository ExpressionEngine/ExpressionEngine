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
 * Tag Token
 */
class Tag extends Token
{
    public function __construct($lexeme)
    {
        parent::__construct('TAG', $lexeme);
    }

    public function canEvaluate()
    {
        return false;
    }
}
