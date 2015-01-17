<?php

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin;
use EllisLab\ExpressionEngine\Service\Validation\Validator;

class Validation implements Mixin {

	protected $scope;

	public function __construct($scope, $manager)
	{
		$this->scope = $scope;
	}

	/**
	 * Validate the model
	 *
	 * @return validation result
	 */
	public function validate()
	{
		if ( ! isset($this->validator))
		{
			return TRUE;
		}

		$this->scope->trigger('beforeValidate');

		$result = $this->validator->validate($this->scope->getDirty());

		$this->scope->trigger('afterValidate');

		return $result;
		/*
		// TODO validate relationships?
		foreach ($this->getAllAssociations() as $assoc)
		{
			$assoc->validate();
		}
		*/
	}


	/**
	 * Set the validatior
	 *
	 * @param Validator $validator The validator to use
	 * @return $this;
	 */
	public function setValidator(Validator $validator)
	{
		$this->validator = $validator;

		$rules = $this->scope->getMetaData('validation_rules');
		$validator->setRules($rules);

		return $this->scope;
	}
}