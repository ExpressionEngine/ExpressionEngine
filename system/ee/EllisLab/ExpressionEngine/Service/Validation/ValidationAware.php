<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Validation;

/**
 * Objects that implement this are safe to treat as more
 * than just fancy arrays. Opens up access to internal
 * validate* callbacks and rules.
 */
interface ValidationAware {

	/**
	 * Return an array of validation data.
	 */
	public function getValidationData();

	/**
	 * Return an array of validation rules
	 */
	public function getValidationRules();
}