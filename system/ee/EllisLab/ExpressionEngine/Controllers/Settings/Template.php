<?php

namespace EllisLab\ExpressionEngine\Controllers\Settings;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Template Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Template extends Settings {

	/**
	 * General Settings
	 */
	public function index()
	{
		ee()->load->model('admin_model');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'strict_urls',
					'desc' => 'strict_urls_desc',
					'fields' => array(
						'strict_urls' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							)
						)
					)
				),
				array(
					'title' => 'site_404',
					'desc' => 'site_404_desc',
					'fields' => array(
						'site_404' => array(
							'type' => 'select',
							'choices' => ee()->admin_model->get_template_list()
						)
					),
				),
				array(
					'title' => 'save_tmpl_revisions',
					'desc' => 'save_tmpl_revisions_desc',
					'fields' => array(
						'save_tmpl_revisions' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'max_tmpl_revisions',
					'desc' => 'max_tmpl_revisions_desc',
					'fields' => array(
						'max_tmpl_revisions' => array('type' => 'text')
					)
				),
				array(
					'title' => 'save_tmpl_files',
					'desc' => 'save_tmpl_files_desc',
					'fields' => array(
						'save_tmpl_files' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'tmpl_file_basepath',
					'desc' => 'tmpl_file_basepath_desc',
					'fields' => array(
						'tmpl_file_basepath' => array('type' => 'text')
					)
				),
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'max_tmpl_revisions',
				'label' => 'lang:max_tmpl_revisions',
				'rules' => 'integer'
			),
			array(
				'field' => 'tmpl_file_basepath',
				'label' => 'lang:tmpl_file_basepath',
				'rules' => 'file_exists'
			),
		));

		$base_url = cp_url('settings/template');

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if ($this->saveSettings($vars['sections']))
			{
				ee()->view->set_message('success', lang('preferences_updated'), lang('preferences_updated_desc'), TRUE);
			}

			ee()->functions->redirect($base_url);
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
		}

		ee()->view->base_url = $base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('template_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(cp_url('design'), lang('template_manager'));

		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

/* End of file Template.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Settings/Template.php */