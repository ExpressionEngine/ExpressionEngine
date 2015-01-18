<?php

namespace EllisLab\ExpressionEngine\Service\Validation;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin as MixinInterface;
use EllisLab\ExpressionEngine\Service\Event\Evented;

class Mixin implements MixinInterface {

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
		return 'Validation';
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
	//		return TRUE;
		}

		$this->emit('beforeValidate');

	//	$result = $this->validator->validate($this->scope->getDirty());

		$this->emit('afterValidate');

//		return $result;
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

	/**
	 *
	 */
	protected function emit($event)
	{
		if ($this->scope->hasMixin('Event'))
		{
			$this->scope->emit($event);
		}
	}
}