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
 * ExpressionEngine CP Avatars Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Avatars extends Settings {

	public function index()
	{
		$vars['sections'] = array(
			array(
				array(
					'title' => 'enable_avatars',
					'desc' => 'enable_avatars_desc',
					'fields' => array(
						'enable_avatars' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'allow_avatar_uploads',
					'desc' => 'allow_avatar_uploads_desc',
					'fields' => array(
						'allow_avatar_uploads' => array('type' => 'yes_no')
					)
				)
			),
			'url_path_settings_title' => array(
				array(
					'title' => 'avatar_url',
					'desc' => 'avatar_url_desc',
					'fields' => array(
						'avatar_url' => array('type' => 'text')
					)
				),
				array(
					'title' => 'avatar_path',
					'desc' => 'avatar_path_desc',
					'fields' => array(
						'avatar_path' => array('type' => 'text')
					)
				)
			),
			'avatar_file_restrictions' => array(
				array(
					'title' => 'avatar_max_width',
					'desc' => 'avatar_max_width_desc',
					'fields' => array(
						'avatar_max_width' => array('type' => 'text')
					)
				),
				array(
					'title' => 'avatar_max_height',
					'desc' => 'avatar_max_height_desc',
					'fields' => array(
						'avatar_max_height' => array('type' => 'text')
					)
				),
				array(
					'title' => 'avatar_max_kb',
					'desc' => 'avatar_max_kb_desc',
					'fields' => array(
						'avatar_max_kb' => array('type' => 'text')
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'avatar_url',
				'label' => 'lang:avatar_url',
				'rules' => 'strip_tags|valid_xss_check'
			),
			array(
				'field' => 'avatar_path',
				'label' => 'lang:avatar_path',
				'rules' => 'strip_tags|valid_xss_check|file_exists|writable'
			),
			array(
				'field' => 'avatar_max_width',
				'label' => 'lang:avatar_max_width',
				'rules' => 'integer'
			),
			array(
				'field' => 'avatar_max_height',
				'label' => 'lang:avatar_max_height',
				'rules' => 'integer'
			),
			array(
				'field' => 'avatar_max_kb',
				'label' => 'lang:avatar_max_kb',
				'rules' => 'integer'
			)
		));

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		$base_url = cp_url('settings/avatars');

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

		ee()->view->ajax_validate = TRUE;
		ee()->view->base_url = $base_url;
		ee()->view->cp_page_title = lang('avatar_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

/* End of file Avatars.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Settings/Avatars.php */