<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\Consent;

use EllisLab\Addons\Consent\Service\Notifications;
use EllisLab\Addons\Consent\Service\Variables\Alert as AlertVars;
use EllisLab\Addons\Consent\Service\Variables\Consent as ConsentVars;

/**
 * Consent Module
 */
class Consent {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		ee()->lang->loadfile('consent');
	}

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
		$consents = $this->getVariablesForRequests($requests);

		if (empty($consents))
		{
			return ee()->TMPL->no_results();
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
				'consent_names' => ee('Encrypt')->encode(json_encode($requests->pluck('consent_name'))),
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
		$consent_names = $this->getValidRequestsFromParameter(ee()->TMPL->fetch_param('consent'));

		if ( ! $consent_names)
		{
			return ee()->TMPL->no_results();
		}

		$requests = ee('Consent')->getConsentDataFor($consent_names);
		$consents = $this->getVariablesForRequests($requests);

		if (empty($consents))
		{
			return ee()->TMPL->no_results();
		}

		return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $consents);
	}

	/**
	 * {exp:discuss:alert}
	 */
	public function alert()
	{
		$alerts = ee()->session->flashdata(md5('Consent/alerts'));

		if (empty($alerts))
		{
			// no content rather than a no results, as this might be inside other tags that have their own
			return '';
		}

		$vars = [];

		foreach ($alerts as $alert)
		{
			$alert_vars = new AlertVars($alert);
			$vars[] = $alert_vars->getTemplateVariables();
		}

		return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);
	}

	/**
	 * Grant Consent
	 * Responds to ACTion request
	 *
	 * @return mixed JSON (if Ajax request), or redirects
	 */
	public function submitConsent()
	{
		$consent_names = @json_decode(ee('Encrypt')->decode(ee()->input->post('consent_names')), TRUE);
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

		$message = sprintf(
			lang('consent_prefs_saved'),
			htmlentities(implode(', ', $requests->pluck('title')))
		);

		if (AJAX_REQUEST)
		{
			ee()->output->send_ajax_response(['success' => $message]);
		}
		else
		{
			$this->setAlertFlashdata('success', $message);
			$return = ee()->input->post('RET')
						? ee('Encrypt')->decode(ee()->input->post('RET'))
						: ee()->functions->form_backtrack(1);
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
		if (empty($_POST))
		{
			$this->validateGetCsrf();
		}

		$request_id = ee()->input->get_post('crid');
		$request = ee('Consent')->getConsentDataFor($request_id)->first();

		if (empty($request))
		{
			ee()->output->throwAuthError();
		}

		try
		{
			ee('Consent')->grant($request_id);
		}
		catch (\InvalidArgumentException $e)
		{
			ee()->output->throwAuthError();
		}

		$message = sprintf(lang('consent_prefs_saved'), htmlentities($request['title']));

		if (AJAX_REQUEST)
		{
			ee()->output->send_ajax_response(['success' => $message]);
		}
		else
		{
			$this->setAlertFlashdata('success', $message);
			$return = ee('Encrypt')->decode(ee()->input->get_post('return'));
			ee()->functions->redirect(ee()->functions->create_url($return));
		}
	}

	/**
	 * Withdraw Consent
	 * Responds to ACTion request
	 *
	 * @return mixed JSON (if Ajax request), or redirects
	 */
	public function withdrawConsent()
	{
		if (empty($_POST))
		{
			$this->validateGetCsrf();
		}

		$request_id = ee()->input->get_post('crid');
		$request = ee('Consent')->getConsentDataFor($request_id)->first();

		if (empty($request))
		{
			ee()->output->throwAuthError();
		}

		try
		{
			ee('Consent')->withdraw($request_id);
		}
		catch (\InvalidArgumentException $e)
		{
			ee()->output->throwAuthError();
		}

		$message = sprintf(lang('consent_prefs_saved'), htmlentities($request['title']));

		if (AJAX_REQUEST)
		{
			ee()->output->send_ajax_response(['success' => $message]);
		}
		else
		{
			$this->setAlertFlashdata('success', $message);
			$return = ee('Encrypt')->decode(ee()->input->get_post('return'));
			ee()->functions->redirect(ee()->functions->create_url($return));
		}
	}

	/**
	 * Set Alert Flashdata
	 *
	 *   Adds an alert onto this requests's flashdata alert stack
	 *
	 * @param string $type    issue/success/warn/
	 * @param string $message Alert message
	 */
	protected function setAlertFlashdata($type, $message)
	{
		$key = md5('Consent/alerts');

		$alert = [
			'type' => $type,
			'message' => $message
		];

		if ($data = ee()->session->flashdata($key))
		{
			$data[] = $alert;
		}
		else
		{
			$data = [$alert];
		}

		ee()->session->set_flashdata($key, $data);
	}

	/**
	 * Get Valid Consent Requests from tag parameter
	 *
	 * @param  string $param supplied tag parameter
	 * @return array valid names in accord with the parameter
	 */
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

	/**
	 * Get Variables for Requests
	 *
	 * 	Abstracted for re-use and to standardize user_created= filtering
	 *
	 * @param  array $requests Consent data from ee('Consent')->getConsentDataFor()
	 * @return array Variables for parsing
	 */
	private function getVariablesForRequests($requests)
	{
		$user_created = ee()->TMPL->fetch_param('user_created');

		$consents = [];
		foreach ($requests as $request)
		{
			if ($user_created == 'only' && ! $request['user_created'])
			{
				continue;
			}

			if ($user_created == 'no' && $request['user_created'])
			{
				continue;
			}

			$request_vars = new ConsentVars($request, ee()->TMPL->var_single);
			$consents[] = $request_vars->getTemplateVariables();
		}

		return $consents;
	}

	/**
	 * Check GET CSRF token
	 *
	 * @return void, throws auth error on failure
	 */
	private function validateGetCsrf()
	{
		$token = ee()->input->get('token');

		if ($token != CSRF_TOKEN)
		{
			if (AJAX_REQUEST)
			{
				ee()->output->send_ajax_response(lang('csrf_token_expired'), TRUE);
			}

			ee()->output->show_user_error('general', array(lang('csrf_token_expired')));
		}
	}
}
// END CLASS

// EOF
