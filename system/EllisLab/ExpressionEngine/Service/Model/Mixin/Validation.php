<?php

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin as MixinInterface;
use EllisLab\ExpressionEngine\Service\Validation\Validator;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Model Validation Mixin
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Validation implements MixinInterface {

	/**
	 * @var Parent scope
	 */
	protected $scope;

	/**
	 * @param Object $scope Parent object
	 */
	public function __construct($scope)
	{
		$this->scope = $scope;
	}

	/**
	 * Get the mixin name
	 *
	 * @return String mixin name
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

		$this->validator->setRules(
			$this->scope->getValidationRules()
		);

		$this->scope->emit('beforeValidate');

		$result = $this->validator->validate(
			$this->scope->getValidationData()
		);

		$this->scope->emit('afterValidate');

		return $result;
	}

	/**
	 * Set the validator
	 *
	 * @param Validator $validator The validator to use
	 * @return Current scope
	 */
	public function setValidator(Validator $validator)
	{
		$this->validator = $validator;

		return $this->scope;
	}

	/**
	 * Get the validator
	 *
	 * @return Validator object
	 */
	public function getValidator()
	{
		return $this->validator;
	}
}