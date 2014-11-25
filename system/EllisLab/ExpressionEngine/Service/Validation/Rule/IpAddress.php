<?php
namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class IpAddress extends ValidationRule {

	protected $flags = '';

	public function validate($value)
	{
		return (bool) filter_var($value, FILTER_VALIDATE_IP, $this->flags);
	}

	public function setParameters(array $params)
	{
		$flags = '';

		foreach ($params as $flag)
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

		$this->flags = $flags;
	}
}