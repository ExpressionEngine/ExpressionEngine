<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
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

	protected $_module_base_url;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->helper('form');
		
		$this->_module_base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blogger_api';
		
		$this->EE->cp->set_right_nav(array(
			'blogger_create_new' => $this->_module_base_url.AMP.'method=create_modify',
		));
	}

	// ------------------------------------------------------------------------

	/**
	 *  Control Panel index
	 */
	public function index()
	{
		$this->EE->load->library(array('table', 'javascript'));
		$this->EE->load->model('blogger_api_model');
		$this->EE->cp->add_js_script(array('fp_module' => 'blogger_api'));
		
		$vars = array(
			'cp_page_title'		=> lang('blogger_api_module_name'),
			'blogger_prefs'		=> array()
		);
		
		$api_url = $this->EE->functions->fetch_site_index(0,0).QUERY_MARKER.'ACT='.$this->EE->cp->fetch_action_id('Blogger_api', 'incoming');
		
		$query = $this->EE->blogger_api_model->get_blogger_prefs();
		
		foreach($query->result() as $row)
		{
			$vars['blogger_prefs'][$row->blogger_id] = array(
					'id'		=> $row->blogger_id,
					'name'		=> $row->blogger_pref_name,
					'url'		=> $api_url.'&id='.$row->blogger_id,
					'toggle'	=> array(
						'name'		=> 'toggle[]',
						'id'		=> "module_{$row->blogger_id}",
						'value'		=> $row->blogger_id,
						'class'		=> 'toggle' 
					)
			);
		}
		
		return $this->EE->load->view('index', $vars, TRUE);
	}

	// ------------------------------------------------------------------------

	/**
	 * Create or Modify a blogger API configuration
	 *
	 * Fetch configuration data depending on if this is a new post or not 
	 *
	 * @return void
	 */
	public function create_modify($id = 0)
	{

		$id = ( ! $this->EE->input->get('id')) ? 0 : (int) $this->EE->input->get('id');			

		$this->EE->load->model(array('blogger_api_model', 'channel_model'));

		$vars = array(
			'cp_page_title'	=> ($id) ? lang('modify_config') : lang('new_config'),
			'field_id'		=> '1:2',
			'pref_name'		=> '',
			'block_entry'	=> 'n',
			'parse_type'	=> 'y',
			'text_format'	=> False,
			'html_format'	=> 'safe',
			'submit_text'	=> ($id) ? 'update' : 'submit',
			'form_hidden'	=> array(
						'id'	=> $id,
			),
		);
		
		if ($id)
		{
			$query = $this->EE->blogger_api_model->get_prefs_by_id($id);
			
			if ($query->num_rows() === 0)
			{
				// Something has gone wrong, or someone is messing with URLs
				// in anycase, error out.
				show_error(lang('not_authorized'));
			}
			
			foreach($query->row_array() as $name => $pref)
			{
				$name	= str_replace('blogger_', '', $name);
				$vars[$name] = $pref;
			}
		}
		
		$allowed_groups	= array();
		$channel_array 	= array();
		$group_array 	= array();
		
		$fields = array('channel_id', 'field_group', 'channel_title');
		
		$query = $this->EE->channel_model->get_channels('all', $fields);
		
		if ($query && $query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				if ($row->field_group != NULL)
				{
					$channel_array[$row->channel_id] = 
								array($row->field_group, $row->channel_title);
				}
			}
		}
		
		$field_array = array();
		
		$query = $this->EE->blogger_api_model->get_channel_fields();
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$field_array[] = array(
										'field_id' 		=> $row->field_id,
										'group_id' 		=> $row->group_id,
										'field_name'	=> $row->field_name,
									);
			}
		}

		$channel_fields = array();
		
		foreach ($channel_array as $channel_id => $meta_channel)
		{
			for ($i = 1; $i < count($field_array); $i++)
			{
				 if ($field_array[$i]['group_id'] == $meta_channel[0])
				 {
				 	$channel_fields[$channel_id.':'.$field_array[$i]['field_id']] = 
											$meta_channel[1].':'.$field_array[$i]['field_name'];
				 }
			}
		}
		
		$v = array(
			'field_id_options'		=> $channel_fields,
			'field_id_selected'		=> $vars['field_id'],
			'block_entry_options'	=> array(
										'y'	=> lang('yes'),
										'n'	=> lang('no')
									),
			'block_entry_selected'	=> ($vars['block_entry'] == 'n') ? 'n' : 'y',
			'parse_type_options'	=> array(
										'y'	=> lang('yes'),
										'n'	=> lang('no')
									),
			'parse_type_selected'	=> ($vars['parse_type'] == 'n') ? 'n' : 'y',
			'text_format_options'	=> array(
										'y'	=> lang('yes'),
										'n'	=> lang('no')
									),
			'text_format_selected'	=> ($vars['text_format'] == 'n') ? 'n' : 'y',
			'html_format_options'	=> array(
										'none'	=> lang('none'),
										'safe'	=> lang('safe'),
										'all'	=> lang('all')
									)
		);

		$vars = array_merge($vars, $v);

		$fields	= array(
						'id', 'pref_name', 'field_id', 'block_entry', 
						'parse_type', 'text_format', 'html_format'
					);

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
	 *
	 * This function sets up form validation rules and checks them.  If
	 * the POST data passes form_validation, send the data to the model for
	 * saving.  Otherwise, display the form & validation errors.
	 *
	 * @return void
	 */
	function save()
	{
		$this->EE->load->library('form_validation');
		$this->EE->load->model('blogger_api_model');
		
		$data = array();

		$this->EE->form_validation->set_rules(
											'id',
											'lang:blogger_id',
											'required'
										)
								  ->set_rules(
											'pref_name',
											'lang:blogger_pref_name',
											'required'
										)		
								  ->set_rules(
											'field_id',
											'lang:blogger_default_field',
											'required'
										)
								  ->set_rules(
											'block_entry',
											'lang:blogger_block_entry',
											'required'
										)
								  ->set_rules(
											'parse_type',
											'lang:blogger_parse_type',
											'required'
										)
								  ->set_rules(
											'text_format',
											'lang:blogger_text_format',
											'required'
										)
								  ->set_rules(
											'text_format',
											'lang:blogger_text_format',
											'required'
										)
								  ->set_error_delimiters(
											'<br /><span class="notice">', 
											'</span>'
									);

		if ( ! $this->EE->form_validation->run())
		{
			$id = $this->EE->input->get_post('id');
			$id = ($id !== 0 & $id != '') ? $id : FALSE;
		
			return $this->create_modify($id);
		}

		$required = array(
						'id', 'pref_name', 'field_id', 'block_entry', 
						'parse_type', 'text_format', 'html_format'
					);

		foreach($required as $var)
		{
			$data['blogger_'.$var] = $this->EE->input->post($var);
		}

		$save = $this->EE->blogger_api_model->save_configuration($data);

		$this->EE->session->set_flashdata('message_success', $save['message']);
		$this->EE->functions->redirect($this->_module_base_url.AMP.'method=create_modify'.AMP.'id='.$save['id']);
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete Confirm
	 *
	 * The first page the user is redirected to after choosing to 'delete' a
	 * blogger api configuration.
	 *
	 *
	 * @return void
	 */
	public function delete_confirm()
	{
		if ( ! $this->EE->input->post('toggle'))
		{
			$this->EE->functions->redirect($this->_module_base_url);
		}

		$vars['cp_page_title'] = lang('blogger_delete_confirm');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}

		return $this->EE->load->view('delete_confirm', $vars, TRUE);
	}


	// ------------------------------------------------------------------------

	/**
	 * Delete Configurations
	 *
	 * This method handles deleting a blogger api configuration.  
	 * A _POST variable of `delete` is required to proceed.  If it does not
	 * exist, the user will be redirected back to the module control panel
	 * home page.
	 *
	 * @return void
	 */
	public function delete()
	{
		if ( ! $config = $this->EE->input->post('delete'))
		{
			$this->EE->functions->redirect($this->_module_base_url);
		}

		$this->EE->load->model('blogger_api_model');

		$message = $this->EE->blogger_api_model->delete_configuration($config);

		$this->EE->session->set_flashdata('message_success', $message);
		$this->EE->functions->redirect($this->_module_base_url);
	}
}

/* End of file mcp.blogger_api.php */
/* Location: ./system/expressionengine/modules/blogger_api/mcp.blogger_api.php */