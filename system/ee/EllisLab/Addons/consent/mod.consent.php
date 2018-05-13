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
		$consent = ee('Variables/Parser')->parseOrParameter(ee()->TMPL->fetch_param('consent'));

		if (empty($consent['options']))
		{
			return ee()->TMPL->no_results();
		}

		$requests = ee('Model')->get('ConsentRequest')
			->with('CurrentVersion')
			->filter('consent_name', (($consent['not']) ? 'NOT IN' : 'IN'), $consent['options'])
			->all();

		$vars = [];
		foreach ($requests as $request)
		{
			$request_vars = new ConsentRequestVars($request);
			$vars[] = $request_vars->getTemplateVariables();
		}
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
