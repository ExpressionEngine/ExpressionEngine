<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Pages_tab {

	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------	
	
	public function publish_tabs($channel_id, $entry_id = '')
	{
		ee()->lang->loadfile('pages');
	
		$site_id			= ee()->config->item('site_id');
		$settings 			= array();
	
		$no_templates 		= NULL;		
		$pages 				= ee()->config->item('site_pages');
		
		$pages_template_id 	= 0;
		$pages_dropdown 	= array();
		$pages_uri 			= '';
	
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
			$qry = ee()->db->select('configuration_value')
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
			ee()->javascript->set_global('publish.pages.pagesUri', lang('example_uri'));
			
			$qry = ee()->db->select('configuration_value')
								->where('configuration_name', 'template_channel_'.$channel_id)
								->where('site_id', (int) $site_id)
								->get('pages_configuration');
			
			if ($qry->num_rows() > 0)
			{
				$pages_template_id = (int) $qry->row('configuration_value');
			}
		}
		else
		{
			ee()->javascript->set_global('publish.pages.pageUri', $pages_uri);
		}
		
		ee()->load->model('template_model');
		$templates = ee()->template_model->get_templates($site_id);
		
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
			ee()->api_channel_fields->set_settings($k, $v);
		}
		
		return $settings;
	}
	
	// --------------------------------------------------------------------	

	/**
	 * Validate Publish
	 *
	 * @param	array
	 * @return 	mixed
	 */
	public function validate_publish($params)
	{
	    $errors         = FALSE;
        $pages_enabled  = FALSE;

        $params = $params[0];
		$pages_uri = (isset($params['pages_uri'])) ? $params['pages_uri'] : '';

        $pages = ee()->config->item('site_pages');

        
        if ($pages !== FALSE && $pages_uri != '' && $pages_uri !== lang('example_uri'))
        {
            $pages = TRUE;
            
        	if ( ! isset($params['pages_template_id']) OR 
        	     ! is_numeric($params['pages_template_id']))
        	{
        		$errors = array(lang('invalid_template') => 'pages_template_id');
        	}
        }
        
        $c_page_uri = preg_replace("#[^a-zA-Z0-9_\-/\.]+$#i", '',
                    str_replace(ee()->config->item('site_url'), '', $pages_uri));
        
        if ($c_page_uri !== $pages_uri)
        {
            $errors = array(lang('invalid_page_uri') => 'pages_uri');
        }
        
        // How many segments are we trying out?
    	$pages_uri_segs = substr_count(trim($pages_uri, '/'), '/');		

    	// More than 9 pages URI segs?  goodbye
    	if ($pages_uri_segs > 8)
    	{
    		$errors = array(lang('invalid_page_num_segs') => 'pages_uri');
    	}
    	
    	// Check if duplicate uri
    	$static_pages = ee()->config->item('site_pages');
    	$uris = $static_pages[ee()->config->item('site_id')]['uris'];

		if ( ! isset($params['entry_id']))
		{
			$params['entry_id'] == 0;
		}
		elseif ($params['entry_id'] !== 0)
		{
			if ( ! isset($uris[$params['entry_id']]) && in_array($pages_uri, $uris))
			{
				$errors = array(lang('duplicate_page_uri') => 'pages_uri');
			}
		}
		elseif (in_array($pages_uri, $uris))
    	{
    		$errors = array(lang('duplicate_page_uri') => 'pages_uri');
    	}

    	return $errors;        
	}
	
	// --------------------------------------------------------------------	
	
	/**
	 * Publish Data.
	 *
	 * @param 	array
	 * @return 	void
	 */	
	public function publish_data_db($params)
	{
	    $site_id    = ee()->config->item('site_id');
	    $mod_data   = (isset($params['mod_data'])) ? $params['mod_data'] : NULL;
	    $site_pages = ee()->config->item('site_pages');
		
        if ($site_pages !== FALSE
            && isset($mod_data['pages_uri']) 
            && $mod_data['pages_uri'] != lang('example_uri')
   			&& $mod_data['pages_uri'] != '')
        {
            if (isset($mod_data['pages_template_id'])
                && is_numeric($mod_data['pages_template_id']))
            {
				$page = preg_replace("#[^a-zA-Z0-9_\-/\.]+$#i", '',
				                    str_replace(ee()->config->item('site_url'), '',
				                                $mod_data['pages_uri']));
				
				$page = '/' . trim($page, '/');
				
				$site_pages[$site_id]['uris'][$params['entry_id']] = $page;
				$site_pages[$site_id]['templates'][$params['entry_id']] = preg_replace("#[^0-9]+$#i", '',
		                                            						$mod_data['pages_template_id']);

				if ($site_pages[$site_id]['uris'][$params['entry_id']] == '//')
				{
					$site_pages[$site_id]['uris'][$params['entry_id']] = '/';
				}
				
				ee()->config->set_item('site_pages', $site_pages);
				ee()->db->where('site_id', (int) $site_id)
							->update('sites', array(
								'site_pages' => base64_encode(serialize($site_pages))
							)
				);
            }
        }
	}

	// --------------------------------------------------------------------	
	
	/**
	 * Delete Actions
	 *
	 * @param 	array
	 * @return 	void
	 */
	public function publish_data_delete_db($params)
	{
		$site_pages = ee()->config->item('site_pages');
		$site_id	= ee()->config->item('site_id');
		
		foreach ($params['entry_ids'] as $entry_id)
		{
			unset($site_pages[$site_id]['uris'][$entry_id]);
			unset($site_pages[$site_id]['templates'][$entry_id]);
		}

		ee()->db->where('site_id', (int) $site_id)
					 ->update('sites', array(
					 			'site_pages'	=> 
									base64_encode(serialize($site_pages))
					 ));
	}

	// --------------------------------------------------------------------		
}