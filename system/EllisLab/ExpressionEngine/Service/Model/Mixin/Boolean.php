<?php

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin;

class Boolean implements Mixin {

	protected $scope;
	protected $manager;

	public function __construct($scope)
	{
		$this->scope = $scope;
	}

	/**
	 * Satisfy the interface
	 */
	public function setMixinManager($manager) {}

	/**
	 * Set a boolean y/n
	 */
	public function setStringBool($property, $new_value)
	{
		if ($new_value == TRUE || $new_value == 'y')
		{
			$this->scope->fill(array($property => 'y'));
		}

		if ($new_value == FALSE || $new_value == 'n')
		{
			$this->scope->fill(array($property => 'n'));
		}
	}
}