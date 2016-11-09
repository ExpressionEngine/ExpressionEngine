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
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Settings extends Profile {

	private $base_url = 'members/profile/settings';

	protected function permissionCheck()
	{
		$id = ee()->input->get('id');

		if ($id != $this->session->userdata['member_id'] && ! empty($id))
		{
			parent::permissionCheck();
		}
	}

	/**
	 * Personal Settings
	 */
	public function index()
	{
		$this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);

		// Birthday Options
		$birthday['days'] = array();

		$birthday['years'][''] = lang('year');

		for ($i = date('Y', $this->localize->now); $i > 1904; $i--)
		{
		  $birthday['years'][$i] = $i;
		}

		$birthday['months'] = array(
			''	 => lang('month'),
			'01' => lang('January'),
			'02' => lang('February'),
			'03' => lang('March'),
			'04' => lang('April'),
			'05' => lang('May_l'),
			'06' => lang('June'),
			'07' => lang('July'),
			'08' => lang('August'),
			'09' => lang('September'),
			'10' => lang('October'),
			'11' => lang('November'),
			'12' => lang('December')
		);

		$birthday['days'][''] = lang('day');

		for ($i = 1; $i <= 31; $i++)
		{
		  $birthday['days'][$i] = $i;
		}

		$settings = array();

		if ($this->member->parse_smileys == 'y')
		{
			$settings[] = 'parse_smileys';
		}

		if ($this->member->display_avatars == 'y')
		{
			$settings[] = 'display_avatars';
		}

		if ($this->member->accept_messages == 'y')
		{
			$settings[] = 'accept_messages';
		}

		$this->load->helper('html');
		$this->load->helper('directory');

		$path = ee()->config->item('avatar_path');

		$directories = ee('Model')->get('UploadDestination')
			->filter('name', 'IN', array('Default Avatars', 'Avatars'))
			->all()
			->indexBy('name');

		$default = $directories['Default Avatars'];

		if ($this->member->avatar_filename)
		{
			foreach ($directories as $dir)
			{
				if ($dir->getFilesystem()->exists($this->member->avatar_filename))
				{
					$directory = $dir;
					break;
				}
			}
		}

		if ( ! isset($directory))
		{
			$directory = $default;
		}

		$fp = ee('CP/FilePicker')->make($default->id);

		$dirs = array();
		$avatar_choices = array();

		if ($directory)
		{
			$link = $fp->getLink('Default Avatars')
				->withImage('avatar')
				->withValueTarget('avatar_filename')
				->disableFilters()
				->disableUploads()
				->asThumbs()
				->setSelected($this->member->avatar_filename)
				->setAttribute('class', 'avatarPicker');

			$dirs[] = $link->render();

			$avatar_choices = array(
				'upload' => array(
					'label' => 'upload_avatar',
					'html' => form_upload('upload_avatar')
				),
				'choose' => array(
					'label' => 'choose_avatar',
					'html' => ul($dirs, array('class' => 'arrow-list'))
				)
			);
		}

		$avatar_choose_lang_desc = lang('change_avatar_desc');
		if (count($avatar_choices) == 1)
		{
			$avatar_choose_lang_desc .= sprintf(lang('update_avatar_path'), ee('CP/URL', 'settings/avatars'));
		}

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
							'edit' => FALSE,
							'image' => ($directory) ? $directory->url . $this->member->avatar_filename : '',
							'value' => $this->member->avatar_filename
						)
					)
				),
				array(
					'title' => 'change_avatar',
					'desc' => $avatar_choose_lang_desc,
					'fields' => array(
						'avatar_picker' => array(
							'type' => 'radio_block',
							'choices' => $avatar_choices,
							'value' => (count($avatar_choices) == 1) ? 'link' : 'choose'
						)
					)
				)
			)
		);

		foreach ($this->member->getDisplay()->getFields() as $field)
		{
			$vars['sections']['custom_fields'][] = array(
				'title' => $field->getLabel(),
				'desc' => '',
				'fields' => array(
					$field->getName() => array(
						'type' => 'html',
						'content' => $field->getForm(),
						'required' => $field->isRequired(),
					)
				)
			);
		}

		if ($this->member->avatar_filename == "")
		{
			$vars['sections']['avatar_settings'][0]['hide'] = TRUE;
		}

		if ( ! empty($_POST))
		{
			$result = $this->saveSettings($vars['sections']);

			if ( ! is_bool($result))
			{
				return $result;
			}

			if ($result)
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('member_updated'))
					->addToBody(lang('member_updated_desc'))
					->defer();
				ee()->functions->redirect($this->base_url);
			}
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
		ee()->view->save_btn_text_working = 'btn_saving';
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
		}

		return parent::saveSettings($settings);
	}

	protected function uploadAvatar()
	{
		$existing = ee()->config->item('avatar_path') . $this->member->avatar_filename;

		if (file_exists($existing) && is_file($existing))
		{
			unlink($existing);
		}

		ee()->load->library('filemanager');
		$directory = ee('Model')->get('UploadDestination')
			->filter('name', 'Avatars')
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		$upload_response = ee()->filemanager->upload_file($directory->id, 'upload_avatar');

		if (isset($upload_response['error']))
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('upload_filedata_error'))
				->addToBody($upload_response['error'])
				->now();

			return;
		}

		// We don't have the suffix, so first we explode to avoid passed by reference error
		// Then we grab our suffix
		$name_array = explode('.', $_FILES['upload_avatar']['name']);
		$suffix = array_pop($name_array);

		$name = $_FILES['upload_avatar']['name'];
		$name = 'avatar_'.$this->member->member_id.'.'.$suffix;

		$file_path = ee()->filemanager->clean_filename(
		        basename($name),
		        $directory->id,
		        array('ignore_dupes' => FALSE)
		);
		$filename = basename($file_path);

		// Upload the file
		ee()->load->library('upload', array('upload_path' => dirname($file_path)));
		ee()->upload->do_upload('file');
		$original = ee()->upload->upload_path . ee()->upload->file_name;

		if ( ! @copy($original, $file_path))
		{
		        if ( ! @move_uploaded_file($original, $file_path))
		        {
		                ee('CP/Alert')->makeInline('shared-form')
		                        ->asIssue()
		                        ->withTitle(lang('upload_filedata_error'))
		                        ->now();

		                return FALSE;
		        }
		}

		unlink($original);
		$result = (array) ee()->upload;

		return $filename;
	}
}
// END CLASS

// EOF
