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
 * Variable Token
 */
class Variable extends Token
{
    protected $has_value = false;

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
        if (is_string($value)) {
            $value = str_replace(
                array('{', '}'),
                array('&#123;', '&#125;'),
                $value
            );
        }

        $this->value = $value;
        $this->has_value = true;
    }

    public function value()
    {
        // in this case the parent assumption is wrong
        // our value is definitely *not* the template string
        if (! $this->has_value) {
            return null;
        }

        return $this->value;
    }

    public function __toString()
    {
        if ($this->has_value) {
            return var_export($this->value, true);
        }

        return $this->lexeme;
    }
}

// EOF
