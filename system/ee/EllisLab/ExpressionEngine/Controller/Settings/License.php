<?php
namespace EllisLab\ExpressionEngine\Controller\Settings;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class License extends Settings {

	/**
	 * General Settings
	 */
	public function index()
	{
		$base_url = ee('CP/URL', 'settings/license');
		$errors = NULL;

		if ( ! empty($_FILES))
		{
			$license_file = ee('Request')->file('license_file');

			$validator = ee('Validation')->make(array(
				'license_file' => 'required',
			));

			$result = $validator->validate(array('license_file' => $license_file['name']));

			if ($result->isNotValid())
			{
				$errors = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('license_file_upload_error'))
					->addToBody(lang('license_file_upload_error_desc'))
					->now();
			}
			else
			{

				$license = ee('License')->getEELicense($license_file['tmp_name']);
				if ($license->isValid())
				{
					if (rename($license_file['tmp_name'], SYSPATH.'user/config/license.key'))
					{
						$alert = ee('CP/Alert')->makeInline('shared-form')
							->asSuccess()
							->withTitle(lang('license_updated'))
							->addToBody(lang('license_updated_desc'))
							->defer();

						ee()->functions->redirect($base_url);
					}
					else
					{
						ee('CP/Alert')->makeInline('shared-form')
							->asIssue()
							->withTitle(lang('license_file_fail'))
							->addToBody(sprintf(lang('license_file_permissions'), SYSPATH.'user/config'))
							->now();
					}
				}
				else
				{
					$alert = ee('CP/Alert')->makeInline('shared-form')
						->asIssue()
						->withTitle(lang('license_file_error'));

					foreach ($license->getErrors() as $key => $value)
					{
						$alert->addToBody(sprintf(lang('license_file_' . $key), 'https://store.ellislab.com/manage'));
					}

					$alert->now();
				}
			}
		}

		if (IS_CORE)
		{
			ee('CP/Alert')->makeInline('core-license')
				->asWarning()
				->cannotClose()
				->withTitle(lang('features_limited'))
				->addtoBody(sprintf(lang('features_limited_desc'), 'https://store.ellislab.com'))
				->now();
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => $base_url,
			'errors' => $errors,
			'has_file_input' => TRUE,
			'license' => ee('License')->getEELicense(),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(
				array(
					array(
						'title' => 'license_file',
						'desc' => sprintf(lang('license_file_desc'), 'https://store.ellislab.com/manage'),
						'fields' => array(
							'license_file' => array('type' => 'file'),
							'required' => TRUE
						)
					),
				)
			)
		);

		ee()->view->cp_page_title = lang('license_and_registration_settings');
		ee()->cp->render('settings/license', $vars);
	}
}

// EOF