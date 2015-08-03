<?php
namespace EllisLab\ExpressionEngine\Controllers\Settings;

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
 * ExpressionEngine CP License Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class License extends Settings {

	/**
	 * General Settings
	 */
	public function index()
	{
		$base_url = ee('CP/URL', 'settings/license');

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => $base_url,
			'has_file_input' => TRUE,
			'license' => ee('License')->getEELicense(),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(
				array(
					array(
						'title' => 'license_file',
						'desc' => 'license_file_desc',
						'fields' => array(
							'license_file' => array('type' => 'file')
						)
					),
				)
			)
		);

		if ( ! empty($_FILES))
		{
			$license_file = ee('Request')->file('license_file');

			$license = ee('License')->getEELicense($license_file['tmp_name']);
			if ($license->isValid())
			{
				if (rename($license_file['tmp_name'], SYSPATH.'user/config/license.key'))
				{
					$alert = ee('Alert')->makeInline('shared-form')
						->asSuccess()
						->withTitle(lang('license_updated'))
						->addToBody(lang('license_updated_desc'))
						->defer();

					ee()->functions->redirect($base_url);
				}
				else
				{
					ee('Alert')->makeInline('shared-form')
						->asIssue()
						->withTitle(lang('license_file_fail'))
						->addToBody(sprintf(lang('license_file_permissions'), SYSPATH.'user/config'))
						->now();
				}
			}
			else
			{
				$alert = ee('Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('license_file_error'));

				foreach ($license->getErrors() as $key => $value)
				{
					$alert->addToBody(lang('license_file_' . $key));
				}

				$alert->now();
			}
		}

		if (IS_CORE)
		{
			ee('Alert')->makeInline('core-license')
				->asWarning()
				->cannotClose()
				->withTitle(lang('features_limited'))
				->addtoBody(lang('features_limited_desc'))
				->now();
		}

		ee()->view->cp_page_title = lang('license_and_registration_settings');
		ee()->cp->render('settings/license', $vars);
	}
}
// EOF