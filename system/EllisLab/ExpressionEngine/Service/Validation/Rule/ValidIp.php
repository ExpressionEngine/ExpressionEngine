<?php
namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule as ValidationRule;
use EllisLab\ExpressionEngine\Library\IpLibrary as IpLibrary;

/**
 * Valid Ip 
 *
 * @param	string
 * @param	value
 * @return	bool
 */
class ValidIp extends ValidationRule {

	public function validate($value)
	{
		return IpLibrary::getInstance()->validIp($value);
	}

}
