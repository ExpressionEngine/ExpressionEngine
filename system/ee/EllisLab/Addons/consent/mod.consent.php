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
use EllisLab\Addons\Consent\Service\Variables\Consent as ConsentVars;

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
		$consent_names = $this->getValidRequestsFromParameter(ee()->TMPL->fetch_param('consent'));

		if ( ! $consent_names)
		{
			return ee()->TMPL->no_results();
		}

		$requests = ee('Consent')->getConsentDataFor($consent_names);

		$consents = [];
		foreach ($requests as $request)
		{
			$request_vars = new ConsentVars($request);
			$consents[] = $request_vars->getTemplateVariables();
		}

		$vars[] = ['consents' => $consents];
		$tagdata = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);

		$form = [
			'action' => ee()->functions->fetch_current_uri(),
			'id'     => ee()->TMPL->form_id,
			'class'  => ee()->TMPL->form_class,
			'hidden_fields' => [
				'ACT' => ee()->functions->fetch_action_id('Consent', 'submitConsent'),
				'RET' => ee('Encrypt')->encode(ee()->TMPL->fetch_param('return')),
				'consent_names' => ee('Encrypt')->encode(serialize($requests->pluck('consent_name'))),
			]
		];

		return ee()->functions->form_declaration($form).$tagdata.'</form>';
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
	public function submitConsent()
	{
		$consent_names = @unserialize(ee('Encrypt')->decode(ee()->input->post('consent_names')));
		$requests = ee('Consent')->getConsentDataFor($consent_names);

		if ($requests->count() == 0)
		{
			ee()->output->throwAuthError();
		}

		foreach ($consent_names as $consent_name)
		{
			if (ee()->input->post($consent_name) == 'y')
			{
				ee('Consent')->grant($consent_name);
			}
			else
			{
				ee('Consent')->withdraw($consent_name);
			}
		}

		if (AJAX_REQUEST)
		{
			$this->output->send_ajax_response(['success' => lang('consent_updated')]);
		}
		else
		{
			$return = ee('Encrypt')->decode(ee()->input->post('RET'));
			ee()->functions->redirect(ee()->functions->create_url($return));
		}
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

	private function getValidRequestsFromParameter($param)
	{
		$requests = ee('Model')->get('ConsentRequest')->fields('consent_name');

		if ($param)
		{
			$consent = ee('Variables/Parser')->parseOrParameter($param);

			if (empty($consent['options']))
			{
				return ee()->TMPL->no_results();
			}

			$requests->filter('consent_name', (($consent['not']) ? 'NOT IN' : 'IN'), $consent['options']);
		}

		return $requests->all()->pluck('consent_name');
	}
}
// END CLASS

// EOF
