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

	private $_channel_data 		= array();
	private $_channel_fields 	= array();
	private $_publish_blocks 	= array();
	private $_publish_layouts 	= array();

	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->load->library('api');
		$this->load->model('channel_model');
		$this->cp->get_installed_modules();
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
		
		
		// @todo only admins
		$this->cp->add_js_script(array('file' => 'cp/publish_admin'));
		
		$this->javascript->set_global(array(
			'date.format'			=> 'us',
			'user.foo'				=> FALSE,
			'publish.markitup.foo'	=> FALSE
		));
		
		$this->javascript->compile();
		
		
		$tab_labels = array(
			'publish' 		=> lang('publish'),
			'categories' 	=> lang('categories'),
			'pings'			=> lang('pings'),
			'options'		=> lang('options'),
			'date'			=> lang('date')
		);
		
		
		$data = array(
			'cp_page_title'	=> $entry_id ? lang('edit_entry') : lang('new_entry'),
			'message'		=> '',	// @todo consider pulling?
			
			'tabs'			=> $tab_hierarchy,
			'first_tab'		=> key($tab_hierarchy),
			'tab_labels'	=> $tab_labels,
			'field_list'	=> $field_list,
			'layout_styles'	=> $layout_styles,
			'field_output'	=> $field_output
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
			
			$field_settings[$settings['field_name']] = $settings;
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
			'categories'	=> array('categories'),
			'options'		=> array('channel', 'status', 'author', 'options')
		);
		
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
		$categories 	= $this->_categories_block($entry_data['entry_id']);
		$pings 			= $this->_ping_block($entry_data['entry_id']);
		$options		= $this->_options_block($entry_data);
		$forum			= $this->_forum_block($entry_data);
		
		return array_merge($field_data, $categories, $pings, $options, $forum);
	}

	// --------------------------------------------------------------------

	/**
	 * Categories Block
	 *
	 *
	 */
	private function _categories_block($entry_id)
	{	
		$cat_data_array = array();
		
		$vars = array(
			'edit_categories_link'	=> FALSE,
			'categories'			=> array()
		);
					
		$qry = $this->db->select('c.cat_name, p.*')
						->from('categories AS c, category_posts AS p')
						->where_in('c.group_id', explode('|', $this->_channel_data['cat_group']))
						->where('p.entry_id', $entry_id)
						->where('c.cat_id = p.cat_id', NULL, FALSE)
						->get();
		
		foreach ($qry->result() as $row)
		{
			// $catlist[$row->cat_id] = $row->cat_id;
		}


		$this->load->view('content/_assets/categories', $vars, TRUE);

		return $cat_data_array;

		/*
		$vars = compact('categories', 'edit_categories_link');
		$category_r = $this->load->view('content/_assets/categories', $vars, TRUE);
		
		$this->field_definitions['category'] = array(
			'string_override'		=> ($cat_groups == '') ? $this->lang->line('no_categories') : $category_r,
			'field_id'				=> 'category',
			'field_name'			=> 'category',
			'field_label'			=> $this->lang->line('categories'),
			'field_required'		=> 'n',
			'field_type'			=> 'multiselect',
			'field_text_direction'	=> 'ltr',
			'field_data'			=> '',
			'field_fmt'				=> 'text',
			'field_instructions'	=> '',
			'field_show_fmt'		=> 'n',
			'selected'				=> 'n',
			'options'				=> $categories
		);		*/
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
	private function _ping_block($entry_id) 
	{
		$ping_servers = $this->channel_entries_model->fetch_ping_servers($entry_id);

		$settings = array('ping' => 
			array(
				'string_override'		=> (isset($ping_servers) && $ping_servers != '') ? '<fieldset>'.$ping_servers.'</fieldset>' : lang('no_ping_sites').'<p><a href="'.BASE.AMP.'C=myaccount'.AMP.'M=ping_servers'.AMP.'id='.$this->session->userdata('member_id').'">'.$this->lang->line('add_ping_sites').'</a></p>',
				'field_id'				=> 'ping',
				'field_label'			=> $this->lang->line('pings'),
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
	private function _options_block($entry_data)
	{
		// sticky, comments, dst
		// author, channel, status
		$settings			= array();
		
		$show_comments		= FALSE;
		$show_sticky		= FALSE;
		$show_dst			= FALSE;
		
		$options_array = array(
			'sticky'	=> array(
				'field_id'				=> 'sticky',
				'field_required'		=> 'n',
				'field_label'			=> lang('sticky'),
				'field_data'			=> '',
				'field_instructions'	=> '',
				'field_type'			=> 'select'				
			)			
		);

		// Allow Comments?
		if ( ! isset($this->cp->installed_modules['comment']))
		{
			$allow_comments = (isset($entry_data['allow_comments'])) ? $entry_data['allow_comments'] : 'n';
		}
		elseif ($this->_channel_data['comment_system_enabled'] == 'y')
		{
			$options_array['allow_comments'] = array(
					'field_id'				=> 'allow_comments',
					'field_label'			=> lang('allow_comments'),
					'field_type'			=> 'select',
					'field_required'		=> 'n',
					'field_instructions'	=> '',	
					'field_data'			=> '',
			);
		}

		// Is DST active? 
		if ($this->config->item('honor_entry_dst') == 'y')
		{
			$options_array['dst_enabled'] = array(
					'field_id'				=> 'dst_enabled',
					'field_label'			=> lang('dst_enabled'),
					'field_required'		=> 'n',
					'field_instructions'	=> '',
					'field_type'			=> 'select',
					'field_data'			=> ($entry_data['dst_enabled']) ? TRUE : FALSE
			);
		}
			
		// Options Field
		// $settings['options'] = array(
		// 	'field_id'				=> 'options',
		// 	'field_required'		=> 'n',
		// 	'field_label'			=> lang('options'),
		// 	'field_data'			=> $options_array,
		// 	'field_instructions'	=> '',
		// 	'field_type'			=> 'select'
		// );
		

			// $this->api_channel_fields->set_settings('dst_enabled', $settings['options']);
				
		return $settings;
	}

	// --------------------------------------------------------------------

	private function _forum_block($entry_data)
	{
		$settings = array();
		
		$hide_forum_fields = FALSE;

		if ($this->config->item('forum_is_installed') == 'n')
		{
			return $settings;
		}

		
		
		
		/*

$hide_forum_fields = FALSE;

if ($this->config->item('forum_is_installed') == "y")
{
	// New forum topics will only be accepted by the submit_new_entry_form() when there is no entry_id sent

	$vars['forum_title']			= '';
	$vars['forum_body']				= '';
	$vars['forum_topic_id_descp']	= '';
	$vars['forum_id']	= '';
	$vars['forum_topic_id']			= ( ! isset($_POST['forum_topic_id'])) ? '' : $_POST['forum_topic_id'];		
	
	if ($which == 'new' OR $entry_id == '')
	{
		// Fetch the list of available forums

		$this->db->select('f.forum_id, f.forum_name, b.board_label');
		$this->db->from('forums AS f, forum_boards AS b');
		$this->db->where('f.forum_is_cat', 'n');
		$this->db->where('b.board_id = f.board_id', NULL, FALSE);
		$this->db->order_by('b.board_label asc, forum_order asc');
		
		$fquery = $this->db->get();

		if ($fquery->num_rows() == 0)
		{
			$vars['forum_id'] = $this->lang->line('forums_unavailable');
		}
		else
		{
			if (isset($entry_id) AND $entry_id != 0)
			{
				if ( ! isset($forum_topic_id))
				{
					$this->db->select('forum_topic_id');
					$fquery2 = $this->db->get_where('channel_titles', 
								array(
									'entry_id' => $entry_id
								)
							);
					
					$forum_topic_id = $fquery2->row('forum_topic_id');
				}

				$vars['form_hidden']['forum_topic_id'] = $forum_topic_id;
			}
			
			foreach ($fquery->result_array() as $forum)
			{
				$forums[$forum['forum_id']] = $forum['board_label'].': '.$forum['forum_name'];
			}

			$forum_title = ( ! $this->input->get_post('forum_title')) ? '' : $this->input->get_post('forum_title');
			$forum_body	 = ( ! $this->input->get_post('forum_body')) ? '' : $this->input->get_post('forum_body');

			$vars['forum_title']			= $forum_title;
			$vars['forum_body']				= $forum_body;
			$vars['forum_topic_id']			= ( ! isset($_POST['forum_topic_id'])) ? '' : $_POST['forum_topic_id'];
			$vars['forum_id']	= form_dropdown('forum_id', $forums, $this->input->get_post('forum_id'));

			$vars['forum_topic_id_descp']	= $this->lang->line('forum_topic_id_exitsts');

			//	Smileys Panes									
			if ($vars['smileys_enabled'])
			{
				$this->table->set_template(array(
					'table_open'			=> '<table style="text-align: center; margin-top: 5px;" class="mainTable padTable smileyTable" border="0" cellspacing="0" cellpadding="0">'
				));

				$image_array = get_clickable_smileys($path = $this->config->slash_item('emoticon_path'), 'forum_title');
				$col_array = $this->table->make_columns($image_array, 8);
				$vars['smiley_table']['forum_title'] = '<div class="smileyContent" style="display: none;">'.$this->table->generate($col_array).'</div>';
				$this->table->clear(); // clear out tables for the next smiley

			
				$image_array = get_clickable_smileys($path = $this->config->slash_item('emoticon_path'), 'forum_body');
				$col_array = $this->table->make_columns($image_array, 8);
				$vars['smiley_table']['forum_body'] = '<div class="smileyContent" style="display: none;">'.$this->table->generate($col_array).'</div>';
				$this->table->clear(); // clear out tables for the next smiley						
			}				
		}

	}
	else
	{
		$hide_forum_fields = TRUE;
		if ( ! isset($forum_topic_id))
		{
			$this->db->select('forum_topic_id');
			$fquery = $this->db->get_where('channel_titles', array('entry_id' => $entry_id));
			
			$forum_topic_id = $fquery->row('forum_topic_id');
		}
		
		$vars['forum_topic_id_descp']	= $this->lang->line('forum_topic_id_info');
		$vars['forum_topic_id'] = $forum_topic_id;
		
		if ($forum_topic_id != 0)
		{
			$this->db->select('title');
			$fquery = $this->db->get_where('forum_topics', 
							array('topic_id' => (int) $forum_topic_id));

			$ftitle = ($fquery->num_rows() == 0) ? '' : $fquery->row('title');
			$vars['forum_title'] = $ftitle;
		}
	}
}
		
		*/
		
		return $settings;
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
		// 'categories', 'pings', 'revisions', 'pages', all forum tab fields, all options tab fields
		
		$deft_fields = array(
			'title' 		=> array(
				'field_id'				=> 'title',
				'field_label'			=> lang('title'),
				'field_required'		=> 'y',
				'field_data'			=> ( ! $this->input->post('title')) ? $entry_data['title'] : $this->input->post('title'),
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



	
}