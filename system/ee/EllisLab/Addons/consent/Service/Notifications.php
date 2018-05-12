<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\Addons\Consent\Service;

/**
 * Notifications class for Consent Module
 */
class Notifications {

	/**
	 * @var object EllisLab\ExpressionEngine\Model\Consent\Consent
	 */
	protected $consent;

	/**
	 * @var array Variables to parse in the notification template
	 */
	protected $variables = array();

	/**
	 * Constructor
	 * @param object $consent EllisLab\ExpressionEngine\Model\Consent\Consent
	 * @param string $url URL for visitor to manage their consents
	 */
	public function __construct(ConsentModel $consent, $variables)
	{
		$this->consent = $consent;
		$this->variables = $variables;
	}
}
// END CLASS

// EOF
