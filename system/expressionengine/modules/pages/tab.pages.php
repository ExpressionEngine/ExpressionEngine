<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Discussion Pages Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Pages_tab {

	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------	
	
	public function publish_tabs($channel_id, $entry_id = '')
	{
		$this->EE->lang->loadfile('pages');
	
		$site_id			= $this->EE->config->item('site_id');
	
		$settings 			= array();
	
		$no_templates 		= NULL;		
		$pages 				= $this->EE->config->item('site_pages');
		
		$pages_template_id 	= 0;
		$pages_dropdown 	= array();
		$pages_uri 			= (isset($entry_data['pages_uri'])) ? $entry_data['pages_uri'] : '';
	
		if ($entry_id !== 0)
		{
			if (isset($pages[$site_id]['uris'][$entry_id]))
			{
				$pages_uri = $pages[$site_id]['uris'][$entry_id];				
			}
	
			if (isset($pages[$site_id]['templates'][$entry_id]))
			{
				$pages_template_id = $pages[$site_id]['templates'][$entry_id];
			}
		}
		else
		{
			$qry = $this->EE->db->select('configuration_value')
								->where('configuration_name', 'template_channel_'.$channel_id)
								->where('site_id', (int) $site_id)
								->get('pages_configuration');
			
			if ($qry->num_rows() > 0)
			{
				$pages_template_id = (int) $qry->row('configuration_value');
			}
		}
	
		if ($pages_uri == '')
		{
			$this->EE->javascript->set_global('publish.pages.pagesUri', lang('example_uri'));
		}
		else
		{
			$this->EE->javascript->set_global('publish.pages.pageUri', $pages_uri);
		}
		
		$templates = $this->EE->template_model->get_templates($site_id);
		
		foreach ($templates->result() as $template)
		{
			$pages_dropdown[$template->group_name][$template->template_id] = $template->template_name;
		}
		
		if ($templates->num_rows() === 0)
		{
			$no_templates = lang('no_templates');
		}
		
		$settings = array(
			'pages_uri'				=> array(
				'field_id'				=> 'pages_uri',
				'field_label'			=> lang('pages_uri'),
				'field_type'			=> 'text',
				'field_required'		=> 'n',
				'field_data'			=> $pages_uri,
				'field_text_direction'	=> 'ltr',
				'field_maxl'			=> 100,
				'field_instructions'	=> '',
			),
			'pages_template_id'		=> array(
				'field_id'				=> 'pages_template_id',
				'field_label'			=> lang('template'),
				'field_type'			=> 'select',
				'field_required'		=> 'n',
				'field_pre_populate'	=> 'n',
				'field_list_items'		=> $pages_dropdown,
				'field_data'			=> $pages_template_id,
				'options'				=> $pages_dropdown,
				'selected'				=> $pages_template_id,
				'field_text_direction'	=> 'ltr',
				'field_maxl'			=> 100,
				'field_instructions'	=> '',
				'string_override'		=> $no_templates,			
			),
		);
		
		foreach ($settings as $k => $v)
		{
			$this->EE->api_channel_fields->set_settings($k, $v);
		}
		
		return $settings;
	}
}