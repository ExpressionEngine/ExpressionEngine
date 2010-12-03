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

	private $_dst_enabled 		= FALSE;

	private $_module_tabs		= array();
	private $_channel_data 		= array();
	private $_channel_fields 	= array();
	private $_publish_blocks 	= array();
	private $_publish_layouts 	= array();
	private $_smileys_enabled	= FALSE;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}
		
		$this->load->library('api');
		$this->load->library('spellcheck');
		$this->load->model('channel_model');
		$this->load->helper(array('typography', 'spellcheck'));
		$this->cp->get_installed_modules();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @return	void
	 */
	public function index()
	{
		if ($this->input->get_post('C') == 'content_publish')
		{
			$title = $this->lang->line('publish');
			
			$data = array(
				'instructions'		=> lang('select_channel_to_post_in'),
				'link_location'		=> BASE.AMP.'C=content_publish'.AMP.'M=entry_form'
			);
		}
		else
		{
			$title = $this->lang->line('edit');
			
			$data = array(
				'instructions'		=> lang('select_channel_to_edit'),
				'link_location'		=> BASE.AMP.'C=content_edit'.AMP.'M=edit_entries'
			);
		}
		
		$this->cp->set_variable('cp_page_title', $title);

		$this->load->model('channel_model');
		$channels = $this->channel_model->get_channels();

		$data['channels_exist'] = ($channels !== FALSE AND $channels->num_rows() === 0) ? FALSE : TRUE;
		$data['assigned_channels'] = $this->session->userdata('assigned_channels');

		// Base Url
		$base_url = BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id=';
		
		// If there's only one publishable channel, no point in asking them which one
		// they want. Auto direct them to the publish form for the only channel available.
		if (count($data['assigned_channels']) === 1)
		{
			if (isset($_GET['print_redirect']))
			{
				exit(str_replace(AMP, '&', $base_url.key($data['assigned_channels'])));
			}

			$this->functions->redirect($base_url.key($data['assigned_channels']));
		}

		$this->javascript->compile();
		$this->load->view('content/channel_select_list', $data);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Entry Form
	 *
	 * Handles new and existing entries. Self submits to save.
	 *
	 * @return	void
	 */
	public function entry_form()
	{
		$this->load->library('form_validation');
		
		$entry_id	= (int) $this->input->get_post('entry_id');
		$channel_id	= (int) $this->input->get_post('channel_id');
		
		$autosave	= ($this->input->get_post('use_autosave') == 'y');

		$this->_smileys_enabled = (isset($this->cp->installed_modules['emoticon']) ? TRUE : FALSE);

		if ($this->_smileys_enabled)
		{
			$this->load->helper('smiley');
			$this->cp->add_to_foot(smiley_js());				
		}

		// Grab the channel_id associated with this entry if
		// required and make sure the current member has access.
		$channel_id = $this->_member_can_publish($channel_id, $entry_id, $autosave);
		
		
		// If they're loading a revision, we stop here
		$this->_check_revisions($entry_id);
		
		
		// Get channel data
		$this->_channel_data = $this->_load_channel_data($channel_id);
		
		// Grab, fields and entry data
		$field_data		= $this->_set_field_settings($this->_channel_data);
		$entry_data		= $this->_load_entry_data($channel_id, $entry_id, $autosave);
		$entry_id		= $entry_data['entry_id'];
		
		// Merge in default fields
		$deft_field_data = $this->_setup_default_fields($this->_channel_data, $entry_data);

		$field_data = array_merge($field_data, $deft_field_data);

		$this->_set_field_validation($this->_channel_data, $field_data);
		
		// @todo setup validation for categories, etc?
		// @todo third party tabs
		
		if ($this->form_validation->run() === TRUE)
		{
			// @todo if autosave is set to yes we
			// have the entry id wrong. This should
			// of course never happen, but double check

			if ($this->_save($channel_id, $entry_id) === TRUE)
			{
				// under normal circumstances _save will redirect
				// if we get here, a hook triggered end_script
				return;
			}

			// @todo Process errors, and proceed with
			// showing the page. These are rather
			// special errors - consider how to
			// best show them . . .
			// $errors = $this->errors

		}
// var_dump($this->form_validation->_error_array);

		/*
		
		prep_field_output();
		
		setup_layout();
		
		setup_view_vars();
		setup_javascript_vars();
		
		show_form();
		*/

		// First figure out what tabs to show, and what fields
		// they contain. Then work through the details of how
		// they are show.
	
		$field_data 	= $this->_setup_field_blocks($field_data, $entry_data);
		$tab_hierarchy	= $this->_setup_tab_hierarchy($field_data);
		$layout_styles	= $this->_setup_layout_styles($field_data);
		$field_list		= $this->_sort_field_list($field_data);		// @todo admin only? or use as master list? skip sorting for non admins, but still compile?
		$field_list		= $this->_prep_field_wrapper($field_list);

		$field_output	= $this->_setup_field_display($field_data);
		
		// Start to assemble view data
		// WORK IN PROGRESS, just need a few things on the page to
		// work with the html - will clean this crap up
		
		$this->load->library('filemanager');
		$this->load->helper('snippets');
		
		$this->filemanager->filebrowser('C=content_publish&M=filemanager_actions');
		
		$this->cp->add_js_script(array(
		        'ui'        => array('datepicker', 'resizable', 'draggable', 'droppable'),
		        'plugin'    => array('markitup', 'toolbox.expose', 'overlay'),
				'file'		=> array('json2', 'cp/publish')
		    )
		);
		
		if ($this->session->userdata('group_id') === 1)
		{
			$this->cp->add_js_script(array('file' => 'cp/publish_admin'));			
		}

		$this->javascript->set_global(array(
			'date.format'					=> $this->config->item('time_format'),
			'user.foo'						=> FALSE,
			'publish.markitup.foo'			=> FALSE,
			'publish.smileys'				=> ($this->_smileys_enabled) ? TRUE : FALSE,
			'publish.which'					=> ($entry_id === 0) ? 'new' : 'edit',
			'publish.default_entry_title'	=> $this->_channel_data['default_entry_title'],
			'publish.word_separator'		=> $this->config->item('word_separator'),
			'publish.url_title_prefix'		=> $this->_channel_data['url_title_prefix'],
		));

		// -------------------------------------------
		//	Publish Page Title Focus - makes the title field gain focus when the page is loaded
		//
		//	Hidden Configuration Variable - publish_page_title_focus => Set focus to the tile? (y/n)

		$this->javascript->set_global('publish.title_focus', FALSE);

		if ( ! $entry_id && $this->config->item('publish_page_title_focus') != 'n')
		{
			$this->javascript->set_global('publish.title_focus', TRUE);
		}
		
		// -------------------------------------------
		
		$this->javascript->compile();
		
		$tab_labels = array(
			'publish' 		=> lang('publish'),
			'categories' 	=> lang('categories'),
			'pings'			=> lang('pings'),
			'options'		=> lang('options'),
			'date'			=> lang('date'),
		);

		foreach ($this->_module_tabs as $k => $tab)
		{
			$tab_labels[$k] = lang($k);
		}

		reset($tab_hierarchy);
		
		
		$parts = $_GET;
		unset($parts['S'], $parts['D']);
		$current_url = http_build_query($parts, '', '&amp;');
	
		$data = array(
			'cp_page_title'	=> $entry_id ? lang('edit_entry') : lang('new_entry'),
			'message'		=> '',	// @todo consider pulling?
			
			'tabs'			=> $tab_hierarchy,
			'first_tab'		=> key($tab_hierarchy),
			'tab_labels'	=> $tab_labels,
			'field_list'	=> $field_list,
			'layout_styles'	=> $layout_styles,
			'field_output'	=> $field_output,
			
			'spell_enabled'		=> TRUE,
			'smileys_enabled'	=> $this->_smileys_enabled,
			
			'show_revision_cluster' => FALSE,
			
			'current_url'	=> $current_url,
			'hidden_fields'	=> array(
				'channel_id'	=> $channel_id
			)
		);
		
		$this->cp->set_breadcrumb(
			BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel_id,
			$this->_channel_data['channel_title']
		);
		
		$this->load->view('content/publish', $data);
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Autosave
	 *
	 * @return	void
	 */
	public function autosave()
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
	 * @return	void
	 */
	public function save_layout()
	{
		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if (empty($_POST))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! function_exists('json_decode'))
		{
			$this->load->library('Services_json');
		}

		$this->output->enable_profiler(FALSE);
		$error 				= array();
		$valid_name_error 	= array();

		$member_group 		= $this->input->post('member_group');
		$channel_id 		= $this->input->post('channel_id');
		$json_tab_layout 	= $this->input->post('json_tab_layout');

		$layout_info = json_decode($json_tab_layout, TRUE);
		
		// Check for required fields being hidden
		$required = $this->api_channel_fields->get_required_fields($channel_id);
		
		$clean_layout = array();

		foreach($layout_info as $tab => $field)
		{
			foreach ($field as $name => $info)
			{
				if (count($required) > 0)
				{					
					// Check for hiding a required field
					if (in_array($name, $required) && $info['visible'] === FALSE)
					{
						$error[] = $name;
					}
				}
					
				// Check for hinkiness in field names
				if (preg_match('/[^a-z0-9\_\-]/i', $name))
				{
					$valid_name_error[] = $name;
				}
				elseif (trim($name) == '')
				{
					$valid_name_error[] = 'missing_name';
				}
			}
			
			$clean_layout[strtolower($tab)] = $layout_info[$tab];	
		}
			
		if (count($error) > 0 OR count($valid_name_error) > 0)
		{
			$resp['messageType'] = 'failure';
			$message = lang('layout_failure');
				
			if (count($error))
			{
				$message .= NBS.NBS.lang('layout_failure_required').implode(', ', $error);
			}
				
			if (count($valid_name_error))
			{
				$message .= NBS.NBS.lang('layout_failure_invalid_name').implode(', ', $valid_name_error);
			}
				
			$resp['message'] = $message; 

			$this->output->send_ajax_response($resp);
		}

		// make this into an array, insert_group_layout will serialize and save
		$layout_info = array_map(array($this, '_sort_publish_fields'), $clean_layout);
		
		if ($this->member_model->insert_group_layout($member_group, $channel_id, $layout_info))
		{
			$resp = array(
				'messageType'	=> 'success',
				'message'		=> lang('layout_success')
			);

			$this->output->send_ajax_response($resp);
		}
		else
		{
			$resp = array(
				'messageType'	=> 'failure',
				'message'		=> lang('layout_failure')
			);

			$this->output->send_ajax_response($resp);	
		}
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
		$entry_id	= $this->input->get('entry_id');
		$channel_id	= $this->input->get('channel_id');
		
		if ( ! $channel_id OR ! $entry_id OR ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}

		$assigned_channels = $this->functions->fetch_assigned_channels();

		if ( ! in_array($channel_id, $assigned_channels))
		{
			show_error(lang('unauthorized_for_this_channel'));
		}
		
		$qry = $this->db->select('field_group')
						->where('channel_id', $channel_id)
						->get('channels');
		
		if ( ! $qry->num_rows())
		{
			show_error(lang('unauthorized_access'));
		}
		
		$field_group = $qry->row('field_group');

		$qry = $this->db->select('field_id, field_type')
						->where('group_id', $field_group)
						->where('field_type !=', 'select')
						->order_by('field_order')
						->get('channel_fields');

		$fields = array();

		foreach ($qry->result_array() as $row)
		{
			$fields['field_id_'.$row['field_id']] = $row['field_type'];
		}

		$res = $this->db->from('channel_titles AS ct, channel_data AS cd, channels AS c')
						->select('ct.*, cd.*, c.*')
						->where('ct.entry_id', $entry_id)
						->where('ct.entry_id = cd.entry_id', NULL, FALSE)
						->where('c.channel_id = ct.channel_id', NULL, FALSE)
						->get();
		
		if ( ! $res->num_rows())
		{
			show_error(lang('unauthorized_access'));
		}
		
		
		$show_edit_link = TRUE;
		$show_comments_link = TRUE;
			
		$resrow = $res->row_array();
		
		$comment_perms = array(
			'can_edit_own_comments',
			'can_delete_own_comments',
			'can_moderate_comments'
		);
				
		if ($resrow['author_id'] != $this->session->userdata('member_id'))
		{
			if ( ! $this->cp->allowed_group('can_view_other_entries'))
			{
				show_error(lang('unauthorized_access'));
			}

			if ( ! $this->cp->allowed_group('can_edit_other_entries'))
			{
				$show_edit_link = FALSE;
			}

			$comment_perms = array(
				'can_view_other_comments',
				'can_delete_all_comments',
				'can_moderate_comments'
			);
		}
		
		$comment_perms		= array_map(array($this->cp, 'allowed_group'), $comment_perms);
		$show_comments_link = (bool) count(array_filter($comment_perms)); // false if all perms fail
		
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
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}
		
		$group_id = $this->input->get_post('group_id');
		
		if ( ! $group_id)
		{
			exit(lang('no_categories'));
		}
		
		$this->load->library('api');
		$this->api->instantiate('channel_categories');
		
		$this->load->model('category_model');
		$this->load->helper('form');
		
		$query = $this->category_model->get_categories($group_id, FALSE);
		$this->api_channel_categories->category_tree($group_id, '', $query->row('sort_order'));

		$vars = array(
			'edit_links' => FALSE,
			'categories' => array('' => $this->api_channel_categories->categories)
		);

		exit($this->load->view('content/_assets/categories', $vars, TRUE));
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Spellcheck
	 *
	 * @return	void
	 */
	public function spellcheck_actions()
	{
		if ( ! $this->input->get('action'))
		{
			show_error(lang('unauthorized_access'));
		}
		
		if ( ! class_exists('EE_Spellcheck'))
		{
			require APPPATH.'libraries/Spellcheck'.EXT;
		}
		
		$this->output->enable_profiler(FALSE);

		switch ($this->input->get('action'))
		{
			case 'iframe':
				return EE_Spellcheck::iframe();
			case 'check':
				return EE_Spellcheck::check();
		}
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
			
			$field_data = ($this->input->get_post('field_id_'.$row['field_id'])) ? $this->input->get_post('field_id_'.$row['field_id']) : $field_data;
			
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
			
			$field_settings[$settings['field_name']] = $settings;
		}
		
		return $field_settings;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Setup channel field validation
	 *
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
	 * @return	void
	 */
	private function _check_revisions($entry_id)
	{
		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Member has access
	 *
	 * @return	void
	 */
	function _load_entry_data($channel_id, $entry_id = FALSE, $autosave = FALSE)
	{
		$result = array(
			'title'		=> $this->_channel_data['default_entry_title'],
			'url_title'	=> $this->_channel_data['url_title_prefix'],
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
		$this->api->instantiate('channel_entries');

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
			
			$data['cp_page_title'] = lang('xmlrpc_ping_errors');
			
			$this->load->view('content/ping_errors', $data);
			
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
	 * Create Sidebar field list
	 *
	 * @access	private
	 * @return	void
	 */
	private function _sort_field_list($field_data)
	{
		$sorted = array();
		
		$_required_field_labels = array();
		$_optional_field_labels = array();
		
		foreach($field_data as $name => $field)
		{
			if ($field['field_required'] == 'y')
			{
				$_required_field_labels[$name] = $field['field_label'];
			}
			else
			{
				$_optional_field_labels[$name] = $field['field_label'];
			}
		}
		
		asort($_required_field_labels);
		asort($_optional_field_labels);
		
		foreach(array($_required_field_labels, $_optional_field_labels) as $sidebar_field_groups)
		{
			foreach($sidebar_field_groups as $name => $label)
			{
				// @todo field_data bad key
				$sorted[$name] = $field_data[$name];
			}
		}
		
		return $sorted;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Setup Field Display
	 *
	 * Calls the fieldtype display_field method
	 *
	 * @access	private
	 * @return	void
	 */
	private function _setup_field_display($field_data)
	{
		$field_output = array();
		
		foreach ($field_data as $name => $data)
		{
			if (isset($data['string_override']))
			{
				$field_output[$name] = $data['string_override'];
				continue;
			}
			
			$this->api_channel_fields->setup_handler($data['field_id']);
			
			$field_value = set_value($data['field_id'], $data['field_data']);
			$field_output[$name] = $this->api_channel_fields->apply('display_publish_field', array($field_value));
		}
		
		return $field_output;
			
		// if (isset($field_info['field_required']) && $field_info['field_required'] == 'y')
		// {
		// 	$vars['required_fields'][] = $field_info['field_id'];
		// }
		
		// if ($vars['smileys_enabled'])
		// {
		// 	$image_array = get_clickable_smileys($path = $this->config->slash_item('emoticon_path'), $field_info['field_name']);
		// 	$col_array = $this->table->make_columns($image_array, 8);
		// 	$vars['smiley_table'][$field] = '<div class="smileyContent" style="display: none;">'.$this->table->generate($col_array).'</div>';
		// 	$this->table->clear(); // clear out tables for the next smiley					
		// }
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Setup Field Wrapper Stuff
	 *
	 * Sets up smileys, spellcheck, glossary, etc
	 *
	 * @access	private
	 * @return	void
	 */
	private function _prep_field_wrapper($field_list)
	{
		$defaults = array(
			'field_show_spellcheck'			=> 'n',
			'field_show_smileys'			=> 'n',
			'field_show_glossary'			=> 'n',
			'field_show_formatting_btns'	=> 'n',
			'field_show_writemode'			=> 'n',
			'field_show_file_selector'		=> 'n',
			'field_show_fmt'				=> 'n'
		);
	
		foreach ($field_list as $field => &$data)
		{
			$data['has_extras'] = FALSE;
			
			foreach($defaults as $key => $val)
			{
				if (isset($data[$key]) && $data[$key] == 'y')
				{
					$data['has_extras'] = TRUE;
					continue;
				}

				$data[$key] = $val;
			}
			
			if ($data['field_show_smileys'] == 'y' && $this->_smileys_enabled === TRUE)
			{
				$data['smiley_table'] = $this->_build_smiley_table($field);
			}
		}

		return $field_list;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Setup Layout Styles for all fields
	 *
	 * @access	private
	 * @return	void
	 */
	private function _setup_layout_styles($field_data)
	{
		$field_display = array(
			'visible'		=> TRUE,
			'collapse'		=> FALSE,
			'html_buttons'	=> TRUE,
			'is_hidden'		=> FALSE,
			'width'			=> '100%'
		);
		
		$layout = array();

		foreach($field_data as $name => $field)
		{
			$layout[$name] = $field_display;
		}
		
		return $layout;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Setup Tab Hierarchy
	 *
	 * @access	private
	 * @return	void
	 */
	private function _setup_tab_hierarchy($field_data)
	{
		$default = array(
			'publish'		=> array('title', 'url_title'),
			'date'			=> array('entry_date', 'expiration_date', 'comment_expiration_date'),
			'categories'	=> array('category'),
			'options'		=> array('channel', 'status', 'author', 'options', 'ping'),
		);
		
		if (isset($this->cp->installed_modules['forum']))
		{
			$default['forum'] = array('forum_id', 'forum_title', 'forum_body', 'forum_topic_id');
		}
		
		if (isset($this->cp->installed_modules['pages']))
		{
			$default['pages'] = array('pages_uri', 'pages_template_id');
		}

		$default = array_merge($default, $this->_third_party_tabs());

		// Add predefined fields to their specific tabs
		foreach ($default as $tab => $fields)
		{
			foreach ($fields as $i => $field_name)
			{
				if (isset($field_data[$field_name]))
				{
					unset($field_data[$field_name]);
				}
				else
				{
					unset($default[$tab][$i]);
				}
			}
		}
		
		// Add anything else to the publish tab
		foreach ($field_data as $name => $field)
		{
			$default['publish'][] = $name;
		}
		
		return $default;
	}

	// --------------------------------------------------------------------

	/**
	 * Setup Field Blocks
	 *
	 * This function sets up default fields and field blocks
	 *
	 * @param 	array
	 * @param	array
	 * @return 	array
	 */
	private function _setup_field_blocks($field_data, $entry_data)
	{
		$categories 	= $this->_build_categories_block($entry_data);
		$pings 			= $this->_build_ping_block($entry_data['entry_id']);
		$options		= $this->_build_options_block($entry_data);
		$third_party  	= $this->_build_third_party_blocks($entry_data);

		return array_merge(
							$field_data, $categories, $pings, 
							$options, $third_party);
	}

	// --------------------------------------------------------------------

	/**
	 * Categories Block
	 *
	 *
	 */
	private function _build_categories_block($entry_data)
	{
		$default	= array(
			'string_override'		=> lang('no_categories'),
			'field_id'				=> 'category',
			'field_name'			=> 'category',
			'field_label'			=> lang('categories'),
			'field_required'		=> 'n',
			'field_type'			=> 'multiselect',
			'field_text_direction'	=> 'ltr',
			'field_data'			=> '',
			'field_fmt'				=> 'text',
			'field_instructions'	=> '',
			'field_show_fmt'		=> 'n',
			'selected'				=> 'n',
			'options'				=> array()
		);
		
		// No categories? Easy peasy
		if ( ! $this->_channel_data['cat_group'])
		{
			return array('category' => $default);
		}
		
		
		
		$this->api->instantiate('channel_categories');
				
		$catlist	= array();
		$categories	= array();
		
		
		// Figure out selected categories
		if ( ! $entry_data['entry_id'] && $this->_channel_data['deft_category'])
		{
			// new entry and a default exists
			$catlist = $this->_channel_data['deft_category'];
		}
		elseif ( ! isset($entry_data['category']))
		{
			$qry = $this->db->select('c.cat_name, p.*')
							->from('categories AS c, category_posts AS p')
							->where_in('c.group_id', explode('|', $this->_channel_data['cat_group']))
							->where('p.entry_id', $entry_data['entry_id'])
							->where('c.cat_id = p.cat_id', NULL, FALSE)
							->get();

			foreach ($qry->result() as $row)
			{
				$catlist[$row->cat_id] = $row->cat_id;
			}			
		}
		elseif (is_array($entry_data['category']))
		{
			foreach ($entry_data['category'] as $val)
			{
				$catlist[$val] = $val;
			}
		}
		
		
		// Figure out valid category options		
		$this->api_channel_categories->category_tree($this->_channel_data['cat_group'], $catlist);

		if (count($this->api_channel_categories->categories) > 0)
		{  
			// add categories in again, over-ride setting above
			foreach ($this->api_channel_categories->categories as $val)
			{
				$categories[$val['3']][] = $val;
			}
		}
		
		
		// If the user can edit categories, we'll go ahead and
		// show the links to make that work
		$edit_links = FALSE;
		
		if ($this->session->userdata('can_edit_categories') == 'y')
		{
			$link_info = $this->api_channel_categories->fetch_allowed_category_groups($this->_channel_data['cat_group']);

			if (is_array($link_info) && count($link_info))
			{
				$edit_links = array();
				
				foreach ($link_info as $val)
				{
					$edit_links[] = array(
						'url' => BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP.'group_id='.$val['group_id'],
						'group_name' => $val['group_name']
					);
				}
			}
		}


		// Build the mess
		$vars = compact('categories', 'edit_links');

		$default['options']			= $categories;		
		$default['string_override'] = $this->load->view('content/_assets/categories', $vars, TRUE);
		
		return array('category' => $default);
	}

	// --------------------------------------------------------------------

	/**
	 * Ping Block
	 *
	 * Setup block that contains ping servers
	 *
	 * @param 	integer		Entry Id
	 * @return 	array
	 */
	private function _build_ping_block($entry_id) 
	{
		$ping_servers = $this->channel_entries_model->fetch_ping_servers($entry_id);

		$settings = array('ping' => 
			array(
				'string_override'		=> (isset($ping_servers) && $ping_servers != '') ? '<fieldset>'.$ping_servers.'</fieldset>' : lang('no_ping_sites').'<p><a href="'.BASE.AMP.'C=myaccount'.AMP.'M=ping_servers'.AMP.'id='.$this->session->userdata('member_id').'">'.lang('add_ping_sites').'</a></p>',
				'field_id'				=> 'ping',
				'field_label'			=> lang('pings'),
				'field_required'		=> 'n',
				'field_type'			=> 'checkboxes',
				'field_text_direction'	=> 'ltr',
				'field_data'			=> $ping_servers,
				'field_fmt'				=> 'text',
				'field_instructions'	=> '',
				'field_show_fmt'		=> 'n'
			)
		);

		$this->api_channel_fields->set_settings('ping', $settings['ping']);

		return $settings;
	}

	// --------------------------------------------------------------------

	/**
	 * Options Block
	 *
	 * 
	 *
	 */
	private function _build_options_block($entry_data)
	{
		// sticky, comments, dst
		// author, channel, status
		$settings			= array();
		
		$show_comments		= FALSE;
		$show_sticky		= FALSE;
		$show_dst			= FALSE;
		
		$options_array[] = 'sticky';

		// Allow Comments?
		if ( ! isset($this->cp->installed_modules['comment']))
		{
			$allow_comments = (isset($entry_data['allow_comments'])) ? $entry_data['allow_comments'] : 'n';
		}
		elseif ($this->_channel_data['comment_system_enabled'] == 'y')
		{
			$options_array[] = 'allow_comments';
		}

		// Is DST active? 
		if ($this->config->item('honor_entry_dst') == 'y')
		{
			$options_array[] = 'dst_enabled';
		}
			
		// Options Field
		$settings['options'] = array(
			'field_id'				=> 'options',
			'field_required'		=> 'n',
			'field_label'			=> lang('options'),
			'field_data'			=> '',
			'field_instructions'	=> '',
			'field_pre_populate'	=> 'n',
			'field_type'			=> 'checkboxes',
			'field_list_items'		=> $options_array,
		);

		$this->api_channel_fields->set_settings('options', $settings['options']);
				
		$settings['author'] 	= $this->_build_author_select($entry_data);
		$settings['channel']	= $this->_build_channel_select();
		$settings['status']		= $this->_build_status_select($entry_data);

		return $settings;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Build Author Vars
	 *
	 * @param 	array
	 */
	protected function _build_author_select($entry_data)
	{
		$this->load->model('member_model');

		// Default author
		$author_id = (isset($entry_data['author_id'])) ? $entry_data['author_id'] : $this->session->userdata('member_id');

		$menu_author_options = array();
		$menu_author_selected = $author_id;
		
		$qry = $this->db->select('username, screen_name')
						->get_where('members', array('member_id' => (int) $author_id));
			
		$author = ($qry->row('screen_name')  == '') ? $qry->row('username') : $qry->row('screen_name');
		$menu_author_options[$author_id] = $author;
		
		// Next we'll gather all the authors that are allowed to be in this list
		$author_list = $this->member_model->get_authors_simple();

		$channel_id = (isset($entry_data['channel_id'])) ? $entry_data['channel_id'] : $this->input->get('channel_id');

		// We'll confirm that the user is assigned to a member group that allows posting in this channel
		if ($author_list->num_rows() > 0)
		{
			foreach ($author_list->result() as $row)
			{
				if (isset($this->session->userdata['assigned_channels'][$channel_id]))
				{
					$menu_author_options[$row->member_id] = ($row->screen_name == '') ? $row->username : $row->screen_name;
				}
			}
		}
		
		$settings = array(
			'author'	=> array(
				'field_id'				=> 'author',
				'field_label'			=> lang('author'),
				'field_required'		=> 'n',
				'field_instructions'	=> '',
				'field_type'			=> 'select',
				'field_pre_populate'	=> 'n',
				'field_text_direction'	=> 'ltr',
				'field_list_items'		=> $menu_author_options,
				'field_data'			=> $menu_author_selected
			)
		);

		$this->api_channel_fields->set_settings('author', $settings['author']);
		return $settings['author'];
	}

	// --------------------------------------------------------------------

	/**
	 * Build Channel Select Options Field
	 *
	 * @return 	array
	 */
	private function _build_channel_select()
	{
		$menu_channel_options 	= array();
		$menu_channel_selected	= '';
		
		$query = $this->channel_model->get_channel_menu(
														$this->_channel_data['status_group'], 
														$this->_channel_data['cat_group'], 
														$this->_channel_data['field_group']
													);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				if ($this->session->userdata['group_id'] == 1 OR in_array($row['channel_id'], $assigned_channels))
				{
					if (isset($_POST['new_channel']) && is_numeric($_POST['new_channel']) && $_POST['new_channel'] == $row['channel_id'])
					{
						$menu_channel_selected = $row['channel_id'];
					}
					elseif ($this->_channel_data['channel_id'] == $row['channel_id'])
					{
						$menu_channel_selected =  $row['channel_id'];
					}

					$menu_channel_options[$row['channel_id']] = form_prep($row['channel_title']);
				}
			}
		}
		
		$settings = array(
			'channel'	=> array(
				'field_id'				=> 'channel',
				'field_label'			=> lang('channel'),
				'field_required'		=> 'n',
				'field_instructions'	=> '',
				'field_type'			=> 'select',
				'field_pre_populate'	=> 'n',
				'field_text_direction'	=> 'ltr',
				'field_list_items'		=> $menu_channel_options,
				'field_data'			=> $menu_channel_selected
			)
		);

		$this->api_channel_fields->set_settings('channel', $settings['channel']);
		return $settings['channel'];		
	}

	// --------------------------------------------------------------------

	/**
	 * Build Status Select
	 *
	 * @return 	array
	 */
	private function _build_status_select($entry_data)
	{
		$this->load->model('status_model');
		
		// check the logic here...
		if ( ! isset($this->_channel_data['deft_status']) && $this->_channel_data['deft_status'] == '')
		{
			$this->_channel_data['deft_status'] = 'open';
		}
		
		$entry_data['status'] = (isset($entry_data['status']) && $entry_data['status'] != 'NULL') ? $entry_data['status'] : $this->_channel_data['deft_status'];
		
		$no_status_access 		= array();
		$menu_status_options 	= array();
		$menu_status_selected 	= $entry_data['status'];

		if ($this->session->userdata('group_id') !== 1)
		{
			$query = $this->status_model->get_disallowed_statuses($this->session->userdata('group_id'));

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$no_status_access[] = $row['status_id'];
				}
			}
			
			// if there is no status group assigned, 
			// only Super Admins can create 'open' entries
			$menu_status_options['open'] = lang('open');		
		}
		
		$menu_status_options['closed'] = lang('closed');
		
		if (isset($this->_channel_data['status_group']))
		{
			$query = $this->status_model->get_statuses($this->_channel_data['status_group']);
			
			if ($query->num_rows())
			{
				$no_status_flag = TRUE;
				$vars['menu_status_options'] = array();

				foreach ($query->result_array() as $row)
				{
					// pre-selected status
					if ($entry_data['status'] == $row['status'])
					{
						$menu_status_selected = $row['status'];
					}

					if (in_array($row['status_id'], $no_status_access))
					{
						continue;
					}

					$no_status_flag = FALSE;
					$status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? lang($row['status']) : $row['status'];
					$menu_status_options[form_prep($row['status'])] = form_prep($status_name);
				}

				//	Were there no statuses?
				// If the current user is not allowed to submit any statuses we'll set the default to closed

				if ($no_status_flag === TRUE)
				{
					$menu_status_selected = 'closed';
				}
			}
		}
		
		$settings = array(
			'status'	=> array(
				'field_id'				=> 'status',
				'field_label'			=> lang('status'),
				'field_required'		=> 'n',
				'field_instructions'	=> '',
				'field_type'			=> 'select',
				'field_pre_populate'	=> 'n',
				'field_text_direction'	=> 'ltr',
				'field_list_items'		=> $menu_status_options,
				'field_data'			=> $menu_status_selected
			)
		);

		$this->api_channel_fields->set_settings('status', $settings['status']);
		return $settings['status'];
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
		$title = ($this->input->get_post('title')) ? $this->input->get_post('title') : $entry_data['title'];
		
		if ($this->_channel_data['default_entry_title'] != '' && $title == '')
		{
			$title = $this->_channel_data['default_entry_title'];
		}
		
		$deft_fields = array(
			'title' 		=> array(
				'field_id'				=> 'title',
				'field_label'			=> lang('title'),
				'field_required'		=> 'y',
				'field_data'			=> $title,
				'field_show_fmt'		=> 'n',
				'field_instructions'	=> '',
				'field_text_direction'	=> 'ltr',
				'field_type'			=> 'text',
				'field_maxl'			=> 100
			),
			'url_title'		=> array(
				'field_id'				=> 'url_title',
				'field_label'			=> lang('url_title'),
				'field_required'		=> 'n',
				'field_data'			=> ($this->input->get_post('url_title') == '') ? $entry_data['url_title'] : $this->input->get_post('url_title'),
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
				'field_data'			=> (isset($entry_data['entry_date'])) ? $entry_data['entry_date'] : '',
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
				'field_data'			=> (isset($entry_data['expiration_date'])) ? $entry_data['expiration_date'] : '',
				'field_fmt'				=> 'text',
				'field_instructions'	=> '',
				'field_show_fmt'		=> 'n',
				'selected'				=> 'y',
				'dst_enabled'			=> $this->_dst_enabled				
			)	
		);
		
		// comment expiry here.
		if (isset($this->cp->installed_modules['comment']))
		{
			$deft_fields['comment_expiration_date'] = array(
				'field_id'				=> 'comment_expiration_date',
				'field_label'			=> lang('comment_expiration_date'),
				'field_required'		=> 'n',
				'field_type'			=> 'date',
				'field_text_direction'	=> 'ltr',
				'field_data'			=> (isset($entry_data['comment_expiration_date'])) ? $entry_data['comment_expiration_date'] : '',
				'field_fmt'				=> 'text',
				'field_instructions'	=> '',
				'field_show_fmt'		=> 'n',
				'selected'				=> 'y',
				'dst_enabled'			=> $this->_dst_enabled
			);
		}
		
		foreach ($deft_fields as $field_name => $f_data)
		{
			$this->api_channel_fields->set_settings($field_name, $f_data);
			
			$rules = 'required|call_field_validation['.$f_data['field_id'].']';
			$this->form_validation->set_rules($f_data['field_id'], $f_data['field_label'], $rules);
		}
		
		return $deft_fields;
	}

	// --------------------------------------------------------------------

	/**
	 * Build Third Party tab blocks
	 *
	 * This method assembles tabs from modules that include a publish tab
	 *
	 * @param 	array
	 * @return 	array
	 */
	private function _build_third_party_blocks($entry_data)
	{
		$module_fields = $this->api_channel_fields->get_module_fields(
														$this->_channel_data['channel_id'], 
														$entry_data['entry_id']
													);
		$settings = array();
		
		if ($module_fields && is_array($module_fields))
		{
			foreach ($module_fields as $tab => $v)
			{
				foreach ($v as $val)
				{
					$settings[$val['field_id']] = $val;
					$this->_module_tabs[$tab][] = array(
													'id' 	=> $val['field_id'],
													'label'	=> $val['field_label']
													);
					
					$this->api_channel_fields->set_settings($val['field_id'], $val);
					
					$rules = 'call_field_validation['.$val['field_id'].']';
					$this->form_validation->set_rules($val['field_id'], $val['field_label'], $rules);
				}
			}
		}

		return $settings;		
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Third Party Tabs
	 *
	 * This method returns an array of third party tabs for merging into
	 * the default tabs array in _setup_tab_hierarchy()
	 *
	 * @return 	array
	 */
	private function _third_party_tabs()
	{
		if (empty($this->_module_tabs))
		{
			return array();
		}

		$out = array();

		foreach ($this->_module_tabs as $k => $v)
		{
			foreach ($v as $key => $val)
			{
				$out[$k][] = $val['id'];			
			}		
		}

		return $out;
	}

	// --------------------------------------------------------------------	

	/**
	 * Sort Publish Fields
	 *
	 * Some browsers (read: chrome) sort JSON arrays by key automatically.
	 * So before we save our fields we need to reorder them according to
	 * their index parameter.
	 *
	 */
	private function _sort_publish_fields($fields)
	{
		// array_multisort couldn't be coerced into maintaining our
		// array keys, so we sort manually ... le sigh.
		
		$positions = array();
		$new_fields = array();
		
		foreach($fields as $id => $field)
		{
			if ($id == '_tab_label')
			{
				$new_fields[$id] = $field;
				continue;
			}
			
			$positions[$field['index']] = $id;
			unset($fields[$id]['index']);
		}
		
		ksort($positions);
		
		foreach($positions as $id)
		{
			$new_fields[$id] = $fields[$id];
		}

		return $new_fields;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Build Smiley Table
	 *
	 * This function builds the smiley table for a given field.
	 *
	 * @param 	string 	Field Name
	 * @return 	string 	Smiley Table HTML
	 */
	private function _build_smiley_table($field_name)
	{		
		$this->load->library('table');

		$this->table->set_template(array(
			'table_open' => 
				'<table style="text-align: center; margin-top: 5px;" class="mainTable padTable smileyTable">'
		));

		$image_array = get_clickable_smileys($this->config->slash_item('emoticon_path'), 
											 $field_name);
		$col_array = $this->table->make_columns($image_array, 8);
		$smilies = '<div class="smileyContent" style="display: none;">';
		$smilies .= $this->table->generate($col_array).'</div>';
		$this->table->clear();
		
		return $smilies;
	}
	
	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file content_publish.php */
/* Location: ./system/expressionengine/controllers/cp/content_publish.php */