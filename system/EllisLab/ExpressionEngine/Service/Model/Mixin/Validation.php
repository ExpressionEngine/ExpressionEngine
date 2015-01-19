<?php

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin as MixinInterface;

class Validation implements MixinInterface {

	protected $scope;

	public function __construct($scope, $manager)
	{
		$this->scope = $scope;
	}

	/**
	 * Get the mixin name
	 */
	public function getName()
	{
		return 'Model:Validation';
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

		$this->scope->emit('beforeValidate');

		$result = $this->validator->validate(
			$this->scope->getValidationData()
		);

		$this->scope->emit('afterValidate');

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
	 * Set the validator
	 *
	 * @param Validator $validator The validator to use
	 * @return $this;
	 */
	public function setValidator(Validator $validator)
	{
		$this->validator = $validator;

		$validator->setRules(
			$this->scope->getValidationRules()
		);

		return $this->scope;
	}
}