<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');



/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Content_publish extends CP_Controller {

	private $_tab_labels		= array();
	private $_module_tabs		= array();
	private $_channel_data 		= array();
	private $_file_manager 		= array();
	private $_channel_fields 	= array();
	private $_publish_blocks 	= array();
	private $_publish_layouts 	= array();
	private $_errors			= array();
	private $_assigned_channels = array();
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

		$this->_assigned_channels = $this->functions->fetch_assigned_channels();
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
			$title = lang('publish');

			$data = array(
				'instructions'		=> lang('select_channel_to_post_in'),
				'link_location'		=> BASE.AMP.'C=content_publish'.AMP.'M=entry_form'
			);
		}
		else
		{
			$title = lang('edit');

			$data = array(
				'instructions'		=> lang('select_channel_to_edit'),
				'link_location'		=> BASE.AMP.'C=content_edit'.AMP.'M=edit_entries'
			);
		}

		$this->view->cp_page_title = $title;

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

		$this->cp->render('content/channel_select_list', $data);
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

        // Needed for custom tabs loaded by layout_model from the db table
        // exp_layout_publish where the whole layout (fields and tabs) are
        // stored in serialized form.  This language file contains the
        // localized names for the fields and tabs.  We may want to push
        // this call deeper down the rabbit hole so that it is simply
        // always available whenever we load the layout_model.  Or this
        // may be the only spot we use it.  Not sure, so sticking it
        // here for now.  -Daniel B.
        $this->lang->loadfile('publish_tabs_custom');

		$entry_id	= (int) ee()->input->get_post('entry_id');
		$channel_id	= (int) ee()->input->get_post('channel_id');
		$site_id	= (int) ee()->input->get_post('site_id');

		// If an entry or channel on a different site is requested, try
		// to switch sites and reload the publish form
		if ($site_id != 0 && $site_id != ee()->config->item('site_id') && empty($_POST))
		{
			ee()->cp->switch_site(
				$site_id,
				cp_url(
					'content_publish/entry_form',
					array(
						'channel_id'	=> $channel_id,
						'entry_id'		=> $entry_id,
					)
				)
			);
		}

		// Prevent publishing new entries if disallowed
		if ( ! $this->cp->allowed_group('can_access_content', 'can_access_publish') AND $entry_id == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		$autosave	= ($this->input->get_post('use_autosave') == 'y');

		// If we're autosaving and this isn't a submitted form
		if ($autosave AND empty($_POST))
		{
			$autosave_entry_id = $entry_id;

			$autosave_data = $this->db->get_where('channel_entries_autosave', array(
				'entry_id' => $entry_id
			));
			$autosave_data = $autosave_data->row();

			$entry_id = $autosave_data->original_entry_id;
		}
		else
		{
			$autosave_entry_id = FALSE;
		}

		$this->_smileys_enabled = (isset($this->cp->installed_modules['emoticon']) ? TRUE : FALSE);

		if ($this->_smileys_enabled)
		{
			$this->load->helper('smiley');
			$this->cp->add_to_foot(smiley_js());
		}

		// Grab the channel_id associated with this entry if
		// required and make sure the current member has access.
		$channel_id = $this->_member_can_publish($channel_id, $entry_id, $autosave_entry_id);

		// If they're loading a revision, we stop here
		$this->_check_revisions($entry_id);


		// Get channel data
		$this->_channel_data = $this->_load_channel_data($channel_id);

		// Grab, fields and entry data
		$entry_data		= $this->_load_entry_data($channel_id, $entry_id, $autosave_entry_id);
		$field_data		= $this->_set_field_settings($entry_id, $entry_data);
		$entry_id		= $entry_data['entry_id'];

		// Merge in default fields
		$deft_field_data = $this->_setup_default_fields($this->_channel_data, $entry_data);

		$field_data = array_merge($field_data, $deft_field_data);
		$field_data = $this->_setup_field_blocks($field_data, $entry_data);

		$this->_set_field_validation($this->_channel_data, $field_data);

		// @todo setup validation for categories, etc?
		// @todo third party tabs

		$this->form_validation->set_message('title', lang('missing_title'));
		$this->form_validation->set_message('entry_date', lang('missing_date'));

		$this->form_validation->set_error_delimiters('<div class="notice">', '</div>');

		if ($this->form_validation->run() === TRUE)
		{
			if ($this->_save($channel_id, $entry_id) === TRUE)
			{
				// under normal circumstances _save will redirect
				// if we get here, a hook triggered end_script
				return;
			}

			// used in _setup_layout_styles
			// @todo handle generic api errors
			$this->errors = $this->api_channel_entries->errors;
		}

		$this->_setup_file_list();

		// get all member groups with cp access for the layout list
		$member_groups_laylist = array();

		$listable = $this->member_model->get_member_groups(array('can_access_admin', 'can_access_edit'), array('can_access_content'=>'y'));

		foreach($listable->result() as $group)
		{
			if ($group->can_access_admin == 'y' OR $group->can_access_edit == 'y')
			{
				$member_groups_laylist[] = array('group_id' => $group->group_id, 'group_title' => $group->group_title);
			}
		}


		// Set default tab labels
		// They may be overwritten or added to in the steps below

		$this->_tab_labels = array(
			'publish' 		=> lang('publish'),
			'categories' 	=> lang('categories'),
			'options'		=> lang('options'),
			'date'			=> lang('date'),
		);


		if (isset($this->_channel_data['enable_versioning'])
			&& $this->_channel_data['enable_versioning'] == 'y')
		{
			$this->_tab_labels['revisions'] = lang('revisions');
		}

		// Load layouts - we'll need them for the steps below
		// if this is a layout group preview, we'll use it, otherwise, we'll use the author's group_id
		$layout_info = $this->_load_layout($channel_id);

		// Merge layout data (mostly width and visbility) into field data for use on the publish page
		$field_data = $this->_set_field_layout_settings($field_data, $layout_info);

		// First figure out what tabs to show, and what fields
		// they contain. Then work through the details of how
		// they are show.

		$this->cp->add_js_script('file', array('cp/publish', 'cp/category_editor'));

		$tab_hierarchy	= $this->_setup_tab_hierarchy($field_data, $layout_info);
		$layout_styles	= $this->_setup_layout_styles($field_data, $layout_info);
		$field_list		= $this->_sort_field_list($field_data);		// @todo admin only? or use as master list? skip sorting for non admins, but still compile?
		$field_list		= $this->_prep_field_wrapper($field_list);

		$field_output	= $this->_setup_field_display($field_data, $entry_id);

		// Start to assemble view data
		// WORK IN PROGRESS, just need a few things on the page to
		// work with the html - will clean this crap up

		$this->load->library('filemanager');
		$this->load->helper('snippets');

		$this->load->library('file_field');
		$this->file_field->browser();

		$this->cp->add_js_script(array(
			'ui'	 => array('resizable', 'draggable', 'droppable'),
			'plugin' => array('markitup', 'toolbox.expose', 'overlay', 'tmpl', 'ee_url_title'),
			'file'	=> array('json2', 'cp/publish_tabs')
		));

		if ($this->session->userdata('group_id') == 1)
		{
			$this->cp->add_js_script(array('file' => 'cp/publish_admin'));
		}

		$this->_set_global_js($entry_id);

		reset($tab_hierarchy);

		$this->_markitup();

		$parts = $_GET;
		unset($parts['S'], $parts['D']);
		$current_url = http_build_query($parts, '', '&amp;');

		$autosave_id = ($autosave) ? $autosave_entry_id : 0;

		// Remove 'layout_preview' from the URL, stripping anything after it
		if (strpos($current_url, 'layout_preview') !== FALSE)
		{
			$preview_url = explode(AMP.'layout_preview=', $current_url, 2);
			$preview_url = $preview_url[0];
		}
		else
		{
			$preview_url = $current_url;
		}

		$data = array(
			'message'			=> '',	// @todo consider pulling?
			'cp_page_title'		=> $entry_id ? lang('edit_entry') : lang('new_entry') . ': '. $this->_channel_data['channel_title'],

			'tabs'				=> $tab_hierarchy,
			'first_tab'			=> key($tab_hierarchy),
			'tab_labels'		=> $this->_tab_labels,
			'field_list'		=> $field_list,
			'layout_styles'		=> $layout_styles,
			'field_output'		=> $field_output,
			'layout_group'	=> (is_numeric($this->input->get_post('layout_preview'))) ?
				$this->input->get_post('layout_preview') : $this->session->userdata('group_id'),

			'spell_enabled'		=> TRUE,
			'smileys_enabled'	=> $this->_smileys_enabled,

			'current_url'		=> $current_url,
			'file_list'			=> $this->_file_manager['file_list'],

			'show_revision_cluster' => $this->_channel_data['enable_versioning'],
			'member_groups_laylist'	=> $member_groups_laylist,

			// For the autosaves, we're using the GET version of entry_id because
			// it's the ID in the autosave table

			'hidden_fields'		=> array(
				'entry_id'			=> $entry_id,
				'channel_id'		=> $channel_id,
				'autosave_entry_id'	=> $autosave_id,
				'filter'			=> $this->input->get_post('filter')
			),

			'preview_url'	=> $preview_url
		);

		if ($this->cp->allowed_group('can_access_publish'))
		{
			$this->cp->set_breadcrumb(BASE.AMP.'C=content_publish', lang('publish'));
		}

		$this->cp->render('content/publish', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Autosave
	 *
	 * @return	void
	 */
	public function autosave()
	{
		$entry_id	= (int) $this->input->get_post('entry_id');
		$channel_id	= (int) $this->input->get_post('channel_id');

		// can they access this channel?
		if ( ! $channel_id OR ! in_array($channel_id, $this->_assigned_channels))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->_channel_data = $this->_load_channel_data($channel_id);

		// Grab, fields and entry data
		$entry_data		= $this->_load_entry_data($channel_id, $entry_id);
		$field_data		= $this->_set_field_settings($entry_id, $entry_data);
		$entry_id		= $entry_data['entry_id'];

		$this->_setup_default_fields($this->_channel_data, $entry_data);

		$this->api->instantiate('channel_entries');

		// Editing a non-existant entry?
		if ($entry_id && ! $this->api_channel_entries->entry_exists($entry_id))
		{
			return FALSE;
		}

		$data = $_POST;
		$data['cp_call']		= TRUE;
		$data['author_id']		= $this->input->post('author');	// @todo double check if this is validated
		$data['revision_post']	= $_POST;			// @todo only if revisions - memory

		// Remove leftovers
		unset($data['author']);
		unset($data['filter']);
		unset($data['return_url']);

		$this->output->enable_profiler(FALSE);

		$id = $this->api_channel_entries->autosave_entry($data);

		// @todo check for errors

		$msg = lang('autosave_success');
		$time = $this->localize->human_time($this->localize->now);
		$time = trim(strstr($time, ' '));

		$this->output->send_ajax_response(array(
			'success' => $msg.$time,
			'autosave_entry_id' => $id,
			'original_entry_id'	=> $entry_id
		));
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
			show_error(lang('unauthorized_access'));
		}

		if (empty($_POST))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->api->instantiate('channel_fields');

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

		foreach($layout_info as $tab)
		{
			foreach ($tab['fields'] as $name => $info)
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

				$defaults = array(
				        lang('publish')     => 'publish',
				        lang('categories')  => 'categories',
				        lang('options')     => 'options',
				        lang('date')        => 'date'
				);

				if($name == '_tab_label' && ! empty($defaults[$info])) {
				        $tab['fields'][$name] = $defaults[$info];
				}
			}

			$clean_layout[strtolower($tab['name'])] = $tab['fields'];
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

	function preview_layout()
	{
		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error(lang('unauthorized_access'));
		}

		if (empty($_POST))
		{
			show_error(lang('unauthorized_access'));
		}

		$member_group_name = $this->input->post('member_group');
		$this->session->set_flashdata('message', lang('layout_preview') . $member_group_name);
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

		if ( ! in_array($channel_id, $this->_assigned_channels))
		{
			show_error(lang('unauthorized_for_this_channel'));
		}

		$channel_info_fields = array(
			'field_group',
			'channel_html_formatting',
			'channel_allow_img_urls',
			'channel_auto_link_urls'
		);

		$qry = $this->channel_model->get_channel_info($channel_id, $channel_info_fields);

		if ( ! $qry->num_rows())
		{
			show_error(lang('unauthorized_access'));
		}

		$channel_info = $qry->row();

		$qry = $this->db->select('field_id, field_type')
						->where('group_id', $channel_info->field_group)
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

		$this->load->library('typography');

		$this->typography->initialize(array('convert_curly' => FALSE));

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

		$r = '';

		$entry_title = $this->typography->format_characters($resrow['title']);

		$publish_another_link = BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel_id;

		// Ugh, we just overwrite? Strong typing please!!
		if ($show_edit_link)
		{
			$show_edit_link = $publish_another_link.AMP.'entry_id='.$entry_id;
		}


		$filter_link = $this->input->get('filter');

		if ($filter_link)
		{
			$show_edit_link .= AMP.'filter='.$filter_link;

			$filters	 = unserialize(base64_decode($filter_link));
			$filter_link = BASE.AMP.'C=content_edit';

			if (isset($filters['keywords']))
			{
				$filters['keywords'] = base64_encode($filters['keywords']);
			}

			$filter_link = BASE.AMP.'C=content_edit'.AMP.http_build_query($filters);
		}


		$comment_count = 0;

		if ($show_comments_link)
		{
			if (isset($this->cp->installed_modules['comment']))
			{
				$comment_count = $this->db->where('entry_id', $entry_id)
										  ->count_all_results('comments');

				$this->db->query_count--;
			}

			$show_comments_link	= BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=index'.AMP.'entry_id='.$entry_id;
		}

		$live_look_link = FALSE;

		if ($resrow['live_look_template'] != 0)
		{
			$this->db->select('template_groups.group_name, templates.template_name');
			$this->db->from('template_groups, templates');
			$this->db->where('exp_template_groups.group_id = exp_templates.group_id', NULL, FALSE);
			$this->db->where('templates.template_id', $resrow['live_look_template']);

			$res = $this->db->get();

			if ($res->num_rows() == 1)
			{
				$live_look_link = $this->cp->masked_url($this->functions->create_url($res->row('group_name').'/'.$res->row('template_name').'/'.$entry_id));
			}
		}

		$data = array(
			'filter_link'			=> $filter_link,
			'live_look_link'		=> $live_look_link,
			'show_edit_link'		=> $show_edit_link,
			'publish_another_link'	=> $publish_another_link,
			'comment_count'			=> $comment_count,
			'show_comments_link'	=> $show_comments_link,

			'entry_title'			=> $entry_title,
			'entry_contents'		=> $r
		);

		$this->view->cp_page_title = lang('view_entry');
		$this->cp->render('content/view_entry', $data);
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
	function filemanager_actions($function = '', $params = array())
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('filemanager');

		$config = array();

		if ($function)
		{
			$this->filemanager->_initialize($config);

			return call_user_func_array(array($this->filemanager, $function), $params);
		}
		$this->filemanager->process_request($config);
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

		$query = $this->category_model->get_category_groups($group_id, FALSE);
		$this->api_channel_categories->category_tree($group_id, '', $query->row('sort_order'));

		$data = array(
			'edit_links' => FALSE,
			'categories' => array('' => $this->api_channel_categories->categories)
		);

		exit($this->load->view('content/_assets/categories', $data, TRUE));
	}


	// --------------------------------------------------------------------

	/**
	 * Spellcheck
	 *
	 * @return	void
	 */
	public function spellcheck_actions()
	{
		if ($act = $this->input->get('action'))
		{
			$this->output->enable_profiler(FALSE);

			if ( ! class_exists('EE_Spellcheck'))
			{
				require APPPATH.'libraries/Spellcheck.php';
			}

			if ($act == 'iframe' OR $act == 'check')
			{
				return EE_Spellcheck::$act();
			}
		}

		show_error(lang('unauthorized_access'));
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
	private function _set_field_settings($entry_id, $entry_data)
	{
		$this->api->instantiate('channel_fields');

		// Get Channel fields in the field group
		$channel_fields = $this->channel_model->get_channel_fields($this->_channel_data['field_group']);


		$field_settings = array();

		foreach ($channel_fields->result_array() as $row)
		{
			$field_fmt 		= $row['field_fmt'];
			$field_dt 		= '';
			$field_data		= '';

			if ($entry_id === 0)
			{
				// Bookmarklet perhaps?
				if (($field_data = $this->input->get('field_id_'.$row['field_id'])) !== FALSE)
				{
					$field_data = $this->_bm_qstr_decode($this->input->get('tb_url')."\n\n".$field_data );
				}
			}
			else
			{
				$field_data = (isset($entry_data['field_id_'.$row['field_id']])) ? $entry_data['field_id_'.$row['field_id']] : $field_data;
				$field_dt	= (isset($entry_data['field_dt_'.$row['field_id']])) ? $entry_data['field_dt_'.$row['field_id']] : 'y';
				$field_fmt	= (isset($entry_data['field_ft_'.$row['field_id']])) ? $entry_data['field_ft_'.$row['field_id']] : $field_fmt;
			}

			$settings = array(
				'field_instructions'	=> trim($row['field_instructions']),
				'field_text_direction'	=> ($row['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr',
				'field_fmt'				=> $field_fmt,
				'field_dt'				=> $field_dt,
				'field_data'			=> $field_data,
				'field_name'			=> 'field_id_'.$row['field_id'],
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
	 * Add the field layout settings array to the field data
	 *
	 * @param Array $field_data Multidimensional array containing all of the fields and their settings
	 * @param Array $layout_info Multidimensional array containing the publish layout info
	 */
	private function _set_field_layout_settings($field_data, $layout_info)
	{
		if ($layout_info !== FALSE)
		{
			foreach ($layout_info as $layout_tab => $layout)
			{
				foreach ($layout as $field_name => $field_layout_settings)
				{
					if ($field_name !== '_tab_label' AND isset($field_data[$field_name]))
					{
						$field_data[$field_name]['field_visibility'] = ($field_layout_settings['visible']) ? 'y' : 'n';
						$field_data[$field_name]['field_width'] = $field_layout_settings['width'];
					}
				}
			}
		}
		else
		{
			foreach ($field_data as $field_name => &$field_settings)
			{
				$field_settings['field_visibility'] = 'y';
				$field_settings['field_width'] = '100%';
			}
		}

		return $field_data;
	}

	// --------------------------------------------------------------------

	/**
	 * Setup channel field validation
	 *
	 * @return	void
	 */
	private function _set_field_validation($channel_data, $field_data)
	{
		foreach ($field_data as $field_name => $fd)
		{
			$required = '';

			if ($fd['field_required'] == 'y' && $fd['field_type'] != 'file')
			{
				$required = 'required|';
			}

			$rules = $required.'call_field_validation['.$fd['field_id'].']';
			$this->form_validation->set_rules($field_name, $fd['field_label'], $rules);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Member has access
	 *
	 * @return	void
	 */
	private function _member_can_publish($channel_id, $entry_id, $autosave_entry_id)
	{
		$this->load->model('channel_entries_model');

		// A given entry id is either a real channel entry id
		// or the unique id for an autosave row.

		if ($entry_id)
		{
			$query = $this->channel_entries_model->get_entry($entry_id, '', $autosave_entry_id);

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
			if ( ! count($this->_assigned_channels))
			{
				show_error(lang('unauthorized_access'));
			}

			if (count($this->_assigned_channels) > 1)
			{
				// go to the channel select list
				$this->functions->redirect('C=content_publish');
			}

			$channel_id = $this->_assigned_channels[0];
		}

		// After all that mucking around, double check to make
		// sure the channel is actually one they can post to.

		$channel_id = (int) $channel_id;

		if ( ! $channel_id OR ! in_array($channel_id, $this->_assigned_channels))
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
	function _load_entry_data($channel_id, $entry_id = FALSE, $autosave_entry_id = FALSE)
	{
		$result = array(
			'title'		=> $this->_channel_data['default_entry_title'],
			'url_title'	=> $this->_channel_data['url_title_prefix'],
			'entry_id'	=> 0
		);

		if ($entry_id OR $autosave_entry_id)
		{
			$this->load->model('channel_entries_model');

			$query = $this->channel_entries_model->get_entry($entry_id, $channel_id, $autosave_entry_id);

			if ( ! $query->num_rows())
			{
				show_error(lang('no_channel_exists'));
			}

			$result = $query->row_array();

			if ($autosave_entry_id)
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
					$result['autosave_entry_id'] = $entry_id;
					$result['entry_id'] = 0;
				}

				unset($result['entry_data']);
				unset($result['original_entry_id']);
			}

			$version_id = $this->input->get_post('version_id');

			if ($result['versioning_enabled'] == 'y'
				&& is_numeric($version_id))
			{
				$vquery = $this->db->select('version_data')
									->where('entry_id', $entry_id)
									->where('version_id', $version_id)
									->get('entry_versioning');

				if ($vquery->num_rows() === 1)
				{
					$vdata = unserialize($vquery->row('version_data'));

					// Legacy fix for revisions where the entry_id in the array was saved as 0
					$vdata['entry_id'] = $entry_id;

					$result = array_merge($result, $vdata);
				}
			}
		}

		// -------------------------------------------
		// 'publish_form_entry_data' hook.
		//  - Modify entry's data
		//  - Added: 1.4.1
			if ($this->extensions->active_hook('publish_form_entry_data') === TRUE)
			{
				$result = $this->extensions->call('publish_form_entry_data', $result);
			}
		// -------------------------------------------

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
		/* -------------------------------------------
		/* 'submit_new_entry_start' hook.
		/*  - Add More Stuff to do when you first submit an entry
		/*  - Added 1.4.2
		*/
			$this->extensions->call('submit_new_entry_start');
			if ($this->extensions->end_script === TRUE) return TRUE;
		/*
		/* -------------------------------------------*/

		$this->api->instantiate('channel_entries');

		// Editing a non-existant entry?
		if ($entry_id && ! $this->api_channel_entries->entry_exists($entry_id))
		{
			return FALSE;
		}

		// We need these later
		$return_url = $this->input->post('return_url');
		$return_url = $return_url ? $return_url : '';

		$filter = $this->input->get_post('filter');
		$filter = $filter ? AMP.'filter='.$filter : '';


		// Copy over new author id, save revision data,
		// and enabled comment status switching (cp_call)
		$data = $_POST;
		$data['cp_call']		= TRUE;
		$data['author_id']		= $this->input->post('author');		// @todo double check if this is validated
		$data['revision_post']	= $_POST;							// @todo only if revisions - memory


		// Remove leftovers
		unset($data['author']);
		unset($data['filter']);
		unset($data['return_url']);

		// New entry or saving an existing one?
		if ($entry_id)
		{
			$type		= '';
			$page_title	= 'entry_has_been_updated';
			$success	= $this->api_channel_entries->save_entry($data, NULL, $entry_id);
		}
		else
		{
			$type		= 'new';
			$page_title	= 'entry_has_been_added';
			$success	= $this->api_channel_entries->save_entry($data, $_POST['channel_id']);
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
	 * Set Global Javascript
	 *
	 * @param 	int
	 * @return 	void
	 */
	private function _set_global_js($entry_id)
	{
		$autosave_interval_seconds = ($this->config->item('autosave_interval_seconds') === FALSE) ?
										60 : $this->config->item('autosave_interval_seconds');

		//	Create Foreign Character Conversion JS
		include(APPPATH.'config/foreign_chars.php');

		/* -------------------------------------
		/*  'foreign_character_conversion_array' hook.
		/*  - Allows you to use your own foreign character conversion array
		/*  - Added 1.6.0
		* 	- Note: in 2.0, you can edit the foreign_chars.php config file as well
		*/
			if (isset($this->extensions->extensions['foreign_character_conversion_array']))
			{
				$foreign_characters = $this->extensions->call('foreign_character_conversion_array');
			}
		/*
		/* -------------------------------------*/

		$this->javascript->set_global(array(
			'lang.add_new_html_button'			=> lang('add_new_html_button'),
			'lang.add_tab' 						=> lang('add_tab'),
			'lang.close' 						=> lang('close'),
			'lang.confirm_exit'					=> lang('confirm_exit'),
			'lang.duplicate_tab_name'			=> lang('duplicate_tab_name'),
			'lang.hide_toolbar' 				=> lang('hide_toolbar'),
			'lang.illegal_characters'			=> lang('illegal_characters'),
			'lang.illegal_tab_name'				=> lang('illegal_tab_name'),
			'lang.loading'						=> lang('loading'),
			'lang.tab_name'						=> lang('tab_name'),
			'lang.show_toolbar' 				=> lang('show_toolbar'),
			'lang.tab_name_required' 			=> lang('tab_name_required'),
			'publish.autosave.interval'			=> (int) $autosave_interval_seconds,
			'publish.channel_id'				=> $this->_channel_data['channel_id'],
			'publish.default_entry_title'		=> $this->_channel_data['default_entry_title'],
			'publish.field_group'				=> $this->_channel_data['field_group'],
			'publish.foreignChars'				=> $foreign_characters,
			'publish.lang.layout_removed'		=> lang('layout_removed'),
			'publish.lang.no_member_groups'		=> lang('no_member_groups'),
			'publish.lang.refresh_layout'		=> lang('refresh_layout'),
			'publish.lang.tab_count_zero'		=> lang('tab_count_zero'),
			'publish.lang.tab_has_req_field'	=> lang('tab_has_req_field'),
			'publish.markitup.foo'				=> FALSE,
			'publish.smileys'					=> ($this->_smileys_enabled) ? TRUE : FALSE,
			'publish.url_title_prefix'			=> $this->_channel_data['url_title_prefix'],
			'publish.which'						=> ($entry_id === 0) ? 'new' : 'edit',
			'publish.word_separator'			=> $this->config->item('word_separator') != "dash" ? '_' : '-',
			'user.can_edit_html_buttons'		=> $this->cp->allowed_group('can_edit_html_buttons'),
			'user.foo'							=> FALSE,
			'user_id'							=> $this->session->userdata('member_id'),
			'upload_directories'				=> $this->_file_manager['file_list'],
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
	private function _setup_field_display($field_data, $entry_id)
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
			$this->api_channel_fields->apply('_init', array(array(
				'content_id' => $entry_id
			)));

			$field_value = set_value($name, $data['field_data']);

			$field_output[$name] = $this->api_channel_fields->apply('display_publish_field', array($field_value));
		}

		return $field_output;
	}

	// --------------------------------------------------------------------

	/**
	 * Setup Field Wrapper Stuff
	 *
	 * Sets up smileys, spellcheck, glossary, etc
	 *
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
			'field_show_fmt'				=> 'n',
			'field_fmt_options'				=> array()
		);

		$markitup_buttons = array();
		$get_format = array();

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

			if ($data['field_show_fmt'] == 'y')
			{
				// We'll get all the format options in one go
				$get_format[] = $data['field_id'];
			}

			if ($this->_channel_data['show_button_cluster'] == 'y' && isset($data['field_show_formatting_btns']) && $data['field_show_formatting_btns'] == 'y')
			{
				$markitup_buttons['fields'][$field] = $data['field_id'];
			}
		}

		// Field formatting
		if (count($get_format) > 0)
		{
			$this->db->select('field_id, field_fmt');
			$this->db->where_in('field_id', $get_format);
			$this->db->order_by('field_fmt');
			$query = $this->db->get('field_formatting');

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $format)
				{
					$name = ucwords(str_replace('_', ' ', $format['field_fmt']));

					if ($name == 'Br')
					{
						$name = lang('auto_br');
					}
					elseif ($name == 'Xhtml')
					{
						$name = lang('xhtml');
					}

					$field_list['field_id_'.$format['field_id']]['field_fmt_options'][$format['field_fmt']] = $name;
				}
			}
		}

		$this->javascript->set_global('publish.markitup', $markitup_buttons);

		return $field_list;
	}

	// --------------------------------------------------------------------

	/**
	 * Setup Layout Styles for all fields
	 *
	 * @access	private
	 * @return	void
	 */
	private function _setup_layout_styles($field_data, $layout_info)
	{
		$field_display = array(
			'visible'		=> TRUE,
			'collapse'		=> FALSE,
			'html_buttons'	=> TRUE,
			'is_hidden'		=> FALSE,
			'width'			=> '100%'
		);

		$layout = array();

		// do we have a layout? use it
		if ($layout_info)
		{
			foreach ($layout_info as $tab => $fields)
			{
				unset($fields['_tab_label']);

				foreach ($fields as $name => $display)
				{
					$layout[$name] = array_merge($field_display, $display);

					if (isset($this->errors[$name]))
					{
						$layout[$name]['visible'] = TRUE;
					}
				}
			}
		}
		else // defaults
		{
			foreach($field_data as $name => $field)
			{
				$field_display['collapse'] = (isset($field['field_is_hidden']) && $field['field_is_hidden'] == 'y') ? TRUE : FALSE;
				$layout[$name] = $field_display;
			}
		}

		// check for api errors
		// @confirm, would be better off in the else for validation::run?
		foreach ($layout as $name => &$info)
		{
			if ($this->session->userdata('group_id') != 1 && $field_data[$name]['field_type'] == 'hidden')
			{
				$info['visible'] = FALSE;
			}

			if (isset($this->errors[$name]))
			{
				$this->form_validation->_field_data[$name]['error'] = $this->errors[$name];
			}
		}

		return $layout;
	}

	// --------------------------------------------------------------------

	/**
	 * Load a layout
	 *
	 * @param	int
	 * @return	mixed
	 */
	function _load_layout($channel_id)
	{
		$layout_group = (is_numeric($this->input->get_post('layout_preview'))) ? $this->input->get_post('layout_preview') : $this->session->userdata('group_id');
		$layout_info = $this->member_model->get_group_layout($layout_group, $channel_id);

		if ( ! is_array($layout_info) OR ! count($layout_info))
		{
			return FALSE;
		}

		return $layout_info;
	}

	// --------------------------------------------------------------------

	/**
	 * Setup Tab Hierarchy
	 *
	 * @access	private
	 * @return	void
	 */
	private function _setup_tab_hierarchy($field_data, $layout_info)
	{
		// Do we have a layout? Woot, saves time!
		if (is_array($layout_info))
		{
			$hierarchy = array();

			foreach ($layout_info as $tab => $fields)
			{
				$this->_tab_labels[$tab] = isset($fields['_tab_label']) ? $fields['_tab_label'] : $tab;

				unset($fields['_tab_label']);
				$hierarchy[$tab] = array_keys($fields);
			}

			return $hierarchy;
		}

		// Otherwise apply the default

		$default = array(
			'publish'		=> array('title', 'url_title'),
			'date'			=> array('entry_date', 'expiration_date', 'comment_expiration_date'),
			'categories'	=> array('category'),
			'options'		=> array('new_channel', 'status', 'author', 'options'),
		);

		if (isset($this->_channel_data['enable_versioning'])
			&& $this->_channel_data['enable_versioning'] == 'y')
		{
			$default['revisions'] = array('revisions');
		}

		$default = array_merge($default, $this->_third_party_tabs());

		// Add predefined fields to their specific tabs
		foreach ($default as $tab => $fields)
		{
			$this->_tab_labels[$tab] = lang($tab);

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
		$options		= $this->_build_options_block($entry_data);
		$revisions		= $this->_build_revisions_block($entry_data);
		$third_party  	= $this->_build_third_party_blocks($entry_data);

		return array_merge(
			$field_data,
			$categories,
			$options,
			$revisions,
			$third_party
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Categories Block
	 */
	private function _build_categories_block($entry_data)
	{
		$this->load->library('publish');

		return $this->publish->build_categories_block(
			$this->_channel_data['cat_group'],
			$entry_data['entry_id'],
			(isset($entry_data['category'])) ? $entry_data['category'] : NULL,
			$this->_channel_data['deft_category']
		);
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

		$selected = (isset($entry_data['sticky']) && $entry_data['sticky'] == 'y') ? TRUE : FALSE;
		$selected = ($this->input->post('sticky') == 'y') ? TRUE : $selected;

		$checks = '<label>'.form_checkbox('sticky', 'y', set_value('sticky', $selected), 'class="checkbox"').' '.lang('sticky').'</label>';

		// Allow Comments?
		if (isset($this->cp->installed_modules['comment']) && $this->_channel_data['comment_system_enabled'] == 'y')
		{
			// Figure out selected categories
			if ( ! count($_POST) && ! $entry_data['entry_id'] && $this->_channel_data['deft_comments'])
			{
				$selected = ($this->_channel_data['deft_comments'] == 'y') ? 1 : '';
			}
			else
			{
				$selected = (isset($entry_data['allow_comments']) && $entry_data['allow_comments'] == 'y') ? TRUE : FALSE;
				$selected = ($this->input->post('allow_comments') == 'y') ? TRUE : $selected;
			}

			$checks .= '<label>'.form_checkbox('allow_comments', 'y', $selected, 'class="checkbox"').' '.lang('allow_comments').'</label>';

		}

		// Options Field
		$settings['options'] = array(
			'field_id'				=> 'options',
			'field_required'		=> 'n',
			'field_label'			=> lang('options'),
			'field_data'			=> '',
			'field_instructions'	=> '',
			'field_text_direction'	=> 'ltr',
			'field_pre_populate'	=> 'n',
			'field_type'			=> 'checkboxes',
			'field_list_items'		=> array(),
			'string_override'		=> $checks
		);


		$this->api_channel_fields->set_settings('options', $settings['options']);


		$settings['author'] 	= $this->_build_author_select($entry_data);
		$settings['new_channel']	= $this->_build_channel_select();
		$settings['status']		= $this->_build_status_select($entry_data);

		return $settings;
	}

	// --------------------------------------------------------------------

	/**
	 * Build Revisions Block
	 *
	 * @param 	array
	 * @return 	array
	 */
	private function _build_revisions_block($entry_data)
	{
		$settings = array();

		$version_id = $this->input->get('version_id');

		// Versioning isn't enabled, tab shouldn't be showing
		if ($this->_channel_data['enable_versioning'] == 'n')
		{
			return $settings;
		}

		$versioning = '';

		// We default versioning to true
		if ( ! isset($entry_data['versioning_enabled']))
		{
			$revisions_checked = TRUE;
		}
		else
		{
			$revisions_checked = ($entry_data['versioning_enabled'] == 'y') ? TRUE : FALSE;
		}

		if ($revisions_checked)
		{
			$versioning = lang('no_revisions_exist');

			$qry = $this->db->select('v.author_id, v.version_id, v.version_date, m.screen_name')
						->from('entry_versioning as v, members as m')
						->where('v.entry_id', $entry_data['entry_id'])
						->where('v.author_id = m.member_id', NULL, FALSE)
						->order_by('v.version_id', 'desc')
						->get();

			if ($qry->num_rows() > 0)
			{
				$this->load->library('table');

				$this->table->set_template(array(
					'table_open'		=> '<table class="mainTable" border="0" cellspacing="0" cellpadding="0">',
					'row_start'			=> '<tr class="even">',
					'row_alt_start'		=> '<tr class="odd">'
				));

				$this->table->set_heading(
					lang('revision'),
					lang('rev_date'),
					lang('rev_author'),
					lang('load_revision')
				);

				$i = 0;
				$j = $qry->num_rows();

				$link_base = BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$entry_data['channel_id'].AMP.'entry_id='.$entry_data['entry_id'].AMP;

				foreach ($qry->result() as $row)
				{
					$revlink = '<a class="revision_warning" href="'.$link_base.'version_id='.$row->version_id.AMP.'version_num='.$j.AMP.'use_autosave=n">'.lang('load_revision').'</a>';

					if ($version_id)
					{
						if ($row->version_id == $version_id)
						{
							$revlink = lang('current_rev');
						}
					}
					elseif ($i == 0)
					{
						$revlink = lang('current_rev');
					}

					$this->table->add_row(array(
							'<strong>' . lang('revision') . ' ' . $j . '</strong>',
							$this->localize->human_time($row->version_date),
							$row->screen_name,
							$revlink
						)
					);

					$j--;
					$i++;
				}

				$versioning = $this->table->generate();

				$outputjs = '
						var revision_target = "";

					$("<div id=\"revision_warning\">'.lang('revision_warning').'</div>").dialog({
						autoOpen: false,
						resizable: false,
						title: "'.lang('revisions').'",
						modal: true,
						position: "center",
						minHeight: "0px",
						buttons: {
							Cancel: function() {
							$(this).dialog("close");
							},
						"'.lang('load_revision').'": function() {
							location=revision_target;
						}
						}});

					$(".revision_warning").click( function (){
						$("#revision_warning").dialog("open");
						revision_target = $(this).attr("href");
						$(".ui-dialog-buttonpane button:eq(2)").focus();
						return false;
				});';

				$this->javascript->output(str_replace(array("\n", "\t"), '', $outputjs));
			}
		}

		$versioning .= '<p><label>'.form_checkbox('versioning_enabled', 'y', $revisions_checked, 'id="versioning_enabled"').' '.lang('versioning_enabled').'</label></p>';


		$settings['revisions'] = array(
			'field_id'				=> 'revisions',
			'field_label'			=> lang('revisions'),
			'field_name'			=> 'revisions',
			'field_required'		=> 'n',
			'field_type'			=> 'checkboxes',
			'field_text_direction'	=> 'ltr',
			'field_data'			=> '',
			'field_fmt'				=> 'text',
			'field_instructions'	=> '',
			'field_show_fmt'		=> '',
			'string_override'		=> $versioning
		);

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

		if ($qry->num_rows() > 0)
		{
			$menu_author_options[$author_id] = ($qry->row('screen_name')  == '')
				? $qry->row('username') : $qry->row('screen_name');
		}

		// Next we'll gather all the authors that are allowed to be in this list
		$author_list = $this->member_model->get_authors();

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
				if ($this->session->userdata('group_id') == 1 OR
					in_array($row['channel_id'], $this->_assigned_channels))
				{
					if (isset($_POST['new_channel']) && is_numeric($_POST['new_channel']) &&
						$_POST['new_channel'] == $row['channel_id'])
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
			'new_channel'	=> array(
				'field_id'				=> 'new_channel',
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

		$this->api_channel_fields->set_settings('new_channel', $settings['new_channel']);
		return $settings['new_channel'];
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
		}

		if ( ! isset($this->_channel_data['status_group']))
		{
			if ($this->session->userdata('group_id') == 1)
			{
				// if there is no status group assigned,
				// only Super Admins can create 'open' entries
				$menu_status_options['open'] = lang('open');
			}

			$menu_status_options['closed'] = lang('closed');
		}
		else
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

				// Were there no statuses?
				// If the current user is not allowed to submit any statuses we'll set the default to closed
				if ($no_status_flag === TRUE)
				{
					$menu_status_options['closed'] = lang('closed');
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
				'field_required'		=> 'y',
				'field_type'			=> 'date',
				'field_text_direction'	=> 'ltr',
				'field_data'			=> (isset($entry_data['entry_date'])) ? $entry_data['entry_date'] : '',
				'field_fmt'				=> 'text',
				'field_instructions'	=> '',
				'field_show_fmt'		=> 'n',
				'always_show_date'		=> 'y',
				'default_offset'		=> 0,
				'selected'				=> 'y',
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
				'default_offset'		=> 0,
				'selected'				=> 'y',
			)
		);

		// comment expiry here.
		if (isset($this->cp->installed_modules['comment']) && $this->_channel_data['comment_system_enabled'] == 'y')
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
				'default_offset'		=> $this->_channel_data['comment_expiration'] * 86400,
				'selected'				=> 'y',
			);
		}

		foreach ($deft_fields as $field_name => $f_data)
		{
			$this->api_channel_fields->set_settings($field_name, $f_data);
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

					// So 3rd party module tab fields get their data on autosave
					if (isset($entry_data[$val['field_id']]))
					{
						$settings[$val['field_id']]['field_data'] = $entry_data[$val['field_id']];
					}

					$this->_tab_labels[$tab]	= lang($tab);
					$this->_module_tabs[$tab][] = array(
													'id' 	=> $val['field_id'],
													'label'	=> $val['field_label']
													);

					$this->api_channel_fields->set_settings($val['field_id'], $val);
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

		$image_array = get_clickable_smileys($this->config->slash_item('emoticon_url'),
											 $field_name);
		$col_array = $this->table->make_columns($image_array, 8);
		$smilies = '<div class="smileyContent" style="display: none;">';
		$smilies .= $this->table->generate($col_array).'</div>';
		$this->table->clear();

		return $smilies;
	}

	// --------------------------------------------------------------------

	/**
	 * bookmarklet qstr decode
	 *
	 * @param 	string
	 */
	private function _bm_qstr_decode($str)
	{
		$str = str_replace("%20",	" ",		$str);
		$str = str_replace("%uFFA5", "&#8226;",	$str);
		$str = str_replace("%uFFCA", " ",		$str);
		$str = str_replace("%uFFC1", "-",		$str);
		$str = str_replace("%uFFC9", "...",		$str);
		$str = str_replace("%uFFD0", "-",		$str);
		$str = str_replace("%uFFD1", "-",		$str);
		$str = str_replace("%uFFD2", "\"",		$str);
		$str = str_replace("%uFFD3", "\"",		$str);
		$str = str_replace("%uFFD4", "\'",		$str);
		$str = str_replace("%uFFD5", "\'",		$str);

		$str = preg_replace_callback(
			"/\%u([0-9A-F]{4,4})/",
			function($matches)
			{
				return base_convert($matches[1], 16, 10);
			},
			$str
		);

		$str = $this->security->xss_clean(stripslashes(urldecode($str)));

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Setup Markitup Data
	 *
	 * @return 	void
	 */
	function _markitup()
	{
		$this->load->model('admin_model');

		$html_buttons = $this->admin_model->get_html_buttons($this->session->userdata('member_id'));
		$button_js = array();
		$has_image = FALSE;

		foreach ($html_buttons->result() as $button)
		{
			if (strpos($button->classname, 'btn_img') !== FALSE)
			{
				// images are handled differently because of the file browser
				// at least one image must be available for this to work
				$has_image = TRUE;

				if (count($this->_file_manager['file_list']))
				{
					$button_js[] = array(
						'name'			=> $button->tag_name,
						'key'			=> $button->accesskey,
						'replaceWith'	=> '',
						'className'		=> $button->classname.' id'.$button->id
					);
					$this->javascript->set_global('filebrowser.image_tag_'.$button->id, $button->tag_open);
				}
			}
			elseif(strpos($button->classname, 'markItUpSeparator') !== FALSE)
			{
				// separators are purely presentational
				$button_js[] = array('separator' => '---');
			}
			else
			{
				$button_js[] = array(
					'name'		=> $button->tag_name,
					'key'		=> strtoupper($button->accesskey),
					'openWith'	=> $button->tag_open,
					'closeWith'	=> $button->tag_close,
					'className'	=> $button->classname.' id'.$button->id
				);
			}
		}

		// Set global variable for optional file browser button
		$this->javascript->set_global(
			'filebrowser.image_tag',
			'<img src="[![Link:!:http://]!]" alt="[![Alternative text]!]" />'
		);

		$markItUp = $markItUp_writemode = array(
			'nameSpace'		=> "html",
			'onShiftEnter'	=> array('keepDefault' => FALSE, 'replaceWith' => "<br />\n"),
			'onCtrlEnter'	=> array('keepDefault' => FALSE, 'openWith' => "\n<p>", 'closeWith' => "</p>\n"),
			'markupSet'		=> $button_js,
		);

		// -------------------------------------------
		//	Hidden Configuration Variable
		//	- allow_textarea_tabs => Add tab preservation to all textareas or disable completely
		// -------------------------------------------

		if ($this->config->item('allow_textarea_tabs') == 'y')
		{
			$markItUp['onTab'] = array('keepDefault' => FALSE, 'replaceWith' => "\t");
			$markItUp_writemode['onTab'] = array('keepDefault' => FALSE, 'replaceWith' => "\t");
		}
		elseif ($this->config->item('allow_textarea_tabs') != 'n')
		{
			$markItUp_writemode['onTab'] = array('keepDefault' => FALSE, 'replaceWith' => "\t");
		}

		$markItUp_nobtns = $markItUp;
		unset($markItUp_nobtns['markupSet']);

		$this->cp->add_js_script(array("
			<script type=\"text/javascript\" charset=\"utf-8\">
			// <![CDATA[
			mySettings = ".json_encode($markItUp).";
			myNobuttonSettings = ".json_encode($markItUp_nobtns).";
			myWritemodeSettings = ".json_encode($markItUp_writemode).";
			// ]]>
			</script>

		"), FALSE);

		$this->javascript->set_global('publish.show_write_mode', ($this->_channel_data['show_button_cluster'] == 'y') ? TRUE : FALSE);
	}

	// --------------------------------------------------------------------

	/**
	 * Setup File List Actions
	 *
	 * @return 	void
	 */
	private function _setup_file_list()
	{
		$this->load->model('file_upload_preferences_model');

		$upload_directories = $this->file_upload_preferences_model->get_file_upload_preferences($this->session->userdata('group_id'));

		$this->_file_manager = array(
			'file_list'						=> array(),
			'upload_directories'			=> array(),
		);

		$fm_opts = array(
							'id', 'name', 'url', 'pre_format', 'post_format',
							'file_pre_format', 'file_post_format', 'properties',
							'file_properties'
						);

		foreach($upload_directories as $row)
		{
			$this->_file_manager['upload_directories'][$row['id']] = $row['name'];

			foreach($fm_opts as $prop)
			{
				$this->_file_manager['file_list'][$row['id']][$prop] = $row[$prop];
			}
		}
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file content_publish.php */
/* Location: ./system/expressionengine/controllers/cp/content_publish.php */
