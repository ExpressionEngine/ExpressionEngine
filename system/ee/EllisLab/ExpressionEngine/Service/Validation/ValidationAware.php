<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
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