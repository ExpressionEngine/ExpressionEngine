<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Validation\Rule;

use ExpressionEngine\Service\Validation\ValidationRule;

/**
 * Limited HTML Validation Rule
 */
class LimitHtml extends ValidationRule
{
    public function validate($key, $value)
    {
        if (preg_match_all('/<(\w+)/', (string) $value, $matches)) {
            // There may be some regex to do this more efficiently
            foreach ($matches[1] as $tag) {
                if (! in_array($tag, $this->parameters)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Return the language data for the validation error.
     */
    public function getLanguageData()
    {
        $list = implode(', ', $this->parameters);

        return array($this->getName(), $list);
    }
}

// EOF
