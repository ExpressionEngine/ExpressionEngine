<?php
namespace EllisLab\ExpressionEngine\Core\Validation\Error;

use EllisLab\ExpressionEngine\Model\Error\Error;

/**
 *
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
