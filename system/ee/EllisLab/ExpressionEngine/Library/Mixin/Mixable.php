<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Library\Mixin;

/**
 * Mixing Mixable interface
 */
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
