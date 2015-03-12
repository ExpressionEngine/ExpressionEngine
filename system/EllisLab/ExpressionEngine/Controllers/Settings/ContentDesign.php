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
 * ExpressionEngine CP Content & Design Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ContentDesign extends Settings {

	/**
	 * General Settings
	 */
	public function index()
	{
		$vars['sections'] = array(
			array(
				array(
					'title' => 'new_posts_clear_caches',
					'desc' => 'new_posts_clear_caches_desc',
					'fields' => array(
						'new_posts_clear_caches' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'enable_sql_caching',
					'desc' => 'enable_sql_caching_desc',
					'fields' => array(
						'enable_sql_caching' => array('type' => 'yes_no')
					)
				)
			),
			'categories_section' => array(
				array(
					'title' => 'auto_assign_cat_parents',
					'desc' => 'auto_assign_cat_parents_desc',
					'fields' => array(
						'auto_assign_cat_parents' => array('type' => 'yes_no')
					)
				)
			)
		);

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		$base_url = cp_url('settings/content-design');

		if (ee()->form_validation->run() !== FALSE)
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
		ee()->view->cp_page_title = lang('content_and_design');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(cp_url('channel'), lang('channel_manager'));

		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

/* End of file ContentDesign.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Settings/ContentDesign.php */