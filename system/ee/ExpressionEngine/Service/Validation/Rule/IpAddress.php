<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Validation\Rule;

use ExpressionEngine\Service\Validation\ValidationRule;

/**
 * IP Address Validation Rule
 */
class IpAddress extends ValidationRule
{
    public function validate($key, $value)
    {
        $flags = $this->processParameters();

        return (bool) filter_var($value, FILTER_VALIDATE_IP, $flags);
    }

    protected function processParameters()
    {
        $flags = 0;

        foreach ($this->parameters as $flag) {
            switch ($flag) {
                case 'ipv4':
                    $flags |= FILTER_FLAG_IPV4;

                    break;
                case 'ipv6':
                    $flags |= FILTER_FLAG_IPV6;

                    break;
                case 'public':
                    $flags |= FILTER_FLAG_NO_PRIV_RANGE;

                    break;
                default:
                    throw new \Exception("Unknown IP validation parameter: {$flag}");
            }
        }

        return $flags;
    }

    public function getLanguageKey()
    {
        return 'valid_ip';
    }
}
