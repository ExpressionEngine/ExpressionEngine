<?php

namespace EllisLab\ExpressionEngine\Controller\Settings;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Simple Commerce Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Commerce extends Settings {

	public function index()
	{
		// Make sure this page can't load without Simple Commerce installed
		if ( ! ee()->addons_model->module_installed('simple_commerce'))
		{
			ee()->functions->redirect(ee('CP/URL', 'settings'));
		}

		$base = reduce_double_slashes(str_replace('/public_html', '', SYSPATH).'/user/encryption/');

		$vars['sections'] = array(
			array(
				ee('Alert')->makeInline('ipn-notice')
					->asWarning()
					->cannotClose()
					->addToBody(sprintf(lang('commerce_ipn_notice'), 'https://developer.paypal.com/webapps/developer/docs/classic/ipn/integration-guide/IPNIntro/'))
					->render(),
				array(
					'title' => 'commerce_ipn_url',
					'desc' => 'commerce_ipn_url_desc',
					'fields' => array(
						'sc_api_url' => array(
							'type' => 'text',
							'value' => ee()->functions->fetch_site_index(0,0).QUERY_MARKER.'ACT='.ee()->cp->fetch_action_id('Simple_commerce', 'incoming_ipn'),
							'disabled' => TRUE
						)
					)
				),
				array(
					'title' => 'commerce_paypal_email',
					'desc' => 'commerce_paypal_email_desc',
					'fields' => array(
						'sc_paypal_account' => array('type' => 'text')
					)
				),
				array(
					'title' => 'commerce_encrypt_paypal',
					'desc' => 'commerce_encrypt_paypal_desc',
					'fields' => array(
						'sc_encrypt_buttons' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'commerce_paypal_cert_id',
					'desc' => 'commerce_paypal_cert_id_desc',
					'fields' => array(
						'sc_certificate_id' => array(
							'type' => 'text',
							'value' => (ee()->config->item('sc_certificate_id') === FALSE) ? '' : ee()->config->item('sc_certificate_id')
						)
					)
				),
				array(
					'title' => 'commerce_cert_path',
					'desc' => 'commerce_cert_path_desc',
					'fields' => array(
						'sc_public_certificate' => array(
							'type' => 'text',
							'value' => (ee()->config->item('sc_public_certificate') === FALSE OR ee()->config->item('sc_public_certificate') == '') ? $base.'public_certificate.pem' : ee()->config->item('sc_public_certificate')
						)
					)
				),
				array(
					'title' => 'commerce_key_path',
					'desc' => 'commerce_key_path_desc',
					'fields' => array(
						'sc_private_key' => array(
							'type' => 'text',
							'value' => (ee()->config->item('sc_private_key') === FALSE OR ee()->config->item('sc_private_key') == '') ? $base.'private_key.pem' : ee()->config->item('sc_private_key')
						)
					)
				),
				array(
					'title' => 'commerce_paypal_cert_path',
					'desc' => 'commerce_paypal_cert_path_desc',
					'fields' => array(
						'sc_paypal_certificate' => array(
							'type' => 'text',
							'value' => (ee()->config->item('sc_paypal_certificate') === FALSE OR ee()->config->item('sc_paypal_certificate') == '') ? $base.'paypal_certificate.pem' : ee()->config->item('sc_paypal_certificate')
						)
					)
				),
				array(
					'title' => 'commerce_temp_path',
					'desc' => 'commerce_temp_path_desc',
					'fields' => array(
						'sc_temp_path' => array('type' => 'text')
					)
				)
			)
		);

		$base_url = ee('CP/URL', 'settings/commerce');

		if ( ! empty($_POST))
		{
			$result = ee('Validation')->make(array(
				'sc_paypal_account'     => 'email',
				'sc_encrypt_buttons'    => 'enum[y,n]',
				'sc_public_certificate' => 'fileExists',
				'sc_private_key'        => 'fileExists',
				'sc_paypal_certificate' => 'fileExists',
				'sc_temp_path'          => 'fileExists'
			))->validate($_POST);

			if ($response = $this->ajaxValidation($result))
			{
				return $response;
			}

			if ($result->isValid())
			{
				// Unset API URL
				unset($vars['sections'][0][1]);
				if ($this->saveSettings($vars['sections']))
				{
					ee('Alert')->makeInline('shared-form')
						->asSuccess()
						->withTitle(lang('preferences_updated'))
						->addToBody(lang('preferences_updated_desc'))
						->defer();
				}

				ee()->functions->redirect($base_url);
			}
			else
			{
				$vars['errors'] = $result;
				ee('Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('settings_save_error'))
					->addToBody(lang('settings_save_error_desc'))
					->now();
			}
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->base_url = $base_url;
		ee()->view->cp_page_title = lang('commerce_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->lang->loadfile('addons');
		ee()->cp->set_breadcrumb(ee('CP/URL', 'addons'), lang('addon_manager'));
		ee()->cp->set_breadcrumb(ee('CP/URL', 'addons/settings/simple_commerce'), lang('simple_commerce'));

		ee()->cp->render('settings/form', $vars);
	}
}
// EOF
