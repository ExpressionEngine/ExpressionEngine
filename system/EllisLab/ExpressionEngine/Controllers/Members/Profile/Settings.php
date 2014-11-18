<?php

namespace EllisLab\ExpressionEngine\Controllers\Members\Profile;

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
		ee()->load->model('language_model');

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

		$vars['sections'] = array(
			array(
				array(
					'title' => 'url',
					'desc' => 'url_desc',
					'fields' => array(
						'url' => array('type' => 'text')
					)
				),
				array(
					'title' => 'location',
					'desc' => 'location_desc',
					'fields' => array(
						'location' => array('type' => 'text')
					)
				),
				array(
					'title' => 'birthday',
					'desc' => 'birthday_desc',
					'fields' => array(
						'month' => array('type' => 'dropdown', 'choices' => $birthday['months']),
						'days' => array('type' => 'dropdown', 'choices' => $birthday['days']),
						'years' => array('type' => 'dropdown', 'choices' => $birthday['years'])
					)
				),
				array(
					'title' => 'biography',
					'desc' => 'biography_desc',
					'fields' => array(
						'biography' => array('type' => 'text')
					)
				),
				array(
					'title' => 'language',
					'desc' => 'language_desc',
					'fields' => array(
						'lang' => array(
							'type' => 'dropdown',
							'choices' => ee()->language_model->language_pack_names(),
							'value' => ee()->config->item('deft_lang') ?: 'english'
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
								'display_emoticons' => 'display_emoticons'
							)
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
							'type' => 'html',
							'content' => ''
						)
					)
				),
				array(
					'title' => 'change_avatar',
					'desc' => 'current_avatar_desc',
					'fields' => array(
						'avatar' => array(
							'type' => 'radio',
							'choices' => array(
								'upload' => 'upload'
							)
						),
						'avatar' => array(
							'type' => 'radio',
							'choices' => array(
								'choose' => 'choose'
							)
						),
						'avatar' => array(
							'type' => 'radio',
							'choices' => array(
								'link' => 'link'
							)
						),
					)
				)
			)
		);

		ee()->view->base_url = cp_url($this->base_url);
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
