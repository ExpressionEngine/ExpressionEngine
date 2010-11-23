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

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Publishing Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Content_publish extends CI_Controller {

	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->load->library('api');
		$this->load->model('channel_model');
		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */
	function index()
	{
		// currently simply calls channel_select_list,
		// can be combined into one
		
		// @todo move ajax call from homepage elsewhere?
		// shouldn't need to parse this entire file to get that
	}
	
	// --------------------------------------------------------------------

	/**
	 * Entry Form
	 *
	 * Handles new and existing entries. Self submits to save.
	 *
	 * @access	public
	 * @return	void
	 */
	function entry_form()
	{
		$this->load->library('form_validation');
		
		$entry_id	= $this->input->get_post('entry_id');
		$channel_id	= $this->input->get_post('channel_id');
		
		// $channel_id = $this->_member_can_publish($channel_id, $entry_id);
		
		if ( ! $channel_id)
		{
			show_error(lang('unauthorized_access'));
		}
		
		// check_permissions();
		
		// Get channel data
		$channel_data	= $this->_load_channel_data($channel_id);
		$this->_set_field_settings($channel_data);
		
		$this->_set_field_validation($channel_data);
		
		// @todo setup validation for categories, etc?
		// @todo third party tabs
		
		if ($this->form_validation->run() === TRUE)
		{
			// merge post and row data
			// save
			// redirect to view page
			exit('saved');
		}
		
		
		// if ($autosaved)
		// {
		// 	$row_data = $this->_get_row_data_autosave($entry_id);
		// }
		// else if ($entry_id)
		// {
		// 	$revision = $this->input->get_post('revision');
		// 	$row_data = $this->_get_row_data($entry_id, $revision);
		// }
		
		/*
		
		prep_field_output();
		
		setup_layout();
		
		setup_view_vars();
		setup_javascript_vars();
		
		show_form();
		*/
		
		$this->load->view('content/publish'); //, $data);
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Autosave
	 *
	 * @access	public
	 * @return	void
	 */
	function autosave()
	{
		/*
		check_permissions();
		
		load_channel_data();	// @todo consider revisions?
		set_field_settings();	// @todo consider third party tabs
		
		save();
		*/
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Save Layout
	 *
	 * @access	public
	 * @return	void
	 */
	function save_layout()
	{
		// self explanatory - works ok
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * View Entry
	 *
	 * @access	public
	 * @return	void
	 */
	function view_entry()
	{
		
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Filemanager Endpoint
	 *
	 * Handles all file actions.
	 *
	 * @access	public
	 * @return	void
	 */
	function filemanager_actions()
	{
		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Ajax Update Categories
	 *
	 * @access	public
	 * @return	void
	 */
	function category_actions()
	{
		
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Spellcheck
	 *
	 * @access	public
	 * @return	void
	 */
	function spellcheck_actions()
	{
		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Load channel data
	 *
	 * @access	private
	 * @return	void
	 */
	private function _load_channel_data($channel_id)
	{
		$this->load->model('channel_model');
		
		$query = $this->channel_model->get_channel_info($channel_id);
		
		if ($query->num_rows() == 0)
		{
			show_error(lang('no_channel_exists'));
		}

		return $query->result_array();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Setup channel field settings
	 *
	 * @access	private
	 * @return	void
	 */
	private function _set_field_settings($channel_data)
	{
		$this->api->instantiate('channel_fields');
		
		// Get Channel fields in the field group
		$channel_fields = $this->channel_model->get_channel_fields($channel_data[0]['field_group']);

		foreach ($channel_fields->result_array() as $row)
		{
			$this->api_channel_fields->set_settings($row['field_id'], $row);
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Setup channel field validation
	 *
	 * @access	private
	 * @return	void
	 */
	private function _set_field_validation($channel_data)
	{
		var_dump($this->api_channel_fields);exit;
		
		foreach($channel_data as $data)
		{
			// $this->api_channel_fields->get_settings($data)
		}
		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Member has access
	 *
	 * @access	private
	 * @return	void
	 */
	private function _member_can_publish($channel_id, $entry_id)
	{
		$assigned_channels = $this->functions->fetch_assigned_channels();
	}
}