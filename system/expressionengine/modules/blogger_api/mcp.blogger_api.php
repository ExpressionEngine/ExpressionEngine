<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2009, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Blogger API Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Blogger_api_mcp {

	/**
	  * Constructor
	  */
	function Blogger_api_mcp( $switch = TRUE )
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		$this->EE->load->helper('form');

		$this->EE->cp->set_right_nav(array(
					'blogger_create_new' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blogger_api'.AMP.'method=create', 
				));		
	}

	/**
	  *  Control Panel index
	  */
	function index()
	{
		$this->EE->load->library('table');
		$this->EE->load->library('javascript');

		$this->EE->cp->add_js_script(array('plugin' => 'tablesorter'));
		$this->EE->jquery->tablesorter('.mainTable', '{
			headers: {2: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->EE->javascript->output(array(
				'$(".toggle_all").toggle(
					function(){
						$("input[class=toggle]").each(function() {
							this.checked = true;
						});
					}, function (){
						var checked_status = this.checked;
						$("input[class=toggle]").each(function() {
							this.checked = false;
						});
					}
				);'
			)
		);

		$vars['cp_page_title'] = $this->EE->lang->line('blogger_api_module_name');

		$api_url = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->cp->fetch_action_id('Blogger_api', 'incoming');

		$this->EE->db->select('blogger_pref_name, blogger_id');
		$query = $this->EE->db->get('blogger');

		$this->EE->javascript->compile();
		
		$vars['blogger_prefs'] = array();
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->load->view('index', $vars, TRUE);
		}

		foreach ($query->result() as $row)
		{

			$vars['blogger_prefs'][$row->blogger_id]['id'] = $row->blogger_id;
			$vars['blogger_prefs'][$row->blogger_id]['name'] = $row->blogger_pref_name;
			$vars['blogger_prefs'][$row->blogger_id]['url'] = $api_url.'&id='.$row->blogger_id;
			$vars['blogger_prefs'][$row->blogger_id]['toggle'] = array(
																			'name'		=> 'toggle[]',
																			'id'		=> 'module_'.$row->blogger_id,
																			'value'		=> $row->blogger_id,
																			'class'		=>'toggle'
			    														);
		}

		return $this->EE->load->view('index', $vars, TRUE);
	}

	// ------------------------------------------------------------------------

	/**
	  *  Create
	  */
	function create()
	{
		return $this->modify('new');
	}

	// ------------------------------------------------------------------------

	/**
	  *  Modify Configuration
	  */
	function modify($id = '')
	{
		$id = ( ! $this->EE->input->get('id')) ? $id : $this->EE->input->get_post('id');

		if ($id == '')
		{
			return $this->index();
		}

		//  Form Values
		$vars['field_id']		= '1:2';
		$vars['pref_name']		= '';
		$vars['block_entry']	= 'n';
		$vars['parse_type']		= 'y';
		$vars['text_format']	= 'false';
		$vars['html_format']	= 'safe';
		$vars['submit_text']	= 'submit';
		
		if ($id != 'new')
		{
			$vars['submit_text']	= 'update';

			$query = $this->EE->db->get_where('blogger', array('blogger_id' => $id));

			if ($query->num_rows() == 0)
			{
				return $this->index();
			}

			foreach($query->row_array() as $name => $pref)
			{
				$name	= str_replace('blogger_', '', $name);
				$vars["$name"] = $pref;
			}
		}

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blogger_api', $this->EE->lang->line('blogger_api_module_name'));

		$vars['cp_page_title'] = ($id == 'new') ? $this->EE->lang->line('new_config') : $this->EE->lang->line('modify_config');

		$vars['form_hidden']['id'] =$id;

		// Fetch Channels
		$channel_array = array();

		$this->EE->db->select('channel_id, field_group, channel_title');
		$this->EE->db->order_by('channel_title');
		$query = $this->EE->db->get('channels');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$channel_array[$row['channel_id']] = array($row['field_group'], $row['channel_title']);
			}
		}

		// Fetch Fields
		$field_array = array();

		$this->EE->db->select('field_id, group_id, field_name');
		$this->EE->db->order_by('field_name');
		$query = $this->EE->db->get_where('channel_fields', array('field_type' => 'textarea'));

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$field_array[] = array($row['field_id'], $row['group_id'], $row['field_name']);
			}
		}

		// Fields to Channels
		$channel_fields = array();

		foreach($channel_array as $channel_id => $meta_channel)
		{
			for($i = 0; $i < count($field_array); $i++)
			{
				if ($field_array[$i]['1'] == $meta_channel['0'])
				{
					$channel_fields[$channel_id][] = array($field_array[$i]['0'], $field_array[$i]['2']);
				}
			}
		}

		$x = explode(':',$vars['field_id']);
		$channel_match = ( ! isset($x['1'])) ? '1' : $x['0'];
		$field_match  = ( ! isset($x['1'])) ? $x['0'] : $x['1'];

		$vars['field_id_options'] = array();
		$vars['field_id_selected'] = $vars['field_id'];

		$t = 1;

		foreach($channel_fields as $channel_id => $field_data)
		{
			$t++;

			for($i = 0; $i < count($field_data); $i++)
			{
				$selected = ($channel_id == $channel_match && $field_data[$i]['0'] == $field_match) ? 'y' : '';
				$vars['field_id_options'][$channel_id.':'.$field_data[$i]['0']] = $channel_array[$channel_id]['1'].' : '.$field_data[$i]['1'];
			}

			if ($t <= count($channel_fields))
			{
				$vars['field_id_options'][$t] = NBS.'----------'.NBS;
			}
		}

		$vars['block_entry_options'] = array(
												'y'=>$this->EE->lang->line('yes'),
												'n'=>$this->EE->lang->line('no')
											);
		$vars['block_entry_selected'] = ($vars['block_entry'] == 'n') ? 'n' : 'y';

		$vars['parse_type_options'] = array(
												'y'=>$this->EE->lang->line('yes'),
												'n'=>$this->EE->lang->line('no')
										);
		$vars['parse_type_selected'] = ($vars['parse_type'] == 'n') ? 'n' : 'y';

		$vars['text_format_options'] = array(
												'y'=>$this->EE->lang->line('yes'),
												'n'=>$this->EE->lang->line('no')
										);
		$vars['text_format_selected'] = ($vars['text_format'] == 'n') ? 'n' : 'y';

		$vars['html_format_options'] = array(
												'none'=>$this->EE->lang->line('none'),
												'safe'=>$this->EE->lang->line('safe'),
												'all'=>$this->EE->lang->line('all')
											);

		$fields	= array('id', 'pref_name', 'field_id', 'block_entry', 'parse_type', 'text_format', 'html_format');
		
		foreach ($fields as $val)
		{
			if ($this->EE->input->post($val))
			{
				$vars[$val] = $this->EE->input->post($val);
			}
		}

		return $this->EE->load->view('create_modify', $vars, TRUE);
	}

	// ------------------------------------------------------------------------

	/**
	  * Save Configuration
	  */
	function save()
	{
		$this->EE->load->library('form_validation');
		$data		= array();

		$this->EE->form_validation->set_rules('id',				'lang:blogger_id',			'required');
		$this->EE->form_validation->set_rules('pref_name',		'lang:blogger_pref_name',	'required');		
		$this->EE->form_validation->set_rules('field_id',		'lang:blogger_default_field',	'required');
		$this->EE->form_validation->set_rules('block_entry',	'lang:blogger_block_entry',	'required');
		$this->EE->form_validation->set_rules('parse_type',		'lang:blogger_parse_type',	'required');
		$this->EE->form_validation->set_rules('text_format',	'lang:blogger_text_format',	'required');
		$this->EE->form_validation->set_rules('text_format',	'lang:blogger_text_format',	'required');										

		$this->EE->form_validation->set_error_delimiters('<br /><span class="notice">', '</span>');

		if ($this->EE->form_validation->run() === FALSE)
		{
			$new = ($this->EE->input->get_post('id') == 'new') ? $this->EE->input->get_post('id') : '';
			return $this->modify($new);
		}

		$required = array('id', 'pref_name', 'field_id', 'block_entry', 'parse_type', 'text_format', 'html_format');
		
		foreach($required as $var)
		{
			$data['blogger_'.$var] = $_POST[$var];
		}
		
		if ($_POST['id'] == 'new' )
		{
			unset($data['blogger_id']);

			$this->EE->db->insert('blogger', $data);
			$id = $this->EE->db->insert_id();
			$message = $this->EE->lang->line('configuration_created');
		}
		else
		{
			$this->EE->db->where('blogger_id', $_POST['id']);
			$this->EE->db->update('blogger', $data);
			
			$id = $_POST['id'];
			$message = $this->EE->lang->line('configuration_updated');
		}

		$this->EE->session->set_flashdata('message_success', $message);
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blogger_api'.AMP
		.'method=modify'.AMP.'id='.$id);
	}

	// ------------------------------------------------------------------------

	/**
	  * Delete Confirm
	  */
	function delete_confirm()
	{
		if ( ! $this->EE->input->post('toggle'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blogger_api');
		}

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blogger_api', $this->EE->lang->line('blogger_api_module_name'));

		$vars['cp_page_title'] = $this->EE->lang->line('blogger_delete_confirm');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}

		return $this->EE->load->view('delete_confirm', $vars, TRUE);
	}

	// ------------------------------------------------------------------------

	/**
	  * Delete Configurations
	  */
	function delete()
	{
		if ( ! $this->EE->input->post('delete'))
		{
			return $this->index();
		}

		// Need to confirm this will work.
		foreach ($this->EE->input->post('delete') as $item)
		{
			$this->EE->db->or_where('blogger_id', $item);
		}

		$this->EE->db->delete('blogger');

		$message = ($this->EE->db->affected_rows() == 1) ? $this->EE->lang->line('blogger_deleted') : $this->EE->lang->line('bloggers_deleted');

		$this->EE->session->set_flashdata('message_success', $message);
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blogger_api');
	}

}

/* End of file mcp.blogger_api.php */
/* Location: ./system/expressionengine/modules/blogger_api/mcp.blogger_api.php */