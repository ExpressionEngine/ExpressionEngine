<?php
namespace EllisLab\ExpressionEngine\Service\Validation;

Use EllisLab\ExpressionEngine\Model\Error\Error as Error;

/**
 * Wrapper class for multiple errors, to be returned from validation.
 */
class ValidationResult {
	protected $errors = array();

	/**
	 * Did validation result in any errors?
	 *
	 * @return	boolean	TRUE if there are errors, FALSE otherwise.
	 */
	public function failed() 
	{
		return ( ! empty($this->errors));
	}

	/**
	 *
	 */
	public function getErrors() 
	{
		return $this->errors;
	}

	/**
	 *
	 */
	public function addError(Error $error)
	{
		$this->errors[] = $error;
	}

	public function addErrors(ValidationResult $result)
	{
		foreach($result->getErrors() as $error)
		{
			$this->addError($error);
		}
	}
}
