<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * IP Address Validation Rule
 */
class IpAddress extends ValidationRule {

	public function validate($key, $value)
	{
		$flags = $this->processParameters();

		return (bool) filter_var($value, FILTER_VALIDATE_IP, $flags);
	}

	protected function processParameters()
	{
		$flags = '';

		foreach ($this->parameters as $flag)
		{
			switch ($flag)
			{
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
