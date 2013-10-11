<?php
namespace EllisLab\ExpressionEngine\Service\Validation;

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
