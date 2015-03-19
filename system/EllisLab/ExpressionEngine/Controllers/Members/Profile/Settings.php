<?php

namespace EllisLab\ExpressionEngine\Controllers\Members\Profile;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Module\FilePicker\FilePicker as FilePicker;

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
		$this->base_url = cp_url($this->base_url, $this->query_string);

		// Birthday Options
		$birthday['days'] = array();

		$birthday['years'][''] = lang('year');

		for ($i = date('Y', $this->localize->now); $i > 1904; $i--)
		{
		  $birthday['years'][$i] = $i;
		}

		$birthday['months'] = array(
			''	 => lang('month'),
			'01' => lang('cal_january'),
			'02' => lang('cal_february'),
			'03' => lang('cal_march'),
			'04' => lang('cal_april'),
			'05' => lang('cal_mayl'),
			'06' => lang('cal_june'),
			'07' => lang('cal_july'),
			'08' => lang('cal_august'),
			'09' => lang('cal_september'),
			'10' => lang('cal_october'),
			'11' => lang('cal_november'),
			'12' => lang('cal_december')
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

		$avatarDirs = directory_map(ee()->config->slash_item('avatar_path'), 2);

		if (is_array($avatarDirs))
		{
			$avatarDirs = array_filter($avatarDirs, 'is_array');	// only grab subfolders
			unset($avatarDirs['uploads']); // remove user uploaded avatars
			$avatarDirs = array_keys($avatarDirs);
		}
		else
		{
			$avatarDirs = array();
		}

		$fp = new FilePicker();
		$fp->inject(ee()->view);
		$dirs = array();
		$dirs[] = $fp->link('test', 'all', array('input' => 'avatar', 'image' => 'avatar'));

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
							'type' => 'dropdown',
							'choices' => $birthday['days'],
							'value' => $this->member->bday_d
						),
						'bday_m' => array(
							'type' => 'dropdown',
							'choices' => $birthday['months'],
							'value' => $this->member->bday_m
						),
						'bday_y' => array(
							'type' => 'dropdown',
							'choices' => $birthday['years'],
							'value' => $this->member->bday_y
						)
					)
				),
				array(
					'title' => 'biography',
					'desc' => 'biography_desc',
					'fields' => array(
						'bio' => array('type' => 'text', 'value' => $this->member->bio)
					)
				),
				array(
					'title' => 'language',
					'desc' => 'language_desc',
					'fields' => array(
						'language' => array(
							'type' => 'dropdown',
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
								'allow_messages' => 'allow_messages',
								'display_avatars' => 'display_avatars',
								'parse_smileys' => 'parse_smileys'
							),
							'value' => $settings
						),
					)
				)
			),
			'avatar' => array(
				array(
					'title' => 'current_avatar',
					'desc' => 'current_avatar_desc',
					'fields' => array(
						'avatar' => array(
							'type' => 'image',
							'id' => 'avatar',
							'image' => '/images/avatars/expression_radar.jpg'
						)
					)
				),
				array(
					'title' => 'change_avatar',
					'desc' => 'current_avatar_desc',
					'fields' => array(
						'avatar_upload' => array(
							'type' => 'radio',
							'choices' => array(
								'upload' => 'upload'
							)
						),
						'avatar_picker' => array(
							'type' => 'radio',
							'value' => 'choose',
							'choices' => array(
								'choose' => 'choose'
							),
							'html' => ul($dirs, array('class' => 'arrow-list'))
						),
						'avatar_link' => array(
							'type' => 'radio',
							'choices' => array(
								'link' => 'link'
							)
						),
					)
				)
			)
		);

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
				ee()->view->set_message('success', lang('member_updated'), lang('member_updated_desc'), TRUE);
				ee()->functions->redirect($base_url);
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
		}

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('personal_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_save_settings_working';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

/* End of file Settings.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Profile/Settings.php */
