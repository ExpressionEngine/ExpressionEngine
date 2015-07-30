<?php
namespace EllisLab\ExpressionEngine\Controllers\Settings;

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
		$vars['sections'] = array(
			array(
				array(
					'title' => 'license_file',
					'desc' => 'license_file_desc',
					'fields' => array(
						'license_file' => array('type' => 'file')
					)
				),
			)
		);

		$base_url = ee('CP/URL', 'settings/license');

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

		ee()->view->base_url = $base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->has_file_input = TRUE;
		ee()->view->cp_page_title = lang('license_and_reg_title');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}
}
// EOF