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
 * ExpressionEngine Publishing Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Content_publish extends Controller { 

	var $SPELL				= FALSE;
	var $autosave_error		= FALSE;
	
	var $installed_modules	= FALSE;
	var $field_definitions	= array();
	var $required_fields	= array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Content_publish()
	{
		parent::Controller();

		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->installed_modules = $this->cp->get_installed_modules();

		$cp_theme = ($this->session->userdata['cp_theme'] == '') ? $this->config->item('cp_theme') : $this->session->userdata['cp_theme'];

		$this->theme_img_url = $this->config->item('theme_folder_url').'cp_themes/'.$cp_theme.'/images/';

		$this->assign_cat_parent = ($this->config->item('auto_assign_cat_parents') == 'n') ? FALSE : TRUE;
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
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// There needs to be a way to choose a channel.
		// This is an intermediary page.

		$this->javascript->compile();
		$this->channel_select_list();
	}

	// --------------------------------------------------------------------

	/**
	 * Channel Select List
	 *
	 * This function shows a list of available channels.
	 * This list will be displayed when a user clicks the
	 * "publish" link when more than one channel exist.
	 *
	 * @access	public
	 * @return	mixed
	 */
	function channel_select_list()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ($this->input->get_post('C') == 'content_publish')
		{
			$vars['instructions'] = $this->lang->line('select_channel_to_post_in');
			$title = $this->lang->line('publish');
			$vars['link_location'] = BASE.AMP.'C=content_publish'.AMP.'M=entry_form';
		}
		else
		{
			$vars['instructions'] = $this->lang->line('select_channel_to_edit');
			$title = $this->lang->line('edit');
			$vars['link_location'] = BASE.AMP.'C=content_edit'.AMP.'M=edit_entries';
		}

		$this->load->model('channel_model');
		$channels = $this->channel_model->get_channels();

		$vars['channels_exist'] = ($channels !== FALSE AND $channels->num_rows() == 0) ? FALSE : TRUE;

		$vars['assigned_channels'] = $this->session->userdata('assigned_channels');

		$this->cp->set_variable('cp_page_title', $title);
		
		// If there's only one publishable channel, no point in asking them which one
		// they want. Auto direct them to the publish form for the only channel available.
		if (count($vars['assigned_channels']) == 1)
		{
			if (isset($_GET['print_redirect']))
			{
				exit(str_replace(AMP, '&', BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.key($vars['assigned_channels'])));
			}
			
			$this->functions->redirect(BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.key($vars['assigned_channels']));
		}

		$this->javascript->compile();
		$this->load->view('content/channel_select_list', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Save publish layout
	 *
	 * @access	public
	 * @return	mixed
	 */
	function save_layout()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('api');
		$this->api->instantiate(array('channel_fields'));
		$this->output->enable_profiler(FALSE);
		$error = array();
		$valid_name_error = array();

		$member_group = $this->input->post('member_group');
		$channel_id = $this->input->post('channel_id');
		$json_tab_layout = $this->input->post('json_tab_layout');

		// A word about JSON decoding... it's more computationally expensive then would generally
		// be a good idea to use in EE, but the javascript data structure, combined with
		// potentially reuse in other scenarios, and the fact that it is only running when an
		// admin saves a layout makes it useful in this circumstance.

		// Not all servers will have json_decode() available but those that do should use it,
		// and we'll fall back to another solution for those who don't. The end goal is to get
		// this into a serialized string for layout purposes on next publish load.
		if ( ! function_exists('json_decode'))
		{
			$this->load->library('Services_json');
		}

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
			$message = $this->lang->line('layout_failure');
				
			if (count($error))
			{
				$message .= NBS.NBS.$this->lang->line('layout_failure_required').implode(', ', $error);
			}
				
			if (count($valid_name_error))
			{
				$message .= NBS.NBS.$this->lang->line('layout_failure_invalid_name').implode(', ', $valid_name_error);
			}
				
			$resp['message'] = $message; 

			$this->output->send_ajax_response($resp); exit;	
		}

		// make this into an array, insert_group_layout will serialize and save
		$layout_info = array_map(array($this, '_sort_publish_fields'), $clean_layout);
		
		if ($this->member_model->insert_group_layout($member_group, $channel_id, $layout_info))
		{
			$resp['messageType'] = 'success';
			$resp['message'] = $this->lang->line('layout_success');

			$this->output->send_ajax_response($resp); exit;	

		}
		else
		{
			$resp['messageType'] = 'failure';
			$resp['message'] = $this->lang->line('layout_failure');

			$this->output->send_ajax_response($resp); exit;	
			
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Sort Publish Fields
	 *
	 * Some browsers (read: chrome) sort JSON arrays by key automatically.
	 * So before we save our fields we need to reorder them according to
	 * their index parameter.
	 *
	 * @access private
	 */
	function _sort_publish_fields($fields)
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
	 * Channel "new entry" form
	 *
	 * This function displays the form used to submit, edit, or
	 * preview new channel entries with.
	 *
	 * @access public
	 */
	function entry_form()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->model('channel_entries_model');
		$this->load->model('channel_model');
		$this->load->model('status_model');
		$this->load->model('tools_model');
		$this->load->model('field_model');
		$this->load->model('admin_model');
		$this->load->model('template_model');
		$this->load->helper(array('form', 'url', 'html', 'snippets', 'custom_field', 'typography', 'smiley'));
		$this->load->library('api');
		$this->load->library('table');
		$this->load->library('spellcheck');
		$this->load->library('form_validation');
		$this->lang->loadfile('publish_tabs_custom');

		
		$this->api->instantiate(array('channel_categories', 'channel_entries', 'channel_fields'));

		$title						= ($this->input->get('title') != '') ? $this->bm_qstr_decode($this->input->get('title')) : '';
		$url_title					= '';
		$url_title_prefix			= '';
		$default_entry_title		= '';
		$status						= '';
		$expiration_date			= '';
		$comment_expiration_date	= '';
		$entry_date					= '';
		$sticky						= '';
		$field_data					= '';
		$allow_comments				= '';
		$catlist					= '';
		$author_id					= '';
		$version_id					= $this->input->get_post('version_id');
		$version_num				= $this->input->get_post('version_num');
		$dst_enabled				= $this->session->userdata('daylight_savings');
		$channel_id					= '';

		$which 						= 'new';
		$entry_id 					= ( ! $this->input->get_post('entry_id')) ? '' : $this->input->get_post('entry_id');
		$hidden 					= array();

		$convert_ascii				= ($this->config->item('auto_convert_high_ascii') == 'y') ? TRUE : FALSE; // Javascript stuff

		$vars = array(
			'message'				=> '',
			'cp_page_title'			=> $this->lang->line('new_entry'),								// modified below if this is an "edit"
			'BK'					=> ($this->input->get_post('BK')) ? AMP.'BK=1'.AMP.'Z=1' : '',
			'required_fields'		=> array('title', 'entry_date', 'url_title')
		);

		$vars['smileys_enabled'] = (isset($this->installed_modules['emoticon']) ? TRUE : FALSE);

		if ($this->config->item('site_pages') !== FALSE)
		{
			$this->lang->loadfile('pages');
		}

		//	We need to first determine which channel to post the entry into.
		$assigned_channels = $this->functions->fetch_assigned_channels();

		// if it's an edit, we just need the entry id and can figure out the rest
		if ($entry_id !== FALSE AND is_numeric($entry_id) AND $channel_id == '')
		{
			$which = 'edit';

			// If a "use_autosave" flag is present, then this entry has already come from the below page
			// and the author has already made their decision. Skip it. If not, check if there is saved
			// data available for this entry. Don't go for revisions.
			if ($this->input->get_post('use_autosave') != 'y' AND $this->input->get_post('use_autosave') != 'n')
			{
				$this->db->select('entry_id');
				$query = $this->db->get_where('channel_entries_autosave', array('original_entry_id'=>$this->input->get('entry_id')));

				if ($query->num_rows() != 0)
				{
					// Allow user to choose if they want the autosaved or original entry
					$this->javascript->compile();
					$this->cp->set_variable('cp_page_title', $this->lang->line('autosave_title'));
					return $this->load->view('content/autosave_options');
				}
			}

			// If the "use_autosave" flag is set, let's grab title and custom fields from autosave tables
			if ($this->input->get_post('use_autosave') == 'y')
			{
				$this->db->select('channel_id');
				$this->db->where('entry_id', $entry_id);
				$query = $this->db->get('channel_entries_autosave');

				if ($query->num_rows() == 1)
				{
					$autosave_channel_id = $query->row('channel_id');
				}
			}
			// end autosave code

			$this->db->select('channel_id');
			$this->db->where('entry_id', $entry_id);
			$query = $this->db->get('channel_titles');
			
			if ($query->num_rows() == 1)
			{
				$channel_id = $query->row('channel_id');
			}
		}

		if ($channel_id == '' AND ! ($channel_id = $this->input->get_post('channel_id')))
		{
			if (count($assigned_channels) == 1)
			{
				$channel_id = $assigned_channels['0'];
			}
			else
			{
				$query = $this->channel_model->get_channel_info();

				if ($query->num_rows() == 1)
				{
					$channel_id = $query->row('channel_id');
				}
				else
				{
					show_error($this->lang->line('unauthorized_access'));
				}
			}
		}

		if ( ! is_numeric($channel_id))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		//	Security check
		if ( ! in_array($channel_id, $assigned_channels))
		{
			$this->session->set_flashdata('message_failure', $this->lang->line('unauthorized_for_this_channel'));
			$this->functions->redirect(BASE.AMP.'C=content_publish'.AMP.'M=index');
		}

		//	Fetch channel preferences

		$query = $this->channel_model->get_channel_info($channel_id);

		if ($query->num_rows() == 0)
		{
			show_error($this->lang->line('no_channel_exists'));
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

		// Sets 'new' / 'edit' in the global json array.  Neat, eh?
		$this->javascript->set_global('publish.which', $which);
		$this->javascript->set_global('lang.loading', $this->lang->line('loading'));

		extract($row);

		//	Fetch Revision if Necessary

		$show_revision_cluster = ($enable_versioning == 'y') ? 'y' : 'n';

		if ($which == 'new')
		{
			$vars['versioning_enabled'] = ($enable_versioning == 'y') ? 'y' : 'n';
		}
		else
		{
			$vars['versioning_enabled'] = (isset($_POST['versioning_enabled'])) ? 'y' : 'n';
		}

		if (is_numeric($version_id))
		{
			$this->db->select('version_data');
			$this->db->where('entry_id', $entry_id);
			$this->db->where('version_id', $version_id);
			$revquery = $this->db->get('entry_versioning');

			if ($revquery->num_rows() == 1)
			{
				// Load the string helper
				$this->load->helper('string');

				$_POST = @unserialize($revquery->row('version_data'));
				$_POST['entry_id'] = $entry_id;
			}
			
			unset($revquery);
		}


		// --------------------------------------------------------------------
		// The $which variable determines what the page should show:
		//	If $which = 'new' we'll show a blank "new entry" page
		//	If $which = "edit", we are editing an already existing entry.
		// --------------------------------------------------------------------

		if ($which == 'new')
		{
			$title		= $default_entry_title;
			$url_title	= $url_title_prefix;
		}
		elseif ($which == 'edit')
		{
			if ( ! $entry_id)
			{
				show_error($this->lang->line('unauthorized_access'));
			}

			$vars['cp_page_title'] = $this->lang->line('edit_entry');
			
			if ($this->input->get_post('use_autosave') == 'y')
			{
				$result = $this->channel_entries_model->get_entry($entry_id, $channel_id, TRUE);
			}
			else
			{
				$result = $this->channel_entries_model->get_entry($entry_id, $channel_id);
			}

			if ($result->num_rows() == 0)
			{
				show_error($this->lang->line('no_channel_exists'));
			}

			$resrow = $result->row_array();

			if ($this->input->get_post('use_autosave') == 'y')
			{
				$res_entry_data = unserialize($resrow['entry_data']);

				// overwrite and add to this array with entry_data
				foreach ($res_entry_data as $k => $v)
				{
					$resrow[$k] = $v;
				}
				
				unset($resrow['entry_data']);
			
				//  This does not work
				$_POST = $resrow;
			}

			if ($resrow['author_id'] != $this->session->userdata('member_id'))
			{
				if ( ! $this->cp->allowed_group('can_edit_other_entries'))
				{
					show_error($this->lang->line('unauthorized_access'));
				}
			}

			if ($enable_versioning == 'y')
			{
				$this->javascript->set_global('publish.versioning_enabled', $resrow['versioning_enabled']);
				$vars['versioning_enabled'] = $resrow['versioning_enabled'];
			}

			// If there's a live look template, show the live look option via ee_notice
			if ($live_look_template != 0)
			{
				$this->db->select('template_groups.group_name, templates.template_name');
				$this->db->from('template_groups, templates');
				$this->db->where('exp_template_groups.group_id = exp_templates.group_id', NULL, FALSE);
				$this->db->where('templates.template_id', $live_look_template);

				$temp_res = $this->db->get();

				if ($temp_res->num_rows() == 1)
				{
					$qm = ($this->config->item('force_query_string') == 'y') ? '' : '?';

					$view_link = '<a href='. $this->functions->fetch_site_index().$qm.'URL='.$this->functions->create_url($temp_res->row('group_name').'/'.$temp_res->row('template_name').'/'.$entry_id).
					" rel='external' >".$this->lang->line('live_view').'</a>';

					if ($this->input->get('revision') == 'saved')
					{
						$view_link .= '<br />'.$this->lang->line('revision_saved');
					}

					$this->javascript->output('
					
						var publishForm = $("#publishForm");

						_destroy_live_view = function() {
							publishForm.trigger("destroy_live_view");
						}

						publishForm.find("input:text, textarea").focus(_destroy_live_view);
						publishForm.find("input:radio, input:checkbox").click(_destroy_live_view);
						publishForm.find("input:hidden, input:file, select").change(_destroy_live_view);

						function view_live_look() {
							$.ee_notice("'.$view_link.'",  {duration:0});
							publishForm.one("destroy_live_view", $.ee_notice.destroy);
						}

						view_live_look();
						');
				}
			}

			// -------------------------------------------
			// 'publish_form_entry_data' hook.
			//  - Modify entry's data
			//  - Added: 1.4.1
				if ($this->extensions->active_hook('publish_form_entry_data') === TRUE)
				{
					$resrow = $this->extensions->call('publish_form_entry_data', $result->row_array());
				}
			// -------------------------------------------

			extract($resrow);
		}

		$vars['cp_page_title'] .= ' - '.$channel_title;

		// if this is a layout group preview, we'll use it, otherwise, we'll use the author's group_id
		$layout_group	= (is_numeric($this->input->get_post('layout_preview'))) ? $this->input->get_post('layout_preview') : $this->session->userdata('group_id');
		$layout_info	= $this->member_model->get_group_layout($layout_group, $channel_id);

		$vars['form_hidden'] = array(
			'channel_id'		=> $channel_id,
			'f_group'			=> $field_group,
			'entry_id'			=> $entry_id
		);

		foreach($hidden as $key => $value)
		{
			$vars['form_hidden'][$key] = $value;
		}

		// Create channel menu
		$vars = array_merge_recursive($vars, $this->_build_channel_vars($which, $status_group, $cat_group, $field_group, $assigned_channels, $channel_id, $channel_title));

		// Create status menu

		$vars = array_merge_recursive($vars, $this->_build_status_vars($status_group, $status, $deft_status));

		// Create author menu
		$vars = array_merge_recursive($vars, $this->_build_author_vars($author_id, $channel_id));

		$this->cp->add_js_script(array(
		        'ui'        => array('datepicker', 'resizable', 'draggable', 'droppable'),
		        'plugin'    => array('markitup', 'toolbox.expose', 'overlay'),
				'file'		=> array('json2', 'cp/publish')
		    )
		);		
		
		//	HTML formatting buttons
		$vars['show_button_cluster'] = $show_button_cluster;

		// Fetch Custom Fields
		$field_query = $this->channel_model->get_channel_fields($field_group);

		// pass field info into view
		$vars['fields'] = $field_query;

		// Upload Directories

		$upload_directories = $this->tools_model->get_upload_preferences($this->session->userdata('group_id'));
		$vars['file_manager_directories'] = $upload_directories;

		$vars['file_list'] = array();
		$vars['upload_directories'] = array();

		foreach($upload_directories->result() as $row)
		{
			$vars['upload_directories'][$row->id] = $row->name;

			foreach(array('id', 'name', 'url', 'pre_format', 'post_format', 'file_pre_format', 'file_post_format', 'properties', 'file_properties') as $prop)
			{
				$vars['file_list'][$row->id][$prop] = $row->$prop;
			}
		}
		
		$this->javascript->set_global('upload_directories', $vars['file_list']);

		$html_buttons = $this->admin_model->get_html_buttons($this->session->userdata('member_id'));
		$button_js = array();

		foreach ($html_buttons->result() as $button)
		{
			if (strpos($button->classname, 'btn_img') !== FALSE)
			{
				// images are handled differently because of the file browser
				// at least one image must be available for this to work
				if (count($vars['file_list']) > 0)
				{
					$button_js[] = array('name' => $button->tag_name, 'key' => $button->accesskey, 'replaceWith' => '', 'className' => $button->classname);
					$this->javascript->set_global('filebrowser.image_tag', $button->tag_open);
				}
			}
			elseif(strpos($button->classname, 'markItUpSeparator') !== FALSE)
			{
				// separators are purely presentational
				$button_js[] = array('separator' => '---');
			}
			else
			{
				$button_js[] = array('name' => $button->tag_name, 'key' => $button->accesskey, 'openWith' => $button->tag_open, 'closeWith' => $button->tag_close, 'className' => $button->classname);
			}
		}
		
		
		$markItUp = $markItUp_writemode = array(
			'nameSpace'		=> "html",
			'onShiftEnter'	=> array('keepDefault' => FALSE, 'replaceWith' => "<br />\n"),
			'onCtrlEnter'	=> array('keepDefault' => FALSE, 'openWith' => "\n<p>", 'closeWith' => "</p>\n"),
			'markupSet'		=> $button_js,
		);
		
		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- allow_textarea_tabs => Add tab preservation to all textareas or disable completely
		/* -------------------------------------------*/
		
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
			mySettings = ".$this->javascript->generate_json($markItUp, TRUE).";
			myNobuttonSettings = ".$this->javascript->generate_json($markItUp_nobtns, TRUE).";
			myWritemodeSettings = ".$this->javascript->generate_json($markItUp_writemode, TRUE).";
			// ]]>
			</script>
			
		"), FALSE);

		$this->javascript->set_global('publish.show_write_mode', ($show_button_cluster == 'y') ? TRUE : FALSE);

		// -------------------------------------------
		//	Publish Page Title Focus - makes the title field gain focus when the page is loaded
		//
		//	Hidden Configuration Variable - publish_page_title_focus => Set focus to the tile? (y/n)
		if ($which != 'edit' && $this->config->item('publish_page_title_focus') !== 'n')
		{
			$this->javascript->set_global('publish.title_focus', TRUE);
		}
		else
		{
			$this->javascript->set_global('publish.title_focus', FALSE);
		}

		// -------------------------------------------

		$fmt = ($this->session->userdata['time_format'] != '') ? $this->session->userdata['time_format'] : $this->config->item('time_format');
		$this->javascript->set_global('date.format', $fmt);

		// --------------------------------
		//	Options Cluster
		// --------------------------------
		if ($allow_comments == '' AND $which == 'new')
		{
			$allow_comments = $deft_comments;
		}

		// some view options, set them all to FALSE for now, they'll be
		// changed below
		$vars['show_comments']	= FALSE;
		$vars['show_sticky']	= FALSE;
		$vars['show_dst']		= FALSE;

		//	"Sticky" checkbox
		$vars['show_sticky'] = TRUE;
		$vars['sticky_data'] = array(
									  'name'		=> 'sticky',
									  'id'			=> 'sticky',
									  'value'		=> 'y',
									  'checked'		=> ($sticky == 'y') ? TRUE : FALSE
									);

		//	"Allow comments"?
		if ( ! isset($this->installed_modules['comment']))
		{
			$vars['form_hidden']['allow_comments'] = $allow_comments;
		}
		elseif ($comment_system_enabled == 'y')
		{
			$vars['show_comments'] = TRUE;
			$vars['comments_data'] = array(
											  'name'		=> 'allow_comments',
											  'id'			=> 'allow_comments',
											  'checked'		=> ($allow_comments == 'y') ? TRUE : FALSE,
											  'value'		=> 'y'
											);
		}
		
		
		// Validation failed?
		if (is_array($_POST) && count($_POST) && ! isset($_POST['dst_enabled']))
		{
			$dst_enabled = 'n';
		}
		
		$vars['dst_enabled'] = $dst_enabled;

		//	"Daylight Saving Time" checkbox
		if ($this->config->item('honor_entry_dst') == 'y')
		{
			$vars['show_dst'] = TRUE;
			$vars['dst_data'] = array(
									  'name'		=> 'dst_enabled',
									  'id'			=> 'dst_enabled',
									  'checked'		=> ($dst_enabled == 'y') ? TRUE : FALSE,
									  'value'		=> 'y'
									);
		}
		
		
		
		$vars['publish_tabs']['publish'] = array();
		
		// Entry date
		
		$settings = array(
					'field_id'				=> 'entry_date',
					'field_label'			=> $this->lang->line('entry_date'),
					'field_required'		=> 'n',
					'field_type'			=> 'date',
					'field_text_direction'	=> 'ltr',
					'field_data'			=> $entry_date,
					'field_fmt'				=> 'text',
					'field_instructions'	=> '',
					'field_show_fmt'		=> 'n',
					'default_offset'		=> 0,
					'selected'				=> 'y',
					'dst_enabled'			=> $dst_enabled
		);
		
		$this->api_channel_fields->set_settings('entry_date', $settings);
		
		$rules = 'call_field_validation['.$settings['field_id'].']';
		$this->form_validation->set_rules($settings['field_id'], $settings['field_label'], $rules);
		
		// Expiration Date
		
		$settings = array(
					'field_id'				=> 'expiration_date',
					'field_label'			=> $this->lang->line('expiration_date'),
					'field_required'		=> 'n',
					'field_type'			=> 'date',
					'field_text_direction'	=> 'ltr',
					'field_data'			=> $expiration_date,
					'field_fmt'				=> 'text',
					'field_instructions'	=> '',
					'field_show_fmt'		=> 'n',
					'selected'				=> 'y',
					'dst_enabled'			=> $dst_enabled
		);
		
		$this->api_channel_fields->set_settings('expiration_date', $settings);
		
		$rules = 'call_field_validation['.$settings['field_id'].']';
		$this->form_validation->set_rules($settings['field_id'], $settings['field_label'], $rules);
		
		// Comment Expiration Date

		if (isset($this->installed_modules['comment']) && $vars['show_comments'])
		{
			$settings = array(
						'field_id'				=> 'comment_expiration_date',
						'field_label'			=> $this->lang->line('comment_expiration_date'),
						'field_required'		=> 'n',
						'field_type'			=> 'date',
						'field_text_direction'	=> 'ltr',
						'field_data'			=> $comment_expiration_date,
						'field_fmt'				=> 'text',
						'field_instructions'	=> '',
						'field_show_fmt'		=> 'n',
						'selected'				=> 'y',
						'dst_enabled'			=> $dst_enabled
			);

			$this->api_channel_fields->set_settings('comment_expiration_date', $settings);

			$rules = 'call_field_validation['.$settings['field_id'].']';
			$this->form_validation->set_rules($settings['field_id'], $settings['field_label'], $rules);
		}


		// ----------------------------------------------
		//	CATEGORY BLOCK
		// ----------------------------------------------

		if ($which == 'edit' && ! isset($_POST['category']))
		{
			$this->db->select('c.cat_name, p.*');
			$this->db->from('categories AS c, category_posts AS p');
			$this->db->where_in('c.group_id', explode('|', $cat_group));
			$this->db->where('p.entry_id', $entry_id);
			$this->db->where('c.cat_id = p.cat_id', NULL, FALSE);
			
			$query = $this->db->get();

			foreach ($query->result_array() as $row)
			{
				$catlist[$row['cat_id']] = $row['cat_id'];
			}
		}
		else
		{
			if (isset($_POST['category']) AND is_array($_POST['category']))
			{
				foreach ($_POST['category'] as $val)
				{
					$catlist[$val] = $val;
				}
			}
		}

		$vars['categories'] = array();
		
		$edit_categories_link = FALSE; //start off as false, meaning user does not have privs

		// Normal Category Display
			
		$catlist = ($which == 'new' && $deft_category != '') ? $deft_category : $catlist;
		
		$this->api_channel_categories->category_tree($cat_group, $catlist);

		if (count($this->api_channel_categories->categories) > 0)
		{  
			// add categories in again, over-ride setting above
			foreach ($this->api_channel_categories->categories as $val)
			{
				$vars['categories'][$val['3']][] = $val;
			}
		}

		$link_info = $this->api_channel_categories->fetch_allowed_category_groups($cat_group);

		$links = array();

		if ($link_info !== FALSE)
		{
			foreach ($link_info as $val)
			{
				$links[] = array('url' => BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP.'group_id='.$val['group_id'],
					'group_name' => $val['group_name']);

			}
		}
		
		// One more check to see if the user can edit categories.  
		// If so, we give them the link on the publish page.
		// Peek at fetch_allowed_category_groups, and it will all make sense.
		if ($this->session->userdata('can_edit_categories') == 'y')
		{
			$edit_categories_link = $links;			
		}
	
		$this->_define_category_fields($vars['categories'], $edit_categories_link, $cat_group);

		// ----------------------------------------------
		// PING BLOCK
		// ----------------------------------------------

		$vars['ping_servers'] = $this->fetch_ping_servers( ($which == 'edit') ? $author_id : '', isset($entry_id) ? $entry_id : '', $which, TRUE);
		$this->_define_ping_fields($vars);

		// ----------------------------------------------
		// REVISIONS BLOCK
		// ----------------------------------------------

		$vars['show_revision_cluster'] = $show_revision_cluster;
		$vars['revs_exist'] = FALSE;
		$versioning = '';

		if ($show_revision_cluster == 'y')
		{
			if (is_numeric($entry_id))
			{
				$this->db->select('v.author_id, v.version_id, v.version_date, m.screen_name');
				$this->db->from('entry_versioning AS v, members AS m');
				$this->db->where('v.entry_id', $entry_id);
				$this->db->where('v.author_id = m.member_id', NULL, FALSE);
				$this->db->order_by('v.version_id', 'desc');
				
				$revquery = $this->db->get();

				if ($revquery->num_rows() > 0)
				{
					$vars['revs_exist'] = TRUE;

					$this->table->set_template(array('table_open'=>'<table class="mainTable" border="0" cellspacing="0" cellpadding="0">'));
					$this->table->set_heading(
						$this->lang->line('revision'), 
						$this->lang->line('rev_date'), 
						$this->lang->line('rev_author'), 
						$this->lang->line('load_revision')
					);

					$i = 0;
					$j = $revquery->num_rows;

					foreach($revquery->result_array() as $row)
					{
						$revlink = '<a class="revision_warning" href="'.BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id.AMP.'version_id='.$row['version_id'].AMP.'version_num='.$j.AMP.'use_autosave=n">'.$this->lang->line('load_revision').'</a>';

						if ($version_id !== FALSE)
						{
							if ($row['version_id'] == $version_id)
							{
								$revlink = $this->lang->line('current_rev');
							}
						}
						elseif ($which == 'edit' AND $i == 0)
						{
							$revlink = $this->lang->line('current_rev');
						}

						$this->table->add_row(
							array('data' => '<b>'.$this->lang->line('revision').' '.$j.'</b>'),
							array('data' => $this->localize->set_human_time($row['version_date'])),
							array('data' => $row['screen_name']),
							array('data' => $revlink)
						);

						$j--;
						$i++;
					} // End foreach

					$versioning = $this->table->generate();
					// $("<div id=\"revision_warning\">'.$this->lang->line('revision_warning').'</div>").dialog({
					$this->javascript->output('
						var revision_target = "";
						$("<div id=\"revision_warning\">'.$this->lang->line('revision_warning').'</div>").dialog({
							autoOpen: false,
							resizable: false,
							title: "'.$this->lang->line('revisions').'",
							modal: true,
							position: "center",
							minHeight: "0px", // fix display bug, where the height of the dialog is too big
							buttons: {
								Cancel: function() {
									$(this).dialog("close");
								},
								"'.$this->lang->line('load_revision').'": function() {
									location=revision_target;
								}
							}
						});

						 $(".revision_warning").click(function(){
						 	$("#revision_warning").dialog("open");
						 	revision_target = $(this).attr("href");
						 	$(".ui-dialog-buttonpane button:eq(2)").focus();
						 	return false;
						 });
					');
				}
			}
		}


		//----------------------------------------------
		//	FORUM BLOCK
		// ---------------------------------------------

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
							$fquery2 = $this->db->query("SELECT forum_topic_id FROM exp_channel_titles WHERE entry_id = '{$entry_id}'");
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
					$fquery = $this->db->query("SELECT forum_topic_id FROM exp_channel_titles WHERE entry_id = '{$entry_id}'");
					$forum_topic_id = $fquery->row('forum_topic_id');
				}
				
				$vars['forum_topic_id_descp']	= $this->lang->line('forum_topic_id_info');
				$vars['forum_topic_id'] = $forum_topic_id;
				
				if ($forum_topic_id != 0)
				{
					$fquery = $this->db->query("SELECT title FROM exp_forum_topics WHERE topic_id = '{$forum_topic_id}'");

					$ftitle = ($fquery->num_rows() == 0) ? '' : $fquery->row('title');
					$vars['forum_title'] = $ftitle;
				}
			}
		}


		// ----------------------------------------------
		//	PAGES BLOCK
		// ----------------------------------------------

		$vars['pages_uri']	= '';
		$vars['pages_dropdown'] = array();
		$vars['pages_dropdown_selected'] = '';
		$pages = FALSE;

		if (isset($this->installed_modules['pages']))
		{
			$pages = $this->config->item('site_pages');
			$pages_uri = '';
			$pages_template_id = '';

			if ($entry_id != '' && isset($pages[$this->config->item('site_id')]['uris'][$entry_id]))
			{
				$pages_uri			= $pages[$this->config->item('site_id')]['uris'][$entry_id];
				$pages_template_id	= $pages[$this->config->item('site_id')]['templates'][$entry_id];
			}
			else
			{
				$this->db->select('configuration_value');
				$this->db->where('configuration_name', 'template_channel_'.$channel_id);
				$this->db->where('site_id', $this->config->item('site_id'));
				$query = $this->db->get('pages_configuration');
				
				if ($query->num_rows() > 0)
				{
					$pages_template_id = $query->row('configuration_value');
				}
			}

			$pages_uri			= ( ! $this->input->get_post('pages_uri'))			 ? $pages_uri : $this->input->get_post('pages_uri');
			$pages_template_id	= ( ! $this->input->get_post('pages_template_id')) ? $pages_template_id : $this->input->get_post('pages_template_id');
			
			// A few overwrites here if the pages_uri is empty
			if ($pages_uri == '')
			{
				$this->javascript->set_global('publish.pages.pagesUri', '/example/pages/uri/');
			}
			else
			{
				$this->javascript->set_global('publish.pages.pageUri', $pages_uri);
			}

			$vars['pages_uri']	= $pages_uri;
			$vars['pages_dropdown_selected'] = $pages_template_id;

			$tquery = $this->template_model->get_templates($this->config->item('site_id'));

			if ($tquery->num_rows())
			{
				foreach ($tquery->result() as $template)
				{
					$vars['pages_dropdown'][$template->group_name][$template->template_id] = $template->template_name;
				}

				// pages_uri options
				$settings = array(
							'field_id'				=> 'pages_uri',
							'field_label'			=> $this->lang->line('pages_uri'),
							'field_required'		=> 'n',
							'field_data'			=> $vars['pages_uri'],
							'field_fmt'				=> 'text',
							'field_instructions'	=> '',
							'field_show_fmt'		=> 'n',
							'field_text_direction'	=> 'ltr',
							'field_type'			=> 'text',
							'field_maxl'			=> 100
				);

				$this->api_channel_fields->set_settings('pages_uri', $settings);

				$rules = 'call_field_validation['.$settings['field_id'].']';
				$this->form_validation->set_rules($settings['field_id'], $settings['field_label'], $rules);


				$settings = array(
					'field_id'				=> 'pages_template_id',
					'field_label'			=> $this->lang->line('template'),
					'field_required' 		=> 'n',
					'field_data'			=> $vars['pages_dropdown_selected'],
					'field_list_items'		=> $vars['pages_dropdown'],
					'options'				=> $vars['pages_dropdown'],
					'selected'				=> $vars['pages_dropdown_selected'],
					'field_fmt'				=> 'text',
					'field_instructions' 	=> '',
					'field_show_fmt'		=> 'n',
					'field_pre_populate'	=> 'n',
					'field_text_direction'	=> 'ltr',
					'field_type' 			=> 'select'
				);


				$this->api_channel_fields->set_settings('pages_template_id', $settings);

				$rules = 'call_field_validation['.$settings['field_id'].']';
				$this->form_validation->set_rules($settings['field_id'], $settings['field_label'], $rules);
			}
			else
			{
				$vars['publish_tabs']['pages']['pages_uri'] = array(
								'visible'		=> TRUE,
								'collapse'		=> FALSE,
								'html_buttons'	=> TRUE,
								'is_hidden'		=> FALSE,
								'width'			=> '100%'
				);

				$this->field_definitions['pages_uri'] = array(
					'string_override'		=> $this->lang->line('no_templates'),
					'field_id'				=> 'pages_uri',
					'field_label'			=> $this->lang->line('pages_uri'),
					'field_name'			=> 'pages_uri',
					'field_required'		=> 'n',
					'field_type'			=> 'text',
					'field_text_direction'	=> 'ltr',
					'field_data'			=> '',
					'field_fmt'				=> 'text',
					'field_instructions'	=> '',
					'field_show_fmt'		=> 'n'
				);
			}

		}
		
		// ----------------------------------------------
		//	Custom Blocks
		// ----------------------------------------------		

		$module_data = $this->api_channel_fields->get_module_fields($channel_id, $entry_id);
		
		$module_tabs = array();
	
		if ($module_data && is_array($module_data))
		{
			foreach ($module_data as $tab => $v)
			{
				foreach ($v as $val)
				{			
					$module_tabs[$tab][] = $val['field_id'];

					$this->api_channel_fields->set_settings($val['field_id'], $val);

					$rules = 'call_field_validation['.$val['field_id'].']';
					$this->form_validation->set_rules($val['field_id'], $val['field_label'], $rules);
				}
			}
		}

		// Title options
		$settings = array(
					'field_id'				=> 'title',
					'field_label'			=> lang('title'),
					'field_required'		=> 'y',
					'field_data'			=> ($this->input->post('title') == '') ? $this->bm_qstr_decode($title) : $this->input->post('title'),
					'field_fmt'				=> 'xhtml',
					'field_instructions'	=> '',
					'field_show_fmt'		=> 'n',
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'text',
					'field_maxl'			=> 100
		);

		$this->api_channel_fields->set_settings('title', $settings);
		
		$rules = 'required|call_field_validation['.$settings['field_id'].']';
		$this->form_validation->set_rules($settings['field_id'], $settings['field_label'], $rules);

		$settings = array(
					'field_id'				=> 'url_title',
					'field_label'			=> lang('url_title'),
					'field_required'		=> 'n',
					'field_data'			=> ($this->input->post('url_title') == '') ? $url_title : $this->input->post('url_title'),
					'field_fmt'				=> 'xhtml',
					'field_instructions'	=> '',
					'field_show_fmt'		=> 'n',
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'text',
					'field_maxl'			=> 75
		);
			
		$this->api_channel_fields->set_settings('url_title', $settings);
		
		$rules = 'call_field_validation['.$settings['field_id'].']';
		$this->form_validation->set_rules($settings['field_id'], $settings['field_label'], $rules);

		$get_format = array();
		
		$markitup_buttons = array();

		foreach ($field_query->result_array() as $row)
		{
			$field_data = '';
			$field_fmt = '';
			$field_dt = '';
			
			if ($which == 'edit')
			{
				$field_data = ( ! isset( $resrow['field_id_'.$row['field_id']])) ? '' : $resrow['field_id_'.$row['field_id']];
				$field_fmt	= ( ! isset( $resrow['field_ft_'.$row['field_id']] )) ? $row['field_fmt'] : $resrow['field_ft_'.$row['field_id']];
				$field_dt	= ( ! isset( $resrow['field_dt_'.$row['field_id']] )) ? 'y' : $resrow['field_dt_'.$row['field_id']];

			}
			elseif ($field_data = $this->input->get('field_id_'.$row['field_id'])) // Is this coming from a bookmarklet?
			{
				$field_data = $this->bm_qstr_decode( $this->input->get('tb_url')."\n\n".$field_data );
				$field_fmt	= $row['field_fmt'];
			}
			else // New entry- use the default setting
			{
				$field_fmt	= $row['field_fmt'];
			}

			// Settings that need to be prepped			
			$settings = array(
				'field_instructions'	=> trim($row['field_instructions']),
				'field_text_direction'	=> ($row['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr',
				'field_fmt'				=> $field_fmt,
				'field_dt'				=> $field_dt,
				'field_data'			=> $field_data,
				'field_name'			=> 'field_id_'.$row['field_id'],
				'dst_enabled'			=> $dst_enabled
			);
			
			$ft_settings = array();
			
			if (isset($row['field_settings']) && strlen($row['field_settings']))
			{
				$ft_settings = unserialize(base64_decode($row['field_settings']));
			}

			$settings = array_merge($row, $settings, $ft_settings);
			
			$rules = 'call_field_validation['.$row['field_id'].']';
			
			if ($row['field_required'] == 'y' && $row['field_type'] != 'file')
			{
				$rules = 'required|'.$rules;
			}

			$this->api_channel_fields->set_settings($row['field_id'], $settings);
			$this->form_validation->set_rules('field_id_'.$row['field_id'], $row['field_label'], $rules);

			$set = $this->api_channel_fields->get_settings($row['field_id']);

			if ($show_button_cluster == 'y' && isset($set['field_show_formatting_btns']) && $set['field_show_formatting_btns'] == 'y')
			{
				$markitup_buttons['fields']['field_id_'.$row['field_id']] = $row['field_id'];
			}

			// Formatting
			if ($row['field_show_fmt'] == 'n')
			{
				$vars['form_hidden']['field_ft_'.$row['field_id']] = $field_fmt;
			}
			else
			{
				$get_format[] = $row['field_id'];
			}
		}
		
		$this->javascript->set_global('publish.markitup', $markitup_buttons);
		

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
						$name = $this->lang->line('auto_br');
					}
					elseif ($name == 'Xhtml')
					{
						$name = $this->lang->line('xhtml');
					}
					
					$this->api_channel_fields->settings[$format['field_id']]['field_fmt_options'][$format['field_fmt']] = $name;
				}
			}
		}

		// These resizable handles need to be initialized before _static_publish_script() is
		// run, but should only be available to admins.
		if ($this->session->userdata('group_id') == 1)
		{
			$this->_static_publish_admin(); // where a great deal of the static js for this page is saved
		}

		$this->_static_publish_non_admin(); // where a great deal of the static js for this page is saved

		if ($show_button_cluster == 'y')
		{
			$this->_static_publish_formatting_buttons();
		}

		$vars['form_additional']['id'] = 'publishForm';
		
		// get all member groups with cp access for the layout list
		$vars['member_groups'] = $this->member_model->get_member_groups(array('can_access_admin'), array('can_access_publish'=>'y'));

		// If fields have been hidden by an admin, then they won't get "drawn" into the document, however without
		// them, they can't be moved at a later time into a tab. What we'll do is store all revealed fields in an
		// array, and then afterwards, compare it to all known fields. Any fields not revealed will be added as
		// hidden elements
		$all_fields = array();
		$revealed_fields = array();

		$vars['unrevealed_fields'] = array(); // used in view to build hidden fields

		// Layout
		// If a custom layout has been defined, then we'll use it, but if not, we'll build the layout defaults

		if (count($layout_info) > 0)
		{
			$vars['publish_tabs'] = $layout_info; // Custom Layout construction
			
			foreach($vars['publish_tabs'] as $tab => $val)
			{
				foreach($val as $key => $custom)
				{
					$revealed_fields[] .= $key;

					// Override forum tab display if it's an edit
					if ($hide_forum_fields)
					{
						if ($key == 'forum_title')
						{
							$custom['visible'] = 'false';
						}
						elseif ($key == 'forum_body')
						{
							$custom['visible'] = 'false';
						}
						elseif ($key == 'forum_id')
						{
							$custom['visible'] = 'false';
						}						
					}
					
					// set up hidden fields (not visible)
					if ($custom['visible'] === FALSE OR $custom['visible'] === 'false')
					{
						$name = (isset($this->field_definitions[$key]['field_id'])) ? $this->field_definitions[$key]['field_id'] : $key;
						
						// this can happen after the form loads...
						$this->javascript->output('$("#remove_field_'.$name.'").children().attr("src", "'.$this->cp->cp_theme_url.'images/closed_eye.png");');
					}
					// set up collapsed fields
					if ($custom['collapse'] === 'true' OR $custom['collapse'] === TRUE)
					{
						// Catch the obvious one with .js_hide
						$vars['publish_tabs'][$tab][$key]['is_hidden'] = TRUE;
						
						$this->javascript->output('
							$("#hold_field_'.$key.' .ui-resizable-handle").hide();
							$("#hold_field_'.$key.' .field_collapse").attr("src", "'.$this->cp->cp_theme_url . 'images/field_collapse.png");
						');
					}

					// set up html buttons
					// with third party modules able to set the value of 'htmlbuttons', its possible this value will
					// be used/passed incorrectly, so an isset() for insurance that its a field that has the buttons
					if (($custom['htmlbuttons'] == 'false' OR $custom['htmlbuttons'] === FALSE) AND isset($this->field_definitions[$key]['field_id']))
					{
						$this->javascript->output('
							$("#hold_field_'.$this->field_definitions[$key]['field_id'].' .close_formatting_buttons a").click();
						');
					}

					// set up field width
					if ($custom['width'] != '100%')
					{
						$this->javascript->output('
							$("#hold_field_'.$key.'").width("'.$custom['width'].'");
						');
					}
				}
			}

			foreach ($this->field_definitions as $field => $data)
			{
				$all_fields[] .= $field;
			}

			$vars['unrevealed_fields'] = array_diff($all_fields, $revealed_fields);
		}
		else
		{
			foreach($this->api_channel_fields->settings as $field => $values)
			{
				 $field_display = array(
								'visible'		=> TRUE,
								'collapse'		=> FALSE,
								'html_buttons'	=> TRUE,
								'is_hidden'		=> FALSE,
								'width'			=> '100%'
				);

				// set up collapsed fields
				if (isset($values['field_is_hidden']) AND $values['field_is_hidden'] == 'y')
				{
					// Catch the obvious one with .js_hide
					$field_display['is_hidden'] = TRUE;

					// this can happen after the form loads...
					$this->javascript->output('
						$("#hold_field_'.$field.' .ui-resizable-handle").hide();
						$("#hold_field_'.$field.' .field_collapse").attr("src", "'.$this->cp->cp_theme_url . 'images/field_collapse.png");
					');
				}
				
				if ($field == 'ping')
				{
					$vars['publish_tabs']['pings'][$field] = $field_display;
				}
				elseif (in_array($field, array('entry_date', 'expiration_date', 'comment_expiration_date')))
				{
					$vars['publish_tabs']['date'][$field] = $field_display;
				}
				elseif (in_array($field, array('pages_uri', 'pages_template_id')))
				{
					if ($pages = isset($this->installed_modules['pages']))
					{
						$vars['publish_tabs']['pages'][$field] = $field_display;
					}
				}
				else
				{
					foreach ($module_tabs as $m_tab => $m_fields)
					{
						if (in_array($field, $m_fields))
						{
							$vars['publish_tabs'][$m_tab][$field] = $field_display;
							continue 2;
						}
					}
					
					$vars['publish_tabs']['publish'][$field] = $field_display;
				}
			}
			
			$field_display['is_hidden'] = FALSE;
			
			// show revisions tab?
			if ($show_revision_cluster != 'n')
			{
				$vars['publish_tabs']['revisions']['revisions'] = $field_display;
			}

			// Options tab
			$vars['publish_tabs']['options'] = array(
				'new_channel'	=> $field_display,
				'status'		=> $field_display,
				'author'		=> $field_display,
				'options'		=> $field_display
			);
		
			$vars['publish_tabs']['categories']['category'] = $field_display;

			if ($this->config->item('forum_is_installed') == "y")
			{
				if (isset($vars['forum_title']))
				{
					$vars['publish_tabs']['forum']['forum_title'] = $field_display;
				}
			
				if (isset($vars['forum_body']) AND isset($vars['forum_id']))
				{
					$vars['publish_tabs']['forum']['forum_body'] = $field_display;
					
					$vars['publish_tabs']['forum']['forum_id'] = $field_display;
					$vars['publish_tabs']['forum']['forum_id']['html_buttons'] = FALSE;
					$vars['publish_tabs']['forum']['forum_id']['width'] = '50%';
				}
		
				$vars['publish_tabs']['forum']['forum_topic_id'] = $field_display;
			}
		}

		$this->javascript->set_global('publish.channel_id', $channel_id);
		$this->javascript->set_global('publish.field_group', $field_group);

		$this->javascript->set_global('publish.lang', array(
			'tab_count_zero'		=> $this->lang->line('tab_count_zero'),
			'no_member_groups'		=> $this->lang->line('no_member_groups'),
			'layout_removed'		=> $this->lang->line('layout_removed'),
			'refresh_layout'		=> $this->lang->line('refresh_layout'),
			'tab_has_req_field'		=> $this->lang->line('tab_has_req_field')
		));

		$layout_preview_links = "<p>".$this->lang->line('choose_layout_group_preview').NBS."<span class='notice'>".$this->lang->line('layout_save_warning')."</span></p><ul class='bullets'>";
		
		foreach($vars['member_groups']->result() as $group)
		{
			$layout_preview_links .= '<li><a href=\"'.BASE.AMP.'C=content_publish'.AMP."M=entry_form".AMP."channel_id=".$channel_id.AMP."layout_preview=".$group->group_id.'\">'.$group->group_title."</a></li>";
		}
		$layout_preview_links .= "</ul>";

		$this->javascript->click("#layout_group_preview", '
			$.ee_notice("'.$layout_preview_links.'", {duration:0, open: true});
		');

		$this->javascript->set_global('lang.tab_name', $this->lang->line('tab_name'));
		$this->javascript->set_global('publish.smileys', ($vars['smileys_enabled']) ? TRUE : FALSE);

		if ($this->session->userdata('group_id') != 1)
		{
			$this->javascript->output('$("#holder").css("margin-right", "10px");');
		}

		$autosave_interval_seconds = ($this->config->item('autosave_interval_seconds') === FALSE) ? 60 : $this->config->item('autosave_interval_seconds');

		if ($entry_id != '' AND $autosave_interval_seconds != 0)
		{
			$this->javascript->set_global('publish.autosave', array(
				'interval'	=> $autosave_interval_seconds,
				'success'	=> $this->lang->line('autosave_success'),
				'error_state' => 'false'
			));
		}
		
		$this->javascript->set_global('publish.url_title_prefix', $url_title_prefix);
		$this->javascript->set_global('publish.default_entry_title', $default_entry_title);

		$this->form_validation->set_message('title', $this->lang->line('missing_title'));
		$this->form_validation->set_message('entry_date', $this->lang->line('missing_date'));

		$this->form_validation->set_error_delimiters('<div class="notice">', '</div>');
		
		$vars['which'] = $which;
		$vars['channel_id'] = $channel_id;
		$vars['field_definitions'] = $this->field_definitions;
		$vars['field_output'] = array();
		
		if ($this->form_validation->run() == FALSE OR is_numeric($version_id) OR $this->input->get_post('use_autosave') == 'y')
		{
			$this->cp->add_to_foot($this->insert_javascript());
		
			if ($vars['smileys_enabled'])
			{
				$this->cp->add_to_foot(smiley_js());				
			}

			foreach($this->api_channel_fields->settings as $field => $field_info)
			{
				if (isset($opts['string_override']))
				{
					$vars['field_output'][$field] = $opts;
				}
								
				if (isset($field_info['field_required']) && $field_info['field_required'] == 'y')
				{
					$vars['required_fields'][] = $field_info['field_id'];
				}
				
				if ($vars['smileys_enabled'])
				{
					$image_array = get_clickable_smileys($path = $this->config->slash_item('emoticon_path'), $field_info['field_name']);
					$col_array = $this->table->make_columns($image_array, 8);
					$vars['smiley_table'][$field] = '<div class="smileyContent" style="display: none;">'.$this->table->generate($col_array).'</div>';
					$this->table->clear(); // clear out tables for the next smiley					
				}

				$this->api_channel_fields->setup_handler($field);
				$field_value = set_value($field_info['field_name'], $field_info['field_data']);
				$vars['field_output'][$field_info['field_id']] = $this->api_channel_fields->apply('display_publish_field', array($field_value));
			}

			$this->javascript->set_global('publish.required_fields', $vars['required_fields']);

			$this->_define_options_fields($vars, $which);
			
			if ($show_revision_cluster == 'y')
			{
				$this->_define_revisions_fields($vars, $versioning);
			}
			
			$this->_define_forum_fields($vars);

			foreach($this->field_definitions as $field => $opts)
			{
				$vars['field_output'][$field] = $opts;
			}
			
			// Publish tabs need a label
			foreach ($vars['publish_tabs'] as $tab => $fields)
			{
				$vars['tab_labels'][$tab] = ( ! isset($vars['publish_tabs'][$tab]['_tab_label'])) ? $tab : $vars['publish_tabs'][$tab]['_tab_label'];
				unset($vars['publish_tabs'][$tab]['_tab_label']);				
			}

			$this->javascript->compile();
			$this->load->view('content/publish', $vars);
		}
		else
		{
			$this->cp->add_to_foot($this->insert_javascript());
			
			if ($vars['smileys_enabled'])
			{
				$this->cp->add_to_foot(smiley_js());				
			}
			
			foreach($this->api_channel_fields->settings as $field => $field_info)
			{
				if (isset($opts['string_override']))
				{
					$vars['field_output'][$field] = $opts;
				}

				if (isset($field_info['field_required']) && $field_info['field_required'] == 'y')
				{
					$vars['required_fields'][] = $field_info['field_id'];
				}	
				
				if ($vars['smileys_enabled'])
				{				
					$image_array = get_clickable_smileys($path = $this->config->slash_item('emoticon_path'), $field_info['field_name']);
					$col_array = $this->table->make_columns($image_array, 8);
					$vars['smiley_table'][$field] = '<div class="smileyContent" style="display: none;">'.$this->table->generate($col_array).'</div>';
					$this->table->clear(); // clear out tables for the next smiley	
				}
				
				$this->api_channel_fields->setup_handler($field);
				$field_value = set_value($field_info['field_name'], $field_info['field_data']);

				$vars['field_output'][$field_info['field_id']] = $this->api_channel_fields->apply('display_publish_field', array($field_value));
			}

			$this->javascript->set_global('publish.required_fields', $vars['required_fields']);
			
			$this->_define_options_fields($vars, $which);
			$this->_define_revisions_fields($vars, $versioning);
			$this->_define_forum_fields($vars);
			
			foreach($this->field_definitions as $field => $opts)
			{
				$vars['field_output'][$field] = $opts;
			}

			// Publish tabs need a label
			foreach ($vars['publish_tabs'] as $tab => $fields)
			{
				$vars['tab_labels'][$tab] = ( ! isset($vars['publish_tabs'][$tab]['_tab_label'])) ? $tab : $vars['publish_tabs'][$tab]['_tab_label'];
				unset($vars['publish_tabs'][$tab]['_tab_label']);				
			}

			// Entry submission will return false if no channel id is provided, and
			// in that event, just reload the publish page
			
			if (($err = $this->_submit_new_entry()) !== TRUE)
			{
				$this->javascript->compile();
				$vars['submission_error'] = $err;
				$this->load->view('content/publish', $vars);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get Upload Directory Files
	 *
	 * Called via ajax right after page load to build the filebrowser
	 *
	 * @access	private
	 * @return	mixed
	 */
	function filemanager_endpoint($function = '', $params = array())
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
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
	 * Build Author Sidebar
	 *
	 * Construct a list authors for the publish page
	 *
	 * @access	private
	 * @return	mixed
	 */
	function build_author_sidebar()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->output->enable_profiler(FALSE);

		if ($this->input->get_post('author_id') == "")
		{
			return;
		}

		$this->load->model('member_model');

		// set the user to in_authorlist = "y"
		$this->member_model->update_authorlist($this->input->get_post('author_id'));

		// gather up info so we can return their data intelligently
		$member = $this->member_model->get_member_data($this->input->get_post('author_id'));
		$member = $member->row();
		echo '<li><a href="'.BASE.AMP.'C=myaccount'.AMP.'id=' . $member->member_id.'">' .  $member->screen_name . '</a> <a onclick="removeAuthor($(this)); return false;" href="#" class="delete" id="mid'.$member->member_id.'"><img src="' . $this->cp->cp_theme_url . 'images/content_custom_tab_delete.png" alt="Delete" width="19" height="18" /></a></li>';
	}

	// --------------------------------------------------------------------

	/**
	 * Remove Author
	 *
	 * Removes an author from the sidebar, and changes their "in_authorlist"
	 * status to "n"
	 *
	 * @access	private
	 * @return	mixed
	 */
	function remove_author()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->output->enable_profiler(FALSE);
		$this->load->model('member_model');

		$id = (int)str_replace("mid", "", $this->input->get_post('mid'));

		$this->member_model->delete_from_authorlist($id);
	}

	// --------------------------------------------------------------------

	/**
	 * Build Author List
	 *
	 * Construct a table of authors for any channel
	 *
	 * @access	private
	 * @param	integer		the channel id
	 * @return	mixed
	 */
	function build_author_table($channel_id = 1)
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->lang->loadfile('content');
		$this->load->library('table');
		$this->table->clear();

		// get all members
		$member_list = $this->member_model->get_members('', 20, $this->input->get_post('offset'));

		$this->load->library('pagination');
		$pconfig['base_url'] = BASE.AMP.'C=content_publish'.AMP.'M=build_author_table'.AMP.'is_ajax=y';
		$pconfig['total_rows'] = $this->member_model->get_member_count();
		$pconfig['offset'] = $this->input->get_post('offset');
		$pconfig['query_string_segment'] = 'offset';
		$pconfig['page_query_string'] = TRUE;
		
		$this->pagination->initialize($pconfig);
		$pagination_links = $this->pagination->create_links();

		// get allowable member groups
		$author_groups = $this->member_model->get_author_groups($channel_id);

		$this->load->model('channel_model');
		$channels = $this->channel_model->get_channels();

		$authorsTableTemplate = array(
									'table_open'			=> '<table id="authorsTable" class="mainTable" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">'
								);
		$this->table->set_template($authorsTableTemplate);
		$this->table->set_heading($this->lang->line('username'), $this->lang->line('screen_name'), $this->lang->line('member_group'), array('class'=>'author_header', 'data'=>$this->lang->line('author')));

		$potential_author_count = 0; // the number of potential authors. If at the end this is still zero, we'll message that to the user

		if ($member_list->num_rows() == 0)
		{
			$this->table->add_row(array('data'=>'no_potential_authors', 'colspan'=>4));
		}
		else
		{
			foreach ($member_list->result() as $member)
			{
				// is the user in an authorlist, a member of a groups in the authorlist, and not a superadmin (who always show)
				if ($member->group_id != 1 AND $member->in_authorlist != 'y' AND ! in_array($member->group_id, $author_groups))
				{
					$this->table->add_row(
								array('class' => 'username', 'data' => '<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='. $member->member_id .'">'.$member->username.'</a>'),
								array('class' => 'screen_name', 'data' => $member->screen_name),
								array('class' => 'group_'.$member->group_id, 'data' => $member->group_title),
								'<img onclick="add_authors_sidebar(this);" class="add_author_modal" id="modal_author_id_'.$member->member_id.'" width="177" height="23" src="'.$this->cp->cp_theme_url.'images/content_add_author_button.png" alt="'.$this->lang->line('add_author').'" /> '
								);

					$potential_author_count++;
				}
			}
		}

		if ($potential_author_count == 0)
		{
			$message = '<p style="padding: 5px 15px;">'.$this->lang->line('no_potential_authors').'</p>';
		}
		else
		{
			$message = $this->table->generate();
		}

		$message .= '<span id="add_author_pagination">'.$pagination_links.'</span>';

		$this->load->vars(array('authors_table' => $message, 'channels' => $channels));

		if ($this->input->get_post('is_ajax'))
		{
			exit($message);
		}
	}

	function _build_channel_vars($which, $status_group, $cat_group, $field_group, $assigned_channels, $channel_id, $channel_title)
	{
		$this->load->model('channel_model');

		// Channel pull-down menu
		$vars['menu_channel_options'] = array();
		$vars['menu_channel_selected'] = '';

		$query = $this->channel_model->get_channel_menu($status_group, $cat_group, $field_group);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				if ($this->session->userdata['group_id'] == 1 OR in_array($row['channel_id'], $assigned_channels))
				{
					if (isset($_POST['new_channel']) && is_numeric($_POST['new_channel']) && $_POST['new_channel'] == $row['channel_id'])
					{
						$vars['menu_channel_selected'] = $row['channel_id'];
					}
					elseif ($channel_id == $row['channel_id'])
					{
						$vars['menu_channel_selected'] =  $row['channel_id'];
					}

					$vars['menu_channel_options'][$row['channel_id']] = form_prep($row['channel_title']);
				}
			}
		}

		return $vars;
	}

	function _build_status_vars($status_group, $status, $deft_status)
	{
		$this->load->model('status_model');

		if ($deft_status == '')
		{
			$deft_status = 'open';
		}
		
		// It seems some blogging tools that don't add in a status 
		// will just pass a string of NULL back to us.
		// So we fight it here.
		if ($status == '' OR $status == 'NULL')
		{
			$status = $deft_status;
		}

		$vars = array();

		// Fetch disallowed statuses

		$no_status_access = array();
		$vars = array();

		if ($this->session->userdata['group_id'] != 1)
		{
			$query = $this->status_model->get_disallowed_statuses($this->session->userdata['group_id']);

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$no_status_access[] = $row['status_id'];
				}
			}
		}

		//	Create status menu options
			
		// Start with the default open/closed in case they didn't select
		// a status group.
			
		$vars['menu_status_options'] = array();
		$vars['menu_status_selected'] = $status;
			
		// if there is no status group assigned, only Super Admins can create 'open' entries
		if ($this->session->userdata['group_id'] == 1)
		{
			$vars['menu_status_options']['open'] = $this->lang->line('open');
		}

		$vars['menu_status_options']['closed'] = $this->lang->line('closed');

		// If the channel has a status group, grab those statuses
			
		if ($status_group)
		{
			$query = $this->status_model->get_statuses($status_group);
			
			if ($query->num_rows())
			{
				$no_status_flag = TRUE;
				$vars['menu_status_options'] = array();

				foreach ($query->result_array() as $row)
				{
					// pre-selected status
					if ($status == $row['status'])
					{
						$vars['menu_status_selected'] = $row['status'];
					}

					if (in_array($row['status_id'], $no_status_access))
					{
						continue;
					}

					$no_status_flag = FALSE;
					$status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? $this->lang->line($row['status']) : $row['status'];
					$vars['menu_status_options'][form_prep($row['status'])] = form_prep($status_name);
				}

				//	Were there no statuses?
				// If the current user is not allowed to submit any statuses we'll set the default to closed

				if ($no_status_flag == TRUE)
				{
					$vars['menu_status_selected'] = 'closed';
				}
			}
		}
		return $vars;
	}

	function _build_author_vars($author_id, $channel_id)
	{
		$this->load->model('member_model');

		// Default author
		if ($author_id == '')
		{
			$author_id = $this->session->userdata('member_id');
		}

		$vars['menu_author_options'] = array();
		$vars['menu_author_selected'] = $author_id;

		$this->db->select('username, screen_name');
		$query = $this->db->get_where('members', array('member_id' => $author_id));
	
		$author = ($query->row('screen_name')  == '') ? $query->row('username')	 : $query->row('screen_name');
		$vars['menu_author_options'][$author_id] = $author;

		// Next we'll gather all the authors that are allowed to be in this list
		$vars['author_list'] = $this->member_model->get_authors();

		// We'll confirm that the user is assigned to a member group that allows posting in this channel
		if ($vars['author_list']->num_rows() > 0)
		{
			foreach ($vars['author_list']->result() as $row)
			{
				if (isset($this->session->userdata['assigned_channels'][$channel_id]))
				{
					$vars['menu_author_options'][$row->member_id] = ($row->screen_name == '') ? $row->username : $row->screen_name;
				}
			}
		}

		return $vars;
	}

	/** -------------------------------------
	/**	 Bookmarklet query string decode
	/** -------------------------------------*/
	function bm_qstr_decode($str)
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$str = str_replace("%20",	" ",		$str);
		$str = str_replace("%uFFA5", "&#8226;", $str);
		$str = str_replace("%uFFCA", " ",		$str);
		$str = str_replace("%uFFC1", "-",		$str);
		$str = str_replace("%uFFC9", "...",	 $str);
		$str = str_replace("%uFFD0", "-",		$str);
		$str = str_replace("%uFFD1", "-",		$str);
		$str = str_replace("%uFFD2", "\"",	  $str);
		$str = str_replace("%uFFD3", "\"",	  $str);
		$str = str_replace("%uFFD4", "\'",	  $str);
		$str = str_replace("%uFFD5", "\'",	  $str);

		$str =	preg_replace("/\%u([0-9A-F]{4,4})/e","'&#'.base_convert('\\1',16,10).';'", $str);

		$str = $this->security->xss_clean(stripslashes(urldecode($str)));

		return $str;
	}


	function autosave_entry()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('api');
		$this->api->instantiate(array('channel_categories', 'channel_entries'));

		$autosave_entry_id = (is_numeric($this->input->post("autosave_entry_id"))) ? $this->input->post("autosave_entry_id") : TRUE;

		$this->output->enable_profiler(FALSE);

		// If the entry was saved successfully, we'll get back the entry_id,
		// which we then need to insert into the hidden field for subsequent
		// saves. If there was an error, a string error message will be
		// returned, and passed for display.
		$data = $this->_submit_new_entry(TRUE, $autosave_entry_id);

		if ( ! $this->autosave_error)
		{
			exit($data['entry_id']);
		}
		else
		{
			$error_message = '<h3>'.$this->lang->line('autosave_failure').'</h3>';

			if (is_array($data))
			{
				foreach ($data as $field => $error)
				{
					$error_message .= '<p>'.$error.': '.$field.'</p>';
				}
			}
			else
			{
				$error_message .= $data;
			}

			exit($error_message);
		}
	}


	/**
	  * Channel entry submission handler
	  *
	  * This function receives a new or edited channel entry and
	  * stores it in the database.	It also sends pings
	  * 
	  */
	function _submit_new_entry($cp_call = TRUE, $autosave = FALSE)
	{
		if ( ! $channel_id = $this->input->post('channel_id') OR ! is_numeric($channel_id))
		{
			return FALSE;
		}

		/* -------------------------------------------
		/* 'submit_new_entry_start' hook.
		/*  - Add More Stuff to do when you first submit an entry
		/*  - Added 1.4.2
		*/
			if ( ! $autosave)
			{
				$edata = $this->extensions->call('submit_new_entry_start');
				if ($this->extensions->end_script === TRUE) return TRUE;
			}
		/*
		/* -------------------------------------------*/
		
		$data = $_POST;
		$data['cp_call'] = TRUE;
		$data['revision_post'] = $_POST;
		$data['author_id'] = $this->input->post('author');

		unset($data['author']);

		$return_url	= ( ! $this->input->post('return_url')) ? '' : $this->input->get_post('return_url');
		unset($_POST['return_url']);
		
		// Fetch xml-rpc ping server IDs
		$data['ping_servers'] = array();
		
		if (isset($_POST['ping']) && is_array($_POST['ping']))
		{
			$data['ping_servers'] = $_POST['ping'];
			unset($_POST['ping']);
		}

		if ($entry_id = $this->input->post('entry_id'))
		{
			$entry_exists = $this->api_channel_entries->entry_exists($entry_id);
		
			if ( ! $entry_exists)
			{
				return FALSE;
			}

			$success = $this->api_channel_entries->update_entry($entry_id, $data, $autosave);

			if ($autosave)
			{
				// a successful autosave will return the entry_id, unsuccessful may return
				// a string error message, or an array of messages.
				if (is_numeric($success))
				{
					return $data;
				}
				else
				{
					$this->autosave_error = TRUE; // there was an error
					return $success; // error messages
				}
			}

			$type = '';
			$page_title = 'entry_has_been_updated';
		}
		else
		{
			$success = $this->api_channel_entries->submit_new_entry($_POST['channel_id'], $data);

			$type = 'new';
			$page_title = 'entry_has_been_added';
		}
	
		// Do we have a reason to quit?
		if ($this->extensions->end_script === TRUE)
		{
			return TRUE;
		}
		elseif ( ! $success)
		{
			return implode('<br />', $this->api_channel_entries->errors);
		}
		
		$channel_id = $this->api_channel_entries->channel_id;
		$entry_id = $this->api_channel_entries->entry_id;

		if ($this->input->post('save_revision'))
		{
			$this->functions->redirect(BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id.AMP.'revision=saved');
		}

		// Redirect to ths "success" page
		$message = ($type == 'new') ? $this->lang->line('entry_has_been_added') : $this->lang->line('entry_has_been_updated');
			
		$this->session->set_flashdata('message_success', $message);

		$loc = BASE.AMP.'C=content_publish'.AMP.'M=view_entry'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id;
		
		// Trigger the submit new entry redirect hook
		$loc = $this->api_channel_entries->trigger_hook('entry_submission_redirect', $loc);
		// have to check this manually since trigger_hook() is returning $loc
		if ($this->extensions->end_script === TRUE)
		{
			return TRUE;
		}

		if (($vars['ping_errors'] = $this->api_channel_entries->get_errors('pings')) !== FALSE)
		{
			$vars['channel_id'] = $this->api_channel_entries->channel_id;
			$vars['entry_id'] = $this->api_channel_entries->entry_id;
			$vars['entry_link'] = BASE.AMP.'C=content_publish'.AMP.'M=view_entry'.AMP.'channel_id='.$vars['channel_id'].AMP.'entry_id='.$vars['entry_id'];
			$this->cp->set_variable('cp_page_title', $this->lang->line('xmlrpc_ping_errors'));
		
			$this->load->view('content/ping_errors', $vars);
			return TRUE;	// tricking it into not publish again
		}

		$this->functions->redirect($loc);
	}

	/** ---------------------------------------------------------------
	/**	 Fetch ping servers
	/** ---------------------------------------------------------------*/
	// This function displays the ping server checkboxes
	//---------------------------------------------------------------
	function fetch_ping_servers($member_id = '', $entry_id = '', $which = 'new', $show = TRUE)
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$sent_pings = array();

		if ($entry_id != '')
		{
			$query = $this->db->query("SELECT ping_id FROM exp_entry_ping_status WHERE entry_id = '$entry_id'");

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$sent_pings[$row['ping_id']] = TRUE;
				}
			}
		}

		$this->db->select('COUNT(*) as count');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('member_id', $this->session->userdata('member_id'));
		$query = $this->db->get('ping_servers');

		$member_id = ($query->row('count')	== 0) ? 0 : $this->session->userdata('member_id');

		$this->db->select('id, server_name, is_default');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('member_id', $member_id);
		$this->db->order_by('server_order');
		$query = $this->db->get('ping_servers');

		if ($query->num_rows() == 0)
		{
			return false;
		}

		$r = '';

		foreach($query->result_array() as $row)
		{
			if (isset($_POST['preview']))
			{
				$selected = '';
				if ($this->input->post('ping') && is_array($this->input->post('ping')))
				{
					if (in_array($row['id'], $this->input->post('ping')))
					{
						$selected = 1; 
					}
				}
			}
			else
			{
				if ($entry_id != '')
				{
					$selected = (isset($sent_pings[$row['id']])) ? 1 : '';
				}
				else
				{
					$selected = ($row['is_default'] == 'y') ? 1 : '';
				}
			}

			if ($which == 'edit')
			{
				$selected = '';
			}

			if ($show == TRUE)
			{
				$r .= '<label>'.form_checkbox('ping[]', $row['id'], $selected, 'class="ping_toggle"').' '.$row['server_name'].'</label>';
			}
			else
			{
				if ($which != 'edit' AND $selected == 1)
				{
					$r .= form_hidden('ping[]', $row['id']);
				}
			}
		}

		if ($show == TRUE)
		{
			$r .= '<label>'.form_checkbox('toggle_pings', 'toggle_pings', FALSE, 'class="ping_toggle_all"').' '.$this->lang->line('select_all').'</label>';

		}

		return $r;
	}


	/** ---------------------------------------------------------------
	/**	 JavaScript For Inserting BBCode, Glossary, and Smileys
	/** ---------------------------------------------------------------*/

	function insert_javascript()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
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
		
		$this->javascript->set_global('publish.foreignChars', $foreign_characters);
		$this->javascript->set_global('publish.word_separator', $this->config->item('word_separator') != "dash" ? '_' : '-');
	}


	// --------------------------------------------
	//	 View channel entry
	//
	// This function displays an individual channel entry
	//--------------------------------------------

	function view_entry()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $entry_id = $this->input->get('entry_id'))
		{
			return false;
		}

		if ( ! $channel_id = $this->input->get('channel_id'))
		{
			return false;
		}

		$assigned_channels = $this->functions->fetch_assigned_channels();

		if ( ! in_array($channel_id, $assigned_channels))
		{
			show_error($this->lang->line('unauthorized_for_this_channel'));
		}

		//	 Instantiate Typography class

		$this->load->library('typography');
		$this->typography->initialize();
		$this->typography->convert_curly = FALSE;

		$this->db->select('channel_html_formatting, channel_allow_img_urls, channel_auto_link_urls');
		$this->db->where('channel_id', $channel_id);
		$query = $this->db->get('channels');

		if ($query->num_rows() > 0)
		{
			foreach ($query->row_array() as $key => $val)
			{
				$$key = $val;
			}
		}

		$message = '';

		$this->db->select('field_group');
		$this->db->where('channel_id', $channel_id);
		$query = $this->db->get('channels');

		if ($query->num_rows() == 0)
		{
			return false;
		}

		$field_group = $query->row('field_group');

		$query = $this->db->query("SELECT field_id, field_type FROM exp_channel_fields WHERE group_id = '$field_group' AND field_type != 'select' ORDER BY field_order");

		$fields = array();

		foreach ($query->result_array() as $row)
		{
			$fields['field_id_'.$row['field_id']] = $row['field_type'];
		}

		$sql = "SELECT exp_channel_titles.*, exp_channel_data.*, exp_channels.* 
				FROM  exp_channel_titles, exp_channel_data, exp_channels 
				WHERE exp_channel_titles.entry_id = '$entry_id' 
				AND	  exp_channel_titles.entry_id = exp_channel_data.entry_id 
				AND	  exp_channels.channel_id = exp_channel_titles.channel_id";

		$result = $this->db->query($sql);
		$resrow = $result->row_array();

		$show_edit_link = TRUE;
		$show_comments_link = TRUE;

		if ( ! $result->num_rows() > 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ($resrow['author_id'] != $this->session->userdata('member_id'))
		{
			if ( ! $this->cp->allowed_group('can_view_other_entries'))
			{
				show_error($this->lang->line('unauthorized_access'));
			}

			if ( ! $this->cp->allowed_group('can_edit_other_entries'))
			{
				$show_edit_link = FALSE;
			}

			if ( ! $this->cp->allowed_group('can_view_other_comments') AND
				 ! $this->cp->allowed_group('can_delete_all_comments') AND
				 ! $this->cp->allowed_group('can_moderate_comments'))
			{
				$show_comments_link = FALSE;
			}
		}
		else
		{
			if ( ! $this->cp->allowed_group('can_edit_own_comments') AND
				 ! $this->cp->allowed_group('can_delete_own_comments') AND
				 ! $this->cp->allowed_group('can_moderate_comments'))
			{
				$show_comments_link = FALSE;
			}
		}

		$r = '';

		if ($result->num_rows() > 0)
		{
			$vars['entry_title'] = $this->typography->format_characters(stripslashes($resrow['title']));

			foreach ($fields as $key => $val)
			{
				if (isset($resrow[$key]) AND $val != 'rel' and $resrow[$key] != '')
				{
					$expl = explode('field_id_', $key);

					if (isset($resrow['field_dt_'.$expl['1']]))
					{
						if ($resrow[$key] > 0)
						{
							$localize = TRUE;
							$date = $resrow[$key];
							if ($resrow['field_dt_'.$expl['1']] != '')
							{
								$date = $this->localize->offset_entry_dst($date, $resrow['dst_enabled']);
								$date = $this->localize->simpl_offset($date, $resrow['field_dt_'.$expl['1']]);
								$localize = FALSE;
							}

							$r .= $this->localize->set_human_time($date, $localize);
						}
					}
					else
					{
						$r .= $this->typography->parse_type( stripslashes($resrow[$key]),
												 array(
															'text_format'	=> $resrow['field_ft_'.$expl['1']],
															'html_format'	=> $channel_html_formatting,
															'auto_links'	=> $channel_auto_link_urls,
															'allow_img_url' => $channel_allow_img_urls,
														)
												);
					}
				}
			}
		}

		// start by assuming we don't want to see an edit link or comments, and change them as needed below
		$vars['show_edit_link'] = FALSE;
		$vars['show_comments_link'] = FALSE;
		$vars['live_look_link'] = FALSE;

		$vars['entry_contents'] = $r;

		if ($show_edit_link)
		{
			$vars['show_edit_link'] = BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id;
		}

		if ($show_comments_link)
		{
			if (isset($this->installed_modules['comment']))
			{
				$res = $this->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$entry_id."'");

				$this->db->query_count--;

				$vars['show_comments_link'] = BASE.AMP.'C=content_edit'.AMP.'M=view_comments'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id;
				$vars['comment_count'] = $res->row('count');
			}

		}

		if ($result->row('live_look_template') != 0)
		{
			$this->db->select('template_groups.group_name, templates.template_name');
			$this->db->from('template_groups, templates');
			$this->db->where('exp_template_groups.group_id = exp_templates.group_id', NULL, FALSE);
			$this->db->where('templates.template_id', $result->row('live_look_template'));
			
			$res = $this->db->get();

			if ($res->num_rows() == 1)
			{
				$qm = ($this->config->item('force_query_string') == 'y') ? '' : '?';

				$vars['live_look_link'] = $this->functions->fetch_site_index().$qm.'URL='.$this->functions->create_url($res->row('group_name').'/'.$res->row('template_name').'/'.$entry_id);
			}
		}

        $this->javascript->compile();

		$this->cp->set_variable('cp_page_title', $this->lang->line('view_entry'));
		$this->load->view('content/view_entry', $vars);
	}

	/** -----------------------------------------
	/**	 Base IFRAME for Spell Check
	/** -----------------------------------------*/
	function spellcheck_iframe()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		if ( ! class_exists('EE_Spellcheck'))
		{
			require APPPATH.'libraries/Spellcheck'.EXT;
		}

		return EE_Spellcheck::iframe();
	}

	/** -----------------------------------------
	/**	 Spell Check for Textareas
	/** -----------------------------------------*/
	function spellcheck()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		if ( ! class_exists('EE_Spellcheck'))
		{
			require APPPATH.'libraries/Spellcheck'.EXT;
		}

		return EE_Spellcheck::check();
	}

	function _define_options_fields($vars, $which)
	{
		//options
		$this->field_definitions['new_channel'] = array(
				'string_override'		=> form_dropdown('new_channel', $vars['menu_channel_options'], $vars['menu_channel_selected']),
				'field_id'				=> 'new_channel',
				'field_label'			=> $this->lang->line('channel'),
				'field_name'			=> 'new_channel',
				'field_required'		=> 'n',
				'field_type'			=> 'select',
				'field_text_direction'	=> 'ltr',
				'field_data'			=> '',
				'field_fmt'				=> 'text',
				'field_instructions'	=> '',
				'field_show_fmt'		=> 'n',
				'selected'				=> $vars['menu_channel_selected'],
				'options'				=> $vars['menu_channel_options']
		);

		$this->field_definitions['status'] = array(
		
			'string_override'		=> form_dropdown('status', $vars['menu_status_options'], $vars['menu_status_selected']),
			'field_id'				=> 'status',
			'field_label'			=> $this->lang->line('status'),
			'field_name'			=> 'status',
			'field_required'		=> 'n',
			'field_type'			=> 'select',
			'field_text_direction'	=> 'ltr',
			'field_data'			=> '',
			'field_fmt'				=> 'text',
			'field_instructions'	=> '',
			'field_show_fmt'		=> 'n',
			'selected'				=> $vars['menu_status_selected'],
			'options'				=> $vars['menu_status_options']
		);

		$this->field_definitions['author'] = array(
			'string_override'		=> form_dropdown('author', $vars['menu_author_options'], $vars['menu_author_selected']),
			'field_id'				=> 'author',
			'field_label'			=> $this->lang->line('author'),
			'field_name'			=> 'author_id',
			'field_required'		=> 'n',
			'field_type'			=> 'select',
			'field_text_direction'	=> 'ltr',
			'field_data'			=> '',
			'field_fmt'				=> 'text',
			'field_instructions'	=> '',
			'field_show_fmt'		=> 'n',
			'selected'				=> $vars['menu_author_selected'],
			'options'				=> $vars['menu_author_options']
		);

		$options_r = '';
		$options_r .= ($vars['show_sticky']) ? '<label>'.form_checkbox($vars['sticky_data']).' '.lang('sticky').'</label>' : '';
		$options_r .= ($vars['show_comments']) ? '<label>'.form_checkbox($vars['comments_data']).' '.lang('allow_comments').'</label>' : '';
		$options_r .= ($vars['show_dst']) ? '<label>'.form_checkbox($vars['dst_data']).' '.lang('dst_enabled').'</label>' : '';

		$this->field_definitions['options'] = array(
			'string_override'		=> ($options_r != '') ? '<fieldset>'.$options_r.'</fieldset>&nbsp;' : '',
			'field_id'				=> 'options',
			'field_label'			=> $this->lang->line('options'),
			'field_name'			=> 'options',
			'field_required'		=> 'n',
			'field_type'			=> '',
			'field_text_direction'	=> 'ltr',
			'field_data'			=> '',
			'field_fmt'				=> 'text',
			'field_instructions'	=> '',
			'field_show_fmt'		=> 'n'
		);
	}
	
	function ajax_update_cat_fields()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->load->library('api');
		$this->api->instantiate('channel_categories');
		
		$this->load->model('category_model');
		$this->load->helper('form');
		
		$group_id = $this->input->get_post('group_id');
		
		$query = $this->category_model->get_categories($group_id, FALSE);
		$this->api_channel_categories->category_tree($group_id, '', $query->row('sort_order'));

		$this->_define_category_fields(array('' => $this->api_channel_categories->categories), FALSE, $group_id);
		exit($this->field_definitions['category']['string_override']);
	}

	function _define_category_fields($categories, $edit_categories_link, $cat_groups = '')
	{
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
		);
	}

	function _define_ping_fields($vars)
	{
		$this->api_channel_fields->set_settings('ping', array(
			'string_override'		=> (isset($vars['ping_servers']) && $vars['ping_servers'] != '') ? '<fieldset>'.$vars['ping_servers'].'</fieldset>' : lang('no_ping_sites').'<p><a href="'.BASE.AMP.'C=myaccount'.AMP.'M=ping_servers'.AMP.'id='.$this->session->userdata('member_id').'">'.$this->lang->line('add_ping_sites').'</a></p>',
			'field_id'				=> 'ping',
			'field_label'			=> $this->lang->line('pings'),
			'field_required'		=> 'n',
			'field_type'			=> 'checkboxes',
			'field_text_direction'	=> 'ltr',
			'field_data'			=> $vars['ping_servers'],
			'field_fmt'				=> 'text',
			'field_instructions'	=> '',
			'field_show_fmt'		=> 'n'
		));
	}

	function _define_revisions_fields($vars, $versioning)
	{
		$revisions_r = $versioning;
		$revisions_checked = ($vars['versioning_enabled'] == 'y')? TRUE : FALSE;
		$revisions_r .= ($vars['revs_exist'] == FALSE) ? '<p>'.$this->lang->line('no_revisions_exist').'</p>' : '';
		$revisions_r .= '<p><label>'.form_checkbox('versioning_enabled', 'y', $revisions_checked, 'id="versioning_enabled"').' '.$this->lang->line('versioning_enabled').'</label></p>';


		// Revisions tab
		$this->field_definitions['revisions'] = array(
			'string_override'		=> $revisions_r,
			'field_id'				=> 'revisions',
			'field_label'			=> $this->lang->line('revisions'),
			'field_name'			=> 'revisions',
			'field_required'		=> 'n',
			'field_type'			=> 'checkboxes',
			'field_text_direction'	=> 'ltr',
			'field_data'			=> '',
			'field_fmt'				=> 'text',
			'field_instructions'	=> '',
			'field_show_fmt'		=> 'n'
		);
	}
	
	function _define_forum_fields(&$vars)
	{
		
		if ( ! isset($vars['forum_topic_id']))
		{
			unset($vars['publish_tabs']['forum']);
			return;
		}
		
		if ($this->config->item('forum_is_installed') == "y")
		{
			// Forum tab
			$this->field_definitions['forum_topic_id'] = array(
				'string_override'		=> form_input('forum_topic_id', $vars['forum_topic_id']),
				'field_id'				=> 'forum_topic_id',
				'field_label'			=> $this->lang->line('forum_topic_id'),
				'field_name'			=> 'forum_topic_id',
				'field_required' 		=> 'n',
				'field_data'			=> $vars['forum_topic_id'],
				'field_fmt'				=> 'text',
				'field_instructions' 	=> $vars['forum_topic_id_descp'],
				'field_show_fmt'		=> 'n',
				'field_text_direction'	=> 'ltr',
				'field_type' 			=> 'text',
				'field_maxl' 			=> 100
			);
			
			if (isset($vars['forum_body']) AND isset($vars['forum_id']))
			{
				$this->field_definitions['forum_title'] = array(
					'string_override'		=> form_input('forum_title', $vars['forum_title']),
					'field_id'				=> 'forum_title',
					'field_label'			=> $this->lang->line('forum_title'),
					'field_name'			=> 'forum_title',
					'field_required' 		=> 'n',
					'field_data'			=> $vars['forum_title'],
					'field_fmt'				=> 'text',
					'field_instructions' 	=> '',
					'field_show_fmt'		=> 'n',
					'field_text_direction'	=> 'ltr',
					'field_type' 			=> 'text',
					'field_maxl' 			=> 100
				);

				$this->field_definitions['forum_body'] = array(
					'string_override'		=> form_textarea(array('name' => 'forum_body', 'id' => 'forum_body'), $vars['forum_body']),
					'field_id'				=> 'forum_body',
					'field_label'			=> $this->lang->line('forum_body'),
					'field_name'			=> 'forum_body',
					'field_required' 		=> 'n',
					'field_data'			=> $vars['forum_body'],
					'field_fmt'				=> 'text',
					'field_instructions' 	=> '',
					'field_show_fmt'		=> 'n',
					'field_text_direction'	=> 'ltr',
					'field_type' 			=> 'textarea',
					'rows'					=> 15
				);

				$this->field_definitions['forum_id'] = array(
					'string_override'		=> $vars['forum_id'],
					'field_id'				=> 'forum_id',
					'field_label'			=> $this->lang->line('forum'),
					'field_name'			=> 'forum_id',
					'field_required' 		=> 'n',
					'field_data'			=> '',
					'field_fmt'				=> 'none',
					'field_instructions' 	=> '',
					'field_show_fmt'		=> 'n',
					'field_text_direction'	=> 'ltr',
					'field_type' 			=> 'select',
					'field_maxl' 			=> 100
				);
			}
			else
			{
				if (isset($vars['forum_title']))
				{
					$this->field_definitions['forum_title'] = array(
						'string_override'		=> $vars['forum_title'],
						'field_id'				=> 'forum_title',
						'field_label'			=> $this->lang->line('forum_title'),
						'field_name'			=> 'forum_title',
						'field_required' 		=> 'n',
						'field_fmt'				=> 'text',
						'field_instructions' 	=> '',
						'field_show_fmt'		=> 'n',
						'field_text_direction'	=> 'ltr',
						'field_type' 			=> 'static',
						'field_maxl' 			=> 100
					);
				}
			}
		}
	}

	function _static_publish_admin()
	{
		$this->javascript->set_global(array(
				'lang.add_tab' 				=> $this->lang->line('add_tab'),
				'lang.close' 				=> $this->lang->line('close'),
				'lang.hide_toolbar' 		=> $this->lang->line('hide_toolbar'),
				'lang.show_toolbar' 		=> $this->lang->line('show_toolbar'),
				'lang.illegal_characters'	=> $this->lang->line('illegal_characters'),
				'lang.tab_name_required' 	=> $this->lang->line('tab_name_required'),
				'lang.duplicate_tab_name'	=> $this->lang->line('duplicate_tab_name')
			)
		);
		
		$this->cp->add_js_script(array('file' => 'cp/publish_admin'));
	}

	function _static_publish_formatting_buttons()
	{
		$this->javascript->set_global(array(
					'user_id' 					=> $this->session->userdata('member_id'),
					'lang.confirm_exit'			=> $this->lang->line('confirm_exit'),
					'lang.add_new_html_button'	=> $this->lang->line('add_new_html_button')
				)
		);		
	}

	function _static_publish_non_admin()
	{
		$this->load->library('filemanager');
		$this->filemanager->filebrowser('C=content_publish&M=filemanager_endpoint');
	}
}
// END CLASS

/* End of file content_publish.php */
/* Location: ./system/expressionengine/controllers/cp/content_publish.php */