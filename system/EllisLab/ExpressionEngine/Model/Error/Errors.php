<?php
namespace EllisLab\ExpressionEngine\Model\Error;

Use EllisLab\ExpressionEngine\Model\Error\Error;

/**
 * Wrapper class for multiple errors, to be returned from validation.
 * Does not track error duplications, so can plausibly contain duplicated
 * errors.  Also does not perform any translation of errors (or create
 * the errors).
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
	 * Get an array of errors, if any.
	 *
	 * @return	array	An array containing all Error objects
	 * 		that have been placed in this object, if any.  If there
	 * 		are none, returns an empty array.
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Add a new error to the container.
	 *
	 * @param	Error	$error	The error object that you wish to add to this
	 * 		container.  Will not check for duplicates or touch the Error's message
	 * 		in any way.
	 *
	 * @return $this.
	 */
	public function addError(Error $error)
	{
		$this->errors[] = $error;
		return $this;
	}

	/**
	 * Merge another Error object into this one.
	 *
	 * Adds all of the errors contained in $errors to this Error object.
	 *
	 *
	 * @param	Errors	$errors	The error container to be merged with this one.
	 * 		The container is not changed in any way.
	 *
	 * @return $this
	 */
	public function addErrors(Errors $errors)
	{
		if ( ! $errors->exist())
		{
			return $this;
		}

		foreach($errors->getErrors() as $error)
		{
			$this->addError($error);
		}

		return $this;
	}

}
