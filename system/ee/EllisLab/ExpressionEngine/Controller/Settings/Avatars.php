<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Settings;

use CP_Controller;

/**
 * Avatars Settings Controller
 */
class Avatars extends Settings {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_members', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'), 403);
		}
	}

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
					'fields' => array(
						'avatar_max_width' => array('type' => 'text')
					)
				),
				array(
					'title' => 'avatar_max_height',
					'fields' => array(
						'avatar_max_height' => array('type' => 'text')
					)
				),
				array(
					'title' => 'avatar_max_kb',
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

		$base_url = ee('CP/URL')->make('settings/avatars');

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$directory_settings = array(
				'avatar_path'       => ee()->input->post('avatar_path'),
				'avatar_url'        => ee()->input->post('avatar_url'),
				'avatar_max_kb'     => ee()->input->post('avatar_max_kb'),
				'avatar_max_width'  => ee()->input->post('avatar_max_width'),
				'avatar_max_height' => ee()->input->post('avatar_max_height')
			);

			if ($this->saveSettings($vars['sections'])
				&& $this->updateUploadDirectory($directory_settings))
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

	/**
	 * Update the upload preferences for the associated upload directory
	 *
	 * @param mixed $data
	 * @access private
	 * @return void
	 */
	private function updateUploadDirectory($data)
	{
		$directory = ee('Model')->get('UploadDestination')
			->filter('name', 'Avatars')
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $directory)
		{
			$directory = ee('Model')->make('UploadDestination');
			$directory->name = 'Avatars';
			$directory->site_id = ee()->config->item('site_id');
			$directory->Module = ee('Model')->get('Module')->filter('module_name', 'Member')->first();
		}
		$directory->server_path = $data['avatar_path'];
		$directory->url = $data['avatar_url'];
		$directory->max_size = $data['avatar_max_kb'];
		$directory->max_width = $data['avatar_max_width'];
		$directory->max_height = $data['avatar_max_height'];
		$directory->Files = NULL;
		$directory->save();

		return TRUE;
	}
}
// END CLASS

// EOF
