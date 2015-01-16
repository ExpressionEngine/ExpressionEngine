<?php

namespace EllisLab\ExpressionEngine\Library\Mixin;

interface Mixable {

	/**
	 * Get the current mixin manager
	 */
	public function getMixinManager();

	/**
	 * Set a mixin manager
	 */
	public function setMixinManager($manager);

}