<?php
namespace EllisLab\ExpressionEngine\Core\Validation;

/**
 *
 */
abstract class ValidationRule {

	/**
	 *
	 */
	public function __construct(array $parameters)
	{

	}


	/**
	 *
	 */
	public abstract function validate($value);

}
