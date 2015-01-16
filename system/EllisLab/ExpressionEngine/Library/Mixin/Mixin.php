<?php

namespace EllisLab\ExpressionEngine\Library\Mixin;

interface Mixin {

	/**
	 * Setup a mixin with the parent scope
	 */
	public function __construct($scope);

	/**
	 * Set the parent mixin manager
	 */
	public function setMixinManager($manager);

}