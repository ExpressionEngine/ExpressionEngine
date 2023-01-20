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
 * Conditional Token
 */
class Token
{
    public $type;
    public $lexeme;	// as written in the template

    public $context;
    public $lineno;

    protected $value; // the real value

    public function __construct($type, $lexeme)
    {
        $this->type = $type;
        $this->lexeme = $lexeme;

        // for most tokens the template representation is their value
        $this->value = $lexeme;
    }

    public function canEvaluate()
    {
        return true;
    }

    public function value()
    {
        return $this->value;
    }

    public function __toString()
    {
        return (string) $this->lexeme;
    }

    public function toArray()
    {
        return array(
            $this->type,
            $this->lexeme
        );
    }

    public function debug()
    {
        return htmlentities($this->type . ' (' . $this->__toString() . ')');
    }
}

// EOF
