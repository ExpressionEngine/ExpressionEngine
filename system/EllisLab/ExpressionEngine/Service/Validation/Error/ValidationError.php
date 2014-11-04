<?php
namespace EllisLab\ExpressionEngine\Service\Validation\Error;

use EllisLab\ExpressionEngine\Service\Error\Error;

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
 * ExpressionEngine Validation Error
 *
 * An error for use when validating data.
 *
 * @package		ExpressionEngine
 * @subpackage	Validation\Error
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ValidationError extends Error {
	protected $property;
	protected $failed_rule;

	/**
	 *
	 */
	public function __construct($property, $failed_rule)
	{
		parent::__construct($property . ' failed to pass ' . $failed_rule);

		$this->property = $property;
		$this->failed_rule = $failed_rule;
	}

	/**
	 *
	 */
	public function getProperty()
	{
		return $this->property;
	}

	/**
	 *
	 */
	public function getFailedRule()
	{
		return $this->failed_rule;
	}

}
