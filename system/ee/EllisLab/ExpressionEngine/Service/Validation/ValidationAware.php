<?php

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