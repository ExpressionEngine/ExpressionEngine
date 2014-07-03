<?php
namespace EllisLab\ExpressionEngine\Core\Validation\Rule;

use EllisLab\ExpressionEngine\Core\Validation\ValidationRule as ValidationRule;
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
