<?php
namespace EllisLab\ExpressionEngine\Model;

Use EllisLab\ExpressionEngine\Model\Error\Error;

/**
 * Wrapper class for multiple errors, to be returned from validation.
 */
class Errors {
	protected $errors = array();

	/**
	 * Do we have any errors?
	 *
	 * @return	boolean	TRUE if there are errors, FALSE otherwise.
	 */
	public function exist() 
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

	/**
	 *
	 */
	public function addErrors(Errors $errors)
	{
		if ( ! $errors->exist())
		{
			return;
		}

		foreach($errors->getErrors() as $error)
		{
			$this->addError($error);
		}
	}

}
