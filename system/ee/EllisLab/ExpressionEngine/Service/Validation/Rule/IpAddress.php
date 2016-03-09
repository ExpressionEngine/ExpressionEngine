<?php

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine IP Address Validation Rule
 *
 *
 * @package		ExpressionEngine
 * @subpackage	Validation\Rule
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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