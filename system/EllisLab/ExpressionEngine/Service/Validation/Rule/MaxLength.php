<?php
namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule as ValidationRule;

/**
 * Max Length
 *
 * @access	public
 * @param	string
 * @param	value
 * @return	bool
 */
class MaxLength extends ValidationRule {
	protected $length=0;

	public function __construct(array $parameters)
	{
		$this->length = $parameters[0];
	}

	public function validate($value)
	{
		if (preg_match("/[^0-9]/", $this->length))
		{
			return FALSE;
		}

		if (function_exists('mb_strlen'))
		{
			return (mb_strlen($value) > $this->length) ? FALSE : TRUE;
		}

		return (strlen($value) > $this->length) ? FALSE : TRUE;
	}

}
