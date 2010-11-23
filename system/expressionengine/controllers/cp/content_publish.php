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
		
		$entry_id	= (int) $this->input->get_post('entry_id');
		$channel_id	= $this->input->get_post('channel_id');


		// Grab the channel_id associated with this entry if
		// required and make sure the current member has access.
		$channel_id = $this->_member_can_publish($channel_id, $entry_id);

		
		// If they're loading a revision, we stop here
		$this->_check_revisions($entry_id);

		
		// Get channel data
		$channel_data	= $this->_load_channel_data($channel_id);
		$field_data		= $this->_set_field_settings($channel_data);
		
		
		$this->_set_field_validation($field_data, $channel_data);
		
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
		
		$this->load->view('content/publish'), $data);
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
		
		$row = $query->row_array();
		
		/* -------------------------------------------
		/* 'publish_form_channel_preferences' hook.
		/*  - Modify channel preferences
		/*  - Added: 1.4.1
		*/
			if ($this->extensions->active_hook('publish_form_channel_preferences') === TRUE)
			{
				$row = $this->extensions->call('publish_form_channel_preferences', $query->row_array());
			}
		/*
		/* -------------------------------------------*/

		return $row;
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
		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Setup channel field validation
	 *
	 * @access	private
	 * @return	void
	 */
	private function _set_field_validation($field_id, $field_label)
	{
		
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
		$this->load->model('channel_entries_model');
		
		$autosave			= ($this->input->get_post('use_autosave') == 'y') ? TRUE : FALSE;
		$assigned_channels	= $this->functions->fetch_assigned_channels();
		
		
		// A given entry id is either a real channel entry id
		// or the unique id for an autosave row.
		
		if ($entry_id)
		{
			$query = $this->channel_entries_model->get_entry($entry_id, '', $autosave);
			
			if ( ! $query->num_rows())
			{
				show_error(lang('unauthorized_access'));
			}
			
			$channel_id = $query->row('channel_id');
			$author_id = $query->row('author_id');
			
			// Different author? No thanks.
			if ($author_id != $this->session->userdata('member_id'))
			{
				if ( ! $this->cp->allowed_group('can_edit_other_entries'))
				{
					show_error(lang('unauthorized_access'));
				}
			}
		}
		
		
		// Do some autodiscovery on the channel id if it wasn't
		// given. We can cleverly redirect them, or - if they only
		// have one channel - we can choose for them.
		
		if ( ! $channel_id)
		{
			if ( ! count($assigned_channels))
			{
				show_error(lang('unauthorized_access'));
			}
			
			if (count($assigned_channels) > 1)
			{
				// go to the channel select list
				$this->functions->redirect('C=content_publish');
			}

			$channel_id = $assigned_channels[0];
		}
		
		
		// After all that mucking around, double check to make
		// sure the channel is actually one they can post to.
		
		$channel_id = (int) $channel_id;
		
		if ( ! $channel_id OR ! in_array($channel_id, $assigned_channels))
		{
			show_error(lang('unauthorized_access'));
		}
		
		return $channel_id;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Member has access
	 *
	 * @access	private
	 * @return	void
	 */
	private function _check_revisions($entry_id)
	{
		
	}
}