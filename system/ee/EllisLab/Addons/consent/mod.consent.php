<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\Addons\Consent;

use EllisLab\Addons\Consent\Service\Notifications;
use EllisLab\Addons\Consent\Service\Variables\ConsentRequest as ConsentRequestVars;

/**
 * Consent Module
 */
class Consent {

	/**
	 * {exp:consent:form}
	 *
	 * @return string The Consent Form
	 */
	public function form()
	{

	}

	/**
	 * {exp:consent:requests}
	 *
	 * @return string Parsed tagdata for the Consent Requests tag
	 */
	public function requests()
	{

	}

	/**
	 * Grant Consent
	 * Responds to ACTion request
	 *
	 * @return mixed JSON (if Ajax request), or redirects
	 */
	public function grantConsent()
	{

	}

	/**
	 * Withdraw Consent
	 * Responds to ACTion request
	 *
	 * @return mixed JSON (if Ajax request), or redirects
	 */
	public function withdrawConsent()
	{

	}
}
// END CLASS

// EOF
