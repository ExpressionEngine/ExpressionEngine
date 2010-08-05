<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Pages Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Pages_mcp {

	var $page_array		    = array();
	var $pages			    = array();
	var $homepage_display;

	/**
	  *  Constructor
	  */
	function Pages_mcp($switch=TRUE)
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		$this->EE->load->model('pages_model');
		
		$query = $this->EE->pages_model->fetch_configuration();


		$default_channel = 0;

		$this->homepage_display = 'not_nested';		

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$$row['configuration_name'] = $row['configuration_value'];
			}
			
			$this->homepage_display = $homepage_display;
		}

        $new_page_location = '';
        
		if ($default_channel != 0)
		{
			$new_page_location = AMP.'M=entry_form'.AMP.'channel_id='.$default_channel;
		}		
		
		$this->EE->cp->set_right_nav(array(
				'create_page'			=> BASE.AMP.'C=content_publish'.$new_page_location,
				'pages_configuration'	=> BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages'.AMP.'method=configuration'
			));
	}

	// --------------------------------------------------------------------

	/**
	  *  Pages Main page
	  */
	function index()
	{
	    $this->EE->load->model('pages_model');
	    
		$vars['cp_page_title'] = $this->EE->lang->line('pages_module_name');
		$vars['new_page_location'] = '';

		$this->EE->load->library('table');
		$this->EE->load->library('javascript');
		$this->EE->load->helper('form');

		$this->EE->javascript->output(array(
				'$(".toggle_all").toggle(
					function(){
						$("input.toggle").each(function() {
							this.checked = true;
						});
					}, function (){
						var checked_status = this.checked;
						$("input.toggle").each(function() {
							this.checked = false;
						});
					}
				);'
			)
		);

		$this->EE->javascript->compile();

		$pages = $this->EE->config->item('site_pages');

		if ($pages === FALSE OR count($pages[$this->EE->config->item('site_id')]['uris']) == 0)
		{
			return $this->EE->load->view('index', $vars, TRUE);
		}

		natcasesort($pages[$this->EE->config->item('site_id')]['uris']);
		$vars['pages'] = array();

		//  Our Pages

		$i = 0;
		$previous = array();
		$spcr = '<img src="'.PATH_CP_GBL_IMG.'clear.gif" border="0"  width="24" height="14" alt="" title="" />';
		$indent = $spcr.'<img src="'.PATH_CP_GBL_IMG.'cat_marker.gif" border="0"  width="18" height="14" alt="" title="" />';

		foreach($pages[$this->EE->config->item('site_id')]['uris'] as $entry_id => $url)
		{
			$url = ($url == '/') ? '/' : '/'.trim($url, '/').'/';

			$vars['pages'][$entry_id]['entry_id'] = $entry_id;
			$vars['pages'][$entry_id]['entry_id'] = $entry_id;
			$vars['pages'][$entry_id]['view_url'] = $this->EE->functions->fetch_site_index().QUERY_MARKER.'URL='.urlencode($this->EE->functions->create_url($url));
			$vars['pages'][$entry_id]['page'] = $url;
			$vars['pages'][$entry_id]['indent'] = '';

			if ($this->homepage_display == 'nested' && $url != '/')
            {
            	$x = explode('/', trim($url, '/'));

            	for($i=0, $s=count($x); $i < $s; ++$i)
            	{
            		if (isset($previous[$i]) && $previous[$i] == $x[$i])
            		{
            			continue;
            		}

					$this_indent = ($i == 0) ? '' : str_repeat($spcr, $i-1).$indent;
					$vars['pages'][$entry_id]['indent'] = $this_indent;
            	}

            	$previous = $x;
            }

			$vars['pages'][$entry_id]['toggle'] = array(
														'name'		=> 'toggle[]',
														'id'		=> 'delete_box_'.$entry_id,
														'value'		=> $entry_id,
														'class'		=>'toggle'
														);

		}

		return $this->EE->load->view('index', $vars, TRUE);
	}


	/*
		Hunting for Bugs in the Code...

	           /      \
	        \  \  ,,  /  /
	         '-.`\()/`.-'
	        .--_'(  )'_--.
	       / /` /`""`\ `\ \
	        |  |  ><  \  \
	        \  \      /  /
	            '.__.'
	*/

	// --------------------------------------------------------------------

	/**
	  *  Pages Configuration Screen
	  */
	function configuration()
	{
	    $this->EE->load->model('pages_model');
	    
		if ( ! $this->EE->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}		
        
        $this->EE->load->library('table');

		$vars['cp_page_title'] = $this->EE->lang->line('pages_configuration');

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages', 
		                              $this->EE->lang->line('pages_module_name'));

		$this->EE->load->helper('form');

		//  Get Channels
        $this->EE->load->model('channel_model');
		$wquery = $this->EE->channel_model->get_channels($this->EE->config->item('site_id'));

        // Get Templates
        $this->EE->load->model('template_model');
        $tquery = $this->EE->template_model->get_templates($this->EE->config->item('site_id'));

		//  Our Configuration Options
		$vars['configuration_fields'] = array('homepage_display'	=>
									  array('type'			=> 'display_pulldown',
									  		'label'			=> $this->EE->lang->line('pages_display_on_homepage'),
									  		'value'			=> ''),
									  
									  'default_channel'		=>
									   array('type' 		=> 'other',
									   		 'label'		=> $this->EE->lang->line('default_for_page_creation'),
									   		 'value'		=> '')								
									);

		foreach($wquery->result_array() as $row)
		{
			$vars['configuration_fields']['template_channel_'.$row['channel_id']] = array('type' => "channel", 'label' => $this->EE->lang->line("default_template").':'.NBS.$row['channel_title'], 'value' => '');
		}

		//  Existing Configuration Data
		$data_query = $this->EE->pages_model->fetch_site_pages_config();

		if ($data_query->num_rows() > 0)
		{
			foreach($data_query->result_array() as $row)
			{
				if (isset($vars['configuration_fields'][$row['configuration_name']]))
				{
					$vars['configuration_fields'][$row['configuration_name']]['value'] = $row['configuration_value'];
				}
			}
		}

		foreach($vars['configuration_fields'] as $field_name => $field_data)
		{
			$vars['configuration_fields'][$field_name]['field_name'] = $field_name;
			if ($field_data['type'] == 'channel')
			{
				$vars['configuration_fields'][$field_name]['options'][0] = $this->EE->lang->line('no_default');
				foreach ($tquery->result_array() as $template)
				{
					$vars['configuration_fields'][$field_name]['options'][$template['template_id']] = $template['group_name'].'/'.$template['template_name'];
				}
			}
			elseif($field_data['type'] == 'display_pulldown')
            {
				$vars['configuration_fields'][$field_name]['options'] = array(
																				'not_nested' => $this->EE->lang->line('not_nested'),
																				'nested' => $this->EE->lang->line('nested')
																			);
            }
			else
			{
				$vars['configuration_fields'][$field_name]['options'][0] = $this->EE->lang->line('no_default');

				foreach ($wquery->result_array() as $row)
				{
					$vars['configuration_fields'][$field_name]['options'][$row['channel_id']] = $row['channel_title'];
				}
			}
		}

		return $this->EE->load->view('configuration', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Save Configuration
	  */
	function save_configuration()
	{
	    $this->EE->load->model('pages_model');
	    
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
		    $this->EE->pages_model->update_pages_configuration($data);
		}

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('configuration_updated'));
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages');
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Confirmation
	  */
	function delete_confirm()
	{
	    $this->EE->load->model('pages_model');
	    
		if ( ! $this->EE->input->post('toggle'))
		{
			return $this->index();
		}

		$this->EE->load->helper('form');

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages', $this->EE->lang->line('pages_module_name'));

		$vars['cp_page_title'] = $this->EE->lang->line('pages_delete_confirm');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}

		$vars['form_hidden']['groups'] = 'n';

		return $this->EE->load->view('delete_confirm', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Pages
	  */
	function delete()
	{
	    $this->EE->load->model('pages_model');
	    
		if ( ! $this->EE->input->post('delete'))
		{
			return $this->index();
		}

		$ids = array();

		foreach ($_POST['delete'] as $key => $val)
		{
			$ids[$val] = $val;
		}

        // Delete Pages & give us the number deleted.
        $delete_pages = $this->EE->pages_model->delete_site_pages($ids);
		
		if ($delete_pages === FALSE)
		{
			return $this->index();
		}
		else
		{
    		$message = ($delete_pages > 1) ? 
    		                $this->EE->lang->line('pages_deleted') : $this->EE->lang->line('page_deleted');

    		$this->EE->session->set_flashdata('message_success', $message);
    	    $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages');
		}
	}
}
// END CLASS

/* End of file mcp.pages.php */
/* Location: ./system/expressionengine/modules/pages/mcp.pages.php */