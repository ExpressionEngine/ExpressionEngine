<?php

namespace EllisLab\ExpressionEngine\Service\Validation;

class Result {

	protected $failed = array();

	/**
	 *
	 */
	public function addFailed($field, $rule)
	{
		if ( ! isset($this->failed[$field]))
		{
			$this->failed[$field] = array();
		}

		$this->failed[$field][] = $rule;
	}

	/**
	 *
	 */
	public function isValid()
	{
		return (bool) empty($this->failed);
	}

	/**
	 *
	 */
	public function isNotValid()
	{
		return ! $this->isValid();
	}

	/**
	 *
	 */
	public function getErrors($field = NULL)
	{
		if (isset($field))
		{
			if ( ! isset($this->failed[$field]))
			{
				return array();
			}

			return $this->getErrorsForField($field);
		}

		$errors = array();

		foreach (array_keys($this->failed) as $field)
		{
			$errors[$field] = $this->getErrorsForField($field);
		}

		return $errors;
	}

	public function createOutput($rule, $field)
	{
		// todo check defaults lang keys, overriden messages, field
		// long names, etc
		$name = get_class($rule);

		return "Validation of '{$field}' failed on rule '{$name}'";
	}

	protected function getErrorsForField($field)
	{
		$errors = array();

		foreach ($this->failed[$field] as $rule)
		{
			$errors[] = $this->createOutput($rule, $field);
		}

		return $errors;
	}
}