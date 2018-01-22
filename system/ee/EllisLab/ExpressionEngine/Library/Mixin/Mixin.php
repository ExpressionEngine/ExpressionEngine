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
 * Mixin interface
 */
interface Mixin {

	/**
	 * Setup a mixin with the parent scope
	 *
	 * @param Object $scope Parent object
	 */
	public function __construct($scope);

	/**
	 * Name the mixin. Make sure yours is unique!
	 *
	 * Preferably these are prefixed with the third party
	 * name, so Event mixin owuld be MyAddon:Event and would
	 * not clash with EE's native Event mixin.
	 *
	 * @return String Mixin name
	 */
	public function getName();
}
