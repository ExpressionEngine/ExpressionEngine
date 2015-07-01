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
 * ExpressionEngine CP Messaging Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Pages extends Settings {

	/**
	 * General Settings
	 */
	public function index()
	{
		// Make sure this page can't load without Pages installed
		if ( ! ee()->addons_model->module_installed('pages'))
		{
			ee()->functions->redirect(cp_url('settings'));
		}

		// Create channels dropdown
		$channels = ee('Model')->get('Channel')
			->filter('site_id', ee()->config->item('site_id'))
			->order('channel_title')
			->all();

		$channels_dropdown = array(0 => lang('pages_no_default'));
		foreach ($channels as $channel)
		{
			$channels_dropdown[$channel->channel_id] = $channel->channel_title;
		}

		// Get data for default template dropdowns
		ee()->load->model('template_model');
		$templates = ee()->template_model->get_templates(ee()->config->item('site_id'));

		$templates_dropdown = array(0 => lang('pages_no_default'));
		foreach ($templates->result_array() as $template)
		{
			$templates_dropdown[$template['template_id']] = $template['group_name'].'/'.$template['template_name'];
		}

		ee()->load->add_package_path(PATH_ADDONS.'pages');
		ee()->load->model('pages_model');
		$pages_config = ee()->pages_model->fetch_site_pages_config();

		// Defaults if settings haven't been saved yet
		$config = array(
			'homepage_display' => 'not_nested',
			'default_channel' => 0
		);

		// Bring in settings from DB
		foreach ($pages_config->result_array() as $row)
		{
			$config[$row['configuration_name']] = $row['configuration_value'];
		}

		// Build array to populate multi-dropdown for default templates per channel
		$template_for_channel = array();
		foreach ($channels as $channel)
		{
			$template_for_channel['template_channel_'.$channel->channel_id] = array(
				'label' => $channel->channel_title,
				'choices' => $templates_dropdown,
				'value' => (isset($config['template_channel_'.$channel->channel_id]))
					? (int) $config['template_channel_'.$channel->channel_id] : 0
			);

			// Only accept integers from these fields
			ee()->form_validation->set_rules(
				'template_channel_'.$channel->channel_id,
				$channel->channel_title,
				'integer'
			);
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'pages_display_urls',
					'desc' => 'pages_display_urls_desc',
					'fields' => array(
						'homepage_display' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'nested' => 'pages_url_nested',
								'not_nested' => 'pages_url_not_nested'
							),
							'value' => $config['homepage_display']
						)
					)
				),
				array(
					'title' => 'pages_channel',
					'desc' => 'pages_channel_desc',
					'fields' => array(
						'default_channel' => array(
							'type' => 'select',
							'choices' => $channels_dropdown,
							'value' => (int) $config['default_channel']
						)
					)
				),
				array(
					'title' => 'pages_templates',
					'desc' => 'pages_templates_desc',
					'fields' => array(
						'pages_templates' => array(
							'type' => 'multiselect',
							'choices' => $template_for_channel
						)
					)
				)
			)
		);

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		$base_url = cp_url('settings/pages');

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
		ee()->view->cp_page_title = lang('pages_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->lang->loadfile('addons');
		ee()->lang->loadfile('pages');
		ee()->cp->set_breadcrumb(cp_url('addons'), lang('addon_manager'));
		ee()->cp->set_breadcrumb(cp_url('pages'), lang('pages_manager'));

		ee()->cp->render('settings/form', $vars);
	}

	/**
	  * Save Pages settings
	  */
	function saveSettings()
	{
		ee()->load->add_package_path(PATH_ADDONS.'pages');
		ee()->load->model('pages_model');

		$data = array();

		foreach($_POST as $key => $value)
		{
			if ($key == 'homepage_display' && in_array($value, array('nested', 'not_nested')))
			{
				$data[$key] = $value;
			}
			elseif (is_numeric($value) && $value != '0' && ($key == 'default_channel' OR substr($key, 0, strlen('template_channel_')) == 'template_channel_'))
			{
				$data[$key] = $value;
			}
		}

		if (count($data) > 0)
		{
			ee()->pages_model->update_pages_configuration($data);
		}

		return TRUE;
	}
}
// END CLASS

/* End of file Messages.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Settings/Messages.php */