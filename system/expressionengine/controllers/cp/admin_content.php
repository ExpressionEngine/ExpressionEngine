<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Admin_content extends CP_Controller {

	var $reserved = array(
					'random', 'date', 'title', 'url_title', 'edit_date',
					'comment_total', 'username', 'screen_name',
					'most_recent_comment', 'expiration_date');

	// Category arrays
	var $categories = array();
	var $cat_update = array();

	var $temp;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		$this->lang->loadfile('admin');
		$this->lang->loadfile('admin_content');

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content', lang('admin_content'));

		// Note- no access check here to allow the publish page access to categories
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
		if ( ! $this->cp->allowed_group('can_access_admin', 'can_access_content_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->view->cp_page_title = lang('admin_content');
		$this->view->controller = 'admin';

		$this->cp->render('_shared/overview');
	}

	// --------------------------------------------------------------------

	/**
	 * Channel Overview
	 *
	 * Displays the Channel Management page
	 *
	 * @access	public
	 * @return	void
	 */
	function channel_management()
	{
		$this->_restrict_prefs_access();

		$this->cp->set_right_nav(array(
			'create_new_channel' => BASE.AMP.'C=admin_content'.AMP.'M=channel_add'
		));

		$this->load->library('table');
		$this->load->model('channel_model');

		$this->jquery->tablesorter('.mainTable', '{
			headers: {2: {sorter: false}, 3: {sorter: false}, 4: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->view->cp_page_title = lang('channels');
		$this->view->channel_data = $this->channel_model->get_channels();

		$this->cp->add_js_script('file', 'cp/custom_fields');
		$this->cp->render('admin/channel_management');
	}

	// --------------------------------------------------------------------

	/**
	 * Add Channel
	 *
	 * Displays the Channel Preferences form
	 *
	 * @access	public
	 * @return	void
	 */
	function channel_add()
	{
		$this->_restrict_prefs_access();

		$this->_channel_validation_rules();

		if ($this->form_validation->run() !== FALSE)
		{
			return $this->channel_update();
		}

		$this->load->helper('snippets');
		$this->load->model('channel_model');
		$this->load->model('category_model');

		$this->cp->add_js_script('plugin', 'ee_url_title');

		$this->javascript->output('
			$("#channel_title").bind("keyup keydown", function() {
				$(this).ee_url_title("#channel_name");
			});
		');

		$this->view->cp_page_title = lang('create_new_channel');

		$channels = $this->channel_model->get_channels($this->config->item('site_id'), array('channel_id', 'channel_title'));

		$vars['duplicate_channel_prefs_options'][''] = lang('do_not_duplicate');

		if ($channels != FALSE && $channels->num_rows() > 0)
		{
			foreach($channels->result() as $channel)
			{
				$vars['duplicate_channel_prefs_options'][$channel->channel_id] = $channel->channel_title;
			}
		}

		$vars['cat_group_options'][''] = lang('none');

		$groups = $this->category_model->get_category_groups('', $this->config->item('site_id'));

		if ($groups->num_rows() > 0)
		{
			foreach ($groups->result() as $group)
			{
				$vars['cat_group_options'][$group->group_id] = $group->group_name;
			}
		}

		$vars['status_group_options'][''] = lang('none');

		$this->db->select('group_id, group_name');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->order_by('group_name');

		$groups = $this->db->get('status_groups');

		if ($groups->num_rows() > 0)
		{
			foreach ($groups->result() as $group)
			{
				$vars['status_group_options'][$group->group_id] = $group->group_name;
			}
		}

		$vars['field_group_options'][''] = lang('none');

		$this->db->select('group_id, group_name');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->order_by('group_name');

		$groups = $this->db->get('field_groups');

		if ($groups->num_rows() > 0)
		{
			foreach ($groups->result() as $group)
			{
				$vars['field_group_options'][$group->group_id] = $group->group_name;
			}
		}

		// New themes may contain more than one group, thus naming collisions will happen
		// unless this is revamped.
		$vars['themes'] = array();

		$this->db->select('group_id, group_name, s.site_label');
		$this->db->from('template_groups tg, sites s');
		$this->db->where('tg.site_id = s.site_id', NULL, FALSE);

		if ($this->config->item('multiple_sites_enabled') !== 'y')
		{
			$this->db->where('tg.site_id', '1');
		}

		$this->db->order_by('tg.group_name');
		$query = $this->db->get();

		$vars['old_group_id'] = array();

		foreach ($query->result_array() as $row)
		{
			$vars['old_group_id'][$row['group_id']] = ($this->config->item('multiple_sites_enabled') == 'y') ? $row['site_label'].NBS.'-'.NBS.$row['group_name'] : $row['group_name'];
		}

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=channel_management', lang('channels'));
		$this->cp->render('admin/channel_add', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Channel
	 *
	 * Displays the Channel Preferences form
	 *
	 * @access	public
	 * @return	void
	 */
	function channel_edit()
	{
		$this->_restrict_prefs_access();

		// Get modules that are installed
		$this->cp->get_installed_modules();

		$this->load->library('table');
		$this->load->helper('snippets');
		$this->load->model('channel_model');
		$this->load->model('template_model');
		$this->load->model('status_model');
		$this->load->model('field_model');
		$this->load->model('admin_model');

		$channel_id = $this->input->get_post('channel_id');

		// If we don't have the $channel_id variable, bail out.
		if ($channel_id == '' OR ! is_numeric($channel_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->_channel_validation_rules(TRUE);
		$this->form_validation->set_old_value('channel_id', $channel_id);

		if ($this->form_validation->run() !== FALSE)
		{
			$this->form_validation->set_old_value('channel_id', $channel_id);
			return $this->channel_update();
		}

		$query = $this->channel_model->get_channel_info($channel_id);

		foreach ($query->row_array() as $key => $val)
		{
			$vars[$key] = $val;
		}

		$vars['form_hidden']['channel_id'] = $channel_id;

		// live_look_template
		$query = $this->template_model->get_templates();

		$vars['live_look_template_options'][0] = lang('no_live_look_template');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $template)
			{
				$vars['live_look_template_options'][$template->template_id] = $template->group_name.'/'.$template->template_name;
			}
		}

		// Default status menu
		$query = $this->status_model->get_statuses($vars['status_group']);

		$vars['deft_status_options']['open'] = lang('open');
		$vars['deft_status_options']['closed'] = lang('closed');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$status_name = ($row->status == 'open' OR $row->status == 'closed') ? lang($row->status) : $row->status;
				$vars['deft_status_options'][$row->status] = $status_name;
			}
		}

		$vars['deft_category_options'][''] = lang('none');

		$cats = $vars['cat_group'] ? explode('|', $vars['cat_group']) : array();

		// Needz moar felineness!
		if (count($cats))
		{
			$this->db->select('CONCAT('.$this->db->dbprefix('category_groups').'.group_name, ": ", '.$this->db->dbprefix('categories').'.cat_name) as display_name', FALSE);
			$this->db->select('categories.cat_id, categories.cat_name, category_groups.group_name');
			$this->db->from('categories, '.$this->db->dbprefix('category_groups'));
			$this->db->where($this->db->dbprefix('category_groups').'.group_id = '.$this->db->dbprefix('categories').'.group_id', NULL, FALSE);
			$this->db->where_in('categories.group_id', $cats);
			$this->db->order_by('display_name');

			$query = $this->db->get();

			if ($query->num_rows() > 0)
			{
				foreach ($query->result() as $row)
				{
					$vars['deft_category_options'][$row->cat_id] = $row->display_name;
				}
			}
		}

		// Default field for search excerpt
		$this->db->select('field_id, field_label');
		$this->db->where('group_id', $vars['field_group']);
		$query = $this->db->get('channel_fields');

		$vars['search_excerpt_options'] = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$vars['search_excerpt_options'][$row->field_id] = $row->field_label;
			}
		}

		// HTML formatting
		$vars['channel_html_formatting_options'] = array(
			'none'	=> lang('convert_to_entities'),
			'safe'	=> lang('allow_safe_html'),
			'all'	=> lang('allow_all_html')
		);

		if (isset($this->cp->installed_modules['comment']))
		{
			// Default comment text formatting
			$vars['comment_text_formatting_options'] = array(
				'none'	=> lang('none'),
				'xhtml'	=> lang('xhtml'),
				'br'	=> lang('auto_br')
			);

			// Comment HTML formatting
			$vars['comment_html_formatting_options'] = array(
				'none'	=> lang('convert_to_entities'),
				'safe'	=> lang('allow_safe_html'),
				'all'	=> lang('allow_all_html_not_recommended')
			);
		}

		$vars['languages'] = $this->admin_model->get_xml_encodings();

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=channel_management', lang('channels'));

		$this->view->cp_page_title = lang('channel_prefs').': '.$vars['channel_title'];
		$this->cp->render('admin/channel_edit', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Channel preference submission validation
	 *
	 * Sets the channel validation rules
	 *
	 * @access	public
	 * @return	void
	 */
	function _channel_validation_rules($editing = FALSE)
	{
		$this->load->library('form_validation');

		$this->form_validation->set_rules('channel_title', 'lang:channel_title', 'required|strip_tags|trim|valid_xss_check');
		$this->form_validation->set_rules('channel_name', 'lang:channel_name', 'required|callback__valid_channel_name');

		if ($editing)
		{
			$this->form_validation->set_rules('channel_description', 'lang:channel_description', 'strip_tags|trim|valid_xss_check');

			$this->form_validation->set_rules('channel_url', 'lang:channel_url', 'strip_tags|trim|valid_xss_check');
			$this->form_validation->set_rules('comment_url', 'lang:comment_url', 'strip_tags|trim|valid_xss_check');
			$this->form_validation->set_rules('search_results_url', 'lang:search_results_url', 'strip_tags|trim|valid_xss_check');
			$this->form_validation->set_rules('rss_url', 'lang:rss_url', 'strip_tags|trim|valid_xss_check');

			$this->form_validation->set_rules('url_title_prefix', 'lang:url_title_prefix', 'strtolower|strip_tags|trim|callback__valid_prefix');
			$this->form_validation->set_rules('comment_expiration', 'lang:comment_expiration', 'numeric');
		}

		$this->form_validation->set_error_delimiters('<p class="notice">', '</p>');
	}

	function _valid_prefix($str)
	{
		if ($str == '')
		{
			return TRUE;
		}
		$this->form_validation->set_message('_valid_prefix', lang('invalid_url_title_prefix'));
		return preg_match('/^[\w\-]+$/', $str) ? TRUE : FALSE;
	}

	function _valid_channel_name($str)
	{
		// Check short name characters
		if (preg_match('/[^a-z0-9\-\_]/i', $str))
		{
			$this->form_validation->set_message('_valid_channel_name', lang('invalid_short_name'));
			return FALSE;
		}

		// Check for duplicates
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('channel_name', $str);

		if ($this->form_validation->old_value('channel_id'))
		{
			$this->db->where('channel_id != ', $this->form_validation->old_value('channel_id'));
		}

		if ($this->db->count_all_results('channels') > 0)
		{
			$this->form_validation->set_message('_valid_channel_name', lang('taken_channel_name'));
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Channel preference submission handler
	 *
	 * This function receives the submitted channel preferences
	 * and stores them in the database.
	 *
	 * @access	public
	 * @return	void
	 */
	function channel_update()
	{
		$this->_restrict_prefs_access();

		unset($_POST['channel_prefs_submit']); // submit button

		// If the $channel_id variable is present we are editing an
		// existing channel, otherwise we are creating a new one

		$edit = (isset($_POST['channel_id'])) ? TRUE : FALSE;

		// Load the layout Library & update the layouts
		$this->load->library('layout');

		$return = ($this->input->get_post('return')) ? TRUE : FALSE;
		unset($_POST['return']);

		$dupe_id = $this->input->get_post('duplicate_channel_prefs');
		unset($_POST['duplicate_channel_prefs']);

		// Check for required fields

		$error = array();

		if (isset($_POST['comment_expiration']) && $_POST['comment_expiration'] == '')
		{
			$_POST['comment_expiration'] = 0;
		}

		if ($this->input->post('apply_comment_enabled_to_existing'))
		{
			if ($this->input->post('comment_system_enabled') == 'y')
			{
				$this->channel_model->update_comments_allowed($_POST['channel_id'], 'y');
			}
			elseif ($this->input->post('comment_system_enabled') == 'n')
			{
				$this->channel_model->update_comments_allowed($_POST['channel_id'], 'n');
			}
		}

		unset($_POST['apply_comment_enabled_to_existing']);



		if (isset($_POST['apply_expiration_to_existing']))
		{
			if ($this->input->post('comment_expiration') == 0)
			{
				$this->channel_model->update_comment_expiration($_POST['channel_id'], $_POST['comment_expiration'], TRUE);
			}
			else
			{
				$this->channel_model->update_comment_expiration($_POST['channel_id'], $_POST['comment_expiration'] * 86400);
			}
		}

		unset($_POST['apply_expiration_to_existing']);

		if (isset($_POST['cat_group']) && is_array($_POST['cat_group']))
		{
			foreach($_POST['cat_group'] as $key => $value)
			{
				unset($_POST['cat_group_'.$key]);
			}

			$_POST['cat_group'] = implode('|', $_POST['cat_group']);
		}

		// Create Channel
		// Construct the query based on whether we are updating or inserting

		if ($edit == FALSE)
		{
			unset($_POST['channel_id']);
			unset($_POST['clear_versioning_data']);

			$_POST['channel_url']	  = $this->functions->fetch_site_index();
			$_POST['channel_lang']	 = $this->config->item('xml_lang');

			// Assign field group if there is only one

			if ($dupe_id != '' && ( ! isset($_POST['field_group']) OR (isset($_POST['field_group']) && ! is_numeric($_POST['field_group']))))
			{
				$this->db->select('group_id');
				$this->db->where('site_id', $this->config->item('site_id'));
				$query = $this->db->get('field_groups');

				if ($query->num_rows() == 1)
				{
					$_POST['field_group'] = $query->row('group_id');
				}
			}

			// Insert data

			$_POST['site_id'] = $this->config->item('site_id');
			$_POST['status_group'] = ($this->input->post('status_group') !== FALSE &&
				$this->input->post('status_group') != '')
				? $this->input->post('status_group') : NULL;
			$_POST['field_group'] = ($this->input->post('field_group') !== FALSE &&
				$this->input->post('field_group') != '')
				? $this->input->post('field_group') : NULL;

			// duplicating preferences?
			if ($dupe_id !== FALSE AND is_numeric($dupe_id))
			{
				$this->db->where('channel_id', $dupe_id);
				$wquery = $this->db->get('channels');

				if ($wquery->num_rows() == 1)
				{
					$exceptions = array('channel_id', 'site_id', 'channel_name', 'channel_title', 'total_entries',
										'total_comments', 'last_entry_date', 'last_comment_date');

					foreach($wquery->row_array() as $key => $val)
					{
						// don't duplicate fields that are unique to each channel
						if ( ! in_array($key, $exceptions))
						{
							switch ($key)
							{
								// category, field, and status fields should only be duped
								// if both channels are assigned to the same group of each
								case 'cat_group':
									// allow to implicitly set category group to "None"
									if ( ! isset($_POST[$key]))
									{
										$_POST[$key] = $val;
									}
									break;
								case 'status_group':
								case 'field_group':
									if ( ! isset($_POST[$key]))
									{
										$_POST[$key] = $val;
									}
									elseif ($_POST[$key] == '')
									{
										 $_POST[$key] = NULL;
									}
									break;
								case 'deft_status':
								case 'deft_status':
									if ( ! isset($_POST['status_group']) OR $_POST['status_group'] == $wquery->row('status_group') )
									{
										$_POST[$key] = $val;
									}
									break;
								case 'search_excerpt':
									if ( ! isset($_POST['field_group']) OR $_POST['field_group'] == $wquery->row('field_group') )
									{
										$_POST[$key] = $val;
									}
									break;
								case 'deft_category':
									if ( ! isset($_POST['cat_group']) OR count(array_diff(explode('|', $_POST['cat_group']), explode('|', $wquery->row('cat_group') ))) == 0)
									{
										$_POST[$key] = $val;
									}
									break;
								case 'blog_url':
								case 'comment_url':
								case 'search_results_url':
								case 'rss_url':
										$_POST[$key] = $val;
									break;
								default :
									$_POST[$key] = $val;
									break;
							}
						}
					}
				}
			}


			$_POST['default_entry_title'] = '';
			$_POST['url_title_prefix'] = '';

			$this->db->insert('channels', $_POST);

			$insert_id = $this->db->insert_id();
			$channel_id = $insert_id;

			// If they made the channel?  Give access to that channel to the member group?

			if ($dupe_id !== FALSE AND is_numeric($dupe_id))
			{
				// Duplicate layouts
				$this->layout->duplicate_layout($dupe_id, $channel_id);
			}

			// If member group has ability to create the channel, they should be
			// able to access it as well
			if ($this->session->userdata('group_id') != 1)
			{
				$data = array(
					'group_id'		=> $this->session->userdata('group_id'),
					'channel_id'	=> $channel_id
				);

				$this->db->insert('channel_member_groups', $data);
			}

			$success_msg = lang('channel_created');

			$this->logger->log_action($success_msg.NBS.NBS.$_POST['channel_title']);
		}
		else
		{
			if (isset($_POST['clear_versioning_data']))
			{
				$this->db->delete('entry_versioning', array('channel_id' => $_POST['channel_id']));

				unset($_POST['clear_versioning_data']);
			}

			// Only one possible is revisions- enabled or disabled.
			// We treat as installed/not and delete the whole tab.

			$this->layout->sync_layout($_POST, $_POST['channel_id']);

			$sql = $this->db->update_string('exp_channels', $_POST, 'channel_id='.$this->db->escape_str($_POST['channel_id']));

			$this->db->query($sql);
			$channel_id = $this->db->escape_str($_POST['channel_id']);

			$success_msg = lang('channel_updated');
		}

		$cp_message = $success_msg.NBS.NBS.$_POST['channel_title'];

		$this->session->set_flashdata('message_success', $cp_message);

		if ($edit == FALSE OR $return === TRUE)
		{
			$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=channel_management');
		}
		else
		{
			$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=channel_edit&channel_id='.$channel_id);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Channel Update Group Assignments
	 *
	 * This function processes changes to the channel's
	 * assigned groups
	 *
	 * @access	public
	 * @return	void
	 */
	function channel_update_group_assignments()
	{
		$this->_restrict_prefs_access();

		$update_fields = FALSE;
		$channel_id = $this->input->post('channel_id');
		$data['field_group'] = ($this->input->post('field_group') != FALSE && $this->input->post('field_group') != '') ? $this->input->post('field_group') : NULL;
		$data['status_group'] = ($this->input->post('status_group') != FALSE && $this->input->post('status_group') != '') ? $this->input->post('status_group') : NULL;

		if (isset($_POST['cat_group']) && is_array($_POST['cat_group']))
		{
			$data['cat_group'] = ltrim(implode('|', $_POST['cat_group']), '|');
		}

		if ( ! isset($data['cat_group']) OR $data['cat_group'] == '')
		{
			$data['cat_group'] = '';
		}


		// Find the old custom fields so we can remove them
		// Have the field assignments changed
		$this->db->select('cat_group, status_group, field_group');
		$this->db->where('channel_id', $channel_id);
		$query = $this->db->get('channels');

		if ($query->num_rows() == 1)
		{
			$old_cat = $query->row('cat_group');
			$old_status = $query->row('status_group');
			$old_field = $query->row('field_group');
		}

		if ($old_field != $data['field_group'])
		{
			$update_fields = TRUE;

			if ( ! is_null($old_field))
			{
				$this->db->select('field_id');
				$this->db->where('group_id', $old_field);
				$query = $this->db->get('channel_fields');

				if ($query->num_rows() > 0)
				{
					foreach($query->result() as $row)
					{
						$tabs[] = $row->field_id;
					}

					$this->load->library('layout');
					$this->layout->delete_layout_fields($tabs, $channel_id);
					unset($tabs);
				}
			}
		}

		$this->db->where('channel_id', $channel_id);
		$this->db->update('channels', $data);

		// Updated saved layouts if field group changed
		if ($update_fields == TRUE && ! is_null($data['field_group']))
		{
			$this->db->select('field_id');
			$this->db->where('group_id', $data['field_group']);
			$query = $this->db->get('channel_fields');

			if ($query->num_rows() > 0)
			{
				foreach($query->result() as $row)
				{
					$tabs['publish'][$row->field_id] = array(
								'visible'		=> 'true',
								'collapse'		=> 'false',
								'htmlbuttons'	=> 'true',
								'width'			=> '100%'
								);

				}

				$this->load->library('layout');
				$this->layout->add_layout_fields($tabs, $channel_id);
			}
		}


		$success_msg = lang('channel_updated');
		$cp_message = $success_msg.NBS.NBS.$_POST['channel_title'];

		$this->session->set_flashdata('message_success', $cp_message);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=channel_management');
	}


	// --------------------------------------------------------------------

	/**
	 * Edit Channel
	 *
	 * This function displays the form used to edit the various
	 * preferences and group assignments for a given channel
	 *
	 * @access	public
	 * @return	void
	 */
	function channel_edit_group_assignments()
	{
		$this->_restrict_prefs_access();

		// If we don't have the $channel_id variable, bail out.
		$channel_id = $this->input->get_post('channel_id');

		if ($channel_id == '' OR ! is_numeric($channel_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model(array(
			'channel_model', 'category_model', 'status_model', 'field_model'
		));

		$query = $this->channel_model->get_channel_info($channel_id);

		foreach ($query->row_array() as $key => $val)
		{
			if ($key == 'cat_group')
			{
				$val = explode('|', $val);
			}

			$vars[$key] = $val;
		}

		$vars['form_hidden'] = array(
			'channel_id'	=> $channel_id,
			'channel_name'	=> $vars['channel_name'],
			'channel_title'	=> $vars['channel_title'],
			'return'		=> 1
		);


		// Category Select List
		$query = $this->category_model->get_category_groups('', FALSE, 2);

		$vars['cat_group_options'][''] = lang('none');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$vars['cat_group_options'][$row->group_id] = $row->group_name;
			}
		}

		// Status group select list
		$this->db->select('group_id, group_name');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->order_by('group_name');

		$query = $this->db->get('status_groups');

		$vars['status_group_options'][''] = lang('none');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$vars['status_group_options'][$row->group_id] = $row->group_name;
			}
		}

		// Field group select list
		$this->db->select('group_id, group_name');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->order_by('group_name');

		$query = $this->db->get('field_groups');

		$vars['field_group_options'][''] = lang('none');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$vars['field_group_options'][$row->group_id] = $row->group_name;
			}
		}

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=channel_management', lang('channels'));

		$this->view->cp_page_title = lang('edit_group_assignments').' : '.$vars['channel_title'];
		$this->cp->render('admin/channel_edit_group_assignments', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete channel confirm
	 *
	 * @access	public
	 * @return	void
	 */
	function channel_delete_confirm()
	{
		$this->_restrict_prefs_access();

		$channel_id = $this->input->get_post('channel_id');

		if ($channel_id == '' OR ! is_numeric($channel_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('channel_model');

		$this->view->cp_page_title = lang('delete_channel');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=channel_management', lang('channels'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=channel_delete';
		$vars['form_extra'] = '';
		$vars['form_hidden']['channel_id'] = $channel_id;
		$vars['message'] = lang('delete_channel_confirmation');

		// Grab category_groups locations with this id
		$items = $this->channel_model->get_channel_info($channel_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->channel_title;
		}

		$this->cp->render('admin/preference_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete channel
	 *
	 * This function deletes a given channel
	 *
	 * @access	public
	 * @return	void
	 */
	function channel_delete()
	{
		$this->_restrict_prefs_access();

		$channel_id = $this->input->get_post('channel_id');

		if ($channel_id == '' OR ! is_numeric($channel_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('channel_model');

		$query = $this->channel_model->get_channel_info($channel_id);

		if ($query->num_rows() == 0)
		{
			$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=channel_management');
		}

		$channel_title = $query->row('channel_title') ;

		$this->logger->log_action(lang('channel_deleted').NBS.NBS.$channel_title);

		$this->db->select('entry_id, author_id');
		$this->db->where('channel_id', $channel_id);
		$query = $this->db->get('channel_titles');

		$entries = array();
		$authors = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$entries[] = $row->entry_id;
				$authors[] = $row->author_id;
			}
		}

		$authors = array_unique($authors);

		$this->channel_model->delete_channel($channel_id, $entries, $authors);

		// Clear saved layouts
		$this->load->library('layout');
		$this->layout->delete_channel_layouts($channel_id);

		$this->session->set_flashdata('message_success', lang('channel_deleted').NBS.$channel_title);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=channel_management');
	}

	// --------------------------------------------------------------------

	function channel_form_settings()
	{
		$this->_restrict_prefs_access();

		$this->load->library('table');

		$all_channels = array();
		$all_settings = array();
		$all_statuses = array();
		$all_authors = array();

		$this->load->model('channel_model');
		$channels = $this->channel_model->get_channels()->result();

		$default_statuses = array(
			''		 => lang('channel_form_default_status_empty'),
			'open'	 => lang('open'),
			'closed' => lang('closed')
		);

		foreach ($channels as &$channel)
		{
			$channel->statuses = $default_statuses;
			$all_channels[$channel->channel_id] = $channel;
			$all_statuses[] = $channel->status_group;
		}

		$status_group_ids = array_filter(array_unique($all_statuses));

		if (count($status_group_ids))
		{
			$status_query = $this->db
				->where_in('group_id', $status_group_ids)
				->get('statuses')
				->result();

			// Create status look-up array
			$statuses = array();
			foreach ($status_query as $status)
			{
				$name = ($status->status == 'open' OR
						$status->status == 'closed')
						? lang($status->status) : $status->status;

				$statuses[$status->group_id][$status->status] = $name;
			}

			// Get the statuses for each channel
			foreach ($all_channels as $channel)
			{
				if ( ! empty($channel->status_group))
				{
					// Merge custom statuses with default statuses
					$all_channels[$channel->channel_id]->statuses = array_merge(
						$default_statuses,
						$statuses[$channel->status_group]
					);
				}
			}
		}

		$this->load->model('member_model');
		$authors = $this->member_model->get_authors()->result();

		foreach ($authors as $author)
		{
			$all_authors[$author->member_id] = $author->username;
		}

		// No authors? Add member ID 1
		if (empty($all_authors))
		{
			foreach ($this->member_model->get_members(1)->result_array() as $member)
			{
				$all_authors[$member['member_id']] = $member['username'];
			}
		}

		$channels = array();
		$default = array(
			'default_author'	=> 0,
			'default_status'	=> '',
			'require_captcha'	=> 'n',
			'allow_guest_posts'	=> 'n'
		);

		if (count($all_channels))
		{
			$settings = $this->db
				->where_in('channel_id', array_keys($all_channels))
				->get('channel_form_settings')
				->result();

			foreach ($settings as &$row)
			{
				$all_settings[$row->channel_id] = $row;
			}
		}

		foreach ($all_channels as $id => $channel)
		{
			$channels[$id] = $default;

			if (isset($all_settings[$id]))
			{
				$channels[$id] = array_merge($channels[$id], (array) $all_settings[$id]);
			}

			$channels[$id]['title'] = $channel->channel_name;
			$channels[$id]['channel_id'] = $id;
			$channels[$id]['statuses'] = $channel->statuses;
			$channels[$id]['authors'] = $all_authors;
		}

		$this->view->cp_page_title = lang('channel_form_settings');

		$this->cp->add_js_script('file', 'cp/admin_content/channel_form_settings');
		$this->cp->render('admin/channel_form_settings', compact('channels'));
	}

	// --------------------------------------------------------------------

	function update_channel_form_settings()
	{
		$this->_restrict_prefs_access();

		$this->load->model('channel_model');
		$channels = $this->channel_model->get_channels()->result();

		$settings = array();
		$default = array(
			'default_author'	=> 0,
			'default_status'	=> '',
			'require_captcha'	=> 'n',
			'allow_guest_posts'	=> 'n'
		);

		$site_id		   = $this->config->item('site_id');
		$default_status    = (array) $this->input->post('default_status');
		$default_author	   = (array) $this->input->post('default_author');
		$require_captcha   = (array) $this->input->post('require_captcha');
		$allow_guest_posts = (array) $this->input->post('allow_guest_posts');

		foreach ($channels as $channel)
		{
			$id = $channel->channel_id;

			$settings[$id] = $default;
			$settings[$id]['site_id'] = $site_id;
			$settings[$id]['channel_id'] = $id;

			if (isset($default_status[$id]))
			{
				$settings[$id]['default_status'] = $default_status[$id];
			}

			if ($allow_guest_posts[$id] == 'y')
			{
				$settings[$id]['default_author'] = $default_author[$id];
				$settings[$id]['require_captcha'] = $require_captcha[$id];
				$settings[$id]['allow_guest_posts'] = $allow_guest_posts[$id];
			}
		}

		// clear all for this site id and re-insert
		$this->db->delete('channel_form_settings', array('site_id' => $site_id));
		$this->db->insert_batch('channel_form_settings', $settings);


		$this->session->set_flashdata('message_success', lang('channel_form_settings_updated'));
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=channel_form_settings');
	}

	// --------------------------------------------------------------------

	/**
	 * Category Management
	 *
	 * Creates the Category Management main page
	 *
	 * @access	public
	 * @return	void
	 */
	function category_management()
	{
		if (AJAX_REQUEST)
		{
			if ( ! $this->cp->allowed_group('can_edit_categories'))
			{
				show_error(lang('unauthorized_access'));
			}
		}
		else
		{
			$this->_restrict_prefs_access();
		}

		$this->load->library('table');
		$this->load->model('category_model');

		$this->view->cp_page_title = lang('categories');

		$this->jquery->tablesorter('.mainTable', '{
			headers: {2: {sorter: false}, 3: {sorter: false}, 4: {sorter: false}, 5: {sorter: false}},
			widgets: ["zebra"]
		}');

		// Fetch count of custom fields per group
		$cfcount = array();

		$this->db->select('COUNT(*) AS count, group_id');
		$this->db->group_by('group_id');
		$cfq = $this->db->get('category_fields');

		if ($cfq->num_rows() > 0)
		{
			foreach ($cfq->result() as $row)
			{
				$cfcount[$row->group_id] = $row->count;
			}
		}

		$cat_count = 1;
		$vars['categories'] = array();

		$categories = $this->category_model->get_category_groups('', FALSE);

		foreach($categories->result() as $row)
		{
			$this->db->where('group_id', $row->group_id);
			$category_count = $this->db->count_all_results('categories');

			$vars['categories'][$cat_count]['group_id'] = $row->group_id;
			$vars['categories'][$cat_count]['group_name'] = htmlentities($row->group_name, ENT_QUOTES);
			$vars['categories'][$cat_count]['category_count'] = $category_count;
			$vars['categories'][$cat_count]['custom_field_count'] = ((isset($cfcount[$row->group_id])) ? $cfcount[$row->group_id] : '0');

			$cat_count++;
		}

		$this->cp->set_right_nav(array(
			'create_new_category_group' => BASE.AMP.'C=admin_content'.AMP.'M=edit_category_group'
		));

		$this->cp->render('admin/category_management', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Category Group
	 *
	 * This function shows the form used to define a new category
	 * group or edit an existing one
	 *
	 * @access	public
	 * @return	mixed
	 */
	function edit_category_group()
	{
		$this->_restrict_prefs_access();

		$this->load->model('admin_model');
		$this->load->model('category_model');
		$this->load->library('table');

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', lang('categories'));

		// Set default values
		$vars['cp_page_title'] = lang('create_new_category_group');
		$vars['submit_lang_key'] = 'submit';
		$vars['form_hidden'] = array(); // nothing needs to be passed into a new cat group
		$vars['group_name'] = '';
		$vars['field_html_formatting'] = 'all';
		$vars['can_edit'] = array();
		$vars['can_delete'] = array();
		$vars['can_edit_selected'] = array();
		$vars['can_delete_selected'] = array();
		$vars['formatting_options'] = array(
			'none' => lang('convert_to_entities'),
			'safe' => lang('allow_safe_html'),
			'all'  => lang('allow_all_html')
		);
		$can_edit_selected = array();
		$can_delete_selected = array();
		$vars['can_edit_categories'] = '';
		$vars['can_delete_categories'] = '';

		$group_id = $this->input->get_post('group_id');

		// If we have the group_id variable, it's an edit request, so fetch the category data
		if ($group_id != '')
		{
			if ( ! is_numeric($group_id))
			{
				show_error();
			}

			// some defaults to overwrite if we're editing
			$vars['cp_page_title'] = lang('edit_category_group');
			$vars['submit_lang_key'] = 'update';
			$vars['form_hidden']['group_id'] = $group_id;

			$this->db->where('group_id', $group_id);
			$this->db->where('site_id', $this->config->item('site_id'));
			$this->db->from('category_groups');
			$this->db->order_by('group_name');
			$query = $this->db->get();

			// there's only 1 possible category
			foreach ($query->row_array() as $key => $val)
			{
				$vars[$key] = $val;
			}

			// convert our | separated list of privileges into an array
			$can_edit_selected = explode('|', rtrim($vars['can_edit_categories'], '|'));
			$can_delete_selected = explode('|', rtrim($vars['can_delete_categories'], '|'));
		}

		//  Grab member groups with potential privs
		$this->db->select('group_id, group_title, can_edit_categories, can_delete_categories');
		$this->db->where_not_in('group_id', array(1,2,3,4));
		$this->db->where('site_id', $this->config->item('site_id'));
		$query = $this->db->get('member_groups');

		$vars['can_edit_checks'] = array();
		$vars['can_delete_checks'] = array();

		// Can Edit/Delete Categories selected
		foreach ($query->result_array() as $row)
		{
			if ($row['can_edit_categories'] == 'y')
			{
				$vars['can_edit_checks'][$row['group_id']]['id'] = $row['group_id'];
				$vars['can_edit_checks'][$row['group_id']]['value'] = $row['group_title'];
				$vars['can_edit_checks'][$row['group_id']]['checked'] = (in_array($row['group_id'], $can_edit_selected)) ? TRUE : FALSE;

				$vars['can_edit'][$row['group_id']] = $row['group_title'];
			}

			if ($row['can_delete_categories'] == 'y')
			{
				$vars['can_delete_checks'][$row['group_id']]['id'] = $row['group_id'];
				$vars['can_delete_checks'][$row['group_id']]['value'] = $row['group_title'];
				$vars['can_delete_checks'][$row['group_id']]['checked'] = (in_array($row['group_id'], $can_delete_selected)) ? TRUE : FALSE;

				$vars['can_delete'][$row['group_id']] = $row['group_title'];
			}
		}

		// Get the selected 'excluded' group
		$vars['exclude_selected'] = (isset($vars['exclude_group'])) ? $vars['exclude_group'] : FALSE;

		$this->cp->render('admin/edit_category_group', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Category Group
	 *
	 * This function receives the submission from the group
	 * form and stores it in the database
	 *
	 * @access	public
	 * @return	void
	 */
	function update_category_group()
	{
		$this->_restrict_prefs_access();

		// If the $group_id variable is present we are editing an
		// existing group, otherwise we are creating a new one

		$edit = ($this->input->post('group_id') != '') ? TRUE : FALSE;

		if ($this->input->post('group_name') == '')
		{
			$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=category_management');
		}

		// this should never happen, but protect ourselves!
		if ( ! isset($_POST['field_html_formatting']) OR ! in_array($_POST['field_html_formatting'], array('all', 'none', 'safe')))
		{
			$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=category_management');
		}

		// Setup Form Validation Rules
		ee()->load->library('form_validation');
		ee()->form_validation->set_rules('group_name', 'lang:group_name', 'required|alpha_dash_space|callback__valid_category_group_name['.ee()->input->post('group_id').']');
		ee()->form_validation->set_rules('can_edit_categories[]', '', '');
		ee()->form_validation->set_rules('can_delete_categories[]', '', '');

		// make data array of variables from our POST data
		$data = array();

		foreach ($_POST as $key => $val)
		{
			// we can ignore some unwanted keys before INSERTing / UPDATEing
			if (strpos($key, 'can_edit_categories_') !== FALSE OR strpos($key, 'can_delete_categories_') !== FALSE OR strpos($key, 'submit') !== FALSE)
			{
				continue;
			}

			$data[$key] = $val;
		}

		// Set our pipe delimited privileges for edit / delete
		if (isset($data['can_edit_categories']) and is_array($data['can_edit_categories']))
		{
			$data['can_edit_categories'] = implode('|', $data['can_edit_categories']);
		}
		else
		{
			$data['can_edit_categories'] = '';
		}

		if (isset($data['can_delete_categories']) and is_array($data['can_delete_categories']))
		{
			$data['can_delete_categories'] = implode('|', $data['can_delete_categories']);
		}
		else
		{
			$data['can_delete_categories'] = '';
		}

		if (ee()->form_validation->run() !== FALSE)
		{
			// Construct the query based on whether we are updating or inserting
			if ($edit == FALSE)
			{
				$this->load->model('category_model');
				$this->category_model->insert_category_group($data);

				$cp_message = lang('category_group_created').' '.$data['group_name'];
				$this->logger->log_action(lang('category_group_created').NBS.NBS.$data['group_name']);

				$this->db->select('channel_id');
				$this->db->where('site_id', $this->config->item('site_id'));
				$query = $this->db->get('channels');

				if ($query->num_rows() > 0)
				{
					$cp_message .= '<br />'.lang('assign_group_to_channel');

					if ($query->num_rows() == 1)
					{
						$link = 'C=admin_content'.AMP.'M=channel_edit_group_assignments'.AMP.'channel_id='.$query->row('channel_id') ;
					}
					else
					{
						$link = 'C=admin_content'.AMP.'M=channel_management';
					}

					$cp_message .= '<br /><a href="'.BASE.AMP.$link.'">'. lang('click_to_assign_group').'</a>';
				}
			}
			else
			{
				$this->category_model->update_category_group($data['group_id'], $data);
				$cp_message = lang('category_group_updated').NBS.$data['group_name'];
			}

			$this->session->set_flashdata('message_success', $cp_message);
			$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=category_management');
		}
		else
		{
			$this->edit_category_group();
		}
	}

	// --------------------------------------------------------------------

	public function _valid_category_group_name($group_name, $group_id)
	{
		ee()->load->model('category_model');

		// Is the group name taken?
		if (ee()->category_model->is_duplicate_category_group($group_name, $group_id))
		{
			ee()->form_validation->set_message(
				'_valid_category_group_name',
				lang('taken_category_group_name')
			);
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete category group confirm
	 *
	 * Warning message if you try to delete a category group
	 *
	 * @access	public
	 * @return	mixed
	 */
	function category_group_delete_conf()
	{
		$this->_restrict_prefs_access();

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('category_model');

		$this->view->cp_page_title = lang('delete_group');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', lang('categories'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=category_group_delete';
		$vars['form_extra'] = '';
		$vars['form_hidden']['group_id'] = $group_id;
		$vars['message'] = lang('delete_cat_group_confirmation');

		// Grab category_groups locations with this id
		$items = $this->category_model->get_category_group_name($group_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->group_name;
		}

		$this->cp->render('admin/preference_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete category group
	 *
	 * This function deletes the category group and all associated categories
	 *
	 * @access	public
	 * @return	void
	 */
	function category_group_delete()
	{
		$this->_restrict_prefs_access();

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('category_model');

		$category = $this->category_model->get_category_group_name($group_id);

		if ($category->num_rows() == 0)
		{
			show_error(lang('not_authorized'));
		}

		$name = $category->row('group_name');

		//  Delete from exp_category_posts
		$this->category_model->delete_category_group($group_id);

		$this->logger->log_action(lang('category_group_deleted').NBS.NBS.$name);

		$this->functions->clear_caching('all', '');

		$this->session->set_flashdata('message_success', lang('category_group_deleted').NBS.NBS.$name);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=category_management');
	}

	// --------------------------------------------------------------------

	/**
	 * Category management page
	 *
	 * This function shows the list of current categories, as
	 * well as the form used to submit a new category
	 *
	 * @access	public
	 * @return	void
	 */
	function category_editor($group_id = '', $update = FALSE)
	{
		if (AJAX_REQUEST)
		{
			$vars['EE_view_disable'] = TRUE;

			if ( ! $this->cp->allowed_group('can_edit_categories'))
			{
				show_error(lang('unauthorized_access'));
			}
		}
		else
		{
			$this->_restrict_prefs_access();
		}

		$this->load->model('category_model');
		$this->load->library('table');
		$this->load->library('api');

		$this->api->instantiate('channel_categories');

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', lang('categories'));

		$vars['message'] = ''; // override lower down if needed
		$vars['form_action'] = '';

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}, 3: {sorter: false}, 4: {sorter: false}},
			widgets: ["zebra"]
		}');

		if ($group_id == '')
		{
			if (($group_id = $this->input->get_post('group_id')) === FALSE OR ! is_numeric($group_id))
			{
				show_error(lang('unauthorized_access'));
			}
		}

		//  Check discrete privileges
		if (AJAX_REQUEST)
		{
			$this->db->select('can_edit_categories');
			$this->db->where('group_id', $group_id);
			$query = $this->db->get('category_groups');

			if ($query->num_rows() == 0)
			{
				show_error(lang('unauthorized_access'));
			}

			$can_edit = explode('|', rtrim($query->row('can_edit_categories') , '|'));

			if ($this->session->userdata['group_id'] != 1 AND ! in_array($this->session->userdata['group_id'], $can_edit))
			{
				show_error(lang('unauthorized_access'));
			}
		}

		$zurl = ($this->input->get_post('Z') == 1) ? AMP.'Z=1' : '';
		$zurl .= ($this->input->get_post('cat_group') !== FALSE) ? AMP.'cat_group='.$this->input->get_post('cat_group') : '';
		$zurl .= ($this->input->get_post('integrated') !== FALSE) ? AMP.'integrated='.$this->input->get_post('integrated') : '';

		$query = $this->category_model->get_category_groups($group_id, FALSE);

		if ($query->num_rows() == 0)
		{
			$this->functions->redirect(BASE.AMP.'C=admin_content&M=category_management');
		}

		$group_name = $query->row('group_name') ;
		$sort_order = $query->row('sort_order') ;

		$this->view->cp_page_title = $group_name;

		if ($update != FALSE)
		{
			$vars['message'] = lang('category_updated');
		}

		// Fetch the category tree
		$this->api_channel_categories->category_tree($group_id, '', $sort_order);

		$vars['categories'] = array();

		if (count($this->api_channel_categories->categories) > 0)
		{
			$vars['categories'] = $this->api_channel_categories->categories;

			// Sanitize the category names
			foreach ($vars['categories'] as $category_id => $category_data)
			{
				$vars['categories'][$category_id][1] = htmlentities(
					$vars['categories'][$category_id][1], ENT_QUOTES
				);
			}

			// Category order

			if ($this->input->get_post('Z') == FALSE)
			{
				$vars['form_action'] = 'C=admin_content'.AMP.'M=global_category_order'.AMP.'group_id='.$group_id;
				$vars['sort_order'] = $sort_order;
			}
		}

		$vars['can_edit'] = ($this->session->userdata('can_edit_categories') == 'y') ? TRUE : FALSE;
		$vars['can_delete'] = ($this->session->userdata('can_delete_categories') == 'y') ? TRUE : FALSE;
		$vars['group_id'] = $group_id;

		$this->cp->set_right_nav(array(
			'new_category'  => BASE.AMP.'C=admin_content'.AMP.'M=category_edit'.AMP.'group_id='.$group_id
		));

		$this->cp->render('admin/category_editor', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Category
	 *
	 * This function displays an existing category in a form so that it can be edited.
	 *
	 * @access	public
	 * @return	mixed
	 */
	function category_edit()
	{
		if (AJAX_REQUEST)
		{
			if ( ! $this->cp->allowed_group('can_edit_categories'))
			{
				show_error(lang('unauthorized_access'));
			}
		}
		else
		{
			$this->_restrict_prefs_access();
		}

		$this->load->model('category_model');
		$this->load->library('form_validation');

		$group_id = $this->input->get_post('group_id');

		if ($group_id != '')
		{
			if ( ! is_numeric($group_id))
			{
				show_error(lang('unauthorized_access'));
			}
		}

		//  Check discrete privileges
		if (AJAX_REQUEST)
		{
			$this->db->select('can_edit_categories');
			$this->db->where('group_id', $group_id);
			$query = $this->db->get('category_groups');

			if ($query->num_rows() == 0)
			{
				show_error(lang('unauthorized_access'));
			}

			$can_edit = explode('|', rtrim($query->row('can_edit_categories') , '|'));

			if ($this->session->userdata['group_id'] != 1 AND ! in_array($this->session->userdata['group_id'], $can_edit))
			{
				show_error(lang('unauthorized_access'));
			}
		}

		$vars['cat_id'] = $this->input->get_post('cat_id');

		$default = array('cat_name', 'cat_url_title', 'cat_description', 'cat_image', 'cat_id', 'parent_id');

		if ($vars['cat_id'] != '')
		{
			$this->db->select('cat_id, cat_name, cat_url_title, cat_description, cat_image, group_id, parent_id');
			$query = $this->db->get_where('categories', array('cat_id' => $vars['cat_id']));

			if ($query->num_rows() == 0)
			{
				show_error(lang('unauthorized_access'));
			}

			$row = $query->row_array();

			foreach ($default as $val)
			{
				$vars[$val] = $row[$val];
			}

			$vars['form_hidden']['cat_id'] = $vars['cat_id'];
			$vars['submit_lang_key'] = 'update';
		}
		else
		{
			foreach ($default as $val)
			{
				$vars[$val] = '';
			}

			$vars['submit_lang_key'] = 'submit';
		}

		//  Override the parent id if there is post data
		if ($this->input->post('parent_id'))
		{
			$vars['parent_id'] = $this->input->post('parent_id');
		}

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', lang('categories'));
		$this->view->cp_page_title = ($vars['cat_id'] == '') ? lang('new_category') : lang('edit_category');

		$foreign_characters = $this->_get_foreign_characters();

		// New entry gets URL title js
		if ($vars['submit_lang_key'] == 'submit')
		{
			// Pipe in necessary globals
			$this->javascript->set_global(array(
				'publish.word_separator'	=> $this->config->item('word_separator') != "dash" ? '_' : '-',
				'publish.foreignChars'		=> $foreign_characters,
			));

			// Load in necessary js files
			$this->cp->add_js_script(array(
				'plugin'	=> array('ee_url_title')
			));

			$this->javascript->keyup('#cat_name', '$("#cat_name").ee_url_title($("#cat_url_title"));');
		}

		$this->load->library('file_field');

		// If there is data in the category image field but the file field library
		// can't parse it, it's likely legacy data from when a URL was entered in a
		// text field for the category image. Let's prompt the user to update the
		// field before they save, otherwise the image will be cleared out.
		$vars['cat_image_error'] = '';
		if ( ! empty($vars['cat_image']) &&
			$this->file_field->parse_field($vars['cat_image']) === FALSE)
		{
			$vars['cat_image_error'] = lang('update_category_image');
		}

		// Setup category image
		$this->file_field->browser();
		$vars['cat_image'] = $this->file_field->field(
			'cat_image',
			$vars['cat_image'],
			'all',
			'image'
		);

		$vars['form_hidden']['group_id'] = $group_id;

		$this->load->library('api');
		$this->api->instantiate('channel_categories');
		$this->api_channel_categories->category_tree($group_id, $vars['parent_id']);

		$vars['parent_id_options'] = $this->api_channel_categories->categories;

		// Display custom fields

		$vars['cat_custom_fields'] = array();

		$this->db->where('group_id', $group_id);
		$this->db->order_by('field_order');
		$field_query = $this->db->get('category_fields');

		$this->db->where('cat_id', $vars['cat_id']);
		$data_query = $this->db->get('category_field_data');

		if ($field_query->num_rows() > 0)
		{
			$dq_row = $data_query->row_array();
			$this->load->model('addons_model');
			$plugins = $this->addons_model->get_plugin_formatting();

			$vars['custom_format_options']['none'] = 'None';
			foreach ($plugins as $k=>$v)
			{
				$vars['custom_format_options'][$k] = $v;
			}
			foreach ($field_query->result_array() as $row)
			{
				$vars['cat_custom_fields'][$row['field_id']]['field_content'] = ( ! isset($dq_row['field_id_'.$row['field_id']])) ? '' : $dq_row['field_id_'.$row['field_id']];

				$vars['cat_custom_fields'][$row['field_id']]['field_fmt'] = ( ! isset($dq_row['field_ft_'.$row['field_id']])) ? $row['field_default_fmt'] : $dq_row['field_ft_'.$row['field_id']];

				$vars['cat_custom_fields'][$row['field_id']]['field_id'] = $row['field_id'];
				$vars['cat_custom_fields'][$row['field_id']]['field_label'] = $row['field_label'];
				$vars['cat_custom_fields'][$row['field_id']]['field_required'] = $row['field_required'];

				$vars['cat_custom_fields'][$row['field_id']]['field_name'] = $row['field_name'];
				$vars['cat_custom_fields'][$row['field_id']]['field_input'] = $row['field_label'];

				$vars['cat_custom_fields'][$row['field_id']]['field_type'] = $row['field_type'];
				$vars['cat_custom_fields'][$row['field_id']]['field_text_direction'] = ($row['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr';
				$vars['cat_custom_fields'][$row['field_id']]['field_show_fmt'] = 'n'; // no by default, over-ridden later when appropriate

				$vars['field_fmt'] = $row['field_default_fmt'];

				//	Textarea field types

				if ($row['field_type'] == 'textarea')
				{
					$vars['cat_custom_fields'][$row['field_id']]['rows'] = ( ! isset($row['field_ta_rows'])) ? '10' : $row['field_ta_rows'];
					$vars['cat_custom_fields'][$row['field_id']]['field_show_fmt'] = $row['field_show_fmt'];

					if ($row['field_show_fmt'] != 'y')
					{
						$vars['form_hidden']['field_ft_'.$row['field_id']] = $vars['field_fmt'];
					}
				}

				//	Text input field types
				elseif ($row['field_type'] == 'text')
				{
					$vars['cat_custom_fields'][$row['field_id']]['field_maxl'] = $row['field_maxl'];

					if ($row['field_show_fmt'] == 'n')
					{
						$vars['form_hidden']['field_ft_'.$row['field_id']] = $vars['field_fmt'];
					}
				}

				//	Drop-down lists
				elseif ($row['field_type'] == 'select')
				{
					$text_direction = ($row['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr';

					unset($field_options); // in case another field type was here
					$field_options = array();

					foreach (explode("\n", trim($row['field_list_items'])) as $v)
					{
						$v = trim($v);
						$field_options[$v] = $v;
					}

					$vars['cat_custom_fields'][$row['field_id']]['field_options'] = $field_options;
				}
			}
		}

		$this->cp->render('admin/category_edit', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete category group confirm
	 *
	 * Warning message if you try to delete a category
	 *
	 * @access	public
	 * @return	mixed
	 */
	function category_delete_conf()
	{
		if (AJAX_REQUEST)
		{
			if ( ! $this->cp->allowed_group('can_delete_categories'))
			{
				show_error(lang('unauthorized_access'));
			}
		}
		else
		{
			$this->_restrict_prefs_access();
		}


		$cat_id = $this->input->get_post('cat_id');

		if ($cat_id == '' OR ! is_numeric($cat_id))
		{
			show_error(lang('not_authorized'));
		}


		$zurl = ($this->input->get_post('modal') == 'yes') ? AMP.'modal=yes' : '';
		$zurl .= ($this->input->get_post('cat_group') !== FALSE) ? AMP.'cat_group='.$this->input->get_post('cat_group') : '';
		$zurl .= ($this->input->get_post('integrated') !== FALSE) ? AMP.'integrated='.$this->input->get_post('integrated') : '';

		$this->load->model('category_model');

		$this->view->cp_page_title = lang('delete_category');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', lang('categories'));

		// Grab category_groups locations with this id
		$items = $this->category_model->get_category_name_group($cat_id);

		$vars = array(
			'form_action'	=> 'C=admin_content'.AMP.'M=category_delete'.$zurl,
			'form_extra'	=> '',
			'message'		=> lang('delete_cat_confirmation'),
			'items'			=> array(),
			'form_hidden'	=> array(
				'group_id'		=> $items->row('group_id'),
				'cat_id'		=> $cat_id
			)
		);

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->cat_name;
		}

		$this->cp->render('admin/preference_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Category
	 *
	 * This function deletes a single category
	 *
	 * @access	public
	 * @return	void
	 */
	function category_delete()
	{
		if (AJAX_REQUEST)
		{
			if ( ! $this->cp->allowed_group('can_delete_categories'))
			{
				show_error(lang('unauthorized_access'));
			}
		}
		else
		{
			$this->_restrict_prefs_access();
		}


		$cat_id = $this->input->get_post('cat_id');

		if ($cat_id == '' OR ! is_numeric($cat_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('category_model');

		$group_id = $this->category_model->delete_category($cat_id);

		$this->session->set_flashdata('message_success', lang('category_deleted'));
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP.'group_id='.$group_id);
	}

	// --------------------------------------------------------------------

	/** -----------------------------------------------------------
	/**  Category submission handler
	/** -----------------------------------------------------------*/
	// This function receives the category information after
	// being submitted from the form (new or edit) and stores
	// the info in the database.
	//-----------------------------------------------------------

	function category_update()
	{
		if (AJAX_REQUEST)
		{
			if ( ! $this->cp->allowed_group('can_edit_categories'))
			{
				show_error(lang('unauthorized_access'));
			}
		}
		else
		{
			$this->_restrict_prefs_access();
		}

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('unauthorized_access'));
		}

		$edit = ($this->input->post('cat_id') == '') ? FALSE : TRUE;

		$this->load->model('category_model');
		$this->load->library('api');
		$this->api->instantiate('channel_categories');

		// Create and validate Category URL Title
		// Kill all the extraneous characters. (We want the URL title to be pure alpha text)

		$word_separator = $this->config->item('word_separator');

		$this->load->library('form_validation');

		if ($this->input->post('cat_url_title') == '')
		{
			$_POST['cat_url_title'] = url_title($this->input->post('cat_name'), $word_separator, TRUE);
		}
		else
		{
			$_POST['cat_url_title'] = url_title($_POST['cat_url_title'], $word_separator);
		}

		$this->form_validation->set_rules('cat_name', 'lang:category_name', 'required|strip_tags|valid_xss_check');
		$this->form_validation->set_rules('cat_url_title', 'lang:cat_url_title', 'callback__cat_url_title');
		$this->form_validation->set_rules('cat_description', 'lang:cat_description', 'valid_xss_check');

		// Get the Category Image
		$this->load->library('file_field');
		$cat_image = $this->file_field->validate(
			$this->input->post('cat_image'),
			'cat_image'
		);

		$_POST['cat_image'] = $cat_image['value'];

		// Finish data prep for insertion
		if ($this->config->item('auto_convert_high_ascii') == 'y')
		{
			// Load the text helper
			$this->load->helper('text');

			$_POST['cat_name'] =  ascii_to_entities($_POST['cat_name']);
		}

		// Pull out custom field data for later insertion

		$fields = array();

		foreach ($_POST as $key => $val)
		{
			if (strpos($key, 'field') !== FALSE)
			{
				$fields[$key] = $val;
			}
		}

		// Check for missing required custom fields
		$this->db->select('field_id, field_label');
		$this->db->where('group_id', $group_id);
		$this->db->where('field_required', 'y');
		$query = $this->db->get('category_fields');

		$required_cat_fields = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$required_cat_fields[$row['field_id']] = $row['field_label'];
				$this->form_validation->set_rules('field_id_'.$row['field_id'],	$row['field_label'], 'required');
				$this->form_validation->set_rules('field_ft_'.$row['field_id'],	'', '');
			}
		}

		foreach ($fields as $id => $val)
		{
			if ( ! isset($required_cat_fields[$id]))
			{
				$this->form_validation->set_rules('field_id_'.$id,	'', '');
				$this->form_validation->set_rules('field_ft_'.$id,	'', '');
			}
		}


		$this->form_validation->set_error_delimiters('<br /><span class="notice">', '<br />');

		if ($this->form_validation->run() === FALSE)
		{
			return $this->category_edit();
		}

		$_POST['site_id'] = $this->config->item('site_id');

		$category_data = array(
			'group_id'			=> $group_id,
			'cat_name'			=> $this->input->post('cat_name'),
			'cat_url_title'		=> $this->input->post('cat_url_title'),
			'cat_description'	=> $this->input->post('cat_description'),
			'cat_image'			=> $this->input->post('cat_image'),
			'parent_id'			=> $this->input->post('parent_id'),
			'cat_order'			=> 1, // Default to the new category appearing first
			'site_id'			=> $this->input->post('site_id')
		);

		if ($edit == FALSE)
		{
			$this->db->insert('categories', $category_data);
			$cat_id = $this->db->insert_id();

			$update = FALSE;

			// Increment each pre-existing category's sort order to make room for the n00b
			$this->db->set('cat_order', 'cat_order + 1', FALSE);
			$this->db->where('cat_id !=', $cat_id);
			$this->db->where('group_id', $group_id);
			$this->db->where('parent_id', $_POST['parent_id']);
			$this->db->update('categories');
		}
		else
		{
			if ($_POST['cat_id'] == $_POST['parent_id'])
			{
				$_POST['parent_id'] = 0;
			}

			// Check for parent becoming child of its child...oy!

			$this->db->select('parent_id, group_id');
			$this->db->where('cat_id', $this->input->post('cat_id'));
			$query = $this->db->get('categories');

			if ($this->input->get_post('parent_id') !== 0 && $query->num_rows() > 0 && $query->row('parent_id')  !== $this->input->get_post('parent_id'))
			{
				$children  = array();

				// Fetch parent info
				$this->db->select('cat_name, cat_id, parent_id');
				$this->db->where('group_id', $group_id);
				$this->db->from('categories');
				$this->db->order_by('parent_id, cat_name');

				$query = $this->db->get();

				if ($query->num_rows() == 0)
				{
					$update = FALSE;
					return $this->category_editor($group_id, $update);
				}

				// Assign the query result to a multi-dimensional array
				foreach($query->result_array() as $row)
				{
					$cat_array[$row['cat_id']]	= array($row['parent_id'], $row['cat_name']);
				}

				foreach($cat_array as $key => $values)
				{
					if ($values['0'] == $this->input->post('cat_id'))
					{
						$children[] = $key;
					}
				}

				if (count($children) > 0)
				{
					if (($key = array_search($this->input->get_post('parent_id'), $children)) !== FALSE)
					{
						$this->db->update(
							'categories',
							array('parent_id' => $query->row('parent_id')),
							array('cat_id' => $children[$key])
						);
					}
					else	// Find All Descendants
					{
						while(count($children) > 0)
						{
							$now = array_shift($children);

							foreach($cat_array as $key => $values)
							{
								if ($values[0] == $now)
								{
									if ($key == $this->input->get_post('parent_id'))
									{
										$this->db->update(
											'categories',
											array('parent_id' => $query->row('parent_id')),
											array('cat_id' => $key)
										);
										break 2;
									}

									$children[] = $key;
								}
							}
						}
					}
				}
			}

			$sql = $this->db->update_string(
				'exp_categories',
				array(
					'cat_name'  		=> $this->input->post('cat_name'),
					'cat_url_title'		=> $this->input->post('cat_url_title'),
					'cat_description'	=> $this->input->post('cat_description'),
					'cat_image' 		=> $this->input->post('cat_image'),
					'parent_id' 		=> $this->input->post('parent_id')
				),
				array(
					'cat_id'	=> $this->input->post('cat_id'),
					'group_id'  => $this->input->post('group_id')
				)
			);

			$this->db->query($sql);
			$update = TRUE;

			// need this later for custom fields
			$cat_id = $this->input->post('cat_id');
		}

		// Need to re-sort alphabetically now?
		$this->db->select('sort_order');
		$query = $this->db->get_where('category_groups', array('group_id' => $group_id));

		if ($query->num_rows() == 1 && $query->row()->sort_order == 'a')
		{
			$this->_reorder_cats_alphabetically($group_id);
		}

		// Insert / Update Custom Field Data
		if ($edit == FALSE)
		{
			$fields['site_id'] = $this->config->item('site_id');
			$fields['cat_id'] = $cat_id;
			$fields['group_id'] = $group_id;

			$this->db->insert('category_field_data', $fields);
		}
		elseif ( ! empty($fields))
		{
			$this->db->query($this->db->update_string('exp_category_field_data', $fields, array('cat_id' => $cat_id)));
		}

		// -------------------------------------------
		// 'category_save' hook.
		//
		if (ee()->extensions->active_hook('category_save') === TRUE)
		{
			ee()->extensions->call('category_save', $cat_id, $category_data);
		}
		//
		// -------------------------------------------

		$this->session->set_flashdata('message_success', lang('preference_updated'));
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP."group_id={$group_id}");
	}

	function _get_foreign_characters()
	{
		$foreign_characters = FALSE;

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

		if ( ! $foreign_characters)
		{
			//	Create Foreign Character Conversion JS
			include(APPPATH.'config/foreign_chars.php');
		}

		return $foreign_characters;
	}

	// --------------------------------------------------------------------

	/**
	 * Category URL Title
	 *
	 *
	 *
	 */
	function _cat_url_title($str)
	{
		$this->load->model('category_model');

		// Is the cat_url_title a pure number?  If so we show an error.
		if (is_numeric($str))
		{
			$this->form_validation->set_message('_cat_url_title', lang('cat_url_title_is_numeric'));
			return FALSE;
		}

		// Is the Category URL Title still empty?  Can't have that
		if (trim($str) == '')
		{
			$this->form_validation->set_message('_cat_url_title', lang('unable_to_create_cat_url_title'));
			return FALSE;
		}

		// Cat URL Title must be unique within the group
		if ($this->category_model->is_duplicate_category_name($str, $this->input->post('cat_id'), $this->input->post('group_id')))
		{
			$this->form_validation->set_message('_cat_url_title', lang('duplicate_cat_url_title'));
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Set Global Category Order
	 */
	function global_category_order()
	{
		if (AJAX_REQUEST)
		{
			if ( ! $this->cp->allowed_group('can_edit_categories'))
			{
				show_error(lang('unauthorized_access'));
			}
		}
		else
		{
			$this->_restrict_prefs_access();
		}

		if (($group_id = $this->input->get_post('group_id')) === FALSE OR ! is_numeric($group_id))
		{
			return FALSE;
		}

		$order = ($_POST['sort_order'] == 'a') ? 'a' : 'c';

		if ($order == 'a')
		{
			if ( ! isset($_POST['override']))
			{
				return $this->global_category_order_confirm();
			}
			else
			{
				$this->_reorder_cats_alphabetically($group_id);
			}
		}

		$this->db->where('group_id', $group_id);
		$this->db->update('category_groups', array('sort_order' => $order));

		$zurl = ($this->input->get_post('modal') == 'yes') ? AMP.'modal=yes' : '';
		$zurl .= ($this->input->get_post('cat_group') !== FALSE) ? AMP.'cat_group='.$this->input->get_post('cat_group') : '';
		$zurl .= ($this->input->get_post('integrated') !== FALSE) ? AMP.'integrated='.$this->input->get_post('integrated') : '';


		// Clear 'ze cache
		$this->functions->clear_caching('db');

		$this->session->set_flashdata('message_success', lang('preferences_updated'));

		// Return Location
		$return = BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP.'group_id='.$group_id.$zurl;
		$this->functions->redirect($return);
	}

	// --------------------------------------------------------------------

	/**
	 * Category order change confirm
	 */
	function global_category_order_confirm()
	{
		if (AJAX_REQUEST)
		{
			if ( ! $this->cp->allowed_group('can_edit_categories'))
			{
				show_error(lang('unauthorized_access'));
			}
		}
		else
		{
			$this->_restrict_prefs_access();
		}

		if (($group_id = $this->input->get_post('group_id')) === FALSE OR ! is_numeric($group_id))
		{
			return FALSE;
		}

		$this->view->cp_page_title = lang('global_sort_order');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP.'group_id='.$group_id, lang('categories'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=global_category_order'.AMP.'group_id='.$group_id;

		$vars['form_hidden']['sort_order'] = $this->input->post('sort_order');
		$vars['form_hidden']['override'] = 1;

		$this->cp->render('admin/category_order_confirm', $vars);

	}

	/** --------------------------------
	/**  Re-order Categories Alphabetically
	/** --------------------------------*/

	private function _reorder_cats_alphabetically($group_id)
	{
		if ( ! isset($group_id) OR ! is_numeric($group_id))
		{
			return FALSE;
		}

		$data = $this->process_category_group($group_id);

		if (count($data) == 0)
		{
			return FALSE;
		}

		foreach($data as $cat_id => $cat_data)
		{
			$this->db->query("UPDATE exp_categories SET cat_order = '{$cat_data['1']}' WHERE cat_id = '{$cat_id}'");
		}

		return TRUE;
	}



	/** --------------------------------
	/**  Process nested category group
	/** --------------------------------*/

	function process_category_group($group_id)
	{
		if (AJAX_REQUEST)
		{
			if ( ! $this->cp->allowed_group('can_edit_categories'))
			{
				show_error(lang('unauthorized_access'));
			}
		}
		else
		{
			$this->_restrict_prefs_access();
		}

		$this->db->select('cat_name, cat_id, parent_id');
		$this->db->where('group_id', $group_id);
		$this->db->order_by('parent_id, cat_name');
		$categories = $this->db->get('categories');

		if ($categories->num_rows() == 0)
		{
			return FALSE;
		}

		$order = 0;
		$parent = 0;

		foreach($categories->result_array() as $category)
		{
			// Once we're on a new parent, reset the ordering
			if ($category['parent_id'] != $parent)
			{
				$order = 0;
				$parent = $category['parent_id'];
			}

			$order++;

			$this->cat_update[$category['cat_id']] = array(
				$category['parent_id'],
				$order,
				$category['cat_name']
			);
		}

		return $this->cat_update;
	}

	// ------------------------------------------------------------------------

	/**
	 * Change Category Order
	 */
	function change_category_order()
	{
		if (AJAX_REQUEST)
		{
			if ( ! $this->cp->allowed_group('can_edit_categories'))
			{
				show_error(lang('unauthorized_access'));
			}
		}
		else
		{
			$this->_restrict_prefs_access();
		}

		// Fetch required globals
		foreach (array('cat_id', 'group_id', 'order') as $val)
		{
			if ( ! $this->input->get($val))
			{
				return FALSE;
			}

			if ($val == 'cat_id' OR $val == 'group_id')
			{
				$$val = (int) $this->input->get($val);
			}
			else
			{
				$order = $this->input->get('order');
			}
		}

		// Return Location
		$return = BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP.'group_id='.$group_id;

		// Fetch the parent ID

		$qry = $this->db->select('parent_id')
						->where('cat_id', $cat_id)
						->get('categories');

		$parent_id = $qry->row('parent_id');

		// Is the requested category already at the beginning/end of the list?

		$dir = ($order == 'up') ? 'asc' : 'desc';

		$qry = $this->db->select('cat_id')
						->where('group_id', $group_id)
						->where('parent_id', $parent_id)
						->order_by('cat_order', $dir)
						->limit(1)
						->get('categories');

		if ($qry->row('cat_id') == $cat_id)
		{
			$this->functions->redirect($return);
		}


		// Fetch all the categories in the parent
		$this->db->select('cat_id, cat_order');
		$this->db->where('group_id', $group_id);
		$this->db->where('parent_id', $parent_id);
		$this->db->order_by('cat_order ASC');
		$query = $this->db->get('categories');

		// If there is only one category, there is nothing to re-order
		if ($query->num_rows() <= 1)
		{
			$this->functions->redirect($return);
		}

		// Assign category ID numbers in an array except the category being shifted.
		// We will also set the position number of the category being shifted, which
		// we'll use in array_shift()

		$flag	= '';
		$i		= 1;
		$cats	= array();

		foreach ($query->result_array() as $row)
		{
			if ($cat_id == $row['cat_id'])
			{
				$flag = ($order == 'down') ? $i+1 : $i-1;
			}
			else
			{
				$cats[] = $row['cat_id'];
			}

			$i++;
		}

		array_splice($cats, ($flag -1), 0, $cat_id);

		// Update the category order for all the categories within the given parent

		$i = 1;

		foreach ($cats as $val)
		{
			$this->db->where('cat_id', $val);
			$this->db->update('categories', array('cat_order' => $i));

			$i++;
		}

		// Switch to custom order
		$this->db->where("group_id", $group_id);
		$this->db->update('category_groups', array('sort_order' => 'c'));

		$this->session->set_flashdata('message_success', lang('preferences_updated'));
		$this->functions->redirect($return);
	}

	// --------------------------------------------------------------------


	/**
	  *  Category Field Group Form
	  *
	  * This function displays the field group management form
	  * and allows you to delete, modify, or create a
	  * category custom field
	*/
	function category_custom_field_group_manager($message = '')
	{
		$this->_restrict_prefs_access();

		$vars['message'] = $message; //lang('preferences_updated')

		$vars['group_id'] = $this->input->get_post('group_id');

		if ($vars['group_id'] == '' OR ! is_numeric($vars['group_id']))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->library('table');
		$this->load->model('category_model');

		$this->view->cp_page_title = lang('custom_category_fields');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', lang('categories'));

		// Fetch the name of the category group
		$query = $this->category_model->get_category_group_name($vars['group_id']);
		$vars['group_name'] = $query->row('group_name');

		$this->db->select('field_id, field_name, field_label, field_type, field_order');
		$this->db->from('category_fields');
		$this->db->where('group_id', $vars['group_id']);
		$this->db->order_by('field_order');
		$custom_fields = $this->db->get();

		$vars['custom_fields'] = array();

		if ($custom_fields->num_rows() > 0)
		{
			foreach ($custom_fields->result() as $row)
			{
				$vars['custom_fields'][$row->field_id]['field_id'] = $row->field_id;
				$vars['custom_fields'][$row->field_id]['field_name'] = $row->field_name;
				$vars['custom_fields'][$row->field_id]['field_order'] = $row->field_order;
				$vars['custom_fields'][$row->field_id]['field_label'] = $row->field_label;

				switch ($row->field_type)
				{
					case 'text' :  $field_type = lang('text_input');
						break;
					case 'textarea' :  $field_type = lang('textarea');
						break;
					case 'select' :  $field_type = lang('select_list');
						break;
				}

				$vars['custom_fields'][$row->field_id]['field_type'] = $field_type;
			}
		}

		$this->jquery->tablesorter('.mainTable', '{
			headers: {3: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->cp->set_right_nav(array(
			'create_new_cat_field' => BASE.AMP.'C=admin_content'.AMP.'M=edit_custom_category_field'.AMP.'group_id='.$vars['group_id']
		));

		$this->cp->render('admin/category_custom_field_group_manager', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Custom Category Field
	 *
	 * Used to edit or create a custom category field
	 *
	 * @access	public
	 * @return	void
	 */
	function edit_custom_category_field()
	{
		$this->_restrict_prefs_access();

		$vars['group_id'] = $this->input->get_post('group_id');
		$group_id = $vars['group_id'];

		$vars['field_id'] = $this->input->get_post('field_id');
		$field_id = $vars['field_id'];

		if ($vars['group_id'] == '' OR ! is_numeric($vars['group_id']))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('addons_model');
		$this->load->helper('snippets_helper');
		$this->load->library('table');

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', lang('categories'));

		$this->javascript->change('#field_type', '
			// hide all field format options
			$(".field_format_option").hide();

			// reveal selected option
			$("#"+$(this).val()+"_format").show();
		');

		// New entry gets URL title js
		if ($vars['field_id'] == '')
		{
			$foreign_characters = $this->_get_foreign_characters();

			// Pipe in necessary globals
			$this->javascript->set_global(array(
				'publish.word_separator'	=> $this->config->item('word_separator') != "dash" ? '_' : '-',
				'publish.foreignChars'		=> $foreign_characters,
			));

			// Load in necessary js files
			$this->cp->add_js_script(array(
				'plugin'	=> array('ee_url_title')
			));

			$this->javascript->keyup('#field_label', '$("#field_label").ee_url_title($("#field_name"));');
		}

		if ($vars['field_id'] == '')
		{
			$vars['update_formatting'] = FALSE;
			$this->view->cp_page_title = lang('create_new_cat_field');

			$vars['submit_lang_key'] = 'submit';

			$this->db->select('group_id');
			$this->db->where('group_id', $vars['group_id']);
			$query = $this->db->get('category_fields');

			$vars['field_order'] = $query->num_rows() + 1;

			$field_id = '';

			if ($query->num_rows() > 0)
			{
				$group_id = $query->row('group_id') ;
			}
			else
			{
				// if there are no existing category fields yet for this group,
				// this allows us to still validate the group_id
				$this->db->select('COUNT(*) AS count');
				$this->db->where('group_id', $group_id);
				$this->db->where('site_id', $this->config->item('site_id'));
				$gquery = $this->db->get('category_groups');

				if ($gquery->row('count')  != 1)
				{
					show_error(lang('unauthorized_access'));
				}
			}
		}
		else
		{
			$vars['update_formatting'] = TRUE;

			$this->javascript->output('$(".formatting_notice_info").hide();');

			$this->view->cp_page_title = lang('edit_cat_field');

			$vars['submit_lang_key'] = 'update';

			$this->javascript->change('#field_default_fmt', '
				// give formatting change notice and checkbox

				$(".formatting_notice_info").show();
				$("#show_formatting_buttons").show();
			');

			$this->db->select('field_id, group_id');
			$this->db->where('group_id', $group_id);
			$this->db->where('field_id', $field_id);
			$query = $this->db->get('category_fields');

			$vars['field_order'] = '';

			if ($query->num_rows() == 0)
			{
				return FALSE;
			}

			$field_id = $query->row('field_id') ;
			$group_id = $query->row('group_id') ;

			$vars['form_hidden']['field_id'] = $field_id;
		}

		$query = $this->db->query("SELECT f.field_id, f.field_name, f.site_id, f.field_label, f.field_type, f.field_default_fmt, f.field_show_fmt,
							f.field_list_items, f.field_maxl, f.field_ta_rows, f.field_text_direction, f.field_required, f.field_order,
							g.group_name
							FROM exp_category_fields AS f, exp_category_groups AS g
							WHERE f.group_id = g.group_id
							AND g.group_id = '{$group_id}'
							AND f.field_id = '{$field_id}'");

		$data = array();

		if ($query->num_rows() == 0)
		{
			foreach ($query->list_fields() as $f)
			{
				$data[$f] = '';
				$$f = '';
				$vars[$f] = '';
			}
		}
		else
		{
			foreach ($query->row_array() as $key => $val)
			{
				$data[$key] = $val;
				$$key = $val;
				$vars[$key] = $val;
			}
		}

		// Adjust $group_name for new custom fields as we display this later

		if ($group_name == '')
		{
			$query = $this->db->query("SELECT group_name FROM exp_category_groups WHERE group_id = '{$group_id}'");

			if ($query->num_rows() > 0)
			{
				$group_name = $query->row('group_name') ;
			}
		}

		$vars['form_hidden']['group_id'] = $vars['group_id'];

		$vars['field_maxl'] = ($vars['field_maxl'] == '') ? 128 : $vars['field_maxl'];
		$vars['field_ta_rows'] = ($vars['field_ta_rows'] == '') ? 6 : $vars['field_ta_rows'];

		$vars['field_type_options'] = array(
											'text' 		=> lang('text_input'),
											'textarea' 	=> lang('textarea'),
											'select' 	=> lang('select_list')
		);

		// Show field formatting?
		if ($vars['field_show_fmt'] == 'n')
		{
			$vars['field_show_fmt_y'] = FALSE;
			$vars['field_show_fmt_n'] = TRUE;
		}
		else
		{
			$vars['field_show_fmt_y'] = TRUE;
			$vars['field_show_fmt_n'] = FALSE;
		}

		// build list of formatting options
		$vars['field_default_fmt_options']['none'] = lang('none');

		// Fetch formatting plugins
		$plugin_formatting = $this->addons_model->get_plugin_formatting();
		foreach ($plugin_formatting as $k=>$v)
		{
			$vars['field_default_fmt_options'][$k] = $v;
		}

		// Text Direction
		if ($vars['field_text_direction'] == 'rtl')
		{
			$vars['field_text_direction_ltr'] = FALSE;
			$vars['field_text_direction_rtl'] = TRUE;
		}
		else
		{
			$vars['field_text_direction_ltr'] = TRUE;
			$vars['field_text_direction_rtl'] = FALSE;
		}

		// Is field required?
		if ($vars['field_required'] == 'n')
		{
			$vars['field_required_y'] = FALSE;
			$vars['field_required_n'] = TRUE;
		}
		else
		{
			$vars['field_required_y'] = TRUE;
			$vars['field_required_n'] = FALSE;
		}

		// Hide/show field formatting options
		$this->javascript->output('
			// hide all field format options
			$(".field_format_option").hide();
			// reveal text as default
			$("#'.$vars['field_type'].'_format").show();

			// if the formatting changes, we can reveal this option
			$("#formatting_notice").hide();
		');

		$this->cp->render('admin/edit_custom_category_field', $vars);
	}

	// --------------------------------------------------------------------

	/** -----------------------------------------------------------
	/**  Update Category Fields
	/** -----------------------------------------------------------*/
	// This function updates or creates category fields
	//-----------------------------------------------------------
	function update_custom_category_fields()
	{
		$this->_restrict_prefs_access();

		// Are we editing or creating?

		$edit = (($field_id = $this->input->get_post('field_id')) !== FALSE AND is_numeric($field_id)) ? TRUE : FALSE;

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('unauthorized_access'));
		}

		unset($_POST['custom_field_edit']); // submit button

		// Check for required fields

		$error = array();

		if ($_POST['field_name'] == '')
		{
			$error[] = lang('no_field_name');
		}
		else
		{
			// Is the field one of the reserved words?

			if (in_array($_POST['field_name'], $this->cp->invalid_custom_field_names()))
			{
				$error[] = lang('reserved_word');
			}
			$field_name = $_POST['field_name'];
		}

		if ($_POST['field_label'] == '')
		{
			$error[] = lang('no_field_label');
		}

		// Does field name contain invalid characters?

		if (preg_match('/[^a-z0-9\_\-]/i', $_POST['field_name']))
		{
			$error[] = lang('invalid_characters');
		}

		// Field name must be unique for across category groups

		if ($edit == FALSE)
		{
			$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_category_fields WHERE site_id = '".$this->db->escape_str($this->config->item('site_id'))."' AND field_name = '".$this->db->escape_str($_POST['field_name'])."'");

			if ($query->row('count')  > 0)
			{
				$error[] = lang('duplicate_field_name');
			}
		}

		// Are there errors to display?

		if (count($error) > 0)
		{
			$str = '';

			foreach ($error as $msg)
			{
				$str .= $msg.BR;
			}

			show_error($str);
		}

		if ($_POST['field_list_items'] != '')
		{
			$_POST['field_list_items'] = quotes_to_entities($_POST['field_list_items']);
		}

		if ( ! in_array($_POST['field_type'], array('text', 'textarea', 'select')))
		{
			$_POST['field_text_direction'] = 'ltr';
		}

		// Construct the query based on whether we are updating or inserting

		if ($edit === TRUE)
		{
			// validate field id

			$query = $this->db->query("SELECT field_id FROM exp_category_fields WHERE group_id = '".$this->db->escape_str($group_id)."' AND field_id = '".$this->db->escape_str($field_id)."'");

			if ($query->num_rows() == 0)
			{
				return FALSE;
			}

			// Update the formatting for all existing entries
			if (isset($_POST['update_formatting']))
			{
				$this->db->query("UPDATE exp_category_field_data SET field_ft_{$field_id} = '".$this->db->escape_str($_POST['field_default_fmt'])."'");
			}

			unset($_POST['group_id']);
			unset($_POST['update_formatting']);

			$this->db->query($this->db->update_string('exp_category_fields', $_POST, "field_id='".$field_id."'"));

			$cp_message = lang('cat_field_edited');
		}
		else
		{
			unset($_POST['update_formatting']);

			if ($_POST['field_order'] == 0 OR $_POST['field_order'] == '')
			{
				$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_category_fields WHERE group_id = '".$this->db->escape_str($group_id)."'");

				$_POST['field_order'] = $query->num_rows() + 1;
			}

			$_POST['site_id'] = $this->config->item('site_id');

			$this->db->insert('category_fields', $_POST);

			$insert_id = $this->db->insert_id();

			$this->db->query("ALTER TABLE exp_category_field_data ADD COLUMN field_id_{$insert_id} text NULL");
			$this->db->query("ALTER TABLE exp_category_field_data ADD COLUMN field_ft_{$insert_id} varchar(40) NULL default 'none'");
			$this->db->query("UPDATE exp_category_field_data SET field_ft_{$insert_id} = '".$this->db->escape_str($_POST['field_default_fmt'])."'");

			$cp_message = lang('cat_field_created');
		}

		$this->functions->clear_caching('all', '');

		$this->session->set_flashdata('message_success', $cp_message.' '.$field_name);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=category_custom_field_group_manager'.AMP.'group_id='.$group_id);
	}

	// --------------------------------------------------------------------

	/**
	  * Delete Category Custom Field Confirmation
	  *
	  * This function displays a confirmation form for deleting
	  * a category custom field
	  */
	function delete_custom_category_field_confirm()
	{
		$this->_restrict_prefs_access();

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$field_id = $this->input->get_post('field_id');

		if ($field_id == '' OR ! is_numeric($field_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('category_model');

		$this->view->cp_page_title = lang('delete_field');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', lang('categories'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=delete_custom_category_field';
		$vars['form_extra'] = '';
		$vars['form_hidden']['group_id'] = $group_id;
		$vars['form_hidden']['field_id'] = $field_id;
		$vars['message'] = lang('delete_cat_field_confirmation');

		// Grab category_groups locations with this id
		$items = $this->category_model->get_category_label_name($group_id, $field_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->field_label;
		}

		$this->cp->render('admin/preference_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  * Delete Custom Category Field
	  *
	  * This function deletes a category field
	  */
	function delete_custom_category_field()
	{
		$this->_restrict_prefs_access();

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$field_id = $this->input->get_post('field_id');

		if ($field_id == '' OR ! is_numeric($field_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('category_model');

		$query = $this->category_model->get_category_label_name($group_id, $field_id);

		if ($query->num_rows() == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->category_model->delete_category_field($group_id, $field_id);

		$cp_message = lang('cat_field_deleted').NBS.$query->row('field_label');
		$this->logger->log_action($cp_message);

		$this->functions->clear_caching('all', '');

		$this->session->set_flashdata('message_success', $cp_message);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=category_custom_field_group_manager'.AMP.'group_id='.$group_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Field Group Management
	 *
	 * This function show the "Custom channel fields" overview page, accessed via the "admin" tab
	 *
	 * @access	public
	 * @return	void
	 */
	function field_group_management($message = '')
	{
		$this->_restrict_prefs_access();

		$this->load->library('table');
		$this->load->model('field_model');

		$this->view->cp_page_title = lang('field_management');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content', lang('admin_content'));

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}},
			widgets: ["zebra"]
		}');

		$vars['message'] = $message;
		$vars['field_groups'] = $this->field_model->get_field_groups(); // Fetch field groups

		$this->cp->set_right_nav(array('create_new_field_group' => BASE.AMP.'C=admin_content'.AMP.'M=field_group_edit'));

		$this->cp->render('admin/field_group_management', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Field Group Edit
	 *
	 * Creates the Edit Field Group page
	 *
	 * @access	public
	 * @return	void
	 */
	function field_group_edit()
	{
		$this->_restrict_prefs_access();

		$this->load->model('status_model');
		$this->load->model('field_model');

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management', lang('field_management'));

		// Set default values
		$vars['group_name'] = '';
		$vars['form_hidden'] = array();

		// If we have the group_id variable it's an edit request, so fetch the status data
		$group_id = $this->input->get_post('group_id');

		if ($group_id != '')
		{
			$this->view->cp_page_title = lang('rename_group');

			$vars['form_hidden']['group_id'] = $group_id;

			$vars['submit_lang_key'] = 'update';

			if ( ! is_numeric($group_id))
			{
				show_error(lang('not_authorized'));
			}

			$query = $this->field_model->get_field_group($group_id);

			foreach ($query->row() as $key => $val)
			{
				$vars[$key] = $val;
			}
		}
		else
		{
			$this->view->cp_page_title = lang('new_field_group');
			$vars['submit_lang_key'] = 'submit';
		}

		$this->cp->render('admin/field_group_edit', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Field Group Delete Confirm
	 *
	 * Warning message shown when you try to delete a field group
	 *
	 * @access	public
	 * @return	mixed
	 */
	function field_group_delete_confirm()
	{
		$this->_restrict_prefs_access();

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('field_model');

		$this->view->cp_page_title = lang('delete_group');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management', lang('field_management'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=field_group_delete';
		$vars['form_extra'] = '';
		$vars['form_hidden']['group_id'] = $group_id;
		$vars['message'] = lang('delete_field_group_confirmation');

		// Grab category_groups locations with this id
		$items = $this->field_model->get_field_group($group_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->group_name;
		}

		$this->cp->render('admin/preference_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Field Group Delete
	 *
	 * Deletes Field Groups
	 *
	 * @access	public
	 * @return	void
	 */
	function field_group_delete()
	{
		$this->_restrict_prefs_access();

		$group_id = $this->input->get_post('group_id');
		$tabs = array();

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('field_model');

		// store the name for the delete message
		$group_name = $this->field_model->get_field_group($group_id);

		// delete routine
		$deleted = $this->field_model->delete_field_groups($group_id);

		// Drop from custom layouts
		$query = $this->field_model->get_assigned_channels($group_id);

		if ($query->num_rows() > 0 && isset($deleted['field_ids']) && count($deleted['field_ids']) > 0)
		{
			foreach ($query->result() as $row)
			{
				$channel_ids[] = $row->channel_id;
			}

			$this->load->library('layout');
			$this->layout->delete_layout_fields($deleted['field_ids'], $channel_ids);
		}

		$this->functions->clear_caching('all', '');

		$cp_message = lang('field_group_deleted').NBS.NBS.$group_name->row('group_name');

		$this->logger->log_action($cp_message);

		$this->session->set_flashdata('message_success', $cp_message);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management');

	}

	// --------------------------------------------------------------------

	/**
	 * Field Group Update
	 *
	 * This function receives the submitted field group data
	 * and puts it in the database
	 *
	 * @access	public
	 * @return	void
	 */
	function field_group_update()
	{
		$this->_restrict_prefs_access();

		$group_id = $this->input->get_post('group_id');

		// If the $group_id variable is present we are editing an
		// existing group, otherwise we are creating a new one
		$edit = (isset($_POST['group_id'])) ? TRUE : FALSE;

		$group_name = $this->input->post('group_name');

		if ($group_name == '')
		{
			return $this->field_group_edit();
		}

		if ( ! preg_match("#^[a-zA-Z0-9_\-/\s]+$#i", $group_name))
		{
			show_error(lang('illegal_characters'));
		}

		$this->load->model('field_model');

		// Is the group name taken?
		if ($this->field_model->is_duplicate_field_group_name($group_name, $group_id))
		{
			show_error(lang('taken_field_group_name'));
		}

		// Construct the query based on whether we are updating or inserting
		if ($edit === FALSE)
		{
			$this->field_model->insert_field_group($group_name);

			$cp_message = lang('field_group_created').NBS.$group_name;

			$this->logger->log_action($cp_message);

			$this->db->select('channel_id');
			$this->db->where('site_id', $this->config->item('site_id'));
			$channel_info = $this->db->get('channels');

			$query = $this->db->query("SELECT channel_id from exp_channels WHERE site_id = '".$this->db->escape_str($this->config->item('site_id'))."'");

			if ($channel_info->num_rows() > 0)
			{
				$cp_message .= '<br />'.lang('assign_group_to_channel').NBS;

				if ($channel_info->num_rows() == 1)
				{
					$link = 'C=admin_content'.AMP.'M=channel_edit_group_assignments'.AMP.'channel_id='.$channel_info->row('channel_id');
					$cp_message .= '<a href="'.BASE.AMP.$link.'">'.lang('click_to_assign_group').'</a>';
				}
				else
				{
					$link = 'C=admin_content';
				}
			}
		}
		else
		{
			$data = array(
					'group_name'	=> $group_name,
					'site_id'		=> $this->config->item('site_id')
				);

			$this->db->where('group_id', $group_id);
			$this->db->update('field_groups', $data);

			$cp_message = lang('field_group_updated').NBS.$group_name;
		}

		$this->session->set_flashdata('message_success', $cp_message);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management');
	}

	// --------------------------------------------------------------------

	/**
	  * Add or Edit Field Group
	  *
	  * This function show a list of current fields in a group
	  *
	  * @access	public
	  * @return	void
	  */
	function field_management($group_id = '', $message = '')
	{
		$this->_restrict_prefs_access();

		$vars['group_id'] = ($group_id != '') ? $group_id : $this->input->get_post('group_id');

		if ($vars['group_id'] == '' OR ! is_numeric($vars['group_id']))
		{
			show_error(lang('not_authorized'));
		}

		$this->cp->set_right_nav(array(
			'create_new_custom_field' =>
			BASE.AMP.'C=admin_content'.AMP.'M=field_edit'.AMP.'group_id='.$vars['group_id']
		));

		$vars['message'] = $message; //lang('preferences_updated')

		$this->load->library('table');
		$this->load->model('field_model');

		// Fetch the name of the category group
		$query = $this->field_model->get_field_group($vars['group_id']);
		$vars['group_name'] = $query->row('group_name');

		$this->view->cp_page_title = lang('group').':'.NBS.$vars['group_name'];
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management', lang('field_management'));

		$custom_fields = $this->field_model->get_fields($vars['group_id'], array('site_id' => $this->config->item('site_id')));

		$vars['custom_fields'] = array();

		if ($custom_fields->num_rows() > 0)
		{
			$this->load->library('api');
			$this->api->instantiate('channel_fields');
			$fts = $this->api_channel_fields->fetch_all_fieldtypes();

			foreach ($custom_fields->result() as $row)
			{
				$vars['custom_fields'][$row->field_id]['field_id'] = $row->field_id;
				$vars['custom_fields'][$row->field_id]['field_name'] = $row->field_name;
				$vars['custom_fields'][$row->field_id]['field_order'] = $row->field_order;
				$vars['custom_fields'][$row->field_id]['field_label'] = $row->field_label;
				$vars['custom_fields'][$row->field_id]['field_type'] = $fts[$row->field_type]['name'];
			}
		}

		$this->jquery->tablesorter('.mainTable', '{
			headers: {4: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->cp->add_js_script('file', 'cp/custom_fields');

		$this->cp->render('admin/field_management', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Add or Edit Field
	 *
	 * This function lets you edit an existing custom field
	 *
	 * @access	public
	 * @return	void
	 */
	function field_edit()
	{
		$this->_restrict_prefs_access();

		$this->load->library(array('table', 'api', 'form_validation'));
		$this->load->helper(array('snippets_helper', 'form'));

		$this->api->instantiate('channel_fields');

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management', lang('field_management'));

		$group_id = $this->input->get_post('group_id');
		$field_id = $this->input->get_post('field_id');

		ee()->form_validation->set_rules(
			array(
				array(
					'field' => 'field_type',
					'label' => 'lang:field_type',
					'rules' => 'required'
				),
				array(
					'field' => 'field_label',
					'label' => 'lang:field_label',
					'rules' => 'required'
				),
				array(
					'field' => 'field_name',
					'label' => 'lang:field_name',
					'rules' => 'trim|required|callback__valid_field_name'
				),
				array(
					'field' => 'field_order',
					'label' => 'lang:field_order',
					'rules' => 'trim|numeric'
				)
			)
		);

		// Allow the saved fieldtype to set form validation rules
		if ($field_type = ee()->input->post('field_type'))
		{
			$ft_api = ee()->api_channel_fields;

			$ft_api->fetch_all_fieldtypes();
			$obj = $ft_api->setup_handler($field_type, TRUE);
			$ft_api->apply(
				'_init',
				array(array('id' => $field_id))
			);

			if ($ft_api->check_method_exists('validate_settings'))
			{
				// Pass the fieldtype object to Form Validation so that it may
				// call callback methods on it
				ee()->form_validation->set_fieldtype($obj);

				$ft_api->apply(
					'validate_settings',
					array($ft_api->get_posted_field_settings($field_type))
				);
			}
		}

		ee()->form_validation->set_error_delimiters('<p class="notice">', '</p>');

		if (ee()->form_validation->run() !== FALSE)
		{
			return $this->field_update();
		}

		if ($field_id == '')
		{
			$type = 'new';
			$this->view->cp_page_title = lang('create_new_custom_field');
		}
		else
		{
			$type = 'edit';
			$this->view->cp_page_title = lang('edit_field');
		}

		$vars = $this->api_channel_fields->field_edit_vars($group_id, $field_id);

		if ($vars === FALSE)
		{
			show_error(lang('unauthorized_access'));
		}

		if ($type == 'new') {
			$this->cp->add_js_script('plugin', 'ee_url_title');

			$this->javascript->output('
				$("#edit_group_prefs").hide();
				$("#field_label").bind("keyup keydown", function() {
					$(this).ee_url_title("#field_name", true);
				});
			');
		}

		$this->javascript->output('
			var ft_divs = $("'.$vars['ft_selector'].'"),
				ft_dropdown = $("#field_type");

			ft_dropdown.change(function() {
				ft_divs.hide();
				$("#ft_"+this.value)
					.show()
					.trigger("activate")
					.find("table").trigger("applyWidgets");

					$("#field_pre_populate_'.$vars['field_pre_populate'].'").trigger("click");
			});

			ft_dropdown.trigger("change");
		');

		$this->jquery->tablesorter('.mainTable', '{
			headers: {0: {sorter: false}, 1: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->cp->render('admin/field_edit', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Validates field short name to make sure no disallowed characters or
	 * words are in there and that the name isn't already taken. Marked as
	 * public so form validation can access it, but really should be private.
	 *
	 * @param	string	Field name
	 * @return	boolean	Whether or not field name passed validation
	 */
	public function _valid_field_name($field_name)
	{
		// Does field name contain invalid characters?
		if (preg_match('/[^a-z0-9\_\-]/i', $field_name))
		{
			$this->form_validation->set_message('_valid_field_name', lang('invalid_characters'));
			return FALSE;
		}

		// Does the field name match any reserved words?
		if (in_array($field_name, ee()->cp->invalid_custom_field_names()))
		{
			$this->form_validation->set_message('_valid_field_name', lang('reserved_word'));
			return FALSE;
		}

		// Truncated field name to test against duplicates
		$trunc_field_name = substr($field_name, 0, 32);
		$old_field_name = $this->form_validation->old_value('field_name');

		// Is the field name taken?
		ee()->db->where(array(
			'site_id' => ee()->config->item('site_id'),
			'field_name' => $trunc_field_name,
		));

		// If editing a field, exclude the current field from the query
		if ($field_id = ee()->input->post('field_id'))
		{
			ee()->db->where('field_id !=', $field_id);
		}

		// Duplicate exists
		if (ee()->db->count_all_results('channel_fields') > 0)
		{
			$error = ($trunc_field_name != $field_name)
				? lang('duplicate_truncated_field_name') : lang('duplicate_field_name');

			$this->form_validation->set_message('_valid_field_name', $error);

			return FALSE;
		}

		return $trunc_field_name;
	}

	// --------------------------------------------------------------------

	/**
	  * Field submission handler
	  *
	  * This function receives the submitted status data and inserts it in the database.
	  *
	  * @access	public
	  * @return	mixed
	  */
	function field_update()
	{
		$this->_restrict_prefs_access();

		if ( ! isset($_POST['group_id']))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('api');
		$this->api->instantiate('channel_fields');

		// If the $field_id variable has data we are editing an
		// existing group, otherwise we are creating a new one

		$edit = ( ! isset($_POST['field_id']) OR $_POST['field_id'] == '') ? FALSE : TRUE;

		// We need this as a variable as we'll unset the array index

		$group_id = $this->input->post('group_id');

		//perform the field update
		$this->api_channel_fields->update_field($_POST);

		// Are there errors to display?

		if ($this->api_channel_fields->error_count() > 0)
		{
			$str = '';

			foreach ($this->api_channel_fields->errors as $msg)
			{
				$str .= $msg.BR;
			}

			show_error($str);
		}

		$cp_message = ($edit) ? lang('custom_field_edited') : lang('custom_field_created');

		$strlen = strlen($this->input->post('field_name'));

		if ($strlen > 32)
		{
			$this->session->set_flashdata('message_failure', lang('field_name_too_lrg'));
		}
		else
		{
			$this->session->set_flashdata('message_success', $cp_message);
		}

		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=field_management'.AMP.'group_id='.$group_id);

	}

	// --------------------------------------------------------------------

	/**
	 * Get fieldtype specific post data
	 *
	 * Different from input->post in that it checks for a fieldtype prefixed
	 * value as well.
	 *
	 * @access	public
	 * @param	fieldtype, key
	 * @return	mixed
	 */
	function _get_ft_post_data($field_type, $key)
	{
		return (isset($_POST[$key])) ? $_POST[$key] : $this->input->post($field_type.'_'.$key);
	}

	// --------------------------------------------------------------------

	/**
	 * Field Status confirm
	 *
	 * Creates the Field Deletion Confirmation page
	 *
	 * @access	public
	 * @return	void
	 */
	function field_delete_confirm()
	{
		$this->_restrict_prefs_access();

		$field_id = $this->input->get_post('field_id');

		if ($field_id == '' OR ! is_numeric($field_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('field_model');

		$this->view->cp_page_title = lang('delete_field');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management', lang('field_management'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=field_delete';
		$vars['form_extra'] = '';
		$vars['form_hidden']['field_id'] = $field_id;
		$vars['form_hidden']['group_id'] = $this->input->get('group_id');
		$vars['message'] = lang('delete_field_confirmation');

		// Grab status with this id
		$items = $this->field_model->get_field($field_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->field_label;
		}

		$this->cp->render('admin/preference_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Field
	 *
	 * @access	public
	 * @return	void
	 */
	function field_delete()
	{
		$this->_restrict_prefs_access();

		$field_id = $this->input->get_post('field_id');

		if ($field_id == '' OR ! is_numeric($field_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('field_model');

		$deleted = $this->field_model->delete_fields($field_id);

		// Drop from custom layouts
		$query = $this->field_model->get_assigned_channels($deleted['group_id']);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$channel_ids[] = $row->channel_id;
			}

			$this->load->library('layout');
			$this->layout->delete_layout_fields($field_id, $channel_ids);
		}

		$cp_message = lang('field_deleted').NBS.$deleted['field_label'];

		$this->logger->log_action($cp_message);

		$this->functions->clear_caching('all', '');

		$this->session->set_flashdata('message_success', $cp_message);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=field_management'.AMP.'group_id='.$deleted['group_id']);
	}


	// --------------------------------------------------------------------

	/** -----------------------------------------------------------
	/**  Edit Formatting Buttons
	/** -----------------------------------------------------------*/
	// This function shows the form that lets you edit the
	// contents of the entry formatting pull-down menu
	//-----------------------------------------------------------

	function edit_formatting_options()
	{
		$this->_restrict_prefs_access();

		if ( ! $id = $this->input->get_post('id'))
		{
			return FALSE;
		}

		$this->db->select('group_id');
		$this->db->from('channel_fields');
		$this->db->where('field_id', $id);
		$query = $this->db->get();

		if ($query->num_rows() !== 1)
		{
			return FALSE;
		}

		$group_id = $query->row('group_id');

		$this->load->library('table');
		$this->load->model('addons_model');

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->view->cp_page_title = lang('formatting_options');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management', lang('field_management'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=update_formatting_options'.AMP.'field_id='.$id.AMP.'group_id='.$group_id;

		$vars['form_hidden']['field_id'] = $id;
		$vars['form_hidden']['none'] = 'y';

		$plugins = $this->addons_model->get_plugin_formatting();

		$query = $this->db->query("SELECT field_fmt FROM exp_field_formatting WHERE field_id = '$id' AND field_fmt != 'none' ORDER BY field_fmt");

		// Current available
		$plugs = array();

		foreach ($query->result_array() as $row)
		{
			$plugs[] = $row['field_fmt'];
		}

		$options = array();

		foreach ($plugins as $val => $name)
		{
			$select = (in_array($val, $plugs)) ? 'y' : 'n';
			$options[$val] = array('name' => $name, 'selected' => $select);
		}

		$vars['format_options'] = $options;

		$this->cp->render('admin/edit_formatting_options', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Formatting Buttons
	 *
	 * @access public
	 * @return void
	 */
	function update_formatting_options()
	{
		$this->_restrict_prefs_access();

		if ( ! $id = $this->input->post('field_id'))
		{
			return FALSE;
		}

		if ( ! is_numeric($id))
		{
			return FALSE;
		}

		unset($_POST['field_id']);

		$this->db->query("DELETE FROM exp_field_formatting WHERE field_id = '$id'");

		foreach ($_POST as $key => $val)
		{
			if ($val == 'y')
				 $this->db->query("INSERT INTO exp_field_formatting (field_id, field_fmt) VALUES ('$id', '$key')");
		}

		$group_id = $this->input->get_post('group_id');
		$field_id = $this->input->get_post('field_id');
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=field_edit'.AMP.'field_id='.$field_id.AMP.'group_id='.$group_id);
	}



	// --------------------------------------------------------------------

	/**
	 * Status Group Management
	 *
	 * This function show the list of current status groups.
	 * It is accessed by clicking "Custom entry statuses" in the "admin" tab
	 *
	 * @access	public
	 * @return	void
	 */
	function status_group_management($message = '')
	{
		$this->_restrict_prefs_access();

		$this->load->library('table');
		$this->load->model('status_model');

		$this->view->cp_page_title = lang('statuses');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content', lang('admin_content'));

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}},
			widgets: ["zebra"]
		}');

		$vars['message'] = $message;

		// Fetch category groups
		$vars['status_groups'] = $this->status_model->get_status_groups();

		$this->cp->set_right_nav(array(
			'create_new_status_group' => BASE.AMP.'C=admin_content'.AMP.'M=status_group_edit'
		));

		$this->cp->render('admin/status_group_management', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Status Group Edit
	 *
	 * @access	public
	 * @return	void
	 */
	function status_group_edit()
	{
		$this->_restrict_prefs_access();

		$this->load->model('status_model');

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=status_group_management', lang('statuses'));

		// If we have the group_id variable it's an edit request, so fetch the status data
		$group_id = $this->input->get_post('group_id');

		// Set default values
		$vars = array(
			'group_id'			=> '',
			'group_name'		=> '',
			'form_hidden'		=> array(),
			'submit_lang_key'	=> ($group_id != '') ? 'update' : 'submit',
			'group_name'		=> ''
		);

		if ($group_id != '')
		{
			$this->view->cp_page_title = lang('rename_group');

			$vars['form_hidden']['group_id'] = $group_id;

			if ( ! is_numeric($group_id))
			{
				show_error(lang('not_authorized'));
			}

			$query = $this->status_model->get_status_group($group_id);

			foreach ($query->row() as $key => $val)
			{
				$vars[$key] = $val;
			}
		}
		else
		{
			$this->view->cp_page_title = lang('create_new_status_group');
		}

		$this->cp->render('admin/status_group_edit', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Status Group Delete Confirm
	 *
	 * Warning message shown when you try to delete a status group
	 *
	 * @access	public
	 * @return	void
	 */
	function status_group_delete_confirm()
	{
		$this->_restrict_prefs_access();

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('status_model');

		$this->view->cp_page_title = lang('delete_group');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=status_management'.AMP.'group_id='.$group_id, lang('statuses'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=status_group_delete';
		$vars['form_extra'] = '';
		$vars['form_hidden']['group_id'] = $group_id;
		$vars['message'] = lang('delete_status_group_confirmation');

		// Grab category_groups locations with this id
		$items = $this->status_model->get_status_group($group_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->group_name;
		}

		$this->cp->render('admin/preference_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Status Group Delete
	 *
	 * This function nukes the status group and associated statuses
	 *
	 * @access	public
	 * @return	void
	 */
	function status_group_delete()
	{
		$this->_restrict_prefs_access();

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('status_model');

		$query = $this->status_model->get_status_group($group_id);

		if ($query->num_rows() == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->status_model->delete_status_group($group_id);

		$cp_message = lang('status_group_deleted').NBS.$query->row('group_name');

		$this->logger->log_action($cp_message);

		$this->functions->clear_caching('all', '');

		$this->session->set_flashdata('message_success', $cp_message);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=status_group_management');
	}

	// --------------------------------------------------------------------

	/**
	 * Status Group Update
	 *
	 * his function receives the submitted status group data
	 * and puts it in the database
	 *
	 * @access	public
	 * @return	void
	 */
	function status_group_update()
	{
		$this->_restrict_prefs_access();

		$group_id = $this->input->get_post('group_id');

		// If the $group_id variable is present we are editing an
		// existing group, otherwise we are creating a new one
		$edit = (isset($_POST['group_id'])) ? TRUE : FALSE;

		$group_name = $this->input->post('group_name');

		if ($group_name == '')
		{
			$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=status_group_management');
		}

		if ( ! preg_match("#^[a-zA-Z0-9_\-/\s]+$#i", $group_name))
		{
			show_error(lang('illegal_characters'));
		}

		$this->load->model('status_model');

		// Is the group name taken?
		if ($this->status_model->is_duplicate_status_group_name($group_name, $group_id))
		{
			show_error(lang('taken_status_group_name'));
		}

		// Construct the query based on whether we are updating or inserting
		if ($edit == FALSE)
		{
			$this->status_model->insert_statuses($group_name);

			$cp_message = lang('status_group_created').NBS.$group_name;

			$this->logger->log_action($cp_message);

			$this->db->select('channel_id');
			$this->db->where('site_id', $this->config->item('site_id'));
			$channel_info = $this->db->get('channels');

			$query = $this->db->query("SELECT channel_id from exp_channels WHERE site_id = '".$this->db->escape_str($this->config->item('site_id'))."'");

			if ($channel_info->num_rows() > 0)
			{
				$cp_message .= lang('assign_group_to_channel').NBS;

				if ($channel_info->num_rows() == 1)
				{
					$link = 'C=admin_content'.AMP.'M=channel_edit_group_assignments'.AMP.'channel_id='.$channel_info->row('channel_id');
					$cp_message .= '<a href="'.BASE.AMP.$link.'">'.lang('click_to_assign_group').'</a>';
				}
				else
				{
					$link = 'C=admin_content'.AMP.'M=channel_management';
				}
			}
		}
		else
		{
			$this->status_model->update_statuses($group_name, $group_id);

			$cp_message = lang('status_group_updated').NBS.$group_name;
		}

		$this->session->set_flashdata('message_success', $cp_message);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=status_group_management');

	}

	// --------------------------------------------------------------------

	/**
	 * Add or Edit Statuses Group Delete
	 *
	 *
	 * @access	public
	 * @return	void
	 */
	function status_management($message = '')
	{
		$this->_restrict_prefs_access();

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('status_model');
		$this->load->library('table');

		$group_name = $this->status_model->get_status_group($group_id);

		$this->view->cp_page_title = lang('group').':'.NBS.$group_name->row('group_name');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=status_group_management', lang('statuses'));

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}},
			widgets: ["zebra"]
		}');

		$vars['message'] = $message;

		// Fetch status groups
		$vars['statuses'] = $this->status_model->get_statuses($group_id);

		$this->cp->set_right_nav(array('create_new_status' => BASE.AMP.'C=admin_content'.AMP.'M=status_edit'.AMP.'group_id='.$group_id));

		$this->cp->render('admin/status_management', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Add or Edit Statuses
	 *
	 * Edit status form
	 *
	 * @access	public
	 * @return	void
	 */
	function status_edit()
	{
		$this->_restrict_prefs_access();

		$status_id = $this->input->get_post('status_id');

		if ($status_id != '' AND ! is_numeric($status_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->cp->add_js_script(array(
			'plugin' => array('jscolor')
		));

		$this->load->library('table');
		$this->load->model('status_model');

		$query = $this->status_model->get_status($status_id);

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=status_group_management', lang('statuses'));

		// Set default values
		$vars['group_name'] = '';

		if ($query->num_rows() > 0)
		{
			$vars['status']			= $query->row('status');
			$vars['status_order']	= $query->row('status_order');
			$vars['highlight']	 	= $query->row('highlight');
		}
		else
		{
			$status_order = $this->status_model->get_next_status_order($this->input->get_post('group_id'));
			$vars['status']			= '';
			$vars['status_order']	= $status_order;
			$vars['highlight']	 	= '000000';
		}

		$vars['form_hidden']['status_id'] = $status_id;
		$vars['form_hidden']['old_status'] = $vars['status'];

		if ($vars['status'] == 'open' OR $vars['status'] == 'closed')
		{
			$vars['form_hidden']['status'] = $vars['status'];
		}

		if ($status_id == '')
		{
			$vars['submit_lang_key'] = 'submit';
			$vars['form_hidden']['group_id'] = $this->input->get_post('group_id');
			$this->view->cp_page_title = lang('status');
		}
		else
		{
			$vars['form_hidden']['group_id'] = $query->row('group_id');
			$vars['submit_lang_key'] = 'update';
			$this->view->cp_page_title = ucfirst($vars['status']);
		}

		if ($this->session->userdata['group_id'] == 1)
		{
			$query = $this->db->query("SELECT group_id, group_title
								FROM exp_member_groups
								WHERE group_id NOT IN (1,2,3,4)
								AND site_id = '".$this->db->escape_str($this->config->item('site_id'))."'
								ORDER BY group_title");

			$group = array();
			$vars['member_perms'] = array();

			$result = $this->db->query("SELECT member_group FROM exp_status_no_access WHERE status_id = '$status_id'");

			if ($result->num_rows() != 0)
			{
				foreach($result->result_array() as $row)
				{
					$group[$row['member_group']] = TRUE;
				}
			}

			foreach ($query->result() as $row)
			{
				$vars['member_perms'][$row->group_id]['group_id'] = $row->group_id;
				$vars['member_perms'][$row->group_id]['group_title'] = $row->group_title;
				if ( ! isset($group[$row->group_id]))
				{
					$vars['member_perms'][$row->group_id]['access_y'] = TRUE;
					$vars['member_perms'][$row->group_id]['access_n'] = FALSE;
				}
				else
				{
					$vars['member_perms'][$row->group_id]['access_y'] = FALSE;
					$vars['member_perms'][$row->group_id]['access_n'] = TRUE;
				}
			}
		}

		$this->cp->render('admin/status_edit', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  * Status submission handler
	  *
	  * This function receives the submitted status data and inserts it in the database.
	  *
	  * @access	public
	  * @return	mixed
	  */
	function status_update()
	{
		$this->_restrict_prefs_access();

		$edit = ( ! $this->input->post('status_id')) ? FALSE : TRUE;

		if ($this->input->post('status') == '')
		{
			$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=status_group_management');
		}

		if (preg_match('/[^a-z0-9\_\-\+\s]/i', $this->input->post('status')))
		{
			show_error(lang('invalid_status_name'));
		}

		$data = array(
						'status'	 	=> $this->input->post('status'),
						'status_order'	=> (is_numeric($this->input->post('status_order'))) ? $this->input->get_post('status_order') : 0,
						'highlight'		=> $this->input->post('highlight')
					);

		if ($edit == FALSE)
		{
			$query = $this->db->query("SELECT count(*) AS count FROM exp_statuses WHERE status = '".$this->db->escape_str($_POST['status'])."' AND group_id = '".$this->db->escape_str($_POST['group_id'])."'");

			if ($query->row('count')  > 0)
			{
				show_error(lang('duplicate_status_name'));
			}

			$data['group_id'] = $_POST['group_id'];
			$data['site_id'] = $this->config->item('site_id');

			$this->db->insert('statuses', $data);

			$status_id = $this->db->insert_id();
			$cp_message = lang('status_created');
		}
		else
		{
			$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_statuses WHERE status = '".$this->db->escape_str($_POST['status'])."' AND group_id = '".$this->db->escape_str($_POST['group_id'])."' AND status_id != '".$this->db->escape_str($_POST['status_id'])."'");

			if ($query->row('count')  > 0)
			{
				show_error(lang('duplicate_status_name'));
			}

			$status_id = $this->input->get_post('status_id');

			$sql = $this->db->update_string(
										'exp_statuses',
										 $data,
										 array(
												'status_id'  => $status_id,
												'group_id'	=> $this->input->post('group_id')
											  )
									 );

			$this->db->query($sql);

			$this->db->query("DELETE FROM exp_status_no_access WHERE status_id = '$status_id'");

			// If the status name has changed, we need to update channel entries with the new status.

			if ($_POST['old_status'] != $_POST['status'])
			{
				$query = $this->db->query("SELECT channel_id FROM exp_channels WHERE site_id = '".$this->db->escape_str($this->config->item('site_id'))."' AND status_group = '".$this->db->escape_str($_POST['group_id'])."'");

				if ($query->num_rows() > 0)
				{
					foreach ($query->result_array() as $row)
					{
						$this->db->query("UPDATE exp_channel_titles SET status = '".$this->db->escape_str($_POST['status'])."'
									WHERE site_id = '".$this->db->escape_str($this->config->item('site_id'))."'
									AND status = '".$this->db->escape_str($_POST['old_status'])."'
									AND channel_id = '".$row['channel_id']."'");
					}
				}
			}

			$cp_message = lang('status_updated');
		}

		// Set access privs

		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 7) == 'access_' AND $val == 'n')
			{
				$this->db->query("INSERT INTO exp_status_no_access (status_id, member_group) VALUES ('$status_id', '".substr($key, 7)."')");
			}
		}

		$this->session->set_flashdata('message_success', $cp_message);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=status_management'.AMP.'group_id='.$this->input->post('group_id'));
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Status confirm
	 *
	 * Creates Delete Status Confirmation page
	 *
	 * @access	public
	 * @return	void
	 */
	function status_delete_confirm()
	{
		$this->_restrict_prefs_access();

		$status_id = $this->input->get_post('status_id');

		if ($status_id == '' OR ! is_numeric($status_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('status_model');

		$this->view->cp_page_title = lang('delete_status');
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=status_management', lang('statuses'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=status_delete';
		$vars['form_extra'] = '';
		$vars['form_hidden']['status_id'] = $status_id;
		$vars['message'] = lang('delete_status_confirmation');

		// Grab status with this id
		$items = $this->status_model->get_status($status_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->status;
		}

		$this->cp->render('admin/preference_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Status
	 *
	 * @access	public
	 * @return	void
	 */
	function status_delete()
	{
		$this->_restrict_prefs_access();

		$status_id = $this->input->get_post('status_id');

		if ($status_id == '' OR ! is_numeric($status_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('status_model');

		$query = $this->status_model->get_status($status_id);

		$group_id = $query->row('group_id') ;
		$status	= $query->row('status') ;

		$query = $this->db->query("SELECT channel_id FROM exp_channels WHERE site_id = '".$this->db->escape_str($this->config->item('site_id'))."' AND status_group = '$group_id'");

		if ($query->num_rows() > 0)
		{
			$this->db->query("UPDATE exp_channel_titles SET status = 'closed' WHERE status = '$status' AND channel_id = '".$this->db->escape_str($query->row('channel_id') )."'");
		}

		if ($status != 'open' AND $status != 'closed')
		{
			$this->db->query("DELETE FROM exp_statuses WHERE status_id = '$status_id' AND site_id = '".$this->db->escape_str($this->config->item('site_id'))."' AND group_id = '".$this->db->escape_str($group_id)."'");
		}

		$this->session->set_flashdata('message_success', lang('status_deleted'));
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=status_management'.AMP.'group_id='.$group_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Not Http
	 *
	 * Custom validation
	 *
	 * @access	private
	 * @return	boolean
	 */
	function not_http($str = '')
	{
		if ($str == 'http://' OR $str == '')
		{
			$this->form_validation->set_message('not_http', lang('no_upload_dir_url'));
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Default HTML Buttons
	 *
	 * Creates the Default HTML Buttons page
	 *
	 * @access	public
	 * @return	void
	 */
	function default_html_buttons()
	{
		$this->_restrict_prefs_access();

		$this->load->library('table');
		$this->load->model('admin_model');

		$member_id = (int) $this->input->get_post('member_id');

		if ($member_id == 0)
		{
			//show_error(lang('unauthorized_access'));
		}

		$this->view->cp_page_title = lang('default_html_buttons');

		$this->cp->add_js_script(array('file' => 'cp/account_html_buttons'));

		$vars['form_hidden']['member_id'] = $this->session->userdata('member_id');
		$vars['form_hidden']['button_submit'] = TRUE;

		// load the systems's predefined buttons
		include(APPPATH.'config/html_buttons.php');
		$vars['predefined_buttons'] = $predefined_buttons;

		$vars['html_buttons'] = $this->admin_model->get_html_buttons(0);
		$button_count = $vars['html_buttons']->num_rows();

		// any predefined buttons?
		$button = $this->input->get_post('button');
		if ($button != '')
		{
			// all buttons also share these settings
			$predefined_buttons[$button]['member_id'] = 0;
			$predefined_buttons[$button]['site_id'] = $this->config->item('site_id');
			$predefined_buttons[$button]['tag_order'] = $button_count++;
			$predefined_buttons[$button]['tag_row'] = 1;

			$this->admin_model->update_html_buttons(0, array($predefined_buttons[$button]), FALSE);

			$this->session->set_flashdata('message_success', lang('preferences_updated'));
			$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=default_html_buttons');
		}
		elseif (is_numeric($member_id) AND $member_id != 0 AND $this->input->get_post('button_submit') != '')
		{
			$data = array();
			foreach ($_POST as $key => $val)
			{
				if (strncmp($key, 'tag_name_', 9) == 0 && $val != '')
				{
					$n = substr($key, 9);

					$data[] = array(
									'member_id' => 0,
									'tag_name'  => $this->input->post('tag_name_'.$n),
									'tag_open'  => $this->input->post('tag_open_'.$n),
									'tag_close' => $this->input->post('tag_close_'.$n),
									'accesskey' => $this->input->post('accesskey_'.$n),
									'tag_order' => ($this->input->post('tag_order_'.$n) != '') ? $this->input->post('tag_order_'.$n) : $button_count++,
									'tag_row'	=> 1, // $_POST['tag_row_'.$n],
									'site_id'	 => $this->config->item('site_id'),
									'classname'	 => "btn_".str_replace(array(' ', '<', '>', '[', ']', ':', '-', '"', "'"), '', $this->input->post('tag_name_'.$n))
									);
				}
			}

			$this->admin_model->update_html_buttons(0, $data);

			$this->session->set_flashdata('message_success', lang('preferences_updated'));
			$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=default_html_buttons');
		}

		$vars['html_buttons'] = $this->admin_model->get_html_buttons(0); // recall it in case in insert happened
		$vars['i'] = 1;

		$this->cp->render('admin/default_html_buttons', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete HTML Button
	 *
	 * @access	public
	 * @return	void
	 */
	function delete_html_button()
	{
		$this->_restrict_prefs_access();

		$this->load->model('admin_model');

		$this->admin_model->delete_html_button($this->input->get_post('id'));
	}

	// --------------------------------------------------------------------

	/**
	 * Reorder HTML Buttons
	 *
	 * @access	public
	 * @return	void
	 */
	function reorder_html_buttons()
	{
		$this->_restrict_prefs_access();

		foreach($this->input->post('ajax_tag_order') as $order=>$tag_id)
		{
			$this->db->set('tag_order', $order);
			$this->db->where('id', $tag_id);
			$this->db->update('html_buttons');
		}

		$this->output->send_ajax_response(lang('preferences_updated'));
	}

	// --------------------------------------------------------------------

	/**
	 * Global Channel Preferences
	 *
	 * @access	public
	 * @return	void
	 */
	function global_channel_preferences()
	{
		$this->_restrict_prefs_access();

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content', lang('admin_content'));

		$this->_config_manager('channel_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Config Manager
	 *
	 * Used to display the various preference pages
	 *
	 * @access	public
	 * @return	void
	 */
	function _config_manager($type, $return_loc)
	{
		$this->jquery->tablesorter('.mainTable', '{
			widgets: ["zebra"],
			headers: {
				1: { sorter: false }
			},
			textExtraction: function(node) {
				var c = $(node).children();

				if (c.length) {
					return c.text();
				}
				else {
					return node.innerHTML;
				}
			}
		}');

		$this->load->library('table');
		$this->load->model('admin_model');

		if ( ! in_array($type, array(
									'general_cfg',
									'cp_cfg',
									'channel_cfg',
									'member_cfg',
									'output_cfg',
									'debug_cfg',
									'db_cfg',
									'security_cfg',
									'throttling_cfg',
									'localization_cfg',
									'email_cfg',
									'cookie_cfg',
									'image_cfg',
									'captcha_cfg',
									'template_cfg',
									'censoring_cfg',
									'mailinglist_cfg',
									'emoticon_cfg',
									'tracking_cfg',
									'avatar_cfg',
									'search_log_cfg'
									)
						)
		)
		{
			show_error(lang('unauthorized_access'));
		}
		$vars['type'] = $type;

		$vars['form_action'] = 'C=admin_content'.AMP.'M=update_config';

		$vars = array_merge(ee()->config->prep_view_vars($type), $vars);

		// if this is an update, show the success message
		$vars['return_loc'] = BASE.AMP.'C=admin_content'.AMP.'M='.$return_loc;

		$this->view->cp_page_title = lang($type);
		$this->cp->render('admin/config_pages', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Config
	 *
	 * Handles system and site pref form submissions
	 *
	 * @access	public
	 * @return	void
	 */
	function update_config()
	{
		$this->_restrict_prefs_access();

		$loc = $this->input->get_post('return_location');

		$this->config->update_site_prefs($_POST);

		if ($loc !== FALSE)
		{
			$this->session->set_flashdata('message_success', lang('preferences_updated'));
			$this->functions->redirect($loc);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Restrict Access
	 *
	 * Helper function for the most common access level in this class
	 *
	 * @access	private
	 * @return	void
	 */
	private function _restrict_prefs_access()
	{
		if ( ! $this->cp->allowed_group(
			'can_access_admin',
			'can_admin_channels',
			'can_access_content_prefs'
		))
		{
			show_error(lang('unauthorized_access'));
		}
	}

}
// END CLASS

/* End of file admin_content.php */
/* Location: ./system/expressionengine/controllers/cp/admin_content.php */
