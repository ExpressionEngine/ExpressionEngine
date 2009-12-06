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
	
	var $installed_modules	= array();
	var $field_definitions	= array();

	var $theme_img_url		= ''; // Path to the cp theme images, set up during init


	function Content_publish()
	{
		// Call the Controller constructor.
		// Without this, the world as we know it will end!
		parent::Controller();

		// Does the "core" class exist?	 Normally it's initialized
		// automatically via the autoload.php file.	 If it doesn't
		// exist it means there's a problem.
		if ( ! isset($this->core) OR ! is_object($this->core))
		{
			show_error('The ExpressionEngine Core was not initialized.	Please make sure your autoloader is correctly set up.');
		}

		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$query = $this->db->query("SELECT LOWER(module_name) as name FROM exp_modules");

		foreach($query->result_array() as $row)
		{
			$this->installed_modules[$row['name']] = $row['name'];
		}

		$cp_theme = ($this->session->userdata['cp_theme'] == '') ? $this->config->item('cp_theme') : $this->session->userdata['cp_theme'];

		$this->theme_img_url = $this->config->item('theme_folder_url').'cp_themes/'.$cp_theme.'/images/';

		// @confirm - probably doesn't need to be in constructor- move to method?
		$this->assign_cat_parent = ($this->config->item('auto_assign_cat_parents') == 'n') ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * Every controller must have an index function, which gets called
	 * automatically by CodeIgniter when the URI does not contain a call to
	 * a specific method call
	 *
	 * @access	public
	 * @return	mixed
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

		$this->output->enable_profiler(FALSE);

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

		// make this into an array, insert_group_layout will serialize and save
		$layout_info = json_decode($json_tab_layout, TRUE);

		if ($this->member_model->insert_group_layout($member_group, $channel_id, $layout_info))
		{
			exit($this->lang->line('layout_success'));
		}
		else
		{
			exit($this->lang->line('layout_failure'));
		}
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
			'BK'					=> ($this->input->get_post('BK')) ? AMP.'BK=1'.AMP.'Z=1' : '',	// @todo - I don't think so...
			'show_author_menu'		=> FALSE
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
					$autosave_channel_id = $query->row('channel_id') ;
				}
			}
			// end autosave code

			$this->db->select('channel_id');
			$this->db->where('entry_id', $entry_id);
			$query = $this->db->get('channel_titles');
			
			if ($query->num_rows() == 1)
			{
				$channel_id = $query->row('channel_id') ;
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
					$channel_id = $query->row('channel_id') ;
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
			show_error($this->lang->line('unauthorized_for_this_channel'));
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

				$_POST = @unserialize(strip_slashes($revquery->row('version_data')));
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
				// overwrite and add to this array with entry_data
				foreach (unserialize($resrow['entry_data']) as $k=>$v)
				{
					$resrow[$k] = $v;
				}

				unset($resrow['entry_data']);
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
				$vars['versioning_enabled'] = $resrow['versioning_enabled'];
			}

			// If there's a live look template, show the live look option via ee_notice
			// @todo I don't like this - it's gone when it autosaves
			if ($live_look_template != 0)
			{
				// @todo: model
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

						_destroy_live_view = function() {
							$("#publishForm").trigger("destroy_live_view");
						}

						$("#publishForm").find("input:text, textarea").focus(_destroy_live_view);
						$("#publishForm").find("input:radio, input:checkbox").click(_destroy_live_view);
						$("#publishForm").find("input:hidden, input:file, select").change(_destroy_live_view);

						function view_live_look() {
							$.ee_notice("'.$view_link.'",  {duration:0});
							$("#publishForm").one("destroy_live_view", $.ee_notice.destroy);
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
		$vars = array_merge_recursive($vars, $this->_build_channel_vars($which, $status_group, $cat_group, $field_group, $assigned_channels, $channel_id));

		// Create status menu
		$vars = array_merge_recursive($vars, $this->_build_status_vars($status_group, $status, $deft_status, $show_status_menu));

		// Create author menu
		$vars = array_merge_recursive($vars, $this->_build_author_vars($author_id, $channel_id, $show_author_menu));



		$this->cp->add_js_script(array(
		        'ui'        => array('datepicker', 'resizable', 'draggable', 'droppable'),
		        'plugin'    => array('markitup', 'thickbox')
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

			$vars['file_list'][$row->id]['id'] = $row->id;
			$vars['file_list'][$row->id]['name'] = $row->name;
			$vars['file_list'][$row->id]['url'] = $row->url;
		}

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
		
		
		// @confirm: just a note that the original 'mysettings' is in javascript/plugins		
		$this->javascript->output("
			mySettings = ".$this->javascript->generate_json($markItUp, TRUE).";
			myWritemodeSettings = ".$this->javascript->generate_json($markItUp_writemode, TRUE).";
		");

		if ($show_button_cluster == 'y')
		{
			$this->javascript->output('
				$("#write_mode_textarea").markItUp(myWritemodeSettings);
			');
		}

		// -------------------------------------------
		//	Publish Page Title Focus - makes the title field gain focus when the page is loaded
		//
		//	Hidden Configuration Variable - publish_page_title_focus => Set focus to the tile? (y/n)
		if ($which != 'edit' && $this->config->item('publish_page_title_focus') !== 'n')
		{
			$this->javascript->output('$("#title").focus();');
		}
		// -------------------------------------------

		if ($which == 'new')
		{
			$this->javascript->output('$("#title").bind("keyup blur", function(){liveUrlTitle();});');
		}

		if ($show_revision_cluster == 'y')
		{
			if ($vars['versioning_enabled'] == 'n')
			{
				$this->javascript->output('$("#revision_button").hide();');
			}

			$this->javascript->output('
				$("input#versioning_enabled").click(function() {
					if($(this).attr("checked")) {
						$("#revision_button").show(); 
					} else {
					$("#revision_button").hide(); 
				}  
				});
			');
		}

		// used in date field
		$this->javascript->output('
			date_obj = new Date();
			date_obj_hours = date_obj.getHours();
			date_obj_mins = date_obj.getMinutes();

			if (date_obj_mins < 10) { date_obj_mins = "0" + date_obj_mins; }

			if (date_obj_hours > 11) {
				date_obj_hours = date_obj_hours - 12;
				date_obj_am_pm = " PM";
			} else {
				date_obj_am_pm = " AM";
			}

			date_obj_time = " \'"+date_obj_hours+":"+date_obj_mins+date_obj_am_pm+"\'";
		');


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

		if ($show_options_cluster == 'n')
		{
			$vars['form_hidden']['sticky']			= $sticky;
			$vars['form_hidden']['allow_comments']	= $allow_comments;
		}
		else
		{
			//	"Sticky" checkbox
			$vars['show_sticky'] = TRUE;
			$vars['sticky_data'] = array(
										  'name'		=> 'sticky',
										  'id'			=> 'sticky',
										  'value'		=> 'y'
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

			//	"Daylight Saving Time" checkbox
			if ($this->config->item('honor_entry_dst') == 'y')
			{
				$vars['show_dst'] = TRUE;
				$vars['dst_enabled'] = ( ! isset($_POST['dst_enabled'])) ? 'n' :  $dst_enabled;

				$vars['dst_data'] = array(
										  'name'		=> 'dst_enabled',
										  'id'			=> 'dst_enabled',
										  'checked'		=> $vars['dst_enabled'],
										  'value'		=> 'y'
										);
			}
		}
		
		
		$vars['publish_tabs']['publish'] = array();
		

		// ----------------------------------------------
		//	DATE BLOCK
		// ----------------------------------------------
		
		if ($comment_expiration_date == '' OR $comment_expiration_date == 0)
		{
			if ($comment_expiration > 0 AND $which != 'edit')
			{
				$comment_expiration_date = $comment_expiration * 86400;
				$comment_expiration_date = $comment_expiration_date + $this->localize->now;
			}
		}

		if ($which == 'edit')
		{
			// -----------------------------
			//	Originally, we had $this->session->userdata['daylight_savings'] being
			//	used here instead of $dst_enabled, but that was, we think,
			//	a bug as it would cause a person without DST turned on for
			//	their user to mess up the date if they were not careful
			// -----------------------------

			if ($entry_date != '')
			{
				$entry_date = $this->localize->offset_entry_dst($entry_date, $dst_enabled, FALSE);
			}

			if ($expiration_date != '' AND $expiration_date != 0)
			{
				$expiration_date = $this->localize->offset_entry_dst($expiration_date, $dst_enabled, FALSE);
			}

			if ($comment_expiration_date != '' AND $comment_expiration_date != 0)
			{
				$comment_expiration_date = $this->localize->offset_entry_dst($comment_expiration_date, $dst_enabled, FALSE);
			}
		}

		$loc_entry_date = $this->localize->set_human_time($entry_date);
		$loc_expiration_date = ($expiration_date == 0) ? '' : $this->localize->set_human_time($expiration_date);
		$loc_comment_expiration_date = ($comment_expiration_date == '' OR $comment_expiration_date == 0) ? '' : $this->localize->set_human_time($comment_expiration_date);

		$cal_entry_date = ($this->localize->set_localized_time($entry_date) * 1000);
		$cal_expir_date = ($expiration_date == '' OR $expiration_date == 0) ? $this->localize->set_localized_time() * 1000 : $this->localize->set_localized_time($expiration_date) * 1000;
		$cal_com_expir_date = ($comment_expiration_date == '' OR $comment_expiration_date == 0) ? $this->localize->set_localized_time() * 1000: $this->localize->set_localized_time($comment_expiration_date) * 1000;

		if ($show_date_menu == 'n' OR ! array_key_exists('date', $layout_info))
		{
			$vars['form_hidden']['entry_date'] = $loc_entry_date;
			$vars['form_hidden']['expiration_date'] = $loc_expiration_date;
			$vars['form_hidden']['comment_expiration_date'] = $loc_comment_expiration_date;
		}
		
		$this->_define_default_date_field('entry_date', $this->localize->convert_human_date_to_gmt($loc_entry_date));
		$this->_define_default_date_field('expiration_date', $this->localize->convert_human_date_to_gmt($loc_expiration_date));
		$this->_define_default_date_field('comment_expiration_date', $this->localize->convert_human_date_to_gmt($loc_comment_expiration_date));


		// ----------------------------------------------
		//	CATEGORY BLOCK
		// ----------------------------------------------

		if ($which == 'edit')
		{
			// @todo model
			$this->db->select('c.cat_name, p.*');
			$this->db->from('categories AS c, category_posts AS p');
			$this->db->where_in('c.group_id', explode('|', $cat_group));
			$this->db->where('p.entry_id', $entry_id);
			$this->db->where('c.cat_id = p.cat_id', NULL, FALSE);
			
			$query = $this->db->get();

			foreach ($query->result_array() as $row)
			{
				if ($show_categories_menu == 'n')
				{
					$vars['form_hidden']['category'][] = $row['cat_id'];
				}
				else
				{
					$catlist[$row['cat_id']] = $row['cat_id'];
				}
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

		$vars['show_categories_menu'] = $show_categories_menu;
		$vars['categories'] = array();
		
		$edit_categories_link = FALSE; //start off as false, meaning user does not have privs

		if ($which == 'new' AND $deft_category != '')
		{
			$vars['form_hidden']['category'][] = $deft_category;
		}
		elseif ($show_categories_menu == 'y')
		{
			// Normal Category Display
			
			$catlist = ($which == 'new' && $deft_category != '') ? $deft_category : $catlist;

			$this->api_channel_categories->category_tree($cat_group, $catlist);

			if (count($this->api_channel_categories->categories) > 0)
			{  
				// add categories in again, over-ride setting above
				// @confirm this is how we want to handle passing it to the view
				//$vars['categories'] = $this->api_channel_categories->categories;
				foreach ($this->api_channel_categories->categories as $val)
				{
					$vars['categories'][$val['3']][] = $val;
				}
			}

			$link_info = $this->api_channel_categories->fetch_allowed_category_groups($cat_group);

			$links = array();
			if ($link_info !== FALSE)
			{
				// @todo needs reworking.  If you include the z it throws an error because that triggers an include of cp.publish.
				// Without the z the permissions may not be working as per 1.6.  

				foreach ($link_info as $val)
				{
					$links[] = array('url' => BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP.'group_id='.$val['group_id'],
					'group_name' => $val['group_name']);
				}
			}

			$edit_categories_link = $links;
			
			$this->javascript->output('
				var cat_groups = [],
					cat_groups_containers = {};
				
				// IE caches $.load requests, so we need a unique number
				function now(){
					return +new Date;
				}
				
				// Grab all group ids
				$(".edit_categories_link").each(function() {
					var gid = this.href.substr(this.href.lastIndexOf("=") + 1);
					$(this).data("gid", gid);
					cat_groups.push(gid);
				});
				
				for(i = 0; i < cat_groups.length; i++) {
					cat_groups_containers[cat_groups[i]] = $("#cat_group_container_"+[cat_groups[i]]);
					cat_groups_containers[cat_groups[i]].data("gid", cat_groups[i]);
				}
				
				// A function to setup new page events
				setup_page = function() {
					var container = $(this);
					
					container.parent().find("#refresh_categories").show();
					container.find("form").submit(function() {
						var that = $(this),
							values = that.serialize(),
							url = that.attr("action");

						$.ajax({
							url: url,
							type: "POST",
							data: values,
							dataType: "html",
							beforeSend: function() {
								container.html("loading...");
							},
							success: function(res) {
								// A bit hacky, but it works - trigger our live event
								container.html($(res).find(".pageContents"));
								setup_page.call(container);
							}
						});

						return false;
					});
					$(this).find(".cp_button a").corner();
					return false;
				}
				
				// And a function to do the work
				function reload() {
					var gid = $(this).data("gid");

					if ( ! gid) {
						gid = $(this).closest(".cat_group_container").data("gid");
					}

					cat_groups_containers[gid].text("loading...").load(this.href+"&modal=yes&timestamp="+now()+" .pageContents", setup_page);
					return false;
				}
				
				// Hijack edit category links to get it off the ground
				$(".edit_categories_link").click(reload);


				// Bind the live events for internal links
				for (var i in cat_groups_containers)
				{
					cat_groups_containers[i].find("a").live("click", reload);
				}
				
				
				// Last but not least - update the checkboxes
				$("a#refresh_categories", "#sub_hold_field_category").live("click", function() {
					var that = $(this).hide().nextAll("div");
					that.text("loading...").load(EE.BASE+"&C=content_publish&M=ajax_update_cat_fields&group_id="+that.data("gid")+"&timestamp="+now());
					return false;
				});
			');
		}
		
		$this->_define_category_fields($vars['categories'], $edit_categories_link);

		// ----------------------------------------------
		// PING BLOCK
		// ----------------------------------------------

		$vars['show_ping_cluster'] = $show_ping_cluster;

		if ($show_ping_cluster == 'y')
		{
			$vars['ping_servers'] = $this->fetch_ping_servers( ($which == 'edit') ? $author_id : '', isset($entry_id) ? $entry_id : '', $which, ($show_ping_cluster == 'y') ? TRUE : FALSE);
			$this->_define_ping_fields($vars);
		}

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

					//@todo: This cluster is essentially one bit "string_over-ride". It'd be nicer to write a
					// way to abstract these a bit more.

					$this->table->set_template(array('table_open'=>'<table class="mainTable" border="0" cellspacing="0" cellpadding="0">')); //@todo: should reference globally set var
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
						if (($row['version_id'] == $version_id) OR ($which == 'edit' AND $i == 0))
						{
							$revlink = $this->lang->line('current_rev');
						}
						else
						{
							$revlink = '<a class="revision_warning" href="'.BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id.AMP.'version_id='.$row['version_id'].AMP.'version_num='.$j.AMP.'use_autosave=n">'.$this->lang->line('load_revision').'</a>';
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

						// $(".revision_warning").click(function(){
						// 	$("#revision_warning").dialog("open");
						// 	revision_target = $(this).attr("href");
						// 	$(".ui-dialog-buttonpane button:eq(2)").focus();
						// 	return false;
						// });
					');
				}
			}
		}


		//----------------------------------------------
		//	FORUM BLOCK
		// ---------------------------------------------

		$vars['show_forum_cluster'] = $show_forum_cluster;

		if ($show_forum_cluster == 'y' AND $this->config->item('forum_is_installed') == "y")
		{
			// New forum topics will only be accepted by the submit_new_entry_form() when there is no entry_id sent

			if ($which == 'new' OR $entry_id == '')
			{
				// Fetch the list of available forums

				// @todo: model this
				$this->db->select('f.forum_id, f.forum_name, b.board_label');
				$this->db->from('forums AS f, forum_boards AS b');
				$this->db->where('f.forum_is_cat', 'n');
				$this->db->where('b.board_id = f.board_id', NULL, FALSE);
				$this->db->order_by('b.board_label asc, forum_order asc');
				
				$fquery = $this->db->get();

				if ($fquery->num_rows() == 0)
				{
					$forum_content = $this->dsp->qdiv('itemWrapper', BR.$this->dsp->qdiv('highlight', $this->lang->line('forums_unavailable', 'title')));
				}
				else
				{
					if (isset($entry_id) AND $entry_id != 0)
					{
						if ( ! isset($forum_topic_id))
						{
							$fquery2 = $this->db->query("SELECT forum_topic_id FROM exp_channel_titles WHERE entry_id = '{$entry_id}'");
							$forum_topic_id = $fquery2->row('forum_topic_id') ;
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
						$this->table->set_template(array(	// @todo remove inline styles
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

					$ftitle = ($fquery->num_rows() == 0) ? '' : $fquery->row('title') ;
					$vars['forum_title'] = $ftitle;
				}
			}
		}


		// ----------------------------------------------
		//	PAGES BLOCK
		// ----------------------------------------------

		$vars['show_pages_cluster'] = $show_pages_cluster;
		
		$vars['pages_uri']	= '';
		$vars['pages_dropdown'] = array();
		$vars['pages_dropdown_selected'] = '';

		if ($show_pages_cluster == 'y' AND ($pages = $this->config->item('site_pages')) !== FALSE)
		{
			$pages_uri = '';
			$pages_template_id = '';

			if ($entry_id != '' && isset($pages['uris'][$entry_id]))
			{
				$pages_uri			= $pages['uris'][$entry_id];
				$pages_template_id	= $pages['templates'][$entry_id];
			}
			else
			{
				$query = $this->db->query("SELECT configuration_value FROM exp_pages_configuration
									 WHERE configuration_name = '".$this->db->escape_str('template_channel_'.$channel_id)."'
									 AND site_id = '".$this->db->escape_str($this->config->item('site_id'))."'");

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
				$pages_uri = '/example/pages/uri/';
			}

			$this->javascript->output('
				$("#pages_uri").focus(function() {$(this).val("")});
			');

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
					'options'				=> $vars['pages_dropdown'],		// @todo this one or field_list_items?
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
				$vars['show_pages_cluster'] = $show_pages_cluster = 'n';
				
				$vars['publish_tabs']['pages']['pages_uri'] = array(
								'visible'		=> TRUE,
								'collapse'		=> FALSE,
								'html_buttons'	=> TRUE,
								'is_hidden'		=> FALSE,
								'width'			=> '100%'
				);

				$this->field_definitions['pages_uri'] = array(
					'string_override'		=> 'No Templates', // @todo language key
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

		// "URL title" input Field
		if ($show_url_title == 'n')
		{
			$vars['form_hidden']['url_title'] = $url_title;
		}
		else
		{
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
			
			$rules = 'required|call_field_validation['.$settings['field_id'].']';
			$this->form_validation->set_rules($settings['field_id'], $settings['field_label'], $rules);
		}

		$get_format = array();

		foreach ($field_query->result_array() as $row)
		{
			$field_data = '';
			$field_fmt = '';
			
			if ($which == 'edit')
			{
				$field_data = ( ! isset( $resrow['field_id_'.$row['field_id']])) ? '' : $resrow['field_id_'.$row['field_id']];
				$field_fmt	= ( ! isset( $resrow['field_ft_'.$row['field_id']] )) ? $row['field_fmt'] : $resrow['field_ft_'.$row['field_id']];

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
				'field_data'			=> $field_data,
				'field_name'			=> 'field_id_'.$row['field_id'],
			);

			$settings = array_merge($row, $settings);
			
			$rules = 'call_field_validation['.$row['field_id'].']';
			
			if ($row['field_required'] == 'y' && $row['field_type'] != 'file') 	// @todo figure out a way to remove the file exception
			{
				$rules = 'required|'.$rules;
			}

			$this->api_channel_fields->set_settings($row['field_id'], $settings);
			$this->form_validation->set_rules('field_id_'.$row['field_id'], $row['field_label'], $rules);

			if ($row['field_type'] == 'textarea' AND $show_button_cluster == 'y')
			{
				$this->javascript->output('
					$("#field_id_'.$row['field_id'].'").markItUp(mySettings);
				');
			}

			// Formatting @todo move
			if ($row['field_show_fmt'] == 'n')
			{
				$vars['form_hidden']['field_ft_'.$row['field_id']] = $field_fmt;
			}
			else
			{
				$get_format[] = $row['field_id'];
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
		$vars['member_groups'] = $this->member_model->get_member_groups(array('can_access_admin'), array('can_access_cp'=>'y'));

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
			if ($this->config->item('site_pages') === FALSE)
			{
				unset($layout_info['pages']);
			}
			
			if ($this->config->item('forum_is_installed') != "y")
			{
				unset($layout_info['forum']);
			}
			
			$vars['publish_tabs'] = $layout_info; // Custom Layout construction

			foreach($vars['publish_tabs'] as $val)
			{
				foreach($val as $key => $custom)
				{
					$revealed_fields[] .= $key;

					// set up hidden fields (not visible)
					if ($custom['visible'] == 'false')
					{
						$name = (isset($this->field_definitions[$key]['field_id'])) ? 
						
						$this->field_definitions[$key]['field_id'] : $key;
						
						$this->javascript->output('$("#hold_field_'.$name.'").hide();');
						$this->javascript->output('$("#remove_field_'.$name.'").children().attr("src", "'.$this->cp->cp_theme_url.'images/closed_eye.png");');
					}
					// set up collapsed fields
					if ($custom['collapse'] == 'true')
					{
						$this->javascript->output('
							$("#sub_hold_field_'.$key.'").hide();
							$("#hold_field_'.$key.' .ui-resizable-handle").hide();
							$("#hold_field_'.$key.' .field_collapse").attr("src", "'.$this->cp->cp_theme_url . 'images/field_collapse.png");
						');
					}

					// set up html buttons
					if ($custom['htmlbuttons'] == 'false')
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

			foreach ($this->field_definitions as $field=>$data)
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
					if ($show_ping_cluster != 'n')
					{
						$vars['publish_tabs']['pings'][$field] = $field_display;
					}
				}
				elseif ($field == 'revisions')
				{
					if ($show_revision_cluster != 'n')
					{
						$vars['publish_tabs']['revisions'][$field] = $field_display;
					}
				}
				elseif (in_array($field, array('entry_date', 'expiration_date', 'comment_expiration_date')))
				{
					if ($show_date_menu != 'n')
					{
						$vars['publish_tabs']['date'][$field] = $field_display;
					}
				}
				elseif (in_array($field, array('pages_uri', 'pages_template_id')))
				{
					if ($show_pages_cluster == 'y' AND ($pages = $this->config->item('site_pages')) !== FALSE)
					{
						$vars['publish_tabs']['pages'][$field] = $field_display;
					}
				}
				else
				{
					$vars['publish_tabs']['publish'][$field] = $field_display;
				}
			}

			// show options tab?
			if ($show_options_cluster != 'n')
			{
				if ($which != 'new')
				{
					$vars['publish_tabs']['options']['new_channel'] = $field_display;
				}

				if ($show_status_menu == 'y')
				{
					$vars['publish_tabs']['options']['status'] = $field_display;
				}

				if ($vars['show_author_menu'])
				{
					$vars['publish_tabs']['options']['author'] = $field_display;
				}

				$vars['publish_tabs']['options']['options'] = $field_display;
			}
			
			if ($show_categories_menu != 'n')
			{
				$vars['publish_tabs']['categories']['category'] = $field_display;
			}

			if ($show_forum_cluster != 'n' AND $this->config->item('forum_is_installed') == "y")
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

		$this->javascript->click("#layout_group_submit", '

			var tab_count = 0;
			var json_tab_layout = "{\n";

			// for width() to work, the element cannot be in a parent div that is display:none
			$(".main_tab").show();
			var cur_tab = $("#tab_menu_tabs li.current").attr("id");

			$("li:visible", "#tab_menu_tabs").each(function() {

				// skip list items with no id (ie: new tab)
				if ($(this).attr("id") != "")
				{
					json_tab_layout += "\t\""+$(this).attr("id").replace(/menu_/, "")+"\": ";

					json_tab_layout_content_array = new Array();

					$("#"+$(this).attr("id").replace(/menu_/, "")+" .publish_field").each(function() {

							var id = $(this).attr("id").replace(/hold_field_/, "");

							json_tab_layout_content = "\t\t\""+id+"\": {";

							json_tab_layout_content += "\"visible\": \"";
							json_tab_layout_content += ($(this).css("display") == "none") ? "false" : "true";
							json_tab_layout_content += "\", \"collapse\": \"";
							json_tab_layout_content += ($("#sub_hold_field_"+id).css("display") == "none") ? "true" : "false";
							json_tab_layout_content += "\", \"htmlbuttons\": \"";

							var temp_buttons = $("#sub_hold_field_"+id+" .markItUp ul li:eq(2)");

							if (temp_buttons.html() != "undefined" && temp_buttons.css("display") != "none")
							{
								json_tab_layout_content += "true";
							}
							else
							{
								json_tab_layout_content += "false";
							}

							percent_width = Math.round(($(this).width() / $(this).parent().width()) * 10) * 10;

							json_tab_layout_content += "\", \"width\": \"";
							json_tab_layout_content += percent_width+"%";

							json_tab_layout_content += "\"},\n";

							json_tab_layout_content_array.push(json_tab_layout_content);
					});

					// there may have been no fields in that tab, but the json still needs to be constructed
					if (json_tab_layout_content_array.length == 0)
					{
						json_tab_layout += "{},\n";
					}
					else
					{
						json_tab_layout += "{\n";

						for ( var i in json_tab_layout_content_array )
						{
						    json_tab_layout += json_tab_layout_content_array[i];
						}

						// get rid of trailing comma of last field
						json_tab_layout = json_tab_layout.substring(0,json_tab_layout.length-2);

						json_tab_layout += "\n\t},\n";
					}

					tab_count++; // add one to the tab count
				}
			});

			tab_focus(cur_tab.replace(/menu_/, ""));

			// get rid of trailing comma
			json_tab_layout = json_tab_layout.substring(0,json_tab_layout.length-2);

			json_tab_layout += "\n}";

			if (tab_count == 0)
			{
				$.ee_notice("'.$this->lang->line('tab_count_zero').'", {"type" : "error"});
			}
			else if ($("#layout_groups_holder input:checked").length == 0)
			{
				$.ee_notice("'.$this->lang->line('no_member_groups').'", {"type" : "error"});
			}
			else
			{
				$.ajax({
					type: "POST",
					url: EE.BASE+"&C=content_publish&M=save_layout",
					data: "XID="+EE.XID+"&json_tab_layout="+json_tab_layout+"&"+$("#layout_groups_holder input").serialize()+"&channel_id='.$channel_id.'",
					success: function(msg){
						$.ee_notice(msg, {type:"success"});
					}
				});
			}
		');

		$this->javascript->click("#layout_group_remove", '

			if ($("#layout_groups_holder input:checked").length == 0)
			{
				$.ee_notice("'.$this->lang->line('no_member_groups').'", {"type" : "error"});
			}
			else
			{
				var json_tab_layout = "{}"; // empty array will remove everything nicely

				$.ajax({
					type: "POST",
					url: EE.BASE+"&C=content_publish&M=save_layout",
					data: "XID="+EE.XID+"&json_tab_layout="+json_tab_layout+"&"+$("#layout_groups_holder input").serialize()+"&channel_id='.$channel_id.'&field_group='.$field_group.'",
					success: function(msg){
						$.ee_notice("'.$this->lang->line('layout_removed').' <a href=\"javascript:location=location\">'.$this->lang->line('refresh_layout').'</a>", {duration:0, type:"success"});
					}
				});
			}
		');

		$layout_preview_links = "<p>".$this->lang->line('choose_layout_group_preview').NBS."<span class='notice'>".$this->lang->line('layout_save_warning')."</span></p><ul class='bullets'>";
		foreach($vars['member_groups']->result() as $group)
		{
			$layout_preview_links .= '<li><a href=\"'.BASE.AMP.'C=content_publish'.AMP."M=entry_form".AMP."channel_id=".$channel_id.AMP."layout_preview=".$group->group_id.'\">'.$group->group_title."</a></li>";
		}
		$layout_preview_links .= "</ul>";

		$this->javascript->click("#layout_group_preview", '
			$.ee_notice("'.$layout_preview_links.'", {duration:0});
		');

		$this->javascript->click(".write_mode_trigger", array(
																'field_for_writemode_publish = "field_"+$(this).attr("id");',
																'// put contents from other page into here',
																'$("#write_mode_textarea").val($("#"+field_for_writemode_publish).val());',
																'$("#write_mode_textarea").focus();'
																)
		);

		$this->javascript->click(".add_tab_link", array(
														'$("#tab_name").val("");',
														'$("#add_tab label").text("'.$this->lang->line('tab_name').': ");',
														'$("#new_tab_dialog").dialog("open")',
														'$("#tab_name").focus();',
														'setup_tabs()'
														)
		);

		$this->javascript->click(".add_author_link", array(
				'$("#add_author_dialog").dialog("open")'
			)
		);

		$this->javascript->output('
		function removeAuthor(e)
		{
			$.get(EE.BASE+"&C=content_publish&M=remove_author' .'", { mid: e.attr("id")});
			e.parent().fadeOut();
			// rebuild author table
			$.ajax({
				type: "POST",
				url: EE.BASE+"&C=content_publish&M=build_author_table",
				data: "is_ajax=true"+$("#publishForm").serialize(),
				success: function(result){
					$("#authorsForm").html(result);
					updateAuthorTable();
				}
			});
		}
		');

		$this->javascript->click("#author_list_sidebar .delete", 'removeAuthor($(this));');
	
		$this->javascript->click("a.reveal_formatting_buttons", "$(this).parent().parent().children('.close_container').slideDown(); $(this).hide();");

		if ($vars['smileys_enabled'])
		{
			$this->javascript->click("a.glossary_link", "$(this).parent().siblings('.glossary_content').slideToggle(\"fast\");$(this).parent().siblings('.smileyContent .spellcheck_content').hide();");	

			$this->javascript->output("

				$('a.smiley_link').toggle(
					function() {
						$(this).parent().siblings('.smileyContent').slideDown('fast', function() { $(this).css('display', ''); });
					}, function() {
						$(this).parent().siblings('.smileyContent').slideUp('fast');
					}
				);
				$(this).parent().siblings('.glossary_content, .spellcheck_content').hide();

				$('.glossary_content a').click(function(){
					$.markItUp({ replaceWith:$(this).attr('title')} );
					return false;
				});

			");
		}

		$this->javascript->output(array(
										$this->javascript->hide("#write_mode_header .reveal_formatting_buttons"),
										$this->jquery->corner("#write_mode_writer", "15px"),
										$this->jquery->corner('#holder', 'bottom-left')
										)
		);

		$vars['write_mode_link'] = '#TB_inline?height=100%'.AMP.'width=100%'.AMP.'modal=true'.AMP.'inlineId=write_mode_container';
		$vars['add_publish_tab_link'] = '#TB_inline?height=150'.AMP.'width=300'.AMP.'modal=true'.AMP.'inlineId=add_tab_popup';

		if ($this->session->userdata('group_id') != 1)
		{
			$this->javascript->output('$("#holder").css("margin-right", "10px");');
		}

		$inline_js = $this->javascript->inline('
			file_manager_context = "";	// @todo - yuck, should be on the EE global
			function disable_fields(state)
			{
				if (state)
				{
					$(".main_tab input, .main_tab textarea, .main_tab select, #submit_button").attr("disabled", true);
					$("#submit_button").addClass("disabled_field");
				}
				else
				{
					$(".main_tab input, .main_tab textarea, .main_tab select, #submit_button").removeAttr("disabled");
					$("#submit_button").removeClass("disabled_field");
				}
			}'
		);

		$autosave_interval_seconds = ( ! $this->config->item('autosave_interval_seconds')) ? 60 : $this->config->item('autosave_interval_seconds');

		if ($entry_id != '' AND $autosave_interval_seconds != 0)
		{
			$this->javascript->set_global('publish.autosave', array(
				'interval'	=> $autosave_interval_seconds,
				'success'	=> $this->lang->line('autosave_success')
			));
			
			// autosave code, function is called via setInterval
			$this->cp->add_to_foot('<script type="text/javascript"><!--
				function autosave_entry()
				{
					// If the sidebar is showing, then form fields are disabled. Thus, enable all form elements,
					// grab the data and re-disable (re-dis-able... does not feel like a word) them.
					if ($("#tools:visible").length == 1)
					{
						disable_fields(true);
					}
					
					var form_data = $("#publishForm").serialize();
					
					if ($("#tools:visible").length == 1)
					{
						disable_fields(false);
					}					

					$.ajax({
						type: "POST",
						url: EE.BASE+"&C=content_publish&M=autosave_entry",
						data: form_data,
						success: function(result){
							
							$.ee_notice.destroy("autosave");
							
							if (isNaN(result))
							{
								$.ee_notice(result, {type:"error"});
							}
							else
							{
								$.ee_notice(EE.publish.autosave.success, {type:"success"});
							}
						}
					});
				}
				// setInterval("autosave_entry();", 1000 * EE.publish.autosave.success); // 1000 milliseconds per second
			--></script>');
		}


		$this->form_validation->set_message('title', $this->lang->line('missing_title'));
		$this->form_validation->set_message('entry_date', $this->lang->line('missing_date'));

		$this->form_validation->set_error_delimiters('<div class="notice">', '</div>');
		
		$vars['which'] = $which;
		$vars['channel_id'] = $channel_id;
		$vars['field_definitions'] = $this->field_definitions;
		$vars['field_output'] = array();
		
		$this->build_author_table();

		// @todo use this for js validation?
		// @todo -- clean this up.
		// $this->javascript->set_global($this->form_validation->_config_rules);
		if ($this->form_validation->run() == FALSE)
		{
			$this->cp->add_to_foot($inline_js.$this->insert_javascript());
			
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

			$this->_define_options_fields($vars, $which);
			$this->_define_revisions_fields($vars, $which);
			$this->_define_forum_fields($vars);
			
			foreach($this->field_definitions as $field => $opts)
			{
				$vars['field_output'][$field] = $opts;
			}
			
			$this->javascript->compile();
			$this->load->view('content/publish', $vars);
		}
		else
		{
			$this->cp->add_to_foot($inline_js.$this->insert_javascript());
			
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

			$this->_define_options_fields($vars, $which);
			$this->_define_revisions_fields($vars, $which);
			$this->_define_forum_fields($vars);
			
			foreach($this->field_definitions as $field => $opts)
			{
				$vars['field_output'][$field] = $opts;
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
		
		$this->load->library('table');
		$this->table->clear();

		// get all members
		$member_list = $this->member_model->get_members();

		// get allowable member groups
		$author_groups = $this->member_model->get_author_groups($channel_id);

		$this->load->model('channel_model');
		$channels = $this->channel_model->get_channels();

		$cp_table_template = array(
									'table_open'			=> '<table id="entries" class="mainTable" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">'
								);
		$this->table->set_template($cp_table_template);
		$this->table->set_heading($this->lang->line('username'), $this->lang->line('screen_name'), $this->lang->line('group'), array('class'=>'author_header', 'data'=>$this->lang->line('author')));

		$potential_author_count = 0; // the number of potential authors. If at the end this is still zero, we'll message that to the user

		if ($member_list->num_rows() == 0)
		{
			$this->table->add_row(array('data'=>'There are no members available to be authors', 'colspan'=>4));
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

		$this->load->vars(array('authors_table' => $message, 'channels' => $channels));

		if ($this->input->get_post('is_ajax'))
		{
			echo $message;
		}
	}

	function _build_channel_vars($which, $status_group, $cat_group, $field_group, $assigned_channels, $channel_id)
	{
		$this->load->model('channel_model');

		// Channel pull-down menu
		$vars['menu_channel_options'] = array();
		$vars['menu_channel_selected'] = '';

		if($which != 'new')
		{
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
		}

		return $vars;
	}

	function _build_status_vars($status_group, $status, $deft_status, $show_status_menu)
	{
		$this->load->model('status_model');

		if ($deft_status == '')
		{
			$deft_status = 'open';
		}

		if ($status == '')
		{
			$status = $deft_status;
		}

		$vars['menu_status_options'] = '';
		$vars['menu_status_selected'] = '';

		if ($show_status_menu == 'n')
		{
			$vars['form_hidden']['status'] = $status;
		}
		else
		{
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

			$query = $this->status_model->get_statuses($status_group);

			if ($query->num_rows() == 0)
			{
				// if there is no status group assigned, only Super Admins can create 'open' entries
				if ($this->session->userdata['group_id'] == 1)
				{
					$vars['menu_status_options']['open'] = $this->lang->line('open');
				}

				$vars['menu_status_options']['closed'] = $this->lang->line('closed');

				// pre-selected status
				$vars['menu_status_selected'] = $status;
			}
			else
			{
				$no_status_flag = TRUE;

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

	function _build_author_vars($author_id, $channel_id, $show_author_menu)
	{
		$this->load->model('member_model');

		// Default author
		if ($author_id == '')
		{
			$author_id = $this->session->userdata('member_id');
		}

		$vars['menu_author_options'] = array();
		$vars['menu_author_selected'] = $author_id;

		if ($show_author_menu == 'n')
		{
			$vars['form_hidden']['author_id'] = $author_id;
		}
		else
		{
			$vars['show_author_menu'] = TRUE;

			$this->db->select('username, screen_name');
			$query = $this->db->get_where('members', array('member_id' => $author_id));
	
			$author = ($query->row('screen_name')  == '') ? $query->row('username')	 : $query->row('screen_name') ;
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
	  * @todo - move to the API library so we can use this from SAEF as well
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

			$type = 'new';
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
		$loc = BASE.AMP.'C=content_publish'.AMP.'M=view_entry'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id.AMP.'U='.$type;
		
		// Trigger the submit new entry redirect hook
		$loc = $this->api_channel_entries->trigger_hook('entry_submission_redirect', $loc);
		// have to check this manually since trigger_hook() is returning $loc
		if ($this->extensions->end_script === TRUE)
		{
			return TRUE;
		}

		if ($this->api_channel_entries->get_errors('pings'))
		{
			$vars['channel_id'] = $this->api_channel_entries->channel_id;
			$vars['entry_id'] = $this->api_channel_entries->entry_id;
			$vars['entry_link'] = BASE.AMP.'C=content_publish'.AMP.'M=view_entry'.AMP.'channel_id='.$vars['channel_id'].AMP.'entry_id='.$vars['entry_id'];
			$this->cp->set_variable('cp_page_title', 'success or something like it');
		
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
	// @todo: this whole function... 
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

		$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_ping_servers WHERE site_id = '".$this->db->escape_str($this->config->item('site_id'))."' AND member_id = '".$this->session->userdata('member_id')."'");

		$member_id = ($query->row('count')	== 0) ? 0 : $this->session->userdata('member_id');

		$query = $this->db->query("SELECT id, server_name, is_default FROM exp_ping_servers WHERE site_id = '".$this->db->escape_str($this->config->item('site_id'))."' AND member_id = '$member_id' ORDER BY server_order");

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
					$r .= $this->dsp->input_hidden('ping[]', $row['id']);
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

		ob_start();

		?>

		<script type="text/javascript">
		// <![CDATA[
		function removeAuthor(e)
		{
			$.get(EE.BASE + "&C=content_publish&M=remove_author", { mid: e.attr("id")});
			e.parent().fadeOut();
		}

		function updateAuthorTable()
		{
			$.ajax({
				type: "POST",
				url: EE.BASE + "&C=content_publish&M=build_author_table",
				data: "XID=" + EE.XID + "&is_ajax=true",
				success: function(e){
					$("#authorsForm").html(e);
				}
			});

			$(".add_author_modal").bind("click", function(e){
				add_authors_sidebar(this);
			});
		}

		function add_authors_sidebar(e)
		{
			var author_id = $(e).attr("id").substring(16);

			$.ajax({
				type: "POST",
				url: EE.BASE + "&C=content_publish&M=build_author_sidebar",
				data: "XID=" + EE.XID + "&author_id="+author_id,
				success: function(e){
					$("#author_list_sidebar").append(e).fadeIn();
					updateAuthorTable();
				}
			});
		}

		  /** ------------------------------------
		  /**  Live URL Title Function
		  /** -------------------------------------*/

		  function liveUrlTitle()
		  {
			var defaultTitle = '',
				newText = document.getElementById("title").value;

			if (defaultTitle != '')
			{
				if (newText.substr(0, defaultTitle.length) == defaultTitle)
				{
					newText = newText.substr(defaultTitle.length);
				}
			}

			newText = newText.toLowerCase();
			var separator = "<?php echo $this->config->item('word_separator') != "dash" ? '_' : '-'; ?>";

			if (separator != "_")
			{
				newText = newText.replace(/\_/g, separator);
			}
			else
			{
				newText = newText.replace(/\-/g, separator);
			}

			// Foreign Character Attempt

			var newTextTemp = '';
			for(var pos=0; pos<newText.length; pos++)
			{
				var c = newText.charCodeAt(pos);

				if (c >= 32 && c < 128)
				{
					newTextTemp += newText.charAt(pos);
				}
				else
				{
					if (c in EE.publish.foreignChars) {
						newTextTemp += EE.publish.foreignChars[c];
					}
				}
			}

			var multiReg = new RegExp(separator + '{2,}', 'g');

			newText = newTextTemp;

			newText = newText.replace('/<(.*?)>/g', '');
			newText = newText.replace(/\s+/g, separator);
			newText = newText.replace(/\//g, separator);
			newText = newText.replace(/[^a-z0-9\-\._]/g,'');
			newText = newText.replace(/\+/g, separator);
			newText = newText.replace(multiReg, separator);
			newText = newText.replace(/^[-_]|[-_]$/g, '');
			newText = newText.replace(/\.+$/g,'');

			if (document.getElementById("url_title"))
			{
				document.getElementById("url_title").value = "" + newText;
			}
		}

		var selField  = false,
			selMode = "normal";

		//	Dynamically set the textarea name

		function setFieldName(which)
		{
			if (which != selField)
			{
				selField = which;

				clear_state();

				tagarray  = new Array();
				usedarray = new Array();
				running	  = 0;
			}
		}

		// Insert tag
		function taginsert(item, tagOpen, tagClose)
		{
			// Determine which tag we are dealing with

			var which = eval('item.name');

			if ( ! selField)
			{
				$.ee_notice(no_cursor);
				return false;
			}

			var theSelection	= false,
				result			= false,
				theField		= document.getElementById('entryform')[selField];

			if (selMode == 'guided')
			{
				data = prompt(enter_text, "");

				if ((data != null) && (data != ""))
				{
					result =  tagOpen + data + tagClose;
				}
			}

			// Is this a Windows user?
			// If so, add tags around selection

			if (document.selection)
			{
				theSelection = document.selection.createRange().text;

				theField.focus();

				if (theSelection)
				{
					document.selection.createRange().text = (result == false) ? tagOpen + theSelection + tagClose : result;
				}
				else
				{
					document.selection.createRange().text = (result == false) ? tagOpen + tagClose : result;
				}

				theSelection = '';

				theField.blur();
				theField.focus();

				return;
			}
			else if ( ! isNaN(theField.selectionEnd))
			{
				var newStart,
					scrollPos = theField.scrollTop,
					selLength = theField.textLength,
					selStart = theField.selectionStart,
					selEnd = theField.selectionEnd;
					
				if (selEnd <= 2 && typeof(selLength) != 'undefined')
					selEnd = selLength;

				var s1 = (theField.value).substring(0,selStart);
				var s2 = (theField.value).substring(selStart, selEnd)
				var s3 = (theField.value).substring(selEnd, selLength);

				if (result == false)
				{
					newStart = selStart + tagOpen.length + s2.length + tagClose.length;
					theField.value = (result == false) ? s1 + tagOpen + s2 + tagClose + s3 : result;
				}
				else
				{
					newStart = selStart + result.length;
					theField.value = s1 + result + s3;
				}

				theField.focus();
				theField.selectionStart = newStart;
				theField.selectionEnd = newStart;
				theField.scrollTop = scrollPos;
				return;
			}
			else if (selMode == 'guided')
			{
				curField = document.submit_post[selfField];
				
				curField.value += result;
				curField.blur();
				curField.focus();

				return;
			}

			// Add single open tags

			if (item == 'other')
			{
				eval("document.getElementById('entryform')." + selField + ".value += tagOpen");
			}
			else if (eval(which) == 0)
			{
				var result = tagOpen;

				eval("document.getElementById('entryform')." + selField + ".value += result");
				eval(which + " = 1");

				arraypush(tagarray, tagClose);
				arraypush(usedarray, which);

				running++;

				styleswap(which);
			}
			else
			{
				// Close tags

				n = 0;

				for (i = 0 ; i < tagarray.length; i++ )
				{
					if (tagarray[i] == tagClose)
					{
						n = i;

						running--;

						while (tagarray[n])
						{
							closeTag = arraypop(tagarray);
							eval("document.getElementById('entryform')." + selField + ".value += closeTag");
						}

						while (usedarray[n])
						{
							clearState = arraypop(usedarray);
							eval(clearState + " = 0");
							document.getElementById(clearState).className = 'htmlButtonA';
						}
					}
				}

				if (running <= 0 && document.getElementById('close_all').className == 'htmlButtonB')
				{
					document.getElementById('close_all').className = 'htmlButtonA';
				}

			}

			curField = eval("document.getElementById('entryform')." + selField);
			curField.blur();
			curField.focus();
		}

		// ]]>
		</script>

		<?php

		$javascript = ob_get_contents();

		ob_end_clean();

		return $javascript;
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
			return $this->dsp->no_access_message($this->lang->line('unauthorized_for_this_channel'));
		}

		//	 Instantiate Typography class

		$this->load->library('typography');
		$this->typography->initialize();
		$this->typography->convert_curly = FALSE;

		$query = $this->db->query("SELECT channel_html_formatting, channel_allow_img_urls, channel_auto_link_urls from exp_channels WHERE channel_id = '$channel_id'");

		if ($query->num_rows() > 0)
		{
			foreach ($query->row_array() as $key => $val)
			{
				$$key = $val;
			}
		}

		$message = '';

		if ($U = $this->input->get_post('U'))
		{
			$message = ($U == 'new') ? $this->dsp->qdiv('success', $this->lang->line('entry_has_been_added')) : $this->dsp->qdiv('success', $this->lang->line('entry_has_been_updated'));
		}

		$query = $this->db->query("SELECT field_group FROM	exp_channels WHERE channel_id = '$channel_id'");

		if ($query->num_rows() == 0)
		{
			return false;
		}

		$field_group = $query->row('field_group') ;

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
			// @confirm double check this is doing what I think it's doing.
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
				//@confirm: do we want to decrement the query count here?
				$this->db->query_count--;

				$vars['show_comments_link'] = BASE.AMP.'C=content_edit'.AMP.'M=view_comments'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id;
				$vars['comment_count'] = $res->row('count');
			}

		}

		if ($result->row('live_look_template') != 0)
		{
			// @todo: model
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

		return EE_Spellcheck::iframe($this->dsp->fetch_stylesheet());
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
		if ($which != 'new')
		{
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
		}

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
			'string_override'		=> ($options_r != '') ? '</p><fieldset>'.$options_r.'</fieldset><p>&nbsp;' : '',
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
		
		// @todo safety checks?
		$this->load->library('api');
		$this->api->instantiate('channel_categories');
		
		$this->load->model('category_model');
		$this->load->helper('form');
		
		$group_id = $this->input->get_post('group_id');
		
		$query = $this->category_model->get_categories($group_id, FALSE);
		$this->api_channel_categories->category_tree($group_id, '', $query->row('sort_order'));

		$this->_define_category_fields(array('' => $this->api_channel_categories->categories), FALSE);
		exit($this->field_definitions['category']['string_override']);
	}

	function _define_category_fields($categories, $edit_categories_link)
	{
		// @todo: integrate this more nicely with custom_field_helper
		// Custom fields are wrapped in <p> tags, which are not needed here.
		$category_r = '</p>';

		foreach ($categories as $key => $val)
		{
			$category_r .= (count($categories) > 1) ? '<fieldset><legend>'.$key.'</legend>' : '';
			
			$group_id = current($val);
			
			$category_r .= '<a href="#" style="display: none;" id="refresh_categories">Apply Changes</a>';
			$category_r .= '<div id="cat_group_container_'.$group_id['2'].'" class="cat_group_container">';

			foreach ($val as $v)
			{
				$indent = ($v['5'] != 1) ? repeater(NBS.NBS.NBS.NBS, $v['5']) : '';
				$category_r .= '<label>'.$indent.form_checkbox('category[]', $v['0'], $v['4']).NBS.NBS.$v['1'].'</label>';
			}

			$category_r .= '</div>';
			$category_r .= (count($categories) > 1) ? '</fieldset>' : '';
		}

		if ($edit_categories_link !== FALSE)
		{
			if (count($edit_categories_link) == 1)
			{
				$category_r .= '<p style="margin: 15px;"><a href="'.$edit_categories_link['0']['url'].'" class="edit_categories_link">'.$this->lang->line('edit_categories').'</a>';
			}
			else
			{
				$category_r .= '<p style="margin: 15px;">'.$this->lang->line('edit_categories').': ';

				foreach ($edit_categories_link as $link)
				{
					$category_r .= '<a href="'.BASE.$link['url'].'" class="edit_categories_link">'.$link['group_name'].'</a>, ';
				}

				$category_r = substr($category_r, 0, -2);
			}
		}

		$this->field_definitions['category'] = array(
			'string_override'		=> (count($categories) == 0) ? $this->lang->line('no_categories') : $category_r,
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
			'string_override'		=> (isset($vars['ping_servers']) && $vars['ping_servers'] != '') ? '<fieldset>'.$vars['ping_servers'].'</fieldset>' : lang('no_ping_sites').'</p><p><a href="'.BASE.AMP.'C=myaccount'.AMP.'M=ping_servers'.AMP.'id='.$this->session->userdata('member_id').'">'.$this->lang->line('add_ping_sites').'</a>',
			'field_id'				=> 'ping',
			'field_label'			=> $this->lang->line('pings'),
			'field_required'		=> 'n',
			'field_type'			=> 'option_group',
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
			'field_type'			=> 'option_group',
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
					'string_override'		=> form_textarea('forum_body', $vars['forum_body']),
					'field_id'				=> 'a',
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
	
	function _define_default_date_field($str_key, $value)
	{
		$settings = array(
					'field_id'				=> $str_key,
					'field_label'			=> $this->lang->line($str_key),
					'field_required'		=> 'n',
					'field_type'			=> 'date',
					'field_text_direction'	=> 'ltr',
					'field_data'			=> $value,
					'field_fmt'				=> 'text',
					'field_instructions'	=> '',
					'field_show_fmt'		=> 'n',
					'selected'				=> 'y'
		);

		// Entry date
		$this->api_channel_fields->set_settings($str_key, $settings);

		$rules = 'call_field_validation['.$settings['field_id'].']';
		$this->form_validation->set_rules($settings['field_id'], $settings['field_label'], $rules);
	}

	function _static_publish_admin()
	{
		$this->javascript->output('
			// Some of the ui widgets are slow to set up (looking at you, sortable) and we
			// don\'t really need these until the sidebar is shown so to save on yet more
			// things happening on document.ready we\'ll bind them when they mouse over the sidebar link
		
			$("a", "#showToolbarLink").one("click", function() {
				// set up resizing of publish fields
				$(".publish_field").resizable({handles: "e", minHeight: 49, stop: function(e){
					percent_width = Math.round(($(this).width() / $(this).parent().width()) * 10) * 10;
					// minimum of 10%
					if (percent_width < 10)
					{
						percent_width = 10;
					}
					// maximum of 100
					if (percent_width > 99)
					{
						percent_width = 100;
					}
					$(this).css("width", percent_width + "%");
				}});

				$("#tools ul li a.field_selector").draggable({
					revert: true,
					zIndex: 33,
					helper: "clone"
				}).click(function() {return false;});

				$("#new_tab_dialog").dialog({
					autoOpen: false,
					resizable: false,
					modal: true,
					position: "center",
					minHeight: "0px", // fix display bug, where the height of the dialog is too big
					buttons: { "'.$this->lang->line('add_tab').'": function() {add_publish_tab();} }
				}).css("overflow", "hidden");

				$("#add_author_dialog").dialog({
					autoOpen: false,
					resizable: false,
					modal: true,
					position: "center",
					width: "auto",
					minHeight: "0px", // fix display bug, where the height of the dialog is too big
					buttons: { "'.$this->lang->line('close').'": function() { $(this).dialog("close"); } }
				});
			});

			$("#tab_menu_tabs").sortable({
				tolerance: "intersect",
				items: "li:not(.addTabButton)",
				axis: "x"
			});

			$("#tools h3 a").toggle(
				function(){
					$(this).parent().next("div").slideUp();
					$(this).toggleClass("closed");
				}, function(){
					$(this).parent().next("div").slideDown();
					$(this).toggleClass("closed");
				}
			);


			$("a", "#showToolbarLink").toggle(
				function(){
					
					// disable all form elements
					disable_fields(true);

					$(".tab_menu").sortable({
						axis: "x",
						tolerance: "pointer",	// feels easier in this case
						placeholder: "publishTabSortPlaceholder",
						items: "li:not(.addTabButton)",
					});
					
					$("a span", "#showToolbarLink").text("'.$this->lang->line('hide_toolbar').'");
					$("#showToolbarLink").animate({
						marginRight: "210"
					});
					$("#holder").animate({
						marginRight: "196"
					}, function(){
						$("#tools").show();
					});
					$(".publish_field").animate({backgroundPosition: "0px 0px"}, "slow");
					$(".handle").css("display", "block");

					$(".ui-resizable-e").animate({
						marginRight: "0px"
					});
					$(".addTabButton").css("display", "inline");
					
					// Swap the image
					$("#showToolbarImg").hide();
					$("#hideToolbarImg").css("display", "inline");	// .show() uses block
					
				}, function (){

					// enable all form elements
					disable_fields(false);

					$("#tools").hide();
					$(".tab_menu").sortable("destroy");
					$("a span", "#showToolbarLink").text("'.$this->lang->line('show_toolbar').'");
					$("#showToolbarLink").animate({
						marginRight: "20"
					});
					$("#holder").animate({
						marginRight: "10"
					});
					$(".publish_field").animate({backgroundPosition: "-15px 0px"}, "slow");
					$(".handle").css("display", "none");

					$(".ui-resizable-e").animate({
						marginRight: "-10px"
					});
					$(".addTabButton").hide();
					
					// Swap the image
					$("#hideToolbarImg").hide();
					$("#showToolbarImg").css("display", "inline");	// .show() uses block
				}
			);

			$("#toggle_member_groups_all").toggle(
				function(){
					$("input[class=toggle_member_groups]").each(function() {
						this.checked = true;
					});
				}, function (){
					$("input[class=toggle_member_groups]").each(function() {
						this.checked = false;
					});
				},
				false
			);

			$(".delete_field").toggle(
				function()
				{
					var field_id = $(this).attr("id").substring(13),
						field = $("#hold_field_"+field_id);
					
					// If the field is not in the active tab - slideUp has no effect
					if (field.is(":hidden")) {
						field.css("display", "none");
					}
					
					field.slideUp();
					$(this).children().attr("src", "'.$this->cp->cp_theme_url.'images/closed_eye.png");
				},
				function()
				{
					var field_id = $(this).attr("id").substring(13);
					$("#hold_field_"+field_id).slideDown();
					$(this).children().attr("src", "'.$this->cp->cp_theme_url.'images/open_eye.png");
				}
			);

			_delete_tab_hide = function() {
				tab_to_delete = $(this).attr("href").substring(1);
				$(".menu_"+tab_to_delete).parent().fadeOut();	// hide the tab
				$(this).parent().fadeOut();						// remove from sidebar
				$("#"+tab_to_delete).fadeOut();					// hide the fields

				// If the tab is selected - move focus to the left
				selected_tab = get_selected_tab();

				if (tab_to_delete == selected_tab) {
					prev = $(".menu_"+selected_tab).parent().prevAll(":visible");
					if (prev.length > 0) {
						prev = prev.attr("id").substr(5);
					}
					else {
						prev = "publish_tab";
					}
					tab_focus(prev);
				}

//				$("#"+tab_to_delete).remove() // remove from DOM

				return false;
			}

			_delete_tab_reveal = function() {
				tab_to_show = $(this).attr("href").substring(1);
				// $(".menu"+tab_to_show).parent().animate({width:0, margin:0, padding:0, border:0, opacity:0}, "fast");
				$(".menu_"+tab_to_show).parent().fadeIn(); // show the tab
				$(this).children().attr("src", "'.$this->cp->cp_theme_url.'images/content_custom_tab_show.gif"); // change icon
				$("#"+tab_to_delete).fadeIn(); // show the fields

				return false;
			}

			function delete_publish_tab()
			{
				// Toggle cannot use a namespaced click event so we need to unbind using the
				// function reference instead
				$(".delete_tab").unbind("click", _delete_tab_hide).unbind("click", _delete_tab_reveal);
				$(".delete_tab").toggle(_delete_tab_hide, _delete_tab_reveal);
			}

			// when the page loads set up existing tabs to delete
			delete_publish_tab();

			function add_publish_tab()
			{
				tab_name = $("#tab_name").val();

				var legalChars = /^[a-zA-Z0-9 _-]+$/; // allow only letters, numbers, spaces, underscores, and dashes

				if ( ! legalChars.test(tab_name))
				{
					$.ee_notice("'.$this->lang->line('illegal_characters').'");
				}
				else if (tab_name == "")
				{
					$.ee_notice("'.$this->lang->line('tab_name_required').'");
				}
				else
				{
					if ( ! _add_tab(tab_name))
					{
						$.ee_notice("'.$this->lang->line('duplicate_tab_name').'");
					}
					else
					{
						// remove thickbox
						$("#new_tab_dialog").dialog("close");
					}
				}
			}

			function _add_tab(tab_name) {
				tab_name_filtered = tab_name.replace(/ /g, "_");

				// ensure there are no duplicate ids provided
				if ($("#"+tab_name_filtered).length) {
					return false;
				}

				// add the custom tab
				$(".addTabButton").before("<li id=\"menu_"+tab_name_filtered+"\" class=\"content_tab\"><a href=\"#\" class=\"menu_"+tab_name_filtered+"\" title=\"menu_"+tab_name_filtered+"\">"+tab_name+"</a></li>").fadeIn();

				// add the tab to the list in the toolbar
				$("#publish_tab_list").append("<li><a class=\"menu_focus\" title=\"menu_"+tab_name_filtered+"\" href=\"#\">"+tab_name+" </a> <a href=\"#"+tab_name_filtered+"\" class=\"delete delete_tab\"><img src=\"'.$this->theme_img_url.'content_custom_tab_delete.png\" alt=\"Delete\" width=\"19\" height=\"18\" /></a></li>");

				new_tab = $("<div class=\"main_tab\"><div class=\"insertpoint\"></div><div class=\"clear\"></div></div>").attr("id", tab_name_filtered);
				new_tab.prependTo("#holder");

				// If this is the only tab on the interface, we should move focus into it
				// The "add tab" button counts for 1, so we look for it plus the new tab (hence 2)
				if ($("#tab_menu_tabs li:visible").length <= 2)
				{
					tab_focus(tab_name_filtered);
				}

				// apply the classes to make it look focused
				$("#tab_menu_tabs li").removeClass("current");
				$("#menu_"+tab_name_filtered).addClass("current");

				// re-assign behaviours
				setup_tabs();
				delete_publish_tab();
				return true;
			}

			$("#tab_name").keypress(function(e){
				if (e.keyCode=="13") { // return key press
					add_publish_tab();
					return false;
				}
			});
			
			// Sidebar starts out closed - kill tab sorting
			$(".tab_menu").sortable("destroy");

		');
	}

	function _static_publish_formatting_buttons()
	{
		$this->javascript->output('
			$(".markItUp ul").append("<li class=\"btn_plus\"><a title=\"'.lang('add_new_html_button').'\" href=\"'.str_replace('&amp;', '&', BASE).'&C=myaccount&M=html_buttons&id='.$this->session->userdata('member_id').'\">+</a></li>");
			$(".btn_plus a").click(function(){
				return confirm("'.$this->lang->line('confirm_exit').'", "");
			});

			// inject the collapse button into the formatting buttons list
			$(".markItUpHeader ul").prepend("<li class=\"close_formatting_buttons\"><a href=\"#\"><img width=\"10\" height=\"10\" src=\"'.$this->cp->cp_theme_url.'images/publish_minus.gif\" alt=\"Close Formatting Buttons\"/></a></li>");

			$(".close_formatting_buttons a").toggle(
				function() {
					$(this).parent().parent().children(":not(.close_formatting_buttons)").hide();
					$(this).parent().parent().css("height", "13px");
					$(this).children("img").attr("src", "'.$this->cp->cp_theme_url.'images/publish_plus.png");
				}, function () {
					$(this).parent().parent().children().show();
					$(this).parent().parent().css("height", "22px");
					$(this).children("img").attr("src", "'.$this->cp->cp_theme_url.'images/publish_minus.gif");
				}
			);
		');
	}

	function _static_publish_non_admin()
	{
		$this->load->library('filemanager');
		$this->filemanager->filebrowser('C=content_publish&M=filemanager_endpoint');

		// File browser
		$this->javascript->output('
			$.ee_filebrowser();
			
			// Prep for a workaround to allow markitup file insertion in file inputs
			$(".btn_img a, .file_manipulate").click(function(){
				window.file_manager_context = ($(this).parent().attr("class").indexOf("markItUpButton") == -1) ? $(this).closest("div").find("input").attr("id") : "textarea_a8LogxV4eFdcbC";
			});

			// Bind the image html buttons
			$.ee_filebrowser.add_trigger(".btn_img a, .file_manipulate", function(file) {
				// We also need to allow file insertion into text inputs (vs textareas) but markitup
				// will not accommodate this, so we need to detect if this request is coming from a 
				// markitup button (textarea_a8LogxV4eFdcbC), or another field type.

				if (window.file_manager_context == "textarea_a8LogxV4eFdcbC")
				{
					// Handle images and non-images differently
					if ( ! file.is_image)
					{
						$.markItUp({name:"Link", key:"L", openWith:"<a href=\"{filedir_"+file.directory+"}"+file.name+"\">", closeWith:"</a>", placeHolder:file.name });
					}
					else
					{
						$.markItUp({ replaceWith:"<img src=\"{filedir_"+file.directory+"}"+file.name+"\" alt=\"[![Alternative text]!]\" "+file.dimensions+"/>" } );
					}
				}
				else
				{
					$("#"+window.file_manager_context).val("{filedir_"+file.directory+"}"+file.name);
				}
			});
			
			// File fields
			function file_field_changed(file, field) {
				var container = $("input[name="+field+"]").closest(".publish_field");
				container.find(".file_set").show().find(".filename").html("<img src=\""+file.thumb+"\" alt=\""+file.name+"\" /><br />"+file.name);

				$("input[name="+field+"_hidden]").val(file.name);
				$("select[name="+field+"_directory]").val(file.directory);
			}
			
			$("input[type=file]", "#publishForm").each(function() {
				var container = $(this).closest(".publish_field"),
					trigger = container.find(".choose_file");
					
				$.ee_filebrowser.add_trigger(trigger, $(this).attr("name"), file_field_changed);
				
				container.find(".remove_file").click(function() {
					container.find("input[type=hidden]").val("");
					container.find(".file_set").hide();
					return false;
				});
			});
		');

		$this->javascript->output('
			// toggle can not be used here, since it may or may not be visible
			// depending on admin customization

			$(".hide_field").click(function(){
				field_id = $(this).attr("for");
				if($("#sub_hold_field_"+field_id).css("display") == "block"){
					$("#sub_hold_field_"+field_id).slideUp();
					$("#hold_field_"+field_id+" .ui-resizable-handle").hide();
					$("#hold_field_"+field_id+" .field_collapse").attr("src", "'.$this->cp->cp_theme_url . 'images/field_collapse.png");

					// We dont want datepicker getting triggered when a field is collapsed/expanded
					return false;
				}
				else
				{
					$("#sub_hold_field_"+field_id).slideDown();
					$("#hold_field_"+field_id+" .ui-resizable-handle").show();
					$("#hold_field_"+field_id+" .field_collapse").attr("src", "'.$this->cp->cp_theme_url . 'images/field_expand.png");

					// We dont want datepicker getting triggered when a field is collapsed/expanded
					return false;
				}
			});

			$(".close_upload_bar").toggle(
				function() {
					$(this).parent().children(":not(.close_upload_bar)").hide();
					$(this).children("img").attr("src", "'.$this->theme_img_url.'publish_plus.png");
				}, function () {
					$(this).parent().children().show();
					$(this).children("img").attr("src", "'.$this->theme_img_url.'publish_minus.gif");
				}
			);

			var field_for_writemode_publish = "";
			var selected_tab = "";

			function tab_focus(tab_id)
			{
				// If the tab was hidden, this was triggered
				// through the sidebar - show it again!
				if ( ! $(".menu_"+tab_id).parent().is(":visible")) {
					// we need to trigger a click to maintain
					// the delete button toggle state
					$("a.delete_tab[href=#"+tab_id+"]").trigger("click");
				}

				$(".tab_menu li").removeClass("current");
				$(".menu_"+tab_id).parent().addClass("current");
				$(".main_tab").hide();
				$("#"+tab_id).fadeIn("fast");
				$(".main_tab").css("z-index", "");
				$("#"+tab_id).css("z-index", "5");
				selected_tab = tab_id;
			}

			function setup_tabs()
			{
				var spring_delay = 500;
				var focused_tab = "menu_publish_tab";
				var field_dropped = false;
				var spring = "";

				
				// allow sorting of publish fields
				$(".main_tab").sortable({
					handle: ".handle",
					start: function(event, ui) {
						ui.item.css("width", $(this).parent().css("width"));
					},
					stop: function(event, ui) {
						ui.item.css("width", "100%");
					}
				});

				$(".tab_menu li a").droppable({
					accept: ".field_selector",
					tolerance: "pointer",
					deactivate: function(e, ui) {
						clearTimeout(spring);
						$(".tab_menu li").removeClass("highlight_tab");
					},
					drop: function(e, ui) {
						field_id = ui.draggable.attr("id").substring(11);
						tab_id = $(this).attr("title").substring(5);

						$("#hold_field_"+field_id).prependTo("#"+tab_id);
						$("#hold_field_"+field_id).hide().slideDown();

						// bring focus
						tab_focus(tab_id);
						return false;
					},
					over: function(e, ui) {

						tab_id = $(this).attr("title").substring(5);
						$(this).parent().addClass("highlight_tab");

						spring = setTimeout(function(){
							tab_focus(tab_id);
							return false;
						}, spring_delay);

					},
					out: function(e, ui) {

						if (spring != "") {
							clearTimeout(spring);
						}

						$(this).parent().removeClass("highlight_tab");

					}
				});

				$("#holder .main_tab").droppable({
					accept: ".field_selector",
					tolerance: "pointer",
					drop: function(e, ui) {
						field_id = (ui.draggable.attr("id") == "hide_title" || ui.draggable.attr("id") == "hide_url_title") ? ui.draggable.attr("id").substring(5) : ui.draggable.attr("id").substring(11);
						tab_id = $(this).attr("id");

						// store the field we are moving, then remove it from the DOM
						$("#hold_field_"+field_id).prependTo("#"+tab_id);// + " div.insertpoint");

						$("#hold_field_"+field_id).hide().slideDown();
					}
				});

				$(".tab_menu li.content_tab a, #publish_tab_list a.menu_focus")
					.unbind("click.publish_tabs")
					.bind("click.publish_tabs", function(){
						tab_id = $(this).attr("title").substring(5);
						tab_focus(tab_id);
						return false;
					});
			}

			setup_tabs();

			function get_selected_tab() {
				return selected_tab;
			}

			// the height of this window depends on the height of the viewport.	 Percentages dont work
			// as the header and footer are absolutely sized.  This is a great compromise.
			write_mode_height = $(window).height() - (33 + 59 + 25); // the height of header + footer + 25px just to be safe
			$("#write_mode_writer").css("height", write_mode_height+"px");
			$("#write_mode_writer textarea").css("height", (write_mode_height-67-17)+"px"); // for formatting buttons + 17px for appearance

			// set up the "publish to field" buttons
			$(".publish_to_field").click(function() {
				$("#"+field_for_writemode_publish).val($("#write_mode_textarea").val());
				tb_remove();
				return false;
			});

			$(".ping_toggle_all").toggle(
				function(){
					$("input[class=ping_toggle]").each(function() {
						this.checked = false;
					});
				}, function (){
					$("input[class=ping_toggle]").each(function() {
						this.checked = true;
					});
				}
			);

			// Hide all tab divisions, then find out which tab is first and reveal it to the world!
			$(".main_tab").hide();
			$(".main_tab:first").show();

			// Apply a class to its companion tab fitting of its position
			$(".tab_menu li:first").addClass("current");

		');
	}
}
// END CLASS

/* End of file content_publish.php */
/* Location: ./system/expressionengine/controllers/cp/content_publish.php */