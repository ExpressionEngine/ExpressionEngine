<?php

namespace EllisLab\ExpressionEngine\Library\Mixin;

interface Mixable {

	/**
	 * Has a given mixin?
	 */
	public function hasMixin($name);

	/**
	 * Get a given mixin
	 */
	public function getMixin($name);

	/**
	 * Get the current mixin manager
	 */
	public function getMixinManager();

	/**
	 * Set a mixin manager
	 */
	public function setMixinManager($manager);

}