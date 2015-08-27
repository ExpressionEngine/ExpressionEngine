<?php

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\Addons\FilePicker\FilePicker as FilePicker;

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
 * ExpressionEngine CP Member Profile Personal Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Settings extends Profile {

	private $base_url = 'members/profile/settings';

	/**
	 * Personal Settings
	 */
	public function index()
	{
		$this->base_url = ee('CP/URL', $this->base_url, $this->query_string);

		// Birthday Options
		$birthday['days'] = array();

		$birthday['years'][''] = lang('year');

		for ($i = date('Y', $this->localize->now); $i > 1904; $i--)
		{
		  $birthday['years'][$i] = $i;
		}

		$birthday['months'] = array(
			''	 => lang('month'),
			'01' => lang('january'),
			'02' => lang('february'),
			'03' => lang('march'),
			'04' => lang('april'),
			'05' => lang('mayl'),
			'06' => lang('june'),
			'07' => lang('july'),
			'08' => lang('august'),
			'09' => lang('september'),
			'10' => lang('october'),
			'11' => lang('november'),
			'12' => lang('december')
		);

		$birthday['days'][''] = lang('day');

		for ($i = 1; $i <= 31; $i++)
		{
		  $birthday['days'][$i] = $i;
		}

		$settings = array();

		if ($this->member->parse_smileys == 'y')
		{
			$settings[] = 'display_emoticons';
		}

		if ($this->member->display_avatars == 'y')
		{
			$settings[] = 'display_avatars';
		}

		if ($this->member->accept_messages == 'y')
		{
			$settings[] = 'allow_messages';
		}

		$this->load->helper('html');
		$this->load->helper('directory');

		$path = ee()->config->item('avatar_path');
		$directory = ee('Model')->get('UploadDestination')
						->filter('server_path', $path)
						->first();

		$fp = new FilePicker();
		$fp->inject(ee()->view);
		$dirs = array();
		$dirs[] = $fp->link('Avatars', $directory->id, array(
			'image' => 'avatar',
			'input' => 'avatar_filename',
			'class' => 'avatarPicker'
		));

		$vars['has_file_input'] = TRUE;
		$vars['sections'] = array(
			array(
				array(
					'title' => 'url',
					'desc' => 'url_desc',
					'fields' => array(
						'url' => array('type' => 'text', 'value' => $this->member->url)
					)
				),
				array(
					'title' => 'location',
					'desc' => 'location_desc',
					'fields' => array(
						'location' => array('type' => 'text', 'value' => $this->member->location)
					)
				),
				array(
					'title' => 'birthday',
					'desc' => 'birthday_desc',
					'fields' => array(
						'bday_d' => array(
							'type' => 'select',
							'choices' => $birthday['days'],
							'value' => $this->member->bday_d
						),
						'bday_m' => array(
							'type' => 'select',
							'choices' => $birthday['months'],
							'value' => $this->member->bday_m
						),
						'bday_y' => array(
							'type' => 'select',
							'choices' => $birthday['years'],
							'value' => $this->member->bday_y
						)
					)
				),
				array(
					'title' => 'biography',
					'desc' => 'biography_desc',
					'fields' => array(
						'bio' => array('type' => 'textarea', 'value' => $this->member->bio)
					)
				),
				array(
					'title' => 'language',
					'desc' => 'language_desc',
					'fields' => array(
						'language' => array(
							'type' => 'select',
							'choices' => ee()->lang->language_pack_names(),
							'value' => $this->member->language ?: ee()->config->item('deft_lang')
						)
					)
				),
				array(
					'title' => 'preferences',
					'desc' => 'preferences_desc',
					'fields' => array(
						'preferences' => array(
							'type' => 'checkbox',
							'choices' => array(
								'accept_messages' => lang('allow_messages'),
								'display_avatars' => lang('display_avatars'),
								'parse_smileys' => lang('parse_smileys')
							),
							'value' => $settings
						),
					)
				)
			),
			'avatar_settings' => array(
				array(
					'title' => 'current_avatar',
					'desc' => 'current_avatar_desc',
					'fields' => array(
						'avatar_filename' => array(
							'type' => 'image',
							'id' => 'avatar',
							'image' => $directory->url . $this->member->avatar_filename
						)
					)
				),
				array(
					'title' => 'change_avatar',
					'desc' => 'change_avatar_desc',
					'fields' => array(
						'avatar_picker' => array(
							'type' => 'radio_block',
							'choices' => array(
								'upload' => array(
									'label' => 'upload_avatar',
									'html' => form_upload('upload_avatar')
								),
								'choose' => array(
									'label' => 'choose_avatar',
									'html' => ul($dirs, array('class' => 'arrow-list'))
								),
								'link' => array(
									'label' => 'link_avatar',
									'html' => form_input('link_avatar', 'http://')
								)
							),
							'value' => 'choose'
						)
					)
				)
			)
		);

		if ($this->member->avatar_filename == "")
		{
			$vars['sections']['avatar_settings'][0]['hide'] = TRUE;
		}

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'url',
				 'label'   => 'lang:timezone',
				 'rules'   => 'valid_xss_check'
			),
			array(
				 'field'   => 'location',
				 'label'   => 'lang:location',
				 'rules'   => 'valid_xss_check'
			),
			array(
				 'field'   => 'bio',
				 'label'   => 'lang:biography',
				 'rules'   => 'valid_xss_check'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if ($this->saveSettings($vars['sections']))
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('member_updated'))
					->addToBody(lang('member_updated_desc'))
					->defer();
				ee()->functions->redirect($base_url);
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('settings_save_erorr'))
				->addToBody(lang('settings_save_error_desc'))
				->now();
		}

		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/members/avatar'
			),
		));

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('personal_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_save_settings_working';
		ee()->cp->render('settings/form', $vars);
	}

	protected function saveSettings($settings)
	{
		unset($settings['avatar_settings']);

		switch (ee()->input->post('avatar_picker')) {
			case "upload":
				$this->member->avatar_filename = $this->uploadAvatar();
				break;
			case "choose":
				$choice = ee()->input->post('avatar_filename');
				$this->member->avatar_filename = $choice;
				break;
			case "link":
				$this->member->avatar_filename = $this->uploadRemoteAvatar();
				break;
		}

		parent::saveSettings($settings);
	}

	private function uploadAvatar()
	{
		ee()->load->library('filemanager');
		$current = ee()->config->item('avatar_path');
		$directory = ee('Model')->get('UploadDestination')->first();
		$upload_response = ee()->filemanager->upload_file($directory->id, 'upload_avatar');

		if (isset($upload_response['error']))
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('upload_filedata_error'))
				->addToBody($upload_response['error'])
				->now();
		}

		return $upload_response->file_name;
	}

	private function uploadRemoteAvatar()
	{
		$url = ee()->input->post('link_avatar');
		$current = ee()->config->item('avatar_path');
		$directory = ee('Model')->get('UploadDestination')->first();

    	$ch = curl_init($url);
    	curl_setopt($ch, CURLOPT_HEADER, 0);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    	$file = curl_exec($ch);
    	curl_close($ch);

		ee()->load->library('filemanager');
		ee()->load->library('upload', $config);

		$file_path = ee()->filemanager->clean_filename(
			$filename,
			$directory_id,
			array('ignore_dupes' => FALSE)
		);
		$filename = basename($file_path);

		// Upload the file
		$config = array('upload_path' => dirname($file_path));

		if (ee()->upload->raw_upload($filename, $file) === FALSE)
		{
			return FALSE;
		}

		$result = ee()->filemanager->save_file(
			$file_path,
			$directory->id,
			array(
				'title'     => $filename,
				'rel_path'  => dirname($file_path),
				'file_name' => $filename
			)
		);

		return $filename;
	}
}
// END CLASS

/* End of file Settings.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Profile/Settings.php */
