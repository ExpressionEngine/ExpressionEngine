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

	protected $_channel_fields = array();
	private $channel_data = array();
	protected $_dst_enabled = FALSE;

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
		
		$entry_id	= (int) $this->input->get_post('entry_id');
		$channel_id	= (int) $this->input->get_post('channel_id');
		
		$autosave	= ($this->input->get_post('use_autosave') == 'y');


		// Grab the channel_id associated with this entry if
		// required and make sure the current member has access.
		$channel_id = $this->_member_can_publish($channel_id, $entry_id, $autosave);
		
		
		// If they're loading a revision, we stop here
		$this->_check_revisions($entry_id);
		
		
		// Get channel data
		$this->channel_data	= $this->_load_channel_data($channel_id);
		
		$field_data			= $this->_set_field_settings($this->channel_data);
		$entry_data			= $this->_load_entry_data($channel_id, $entry_id, $autosave);
		$entry_id			= $entry_data['entry_id'];
		
		$deft_field_data 	= $this->_setup_default_fields($this->channel_data, $entry_data);

		$field_data = array_merge($field_data, $deft_field_data);

		var_dump($field_data);

		$this->_set_field_validation($this->channel_data, $field_data);
		
		// @todo setup validation for categories, etc?
		// @todo third party tabs
		
		if ($this->form_validation->run() === TRUE)
		{
			// @todo if autosave is set to yes we
			// have the entry id wrong. This should
			// of course never happen, but double check
			
			if ($this->_save($channel_id, $entry_id) === TRUE)
			{
				exit('saved');
				// @todo redirect to view page
				// pass along filter!
			}

			// @todo Process errors, and proceed with
			// showing the page. These are rather
			// special errors - consider how to
			// best show them . . .
			// $errors = $this->errors

		}
		

		
		echo '<pre>';
		print_r($entry_data);
		echo '</pre>';
		
		
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
		
		$row = $query->row_array();
		
		/* -------------------------------------------
		/* 'publish_form_channel_preferences' hook.
		/*  - Modify channel preferences
		/*  - Added: 1.4.1
		*/
			if ($this->extensions->active_hook('publish_form_channel_preferences') === TRUE)
			{
				$row = $this->extensions->call('publish_form_channel_preferences', $row);
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
		$this->api->instantiate('channel_fields');
		
		// Get Channel fields in the field group
		$channel_fields = $this->channel_model->get_channel_fields($channel_data['field_group']);

		$this->_dst_enabled = ($this->session->userdata('daylight_savings') == 'y' ? TRUE : FALSE);

		$field_settings = array();

		foreach ($channel_fields->result_array() as $row)
		{
			$field_fmt 		= '';
			$field_dt 		= '';
			$field_data		= '';
			$dst_enabled	= '';
			
			$settings = array(
				'field_instructions'	=> trim($row['field_instructions']),
				'field_text_direction'	=> ($row['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr',
				'field_fmt'				=> $field_fmt,
				'field_dt'				=> $field_dt,
				'field_data'			=> $field_data,
				'field_name'			=> 'field_id_'.$row['field_id'],
				'dst_enabled'			=> $this->_dst_enabled
			);
			
			$ft_settings = array();

			if (isset($row['field_settings']) && strlen($row['field_settings']))
			{
				$ft_settings = unserialize(base64_decode($row['field_settings']));
			}
			
			$settings = array_merge($row, $settings, $ft_settings);
			$this->api_channel_fields->set_settings($row['field_id'], $settings);
			
			$field_settings[] = $settings;
		}
		
		return $field_settings;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Setup channel field validation
	 *
	 * @access	private
	 * @return	void
	 */
	private function _set_field_validation($channel_data, $field_data)
	{
		foreach ($field_data as $fd)
		{
			$rules = 'call_field_validation['.$fd['field_id'].']';
			$this->form_validation->set_rules($fd['field_id'], $fd['field_label'], $rules);
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Member has access
	 *
	 * @access	private
	 * @return	void
	 */
	private function _member_can_publish($channel_id, $entry_id, $autosave)
	{
		$this->load->model('channel_entries_model');
		
		$assigned_channels = $this->functions->fetch_assigned_channels();
		
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
	
	// --------------------------------------------------------------------

	/**
	 * Member has access
	 *
	 * @access	private
	 * @return	void
	 */
	function _load_entry_data($channel_id, $entry_id = FALSE, $autosave = FALSE)
	{
		$result = array(
			'title'		=> $this->channel_data['default_entry_title'],
			'url_title'	=> $this->channel_data['url_title_prefix'],
			'entry_id'	=> 0
		);
		
		if ($entry_id)
		{
			$query = $this->channel_entries_model->get_entry($entry_id, $channel_id, $autosave);
			
			if ( ! $query->num_rows())
			{
				show_error(lang('no_channel_exists'));
			}

			$result = $query->row_array();
			
			if ($autosave)
			{
				$res_entry_data = unserialize($result['entry_data']);

				// overwrite and add to this array with entry_data
				foreach ($res_entry_data as $k => $v)
				{
					$result[$k] = $v;
				}

				// if the autosave was a new entry, kill the entry id
				if ($result['original_entry_id'] == 0)
				{
					$result['entry_id'] = 0;
				}

				unset($result['entry_data']);
				unset($result['original_entry_id']);
			}
		}
		
		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Member has access
	 *
	 * @access	private
	 * @return	void
	 */
	private function _save($channel_id, $entry_id = FALSE)
	{
		// Editing a non-existant entry?
		if ($entry_id && ! $this->api_channel_entries->entry_exists($entry_id))
		{
			return FALSE;
		}
		
		
		// We need these later
		$return_url = $this->input->post('return_url');
		$return_url = $return_url ? $return_url : '';
		
		$filter = $this->input->post('filter');
		$filter = $filter ? AMP.'filter='.$filter : '';
		
		
		// Copy over new author id, save revision data,
		// and enabled comment status switching (cp_call)
		$data = $_POST;
		$data['cp_call']		= TRUE;
		$data['author_id']		= $this->input->post('author');		// @todo double check if this is validated
		$data['revision_post']	= $_POST;							// @todo only if revisions - memory
		$data['ping_servers']	= array();
		
		
		// Fetch xml-rpc ping server IDs
		if (isset($_POST['ping']) && is_array($_POST['ping']))
		{
			$data['ping_servers'] = $_POST['ping'];
		}
		
		
		// Remove leftovers
		unset($data['ping']);
		unset($data['author']);
		unset($data['filter']);
		unset($data['return_url']);
		
		
		// New entry or saving an existing one?
		if ($entry_id)
		{
			$type		= '';
			$page_title	= 'entry_has_been_updated';
			$success	= $this->api_channel_entries->update_entry($entry_id, $data);
		}
		else
		{
			$type		= 'new';
			$page_title	= 'entry_has_been_added';
			$success	= $this->api_channel_entries->submit_new_entry($_POST['channel_id'], $data);
		}
		
		
		// Do we have a reason to quit?
		if ($this->extensions->end_script === TRUE)
		{
			return TRUE;
		}
		
		
		// I want this to be above the extension check, but
		// 1.x didn't do that, so we'll be blissfully ignorant
		// that something went totally wrong.
		
		if ( ! $success)
		{
			// @todo consider returning false or an array?
			return implode('<br />', $this->api_channel_entries->errors);
		}
		
		
		// Ok, we've succesfully submitted, but a few more things need doing
		
		$entry_id	= $this->api_channel_entries->entry_id;
		$channel_id	= $this->api_channel_entries->channel_id;
		
		$edit_url = BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id.$filter;
		$view_url = BASE.AMP.'C=content_publish'.AMP.'M=view_entry'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id.$filter;
		
		
		// Saved a revision - carry on editing
		if ($this->input->post('save_revision'))
		{
			$this->functions->redirect($edit_url.AMP.'revision=saved');
		}

		
		// Trigger the submit new entry redirect hook
		$view_url = $this->api_channel_entries->trigger_hook('entry_submission_redirect', $view_url);
		
		// have to check this manually since trigger_hook() is returning $view_url
		if ($this->extensions->end_script === TRUE)
		{
			return TRUE;
		}
		
		
		// Check for ping errors
		if ($ping_errors = $this->api_channel_entries->get_errors('pings'))
		{
			$entry_link = $view_url;
			$data = compact('ping_errors', 'channel_id', 'entry_id', 'entry_link');
			
			$this->cp->set_variable('cp_page_title', lang('xmlrpc_ping_errors'));
			$this->load->view('content/ping_errors', $vars);
			return TRUE;	// tricking it into not publish again
		}
		

		// Trigger the entry submission absolute end hook
		if ($this->api_channel_entries->trigger_hook('entry_submission_absolute_end', $view_url) === TRUE)
		{
			return TRUE;
		}

		// Redirect to ths "success" page
		$this->session->set_flashdata('message_success', lang($page_title));
		$this->functions->redirect($view_url);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Setup Default Fields
	 *
	 * This method sets up Default fields that are required on the entry page.
	 *
	 * @todo 	Make field_text_directions configurable
	 * @return 	array
	 */
	private function _setup_default_fields($channel_data, $entry_data)
	{
		// 'entry_date', 'expiration_date', 'comment_expiration_date', 'categories', 'pings', 'revisions', 'pages', all forum tab fields, all options tab fields
		
		$deft_fields = array(
			'title' 		=> array(
				'field_id'				=> 'title',
				'field_label'			=> lang('title'),
				'field_required'		=> 'y',
				'field_data'			=> ( ! $this->input->post('title')) ? $entry_data['title'] : $this->input->post('title'),
				'field_show_fmt'		=> 'n',
				'field_text_direction'	=> 'ltr',
				'field_type'			=> 'text',
				'field_maxl'			=> 100
			),
			'url_title'		=> array(
				'field_id'				=> 'url_title',
				'field_label'			=> lang('url_title'),
				'field_required'		=> 'n',
				'field_data'			=> ($this->input->post('url_title') == '') ? $entry_data['url_title'] : $this->input->post('url_title'),
				'field_fmt'				=> 'xhtml',
				'field_instructions'	=> '',
				'field_show_fmt'		=> 'n',
				'field_text_direction'	=> 'ltr',
				'field_type'			=> 'text',
				'field_maxl'			=> 75
			),
			'entry_date'	=> array(
				'field_id'				=> 'entry_date',
				'field_label'			=> lang('entry_date'),
				'field_required'		=> 'n',
				'field_type'			=> 'date',
				'field_text_direction'	=> 'ltr',
				'field_data'			=> $entry_data['entry_date'],
				'field_fmt'				=> 'text',
				'field_instructions'	=> '',
				'field_show_fmt'		=> 'n',
				'default_offset'		=> 0,
				'selected'				=> 'y',
				'dst_enabled'			=> $this->_dst_enabled				
			),
			'expiration_date' => array(
				'field_id'				=> 'expiration_date',
				'field_label'			=> lang('expiration_date'),
				'field_required'		=> 'n',
				'field_type'			=> 'date',
				'field_text_direction'	=> 'ltr',
				'field_data'			=> $entry_data['expiration_date'],
				'field_fmt'				=> 'text',
				'field_instructions'	=> '',
				'field_show_fmt'		=> 'n',
				'selected'				=> 'y',
				'dst_enabled'			=> $this->_dst_enabled				
			),
		);
		
		foreach ($deft_fields as $field_name => $f_data)
		{
			$this->api_channel_fields->set_settings($field_name, $f_data);
			
			$rules = 'required|call_field_validation['.$f_data['field_id'].']';
			$this->form_validation->set_rules($f_data['field_id'], $f_data['field_label'], $rules);
		}
		
		return $deft_fields;
	}

	// --------------------------------------------------------------------
	
}