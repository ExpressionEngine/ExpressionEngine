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
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Admin_content extends CI_Controller {

	var $reserved = array('random', 'date', 'title', 'url_title', 'edit_date', 'comment_total', 'username', 'screen_name', 'most_recent_comment', 'expiration_date');

	// Default "open" and "closed" status colors
	var $status_color_open	= '009933';
	var $status_color_closed = '990000';

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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->cp->set_variable('cp_page_title', $this->lang->line('admin'));

		$this->javascript->compile();

		$this->cp->set_variable('cp_page_title', $this->lang->line('admin_content'));

		$this->load->vars(array('controller'=>'admin'));
		
		$this->load->view('_shared/overview');
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

        $this->cp->set_right_nav(array('create_new_channel' => BASE.AMP.'C=admin_content'.AMP.'M=channel_add'));

		$this->lang->loadfile('admin_content');
		$this->load->model('channel_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('channel_management'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content', $this->lang->line('admin_content'));
		
		$this->load->library('table');

		$vars['channel_data'] = $this->channel_model->get_channels();

		$this->load->view('admin/channel_management', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->_channel_validation_rules();

		if ($this->form_validation->run() !== FALSE)
		{
			return $this->channel_update();
		}

		$this->lang->loadfile('admin_content');
		$this->load->helper(array('form', 'snippets'));
		$this->load->model('channel_model');
		$this->load->model('category_model');

		$this->javascript->output('
			$("#edit_group_prefs").hide();
		');

		$this->javascript->click("#edit_group_prefs_y", '$("#edit_group_prefs").show();', FALSE);
		$this->javascript->click("#edit_group_prefs_n", '$("#edit_group_prefs").hide();', FALSE);

		$this->cp->set_variable('cp_page_title', $this->lang->line('create_new_channel'));

		$channels = $this->channel_model->get_channels($this->config->item('site_id'), array('channel_id', 'channel_title'));

		$vars['duplicate_channel_prefs_options'][''] = $this->lang->line('do_not_duplicate');

		if ($channels->num_rows() > 0)
		{
			foreach($channels->result() as $channel)
			{
				$vars['duplicate_channel_prefs_options'][$channel->channel_id] = $channel->channel_title;
			}
		}

		$vars['cat_group_options'][''] = $this->lang->line('none');

		$groups = $this->category_model->get_categories('', $this->config->item('site_id'));

		if ($groups->num_rows() > 0)
		{
			foreach ($groups->result() as $group)
			{
				$vars['cat_group_options'][$group->group_id] = $group->group_name;
			}
		}

		$vars['status_group_options'][''] = $this->lang->line('none');

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

		$vars['field_group_options'][''] = $this->lang->line('none');

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

		$data = $this->functions->create_directory_map(PATH_THEMES.'site_themes/', TRUE);
		
		// New themes may contain more than one group, thus naming collisions will happen
		// unless this is revamped.
		$vars['themes'] = array();

		//if (count($data) > 0)
		//{
		//	foreach ($data as $val)
		//	{
		//		if ($val == 'rss.php')
		//		{
		//			continue;
		//		}

		//		$vars['themes'][$val] = ucwords(str_replace("_", " ", $val));
		//	}
		//}

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

		$this->javascript->compile();
		$this->load->view('admin/channel_add', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		// Get modules that are installed
		$this->cp->get_installed_modules();

		$this->lang->loadfile('admin_content');
		$this->load->library('table');
		$this->load->helper(array('form', 'snippets'));
		$this->load->model('channel_model');
		$this->load->model('template_model');
		$this->load->model('status_model');
		$this->load->model('field_model');

		$this->jquery->ui(BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'ui=accordion', TRUE);

		$this->javascript->output('
			$("#channel_prefs").accordion({autoHeight: false,header: "h3"});
		');

		$channel_id = $this->input->get_post('channel_id');

		// If we don't have the $channel_id variable, bail out.
		if ($channel_id == '' OR ! is_numeric($channel_id))
		{
			show_error(lang('not_authorized'));
		}
		
		$this->_channel_validation_rules();
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

		$vars['live_look_template_options'][0] = $this->lang->line('no_live_look_template');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $template)
			{
				$vars['live_look_template_options'][$template->template_id] = $template->group_name.'/'.$template->template_name;
			}
		}

		// Default status menu
		$query = $this->status_model->get_statuses($vars['status_group']);

		$vars['deft_status_options']['open'] = $this->lang->line('open');
		$vars['deft_status_options']['closed'] = $this->lang->line('closed');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$status_name = ($row->status == 'open' OR $row->status == 'closed') ? $this->lang->line($row->status) : $row->status;
				$vars['deft_status_options'][$row->status] = $status_name;
			}
		}
		
		$vars['deft_category_options'][''] = $this->lang->line('none');
		
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
		$this->db->where('field_search', 'y');
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
			'none'	=> $this->lang->line('convert_to_entities'),
			'safe'	=> $this->lang->line('allow_safe_html'),
			'all'	=> $this->lang->line('allow_all_html')
		);

		if (isset($this->cp->installed_modules['comment']))
		{
			// Default comment text formatting
			$vars['comment_text_formatting_options'] = array(
				'none'	=> $this->lang->line('none'),
				'xhtml'	=> $this->lang->line('xhtml'),
				'br'	=> $this->lang->line('auto_br')
			);

			// Comment HTML formatting
			$vars['comment_html_formatting_options'] = array(
				'none'	=> $this->lang->line('convert_to_entities'),
				'safe'	=> $this->lang->line('allow_safe_html'),
				'all'	=> $this->lang->line('allow_all_html_not_recommended')
			);		
		}

		$this->javascript->compile();

		$this->cp->set_variable('cp_page_title', $this->lang->line('channel_prefs').' - '.$vars['channel_title']);
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=channel_management', $this->lang->line('channel_management'));


		$this->load->view('admin/channel_edit', $vars);
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
	function _channel_validation_rules()
	{
		$this->load->library('form_validation');
		
		$this->form_validation->set_rules('channel_title',		'lang:channel_title',		'required');
		$this->form_validation->set_rules('channel_name',		'lang:channel_name',		'required|callback__valid_channel_name');
		$this->form_validation->set_rules('url_title_prefix',	'lang:url_title_prefix',	'strtolower|strip_tags|callback__valid_prefix');
		$this->form_validation->set_rules('comment_expiration',	'lang:comment_expiration',	'numeric');
		
		$this->form_validation->set_error_delimiters('<p class="notice">', '</p>');
	}
	
	function _valid_prefix($str)
	{
		if ($str == '')
		{
			return TRUE;
		}
		$this->form_validation->set_message('_valid_prefix', $this->lang->line('invalid_url_title_prefix'));
		return preg_match('/^[\w\-]+$/', $str) ? TRUE : FALSE;
	}
	
	function _valid_channel_name($str)
	{
		// Check short name characters
		if (preg_match('/[^a-z0-9\-\_]/i', $str))
		{
			$this->form_validation->set_message('_valid_channel_name', $this->lang->line('invalid_short_name'));
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
			$this->form_validation->set_message('_valid_channel_name', $this->lang->line('taken_channel_name'));
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->lang->loadfile('admin_content');

		unset($_POST['channel_prefs_submit']); // submit button

		// If the $channel_id variable is present we are editing an
		// existing channel, otherwise we are creating a new one

		$edit = (isset($_POST['channel_id'])) ? TRUE : FALSE;

		// Load the layout Library & update the layouts
		$this->load->library('layout');

		$add_rss = (isset($_POST['add_rss'])) ? TRUE : FALSE;
		unset($_POST['add_rss']);

		$return = ($this->input->get_post('return')) ? TRUE : FALSE;
		unset($_POST['return']);
		$edit_group_prefs = TRUE;
		
		if ($this->input->get_post('edit_group_prefs') !== 'y')
		{
			unset($_POST['cat_group']);
			unset($_POST['status_group']);
			unset($_POST['field_group']);
			$edit_group_prefs = FALSE;
		}

		unset($_POST['edit_group_prefs']);

		$dupe_id = $this->input->get_post('duplicate_channel_prefs');
		unset($_POST['duplicate_channel_prefs']);

		// Check for required fields

		$error = array();

		 if (isset($_POST['comment_expiration']) && $_POST['comment_expiration'] == '')
		 {
			$_POST['comment_expiration'] = 0;
		 }

		// Template Error Trapping

		if ($edit == FALSE)
		{
			$this->load->library('security');
			$create_templates	= $this->input->get_post('create_templates');
			$old_group_id		= $this->input->get_post('old_group_id');
			$group_name			= $this->input->post('group_name');

			$template_theme	= $this->security->sanitize_filename($this->input->get_post('template_theme'));

			unset($_POST['create_templates']);
			unset($_POST['old_group_id']);
			unset($_POST['group_name']);
			unset($_POST['template_theme']);

			if ($create_templates != 'no')
			{
				$this->lang->loadfile('design');

				if ( ! $this->cp->allowed_group('can_admin_templates'))
				{
					show_error($this->lang->line('unauthorized_access'));
				}

				if ( ! $group_name)
				{
					show_error($this->lang->line('group_required'));
				}

				if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i", $group_name))
				{
					show_error($this->lang->line('illegal_characters'));
				}

				$reserved[] = 'act';

				if ($this->config->item("forum_is_installed") == 'y' AND $this->config->item("forum_trigger") != '')
				{
					$reserved[] = $this->config->item("forum_trigger");
				}

				if (in_array($group_name, $reserved))
				{
					show_error($this->lang->line('reserved_name'));
				}

				$this->db->where('site_id', $this->config->item('site_id'));
				$this->db->where('group_name', $group_name);
				
				$count = $this->db->count_all_results('template_groups');

				if ($count > 0)
				{
					show_error($this->lang->line('template_group_taken'));
				}
			}
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

			if ( ! isset($_POST['field_group']) OR (isset($_POST['field_group']) && ! is_numeric($_POST['field_group'])))
			{
				$this->db->select('group_id');
				$this->db->where('site_id', $this->config->item('site_id'));
				$query = $this->db->get('field_groups');
				
				if ($query->num_rows() == 1)
				{
					$_POST['field_group'] = $query->row('group_id') ;
				}
			}

			// Insert data

			$_POST['site_id'] = $this->config->item('site_id');

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
								case 'ping_return_url':
								case 'rss_url':
									if ($create_templates != 'no')
									{
										if ( ! isset($old_group_name))
										{
											$this->db->select('group_name');
											$this->db->where('group_id', $old_group_id);
											$gquery = $this->db->get('template_groups');

											$old_group_name = $gquery->row('group_name');
										}

										$_POST[$key] = str_replace("/{$old_group_name}/", "/{$group_name}/", $val);
									}
									else
									{
										$_POST[$key] = $val;
									}
									break;
								default :
									$_POST[$key] = $val;
									break;
							}
						}
					}
				}
			}


			$_POST['default_entry_title'] = ( ! isset(	$_POST['default_entry_title'])) ? '' : $_POST['default_entry_title'];
			$_POST['url_title_prefix'] = ( ! isset(	$_POST['url_title_prefix'])) ? '' : $_POST['url_title_prefix'];
					
			$this->db->insert('channels', $_POST);		
						
			$insert_id = $this->db->insert_id();
			$channel_id = $insert_id;
			
			if ($dupe_id !== FALSE AND is_numeric($dupe_id) && $edit_group_prefs == FALSE)
			{
				// Duplicate layouts
				$this->layout->duplicate_layout($dupe_id, $channel_id);
			}

			$success_msg = $this->lang->line('channel_created');

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

			$success_msg = $this->lang->line('channel_updated');
		}
		
		/** -----------------------------------------
		/**  Create Templates
		/** -----------------------------------------*/
		if ($edit == FALSE)
		{
			if ($create_templates != 'no')
			{
				$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_template_groups");
				$group_order = $query->row('count')  +1;

				$this->db->insert('template_groups', array(
														 'group_name'	  => $group_name,
														 'group_order'	 => $group_order,
														 'is_site_default' => 'n',
														 'site_id'			=> $this->config->item('site_id')
														));

				$group_id = $this->db->insert_id();

				if ($create_templates == 'duplicate')
				{
					$this->db->select('group_name');
					$this->db->where('group_id', $old_group_id);
					$query = $this->db->get('template_groups');
					$old_group_name = $query->row('group_name') ;

					$this->db->select('template_name, template_data, template_type, 
										template_notes, cache, refresh, no_auth_bounce, 
										allow_php, php_parse_location');
					$this->db->where('group_id', $old_group_id);
					$query = $this->db->get('templates');

					if ($query->num_rows() == 0)
					{
						$this->db->insert('templates', array(
															'group_id'	  => $group_id,
															'template_name' => 'index',
															'edit_date'		=> $this->localize->now,
															'site_id'		=> $this->config->item('site_id')
														 ));
					}
					else
					{
						$old_channel_name = '';

						foreach ($query->result_array() as $row)
						{
							if ($old_channel_name == '')
							{
								if (preg_match_all("/channel=[\"'](.+?)[\"']/", $row['template_data'], $matches))
								{
									for ($i = 0; $i < count($matches['1']); $i++)
									{
										if (substr($matches['1'][$i], 0, 1) != '{')
										{
											$old_channel_name = $matches['1'][$i];
											break;
										}
									}
								}
							}

							$temp = str_replace('channel="'.$old_channel_name.'"', 'channel="'.$_POST['channel_name'].'"', $row['template_data']);
							$temp = str_replace("channel='".$old_channel_name."'", 'channel="'.$_POST['channel_name'].'"', $temp);
							$temp = preg_replace("/{stylesheet=.+?\/(.+?)}/", "{stylesheet=".$group_name."/\\1}", $temp);

							$temp = preg_replace("#preload_replace:master_channel_name=\".+?\"#", 'preload_replace:master_channel_name="'.$_POST['channel_name'].'"', $temp);
							$temp = preg_replace("#preload_replace:master_channel_name=\'.+?\'#", "preload_replace:master_channel_name='".$_POST['channel_name']."'", $temp);
							$temp = preg_replace('#preload_replace:my_template_group=(\042|\047)([^\\1]*?)\\1#', "preload_replace:my_template_group=\\1{$group_name}\\1", $temp);

							$temp = preg_replace("#".$old_group_name."/(.+?)#", $group_name."/\\1", $temp);

							$data = array(
											'group_id'				=> $group_id,
											'template_name'  		=> $row['template_name'],
											'template_notes'  		=> $row['template_notes'],
											'cache'  				=> $row['cache'],
											'refresh'  				=> $row['refresh'],
											'no_auth_bounce'  		=> $row['no_auth_bounce'],
											'php_parse_location'	=> $row['php_parse_location'],
											'allow_php'  			=> ($this->session->userdata['group_id'] == 1) ? $row['allow_php'] : 'n',
											'template_type' 		=> $row['template_type'],
											'template_data'  		=> $temp,
											'edit_date'				=> $this->localize->now,
											'last_author_id' 		=> 0,
											'site_id'				=> $this->config->item('site_id')
										 );

									$this->db->insert('templates', $data);
							}
					}
				}
				else
				{
					$type = 'core';
					if ($fp = @opendir(PATH_MOD))
					{
						while (FALSE !== ($file = readdir($fp)))
						{
							if (strpos($file, '.') === FALSE)
							{
								if ($file == 'mailinglist')
								{
									$type = 'full';
									break;
								}
							}
						}
						closedir($fp);
					}


					require PATH_THEMES.'site_themes/'.$template_theme.'/'.$template_theme.'.php';

					foreach ($template_matrix as $tmpl)
					{
						$Q[] = array($tmpl['0'](), "INSERT INTO exp_templates(group_id, template_name, template_type, template_data, edit_date, site_id)
													VALUES ('$group_id', '".$this->db->escape_str($tmpl['0'])."', '".$this->db->escape_str($tmpl['1'])."', '{template}', '".$this->localize->now."', '".$this->db->escape_str($this->config->item('site_id'))."')");
					}

					if ($add_rss == TRUE)
					{
						require PATH_THEMES.'site_themes/rss/rss.php';
						$Q[] = array(rss_2(), "INSERT INTO exp_templates(group_id, template_name, template_type, template_data, edit_date, site_id)
												VALUES ('$group_id', 'rss_2.0', 'feed', '{template}', '".$this->db->escape_str($this->localize->now)."', '".$this->db->escape_str($this->config->item('site_id'))."')");

						$Q[] = array(atom(), "INSERT INTO exp_templates(group_id, template_name, template_type, template_data, edit_date, site_id)
											  VALUES ('$group_id', 'atom', 'feed', '{template}', '".$this->db->escape_str($this->localize->now)."', '".$this->db->escape_str($this->config->item('site_id'))."')");
					}

					foreach ($Q as $val)
					{
						$temp = $val['0'];

						$temp = str_replace('channel="channel1"', 'channel="'.$_POST['channel_name'].'"', $temp);
						$temp = str_replace("channel='channel1'", 'channel="'.$_POST['channel_name'].'"', $temp);
						$temp = str_replace('my_channel="channel1"', 'my_channel="'.$_POST['channel_name'].'"', $temp);
						$temp = str_replace("my_channel='channel1'", 'my_channel="'.$_POST['channel_name'].'"', $temp);

						$temp = str_replace('channel="default_site"', 'channel="'.$_POST['channel_name'].'"', $temp);
						$temp = str_replace("channel='default_site'", 'channel="'.$_POST['channel_name'].'"', $temp);
						$temp = str_replace('my_channel="default_site"', 'my_channel="'.$_POST['channel_name'].'"', $temp);
						$temp = str_replace("my_channel='default_site'", 'my_channel="'.$_POST['channel_name'].'"', $temp);

						$temp = str_replace('my_template_group="site"', 'my_template_group="'.$group_name.'"', $temp);
						$temp = str_replace("my_template_group='site'", 'my_template_group="'.$group_name.'"', $temp);

						$temp = str_replace("{stylesheet=channel/channel_css}", "{stylesheet=".$group_name."/site_css}", $temp);
						$temp = str_replace("{stylesheet=site/site_css}", "{stylesheet=".$group_name."/site_css}", $temp);

						$temp = str_replace('preload_replace:master_channel_name="channel1"', 'preload_replace:master_channel_name="'.$_POST['channel_name'].'"', $temp);
						$temp = preg_replace("#channel/(.+?)#", $group_name."/\\1", $temp);

						$temp = addslashes($temp);
						$sql  = str_replace('{template}', $temp, $val['1']);

						$this->db->query($sql);
					}
				}
			}
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$update_fields = FALSE;
		$channel_id = $this->input->post('channel_id');
		$data['field_group'] = ($this->input->post('field_group') != FALSE && $this->input->post('field_group') != '') ? $this->input->post('field_group') : NULL;
		$data['status_group'] = ($this->input->post('status_group') != FALSE && $this->input->post('status_group') != '') ? $this->input->post('status_group') : NULL;

		$this->lang->loadfile('admin_content');

		if (isset($_POST['cat_group']) && is_array($_POST['cat_group']))
		{
			$data['cat_group'] = implode('|', $_POST['cat_group']);
		}
		
		if ( ! isset($data['cat_group']) OR $data['cat_group'] == '')
		{
			$data['cat_group'] = NULL;
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

		if ($old_field != $data['field_group'] && ! is_null($old_field))
		{
			$update_fields = TRUE;
			
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


		$success_msg = $this->lang->line('channel_updated');
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// If we don't have the $channel_id variable, bail out.
		$channel_id = $this->input->get_post('channel_id');

		if ($channel_id == '' OR ! is_numeric($channel_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->lang->loadfile('admin_content');
		$this->load->helper('form');
		$this->load->model('channel_model');
		$this->load->model('category_model');
		$this->load->model('status_model');
		$this->load->model('field_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('edit_group_assignments'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=channel_management', $this->lang->line('channel_management'));

		$query = $this->channel_model->get_channel_info($channel_id);

		foreach ($query->row_array() as $key => $val)
		{
			if ($key == 'cat_group')
			{
				$val = explode('|', $val);
			}
			
			$vars[$key] = $val;  
		}

		$vars['form_hidden']['channel_id'] = $channel_id;
		$vars['form_hidden']['channel_name'] = $vars['channel_name'];
		$vars['form_hidden']['channel_title'] = $vars['channel_title'];
		$vars['form_hidden']['return'] = 1;


		// Category Select List
		$query = $this->category_model->get_categories('', FALSE);

		$vars['cat_group_options'][''] = $this->lang->line('none');

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

		$vars['status_group_options'][''] = $this->lang->line('none');

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

		$vars['field_group_options'][''] = $this->lang->line('none');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$vars['field_group_options'][$row->group_id] = $row->group_name;
			}
		}

		$this->javascript->compile();

		$this->load->view('admin/channel_edit_group_assignments', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$channel_id = $this->input->get_post('channel_id');

		if ($channel_id == '' OR ! is_numeric($channel_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->helper('form');
		$this->lang->loadfile('admin_content');
		$this->load->model('channel_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_channel'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=channel_management', $this->lang->line('channel_management'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=channel_delete';
		$vars['form_extra'] = '';
		$vars['form_hidden']['channel_id'] = $channel_id;
		$vars['message'] = $this->lang->line('delete_channel_confirmation');

		// Grab category_groups locations with this id
		$items = $this->channel_model->get_channel_info($channel_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->channel_title;
		}

		$this->javascript->compile();
		$this->load->view('admin/preference_delete_confirm', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$channel_id = $this->input->get_post('channel_id');

		if ($channel_id == '' OR ! is_numeric($channel_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->lang->loadfile('admin_content');
		$this->load->model('channel_model');

		$query = $this->channel_model->get_channel_info($channel_id);

		if ($query->num_rows() == 0)
		{
			$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=channel_management');
		}

		$channel_title = $query->row('channel_title') ;

		$this->logger->log_action($this->lang->line('channel_deleted').NBS.NBS.$channel_title);

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

		$this->session->set_flashdata('message_success', $this->lang->line('channel_deleted').NBS.$channel_title);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=channel_management');
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

		if ( ! ($this->cp->allowed_group('can_edit_categories') OR 
				$this->cp->allowed_group('can_delete_categories') OR
				$this->cp->allowed_group('can_access_admin') OR
				$this->cp->allowed_group('can_access_content_prefs')))
		{		
			show_error($this->lang->line('unauthorized_access'));
		}		

		$this->load->library('table');
		$this->load->model('category_model');
		$this->lang->loadfile('admin_content');

		$this->cp->set_variable('cp_page_title', $this->lang->line('categories'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content', $this->lang->line('admin_content'));

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}, 3: {sorter: false}, 4: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->javascript->compile();

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

		$categories = $this->category_model->get_categories('', FALSE);

		foreach($categories->result() as $row)
		{
			$this->db->where('group_id', $row->group_id);
			$category_count = $this->db->count_all_results('categories');
			
			$vars['categories'][$cat_count]['group_id'] = $row->group_id;
			$vars['categories'][$cat_count]['group_name'] = $row->group_name;
			$vars['categories'][$cat_count]['category_count'] = $category_count;
			$vars['categories'][$cat_count]['custom_field_count'] = ((isset($cfcount[$row->group_id])) ? $cfcount[$row->group_id] : '0');

			$cat_count++;
		}

        $this->cp->set_right_nav(array('create_new_category_group' => BASE.AMP.'C=admin_content'.AMP.'M=edit_category_group'));

		$this->load->view('admin/category_management', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->model('admin_model');
		$this->load->model('category_model');
		$this->load->helper('form');
		$this->lang->loadfile('admin_content');
		$this->load->library('table');

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', $this->lang->line('categories'));

		// Set default values
		$vars['cp_page_title'] = $this->lang->line('create_new_category_group');
		$vars['submit_lang_key'] = 'submit';
		$vars['form_hidden'] = array(); // nothing needs to be passed into a new cat group
		$vars['group_name'] = '';
		$vars['field_html_formatting'] = 'all';
		$vars['can_edit'] = array();
		$vars['can_delete'] = array();
		$vars['can_edit_selected'] = array();
		$vars['can_delete_selected'] = array();
		$vars['formatting_options'] = array(
												'none'	=> $this->lang->line('convert_to_entities'),
												'safe'	=> $this->lang->line('allow_safe_html'),
												'all'	=> $this->lang->line('allow_all_html')
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
			$vars['cp_page_title'] = $this->lang->line('edit_category_group');
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

		$this->javascript->compile();

		$this->load->view('admin/edit_category_group', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

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

		$this->lang->loadfile('admin_content');

		// check for bad characters in group name
		if ( ! preg_match("#^[a-zA-Z0-9_\-/\s]+$#i", $_POST['group_name']))
		{
			show_error($this->lang->line('illegal_characters'));
		}

		$this->load->model('category_model');

		// Is the group name taken?
		if ($this->category_model->is_duplicate_category_group($this->input->post('group_name'), $this->input->post('group_id')))
		{
			show_error($this->lang->line('taken_category_group_name'));
		}

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

		// Construct the query based on whether we are updating or inserting
		if ($edit == FALSE)
		{
			$this->category_model->insert_category_group($data);

			$cp_message = $this->lang->line('category_group_created').' '.$data['group_name'];
			$this->logger->log_action($this->lang->line('category_group_created').NBS.NBS.$data['group_name']);

			$this->db->select('channel_id');
			$this->db->where('site_id', $this->config->item('site_id'));
			$query = $this->db->get('channels');

			if ($query->num_rows() > 0)
			{
				$cp_message .= '<br />'.$this->lang->line('assign_group_to_channel');

				if ($query->num_rows() == 1)
				{
					$link = 'C=admin_content'.AMP.'M=channel_edit_group_assignments'.AMP.'channel_id='.$query->row('channel_id') ;
				}
				else
				{
					$link = 'C=admin_content'.AMP.'M=channel_management';
				}
			
				$cp_message .= '<br /><a href="'.BASE.AMP.$link.'">'. $this->lang->line('click_to_assign_group').'</a>';
			}
		}
		else
		{
			$this->category_model->update_category_group($data['group_id'], $data);
			$cp_message = $this->lang->line('category_group_updated').NBS.$data['group_name'];
		}

		$this->session->set_flashdata('message_success', $cp_message);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=category_management');
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->helper('form');
		$this->lang->loadfile('admin_content');
		$this->load->model('category_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_group'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', $this->lang->line('categories'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=category_group_delete';
		$vars['form_extra'] = '';
		$vars['form_hidden']['group_id'] = $group_id;
		$vars['message'] = $this->lang->line('delete_cat_group_confirmation');

		// Grab category_groups locations with this id
		$items = $this->category_model->get_category_group_name($group_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->group_name;
		}

		$this->javascript->compile();
		$this->load->view('admin/preference_delete_confirm', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->lang->loadfile('admin_content');
		$this->load->model('category_model');

		$category = $this->category_model->get_category_group_name($group_id);

		if ($category->num_rows() == 0)
		{
			show_error(lang('not_authorized'));
		}

		$name = $category->row('group_name');

		//  Delete from exp_category_posts
		$this->category_model->delete_category_group($group_id);

		$this->logger->log_action($this->lang->line('category_group_deleted').NBS.NBS.$name);

		$this->functions->clear_caching('all', '', TRUE);

		$this->session->set_flashdata('message_success', $this->lang->line('category_group_deleted').NBS.NBS.$name);
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
		}
		
		if ( ! ($this->cp->allowed_group('can_edit_categories') OR 
				$this->cp->allowed_group('can_access_admin') OR
				$this->cp->allowed_group('can_access_content_prefs')))
		{		
			show_error($this->lang->line('unauthorized_access'));
		}


		$this->lang->loadfile('admin_content');
		$this->load->model('category_model');
		$this->load->library('table');
		$this->load->library('api');
		$this->load->helper('form');
		
		$this->api->instantiate('channel_categories');
		
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', $this->lang->line('categories'));

		$vars['message'] = ''; // override lower down if needed
		$vars['form_action'] = '';

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}, 3: {sorter: false}, 4: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->javascript->compile();


		if ($group_id == '')
		{
			if (($group_id = $this->input->get_post('group_id')) === FALSE OR ! is_numeric($group_id))
			{
				show_error($this->lang->line('unauthorized_access'));
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
				show_error($this->lang->line('unauthorized_access'));
			}

			$can_edit = explode('|', rtrim($query->row('can_edit_categories') , '|'));

			if ($this->session->userdata['group_id'] != 1 AND ! in_array($this->session->userdata['group_id'], $can_edit))
			{
				show_error($this->lang->line('unauthorized_access'));
			}
		}

		$zurl = ($this->input->get_post('Z') == 1) ? AMP.'Z=1' : '';
		$zurl .= ($this->input->get_post('cat_group') !== FALSE) ? AMP.'cat_group='.$this->input->get_post('cat_group') : '';
		$zurl .= ($this->input->get_post('integrated') !== FALSE) ? AMP.'integrated='.$this->input->get_post('integrated') : '';

		$query = $this->category_model->get_categories($group_id, FALSE);
		
		if ($query->num_rows() == 0)
		{
			$this->functions->redirect(BASE.AMP.'C=admin_content&M=category_management');
		}
		
		$group_name = $query->row('group_name') ;
		$sort_order = $query->row('sort_order') ;

		$this->cp->set_variable('cp_page_title', $group_name);

		if ($update != FALSE)
		{
			$vars['message'] = $this->lang->line('category_updated');
		}

		// Fetch the category tree
		$this->api_channel_categories->category_tree($group_id, '', $sort_order);

		if (count($this->api_channel_categories->categories) == 0)
		{
			$vars['categories'] = array();
		}
		else
		{
			$vars['categories'] = $this->api_channel_categories->categories;

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

		$this->load->view('admin/category_editor', $vars);
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
		if ( ! $this->cp->allowed_group('can_edit_categories'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->model('category_model');
		$this->lang->loadfile('admin_content');
		$this->load->helper('form');
		$this->load->helper('string');
		$this->load->library('form_validation');

		$group_id = $this->input->get_post('group_id');

		if ($group_id != '')
		{
			if ( ! is_numeric($group_id))
			{
				show_error($this->lang->line('unauthorized_access'));
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
				show_error($this->lang->line('unauthorized_access'));
			}

			$can_edit = explode('|', rtrim($query->row('can_edit_categories') , '|'));

			if ($this->session->userdata['group_id'] != 1 AND ! in_array($this->session->userdata['group_id'], $can_edit))
			{
				show_error($this->lang->line('unauthorized_access'));
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
				show_error($this->lang->line('unauthorized_access'));
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

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', $this->lang->line('categories'));
		$this->cp->set_variable('cp_page_title', ($vars['cat_id'] == '') ? $this->lang->line('new_category') : $this->lang->line('edit_category'));

		$word_separator = $this->config->item('word_separator') != "dash" ? '_' : '-';

		//  Create Foreign Character Conversion JS
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

		$foreign_replace = '';

		foreach($foreign_characters as $old => $new)
		{
			$foreign_replace .= "if (c == '$old') {NewTextTemp += '$new'; continue;}\n\t\t\t\t";
		}

		$live_title_js = $this->javascript->inline("
			/** ------------------------------------
			/**  Live URL Title Function
			/** -------------------------------------*/
			function liveUrlTitle()
			{
				var NewText = document.getElementById('cat_name').value;

				NewText = NewText.toLowerCase();

				var separator = '{$word_separator}';

				// Foreign Character Attempt

				var NewTextTemp = '';
				for(var pos=0; pos<NewText.length; pos++)
				{
					var c = NewText.charCodeAt(pos);

					if (c >= 32 && c < 128)
					{
						NewTextTemp += NewText.charAt(pos);
					}
					else
					{
						{$foreign_replace}
					}
				}

				var multiReg = new RegExp(separator + '{2,}', 'g');

				NewText = NewTextTemp;

				NewText = NewText.replace('/<(.*?)>/g', '');
				NewText = NewText.replace(/\s+/g, separator);
				NewText = NewText.replace(/\//g, separator);
				NewText = NewText.replace(/[^a-z0-9\-\._]/g,'');
				NewText = NewText.replace(/\+/g, separator);
				NewText = NewText.replace(multiReg, separator);
				NewText = NewText.replace(/-$/g,'');
				NewText = NewText.replace(/_$/g,'');
				NewText = NewText.replace(/^_/g,'');
				NewText = NewText.replace(/^-/g,'');
				NewText = NewText.replace(/\.+$/g,'');

				document.getElementById('cat_url_title').value = NewText;
			}");
		
		$this->cp->add_to_foot($live_title_js);

		// New entry gets URL title js
		if ($vars['submit_lang_key'] == 'submit')
		{		
			$this->javascript->keyup('#cat_name', 'liveUrlTitle()');
		}

		$vars['form_hidden']['group_id'] = $group_id;

		$this->load->library('api');
		$this->api->instantiate('channel_categories');
		$this->api_channel_categories->category_tree($group_id, $vars['parent_id']);

		$vars['parent_id_options'] = $this->api_channel_categories->categories;

		//$vars['parent_id_options'][0] = $this->lang->line('none');

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

		$this->javascript->compile();

		$this->load->view('admin/category_edit', $vars);
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
		if ( ! $this->cp->allowed_group('can_delete_categories'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$cat_id = $this->input->get_post('cat_id');

		if ($cat_id == '' OR ! is_numeric($cat_id))
		{
			show_error(lang('not_authorized'));
		}


		$zurl = ($this->input->get_post('modal') == 'yes') ? AMP.'modal=yes' : '';
		$zurl .= ($this->input->get_post('cat_group') !== FALSE) ? AMP.'cat_group='.$this->input->get_post('cat_group') : '';
		$zurl .= ($this->input->get_post('integrated') !== FALSE) ? AMP.'integrated='.$this->input->get_post('integrated') : '';

		$this->load->helper('form');
		$this->lang->loadfile('admin_content');
		$this->load->model('category_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_category'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', $this->lang->line('categories'));

		// Grab category_groups locations with this id
		$items = $this->category_model->get_category_name_group($cat_id);

		$vars = array(
			'form_action'	=> 'C=admin_content'.AMP.'M=category_delete'.$zurl,
			'form_extra'	=> '',
			'message'		=> $this->lang->line('delete_cat_confirmation'),
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

		$this->javascript->compile();
		$this->load->view('admin/preference_delete_confirm', $vars);
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
		if ( ! ($this->cp->allowed_group('can_delete_categories') OR
				$this->cp->allowed_group('can_access_admin')))
		{		
			show_error($this->lang->line('unauthorized_access'));
		}

		$cat_id = $this->input->get_post('cat_id');

		if ($cat_id == '' OR ! is_numeric($cat_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->lang->loadfile('admin_content');
		$this->load->model('category_model');

		$group_id = $this->category_model->delete_category($cat_id);

		$this->session->set_flashdata('message_success', $this->lang->line('category_deleted'));
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
		if ( ! $this->cp->allowed_group('can_edit_categories'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$edit = ($this->input->post('cat_id') == '') ? FALSE : TRUE;

		$this->lang->loadfile('admin_content');
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

		$this->form_validation->set_rules('cat_name',		'lang:category_name',		'required');
		$this->form_validation->set_rules('cat_url_title',	'lang:cat_url_title',	'callback__cat_url_title');

		$this->form_validation->set_rules('cat_description', '', '');
		$this->form_validation->set_rules('cat_image', '', '');


		// Finish data prep for insertion
		if ($this->config->item('auto_convert_high_ascii') == 'y')
		{
			// Load the text helper
			$this->load->helper('text');

			$_POST['cat_name'] =  ascii_to_entities($_POST['cat_name']);
		}

		$_POST['cat_name'] = str_replace(array('<', '>'), array('&lt;', '&gt;'), $_POST['cat_name']);

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
	
		/*
		foreach ($fields as $id => $val)
		{
			unset($_POST[$id]);
		}
		*/

		$_POST['site_id'] = $this->config->item('site_id');

		if ($edit == FALSE)
		{
			$category_data = array(
							'group_id' => $this->input->post('group_id'),
							'cat_name'  => $this->input->post('cat_name'),
							'cat_url_title' => $this->input->post('cat_url_title'),
							'cat_description' => $this->input->post('cat_description'),
							'cat_image' => $this->input->post('cat_image'),
							'parent_id' => $this->input->post('parent_id'),
							'cat_order' => $this->input->post('cat_order'),
							'site_id' => $this->input->post('site_id')
			);

			$this->db->insert('categories', $category_data);

			$update = FALSE;

			// need this later for custom fields
			$field_cat_id = $this->db->insert_id();

			// Re-order categories

			// When a new category is inserted we need to assign it an order.
			// Since the list of categories might have a custom order, all we
			// can really do is position the new category alphabetically.

			// First we'll fetch all the categories alphabetically and assign
			// the position of our new category

			$this->db->select('cat_id, cat_name');
			$this->db->where('group_id', $group_id);
			$this->db->where('parent_id', $_POST['parent_id']);
			$this->db->order_by('cat_name', 'ASC');
			$query = $this->db->get('categories');

			$position = 0;
			$cat_id = '';

			foreach ($query->result_array() as $row)
			{
				if ($_POST['cat_name'] == $row['cat_name'])
				{
					$cat_id = $row['cat_id'];
					break;
				}

				$position++;
			}

			// Next we'll fetch the list of categories ordered by the custom order
			// and create an array with the category ID numbers

			$this->db->select('cat_id, cat_name');
			$this->db->where('group_id', $group_id);
			$this->db->where('parent_id', $_POST['parent_id']);
			$this->db->where('cat_id !=', $cat_id);
			$this->db->order_by('cat_order');
			$query = $this->db->get('categories');

			$cat_array = array();

			foreach ($query->result_array() as $row)
			{
				$cat_array[] = $row['cat_id'];
			}

			// Now we'll splice in our new category to the array.
			// Thus, we now have an array in the proper order, with the new
			// category added in alphabetically
			array_splice($cat_array, $position, 0, $cat_id);

			// Lastly, update the whole list

			$i = 1;
			foreach ($cat_array as $val)
			{
				$this->db->query("UPDATE exp_categories SET cat_order = '$i' WHERE cat_id = '$val'");
				$i++;
			}
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
						$this->db->query($this->db->update_string('exp_categories', array('parent_id' => $query->row('parent_id') ), "cat_id = '".$children[$key]."'"));
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
										$this->db->query($this->db->update_string('exp_categories', array('parent_id' => $query->row('parent_id') ), "cat_id = '".$key."'"));
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
			$field_cat_id = $this->input->post('cat_id');
		}

		// Insert / Update Custom Field Data

		if ($edit == FALSE)
		{
			$fields['site_id'] = $this->config->item('site_id');
			$fields['cat_id'] = $field_cat_id;
			$fields['group_id'] = $group_id;

			$this->db->insert('category_field_data', $fields);
		}
		elseif ( ! empty($fields))
		{
			$this->db->query($this->db->update_string('exp_category_field_data', $fields, array('cat_id' => $field_cat_id)));
		}

		$this->functions->clear_caching('relationships');

		$this->session->set_flashdata('message_success', $this->lang->line('preference_updated'));
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP."group_id={$group_id}");
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
			$this->form_validation->set_message('_cat_url_title', $this->lang->line('cat_url_title_is_numeric'));
			return FALSE;			
		}

		// Is the Category URL Title still empty?  Can't have that
		if (trim($str) == '')
		{
			$this->form_validation->set_message('_cat_url_title', $this->lang->line('unable_to_create_cat_url_title'));
			return FALSE;	
		}

		// Cat URL Title must be unique within the group
		if ($this->category_model->is_duplicate_category_name($str, $this->input->post('cat_id'), $this->input->post('group_id')))
		{
			$this->form_validation->set_message('_cat_url_title', $this->lang->line('duplicate_cat_url_title'));
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
				show_error($this->lang->line('unauthorized_access'));
			}
		}
		else
		{
			if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
			{
				show_error($this->lang->line('unauthorized_access'));
			}		
		}

		if (($group_id = $this->input->get_post('group_id')) === FALSE OR ! is_numeric($group_id))
		{
			return FALSE;
		}
		
		$order = ($_POST['sort_order'] == 'a') ? 'a' : 'c';
		
		$this->db->select('sort_order');
		$query = $this->db->get_where('category_groups', array('group_id' => $group_id));
		
		if ($order == 'a')
		{
			if ( ! isset($_POST['override']))
			{
				return $this->global_category_order_confirm();
			}
			else
			{
				$this->reorder_cats_alphabetically();
			}
		}

		$this->db->where('group_id', $group_id);
		$this->db->update('category_groups', array('sort_order' => $order));

		$zurl = ($this->input->get_post('modal') == 'yes') ? AMP.'modal=yes' : '';
		$zurl .= ($this->input->get_post('cat_group') !== FALSE) ? AMP.'cat_group='.$this->input->get_post('cat_group') : '';
		$zurl .= ($this->input->get_post('integrated') !== FALSE) ? AMP.'integrated='.$this->input->get_post('integrated') : '';


		// Clear 'ze cache
		$this->functions->clear_caching('db');

		$this->session->set_flashdata('message_success', $this->lang->line('preferences_updated'));

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
				show_error($this->lang->line('unauthorized_access'));
			}
		}
		elseif ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if (($group_id = $this->input->get_post('group_id')) === FALSE OR ! is_numeric($group_id))
		{
			return FALSE;
		}
		
		$this->load->helper('form');
		$this->lang->loadfile('admin_content');
		
		$this->cp->set_variable('cp_page_title', $this->lang->line('global_sort_order'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP.'group_id='.$group_id, $this->lang->line('categories'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=global_category_order'.AMP.'group_id='.$group_id;
		
		$vars['form_hidden']['sort_order'] = $this->input->post('sort_order');
		$vars['form_hidden']['override'] = 1;		

		$this->javascript->compile();
		$this->load->view('admin/category_order_confirm', $vars);					
		
	}

	/** --------------------------------
	/**  Re-order Categories Alphabetically
	/** --------------------------------*/

	function reorder_cats_alphabetically()
	{
		if (AJAX_REQUEST)
		{
			if ( ! $this->cp->allowed_group('can_edit_categories'))
			{
				show_error($this->lang->line('unauthorized_access'));
			}
		}
		else
		{
			if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
			{
				show_error($this->lang->line('unauthorized_access'));
			}		
		}

		if (($group_id = $this->input->get_post('group_id')) === FALSE OR ! is_numeric($group_id))
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$sql = "SELECT cat_name, cat_id, parent_id FROM exp_categories WHERE group_id ='$group_id' ORDER BY parent_id, cat_name";
		
		$query = $this->db->query($sql);
			  
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
							
		foreach($query->result_array() as $row)
		{		
			$this->cat_update[$row['cat_id']]  = array($row['parent_id'], '1', $row['cat_name']);
		}
	 	
		$order = 0;
		
		foreach($this->cat_update as $key => $val) 
		{
			if (0 == $val['0'])
			{	
				$order++;
				$this->cat_update[$key]['1'] = $order;
				$this->process_subcategories($key);  // Sends parent_id
			}
		} 
		
		return $this->cat_update;
	}

	
	
	
	/** --------------------------------
	/**  Process Subcategories
	/** --------------------------------*/
		
	function process_subcategories($parent_id)
	{		
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$order = 0;
		
		foreach($this->cat_update as $key => $val) 
		{
			if ($parent_id == $val['0'])
			{
				$order++;
				$this->cat_update[$key]['1'] = $order;												
				$this->process_subcategories($key);
			}
		}
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
				show_error($this->lang->line('unauthorized_access'));
			}
		}
		elseif ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// Fetch required globals
		foreach (array('cat_id', 'group_id', 'order') as $val)
		{
			if ($this->input->get($val) === FALSE)
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

		$this->db->select('parent_id');
		$this->db->where('cat_id', $cat_id);
		$query = $this->db->get('categories');

		$parent_id = $query->row('parent_id') ;

		// Is the requested category already at the beginning/end of the list?

		$dir = ($order == 'up') ? 'asc' : 'desc';

		$this->db->select('cat_id');
		$this->db->where('group_id', $group_id);
		$this->db->where('parent_id', $parent_id);
		$this->db->order_by('cat_order', $dir);
		$this->db->limit(1);
		$query = $this->db->get('categories');

		if ($query->row('cat_id') == $cat_id)
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

		$this->session->set_flashdata('message_success', $this->lang->line('preferences_updated'));
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$vars['message'] = $message; //$this->lang->line('preferences_updated')

		$vars['group_id'] = $this->input->get_post('group_id');

		if ($vars['group_id'] == '' OR ! is_numeric($vars['group_id']))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->library('table');
		$this->lang->loadfile('admin_content');
		$this->load->model('category_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('custom_category_fields'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', $this->lang->line('categories'));

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
					case 'text' :  $field_type = $this->lang->line('text_input');
						break;
					case 'textarea' :  $field_type = $this->lang->line('textarea');
						break;
					case 'select' :  $field_type = $this->lang->line('select_list');
						break;
				}

				$vars['custom_fields'][$row->field_id]['field_type'] = $field_type;
			}
		}

		$this->jquery->tablesorter('.mainTable', '{
			headers: {3: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->javascript->compile();
		$this->load->view('admin/category_custom_field_group_manager', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$vars['group_id'] = $this->input->get_post('group_id');
		$group_id = $vars['group_id'];

		$vars['field_id'] = $this->input->get_post('field_id');
		$field_id = $vars['field_id'];

		if ($vars['group_id'] == '' OR ! is_numeric($vars['group_id']))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('addons_model');
		$this->load->helper(array('snippets_helper', 'form'));
		$this->lang->loadfile('admin_content');
		$this->load->library('table');

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', $this->lang->line('categories'));

		$this->javascript->change('#field_type', '
			// hide all field format options
			$(".field_format_option").hide();

			// reveal selected option
			$("#"+$(this).val()+"_format").show();
		');

		if ($vars['field_id'] == '')
		{
			$vars['update_formatting'] = FALSE;
			$this->cp->set_variable('cp_page_title', $this->lang->line('create_new_cat_field'));

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
					show_error($this->lang->line('unauthorized_access'));
				}
			}
		}
		else
		{
			$vars['update_formatting'] = TRUE;
			
			$this->javascript->output('$(".formatting_notice_info").hide();');
			
			$this->cp->set_variable('cp_page_title', $this->lang->line('edit_cat_field'));

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
											'text' 		=> $this->lang->line('text_input'),
											'textarea' 	=> $this->lang->line('textarea'),
											'select' 	=> $this->lang->line('select_list')
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
		$vars['field_default_fmt_options']['none'] = $this->lang->line('none');

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

		$this->javascript->compile();
		$this->load->view('admin/edit_custom_category_field', $vars);
	}

	// --------------------------------------------------------------------

	/** -----------------------------------------------------------
	/**  Update Category Fields
	/** -----------------------------------------------------------*/
	// This function updates or creates category fields
	//-----------------------------------------------------------
	function update_custom_category_fields()
	{
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// Are we editing or creating?

		$edit = (($field_id = $this->input->get_post('field_id')) !== FALSE AND is_numeric($field_id)) ? TRUE : FALSE;

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->lang->loadfile('admin_content');

		unset($_POST['custom_field_edit']); // submit button

		// Check for required fields

		$error = array();

		if ($_POST['field_name'] == '')
		{
			$error[] = $this->lang->line('no_field_name');
		}
		else
		{
			// Is the field one of the reserved words?

			if (in_array($_POST['field_name'], $this->cp->invalid_custom_field_names()))
			{
				$error[] = $this->lang->line('reserved_word');
			}
			$field_name = $_POST['field_name'];
		}

		if ($_POST['field_label'] == '')
		{
			$error[] = $this->lang->line('no_field_label');
		}

		// Does field name contain invalid characters?

		if (preg_match('/[^a-z0-9\_\-]/i', $_POST['field_name']))
		{
			$error[] = $this->lang->line('invalid_characters');
		}

		// Field name must be unique for across category groups

		if ($edit == FALSE)
		{
			$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_category_fields WHERE site_id = '".$this->db->escape_str($this->config->item('site_id'))."' AND field_name = '".$this->db->escape_str($_POST['field_name'])."'");

			if ($query->row('count')  > 0)
			{
				$error[] = $this->lang->line('duplicate_field_name');
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
			// Load the string helper
			$this->load->helper('string');

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
			
			$cp_message = $this->lang->line('cat_field_edited');
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
			
			$cp_message = $this->lang->line('cat_field_created');
		}

		$this->functions->clear_caching('all', '', TRUE);

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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

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

		$this->load->helper('form');
		$this->lang->loadfile('admin_content');
		$this->load->model('category_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_field'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=category_management', $this->lang->line('categories'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=delete_custom_category_field';
		$vars['form_extra'] = '';
		$vars['form_hidden']['group_id'] = $group_id;
		$vars['form_hidden']['field_id'] = $field_id;
		$vars['message'] = $this->lang->line('delete_cat_field_confirmation');

		// Grab category_groups locations with this id
		$items = $this->category_model->get_category_label_name($group_id, $field_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->field_label;
		}

		$this->javascript->compile();
		$this->load->view('admin/preference_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  * Delete Custom Category Field
	  *
	  * This function deletes a category field
	  */
	function delete_custom_category_field()
	{
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

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
		$this->lang->loadfile('admin_content');

		$query = $this->category_model->get_category_label_name($group_id, $field_id);

		if ($query->num_rows() == 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->category_model->delete_category_field($group_id, $field_id);

		$cp_message = $this->lang->line('cat_field_deleted').NBS.$query->row('field_label');
		$this->logger->log_action($cp_message);

		$this->functions->clear_caching('all', '', TRUE);

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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('table');
		$this->load->model('field_model');
		$this->lang->loadfile('admin_content');

		$this->cp->set_variable('cp_page_title', $this->lang->line('field_management'));

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}, 3: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->javascript->compile();

		$vars['message'] = $message;
		$vars['field_groups'] = $this->field_model->get_field_groups(); // Fetch field groups

        $this->cp->set_right_nav(array('create_new_field_group' => BASE.AMP.'C=admin_content'.AMP.'M=field_group_edit'));

		$this->load->view('admin/field_group_management', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper('form');
		$this->load->model('status_model');
		$this->lang->loadfile('admin_content');
		$this->load->model('field_model');

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management', $this->lang->line('field_management'));

		// Set default values
		$vars['group_name'] = '';
		$vars['form_hidden'] = array();

		// If we have the group_id variable it's an edit request, so fetch the status data
		$group_id = $this->input->get_post('group_id');

		if ($group_id != '')
		{
			$this->cp->set_variable('cp_page_title', $this->lang->line('edit_field_group_name'));

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
			$this->cp->set_variable('cp_page_title', $this->lang->line('new_field_group'));
			$vars['submit_lang_key'] = 'submit';
		}

		$this->javascript->compile();

		$this->load->view('admin/field_group_edit', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->helper('form');
		$this->lang->loadfile('admin_content');
		$this->load->model('field_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_field_group'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management', $this->lang->line('field_management'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=field_group_delete';
		$vars['form_extra'] = '';
		$vars['form_hidden']['group_id'] = $group_id;
		$vars['message'] = $this->lang->line('delete_field_group_confirmation');

		// Grab category_groups locations with this id
		$items = $this->field_model->get_field_group($group_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->group_name;
		}

		$this->javascript->compile();
		$this->load->view('admin/preference_delete_confirm', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$group_id = $this->input->get_post('group_id');
		$tabs = array();

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('field_model');
		$this->lang->loadfile('admin_content');

		// store the name for the delete message
		$group_name = $this->field_model->get_field_group($group_id);

		// delete routine
		$deleted = $this->field_model->delete_field_groups($group_id);
		
		// Drop from custom layouts
		$query = $this->field_model->get_assigned_channels($group_id);
			
		if ($query->num_rows() > 0 && count($deleted['field_ids']) > 0)
		{
			foreach ($query->result() as $row)
			{
				$channel_ids[] = $row->channel_id;
			}
	
			$this->load->library('layout');
			$this->layout->delete_layout_fields($deleted['field_ids'], $channel_ids);
		}
		
		$this->functions->clear_caching('all', '', TRUE);

		$cp_message = $this->lang->line('field_group_deleted').NBS.NBS.$group_name->row('group_name');

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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$group_id = $this->input->get_post('group_id');

		// If the $group_id variable is present we are editing an
		// existing group, otherwise we are creating a new one
		$edit = (isset($_POST['group_id'])) ? TRUE : FALSE;

		$group_name = $this->input->post('group_name');

		if ($group_name == '')
		{
			return $this->field_group_edit();
		}

		$this->lang->loadfile('admin_content');

		if ( ! preg_match("#^[a-zA-Z0-9_\-/\s]+$#i", $group_name))
		{
			show_error($this->lang->line('illegal_characters'));
		}

		$this->load->model('field_model');

		// Is the group name taken?
		if ($this->field_model->is_duplicate_field_group_name($group_name, $group_id))
		{
			show_error($this->lang->line('taken_status_group_name'));
		}

		// Construct the query based on whether we are updating or inserting
		if ($edit === FALSE)
		{
			$this->field_model->insert_field_group($group_name);

			$cp_message = $this->lang->line('field_group_created').NBS.$group_name;

			$this->logger->log_action($cp_message);

			$this->db->select('channel_id');
			$this->db->where('site_id', $this->config->item('site_id'));
			$channel_info = $this->db->get('channels');

			$query = $this->db->query("SELECT channel_id from exp_channels WHERE site_id = '".$this->db->escape_str($this->config->item('site_id'))."'");

			if ($channel_info->num_rows() > 0)
			{
				$cp_message .= '<br />'.$this->lang->line('assign_group_to_channel').NBS;

				if ($channel_info->num_rows() == 1)
				{
					$link = 'C=admin_content'.AMP.'M=channel_edit_group_assignments'.AMP.'channel_id='.$channel_info->row('channel_id');
					$cp_message .= '<a href="'.BASE.AMP.$link.'">'.$this->lang->line('click_to_assign_group').'</a>';
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
			
			$cp_message = $this->lang->line('field_group_updated').NBS.$group_name;
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$vars['group_id'] = ($group_id != '') ? $group_id : $this->input->get_post('group_id');

		if ($vars['group_id'] == '' OR ! is_numeric($vars['group_id']))
		{
			show_error(lang('not_authorized'));
		}

		$this->cp->set_right_nav(array(
					'create_new_custom_field' =>
					BASE.AMP.'C=admin_content'.AMP.'M=field_edit'.AMP.'group_id='.$vars['group_id']
		));

		$vars['message'] = $message; //$this->lang->line('preferences_updated')

		$this->load->library('table');
		$this->lang->loadfile('admin_content');
		$this->load->model('field_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('add_edit_fields'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management', $this->lang->line('field_management'));

		// Fetch the name of the category group
		$query = $this->field_model->get_field_group($vars['group_id']);
		$vars['group_name'] = $query->row('group_name');

		$custom_fields = $this->field_model->get_fields($vars['group_id']);

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
			headers: {3: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->javascript->compile();
		$this->load->view('admin/field_management', $vars);
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
		$this->load->library('table');
		
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('api');
		$this->load->helper(array('snippets_helper', 'form'));
		$this->load->model('field_model');
		
		$this->api->instantiate('channel_fields');
		$this->lang->loadfile('admin_content');

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management', $this->lang->line('field_management'));

		$vars = array(
			'group_id' => $this->input->get_post('group_id'),
			'field_id' => $this->input->get_post('field_id')
		);
		
		$this->db->select('f.*');
		$this->db->from('channel_fields AS f, field_groups AS g');
		$this->db->where('f.group_id = g.group_id');
		$this->db->where('g.site_id', $this->config->item('site_id'));
		$this->db->where('f.field_id', $vars['field_id']);
		
		$field_query = $this->db->get();

		$this->jquery->tablesorter('.mainTable', '{
			headers: {0: {sorter: false}, 1: {sorter: false}},
			widgets: ["zebra"]
		}');

		$field_query = $this->db->query("SELECT f.* FROM exp_channel_fields AS f, exp_field_groups AS g
						WHERE f.group_id = g.group_id
						AND g.site_id = '".$this->db->escape_str($this->config->item('site_id'))."'
						AND f.field_id = '{$vars['field_id']}'");

		if ($vars['field_id'] == '')
		{
			$type = 'new';
			$this->cp->set_variable('cp_page_title', $this->lang->line('create_new_custom_field'));

			foreach ($field_query->list_fields() as $f)
			{
				if ( ! isset($vars[$f]))
				{
					$vars[$f] = '';
				}
			}

			$this->db->select('group_id');
			$this->db->where('group_id', $vars['group_id']);
			$this->db->where('site_id', $this->config->item('site_id'));
			$query = $this->db->get('channel_fields');

			$vars['field_order'] = $query->num_rows() + 1;

			if ($query->num_rows() > 0)
			{
				$vars['group_id'] = $query->row('group_id');
			}
			else
			{
				// if there are no existing fields yet for this group, this allows us to still validate the group_id
				$this->db->where('group_id', $vars['group_id']);
				$this->db->where('site_id', $this->config->item('site_id'));

				if ($this->db->count_all_results('field_groups') != 1)
				{
					show_error($this->lang->line('unauthorized_access'));
				}
			}
		}
		else
		{
			$type = 'edit';
			$this->cp->set_variable('cp_page_title', $this->lang->line('edit_field'));
			
			foreach ($field_query->row_array() as $key => $val)
			{
				if ($key == 'field_settings' && $val)
				{
					$ft_settings = unserialize(base64_decode($val));
					$vars = array_merge($vars, $ft_settings);
				}
				else
				{
					$vars[$key] = $val;
				}
			}
			
			$vars['update_formatting']	= FALSE;
		}
		
		extract($vars);
		
		// Fetch the name of the group
		$query = $this->field_model->get_field_group($group_id);
		
		$vars['group_name']			= $query->row('group_name');
		$vars['submit_lang_key']	= ($type == 'new') ? 'submit' : 'update';

		// Content types
		$all_content_types = $this->field_model->get_field_content_types();
		
		foreach($all_content_types as $parent => $content_types)
		{
			$vars['field_content_options_'.$parent]['any'] = $this->lang->line('any');
			
			foreach($content_types as $content_type)
			{
				$vars['field_content_options_'.$parent][$content_type] = $this->lang->line('type_'.$content_type);

				if ($content_type == $vars['field_content_type'])
				{
					$vars['field_content_'.$parent] = $content_type;
				}
			}
			
			// Default
			if ( ! isset($vars['field_content_'.$parent]))
			{
				$vars['field_content_'.$parent] = 'any';
			}
		}

		// Fetch the channel names

		$this->db->select('channel_id, channel_title, field_group');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->order_by('channel_title', 'asc');
		$query = $this->db->get('channels');

		$vars['field_pre_populate_id_options'] = array();

		foreach ($query->result_array() as $row)
		{
			// Fetch the field names
			$this->db->select('field_id, field_label');
			$this->db->where('group_id', $row['field_group']);
			$this->db->order_by('field_label','ASC');
			$rez = $this->db->get('channel_fields');

			if ($rez->num_rows() > 0)
			{
				$vars['field_pre_populate_id_options'][$row['channel_title']] = array();
				
				foreach ($rez->result_array() as $frow)
				{
					$vars['field_pre_populate_id_options'][$row['channel_title']][$row['channel_id'].'_'.$frow['field_id']] = $frow['field_label'];
				}
			}
		}

		$vars['field_pre_populate_id_select'] = $field_pre_channel_id.'_'.$field_pre_field_id;

		// build list of formatting options
		if ($type == 'new')
		{
			$vars['edit_format_link'] = '';
			$vars['field_fmt_options'] = array(
				'none'		=> $this->lang->line('none'),
				'br'		=> $this->lang->line('auto_br'),
				'xhtml'		=> $this->lang->line('xhtml')
			);
		}
		else
		{
			$confirm = "onclick=\"if( !confirm('".$this->lang->line('list_edit_warning')."')) return false;\"";
			$vars['edit_format_link'] = '<strong><a '.$confirm.' href="'.BASE.AMP.'C=admin_content'.AMP.'M=edit_formatting_options'.AMP.'id='.$field_id.'" title="'.$this->lang->line('edit_list').'">'.$this->lang->line('edit_list').'</a></strong>';

			$this->db->select('field_fmt');
			$this->db->where('field_id', $field_id);
			$this->db->order_by('field_fmt');
			$query = $this->db->get('field_formatting');

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$name = ucwords(str_replace('_', ' ', $row['field_fmt']));
				
					if ($name == 'Br')
					{
						$name = $this->lang->line('auto_br');
					}
					elseif ($name == 'Xhtml')
					{
						$name = $this->lang->line('xhtml');
					}
					$vars['field_fmt_options'][$row['field_fmt']] = $name;
				}
			}
		}

		$vars['field_fmt'] = (isset($field_fmt) && $field_fmt != '') ? $field_fmt : 'xhtml';

		// Prep our own fields
		
		$fts = $this->api_channel_fields->fetch_installed_fieldtypes();
		
		$default_values = array(
			'field_type'					=> isset($fts['text']) ? 'text' : key($fts),
			'field_show_fmt'				=> 'n',
			'field_required'				=> 'n',
			'field_search'					=> 'n',
			'field_is_hidden'				=> 'n',
			'field_pre_populate'			=> 'n',
			'field_show_spellcheck'			=> 'n',
			'field_show_smileys'			=> 'n',
			'field_show_glossary'			=> 'n',
			'field_show_formatting_btns'	=> 'n',
			'field_show_writemode'			=> 'n',
			'field_show_file_selector'		=> 'n',
			'field_text_direction'			=> 'ltr'
		);

		foreach($default_values as $key => $val)
		{
			$vars[$key] = ( ! isset($vars[$key]) OR $vars[$key] == '') ? $val : $vars[$key];
		}
		
		foreach(array('field_pre_populate', 'field_required', 'field_search', 'field_show_fmt') as $key)
		{
			$current = ($vars[$key] == 'y') ? 'y' : 'n';
			$other = ($current == 'y') ? 'n' : 'y';
			
			$vars[$key.'_'.$current] = TRUE;
			$vars[$key.'_'.$other] = FALSE;
		}
		
		// Text Direction
		$current = $vars['field_text_direction'];
		$other = ($current == 'rtl') ? 'ltr' : 'rtl';
		
		$vars['field_text_direction_'.$current] = TRUE;
		$vars['field_text_direction_'.$other] = FALSE;
		
		// Grab Field Type Settings
		
		$vars['field_type_table']	= array();
		$vars['field_type_options']	= array();

		$created = FALSE;

		foreach($fts as $key => $attr)
		{
			// Global settings
			$settings = unserialize(base64_decode($fts[$key]['settings']));
			$settings['field_type'] = $key;
			
			$this->table->clear();
			
			$this->api_channel_fields->set_settings($key, $settings);
			$this->api_channel_fields->setup_handler($key);
			
			$str = $this->api_channel_fields->apply('display_settings', array($vars));

			$vars['field_type_tables'][$key]	= $str;
			$vars['field_type_options'][$key]	= $attr['name'];
			
			if (count($this->table->rows))
			{
				$vars['field_type_tables'][$key] = $this->table->rows;
			}
		}

		asort($vars['field_type_options']);	// sort by title

		$vars['form_hidden'] = array(
			'group_id'		=> $group_id,
			'field_id'		=> $field_id,
			'site_id'		=> $this->config->item('site_id')
		);

		$ft_selector = "#ft_".implode(", #ft_", array_keys($fts));

		$this->javascript->output('
			var ft_divs = $("'.$ft_selector.'"),
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

		$this->javascript->compile();
		$this->load->view('admin/field_edit', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs') OR ! isset($_POST['group_id']))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->lang->loadfile('admin_content');
		$this->load->library('api');
		$this->api->instantiate('channel_fields');

		// If the $field_id variable has data we are editing an
		// existing group, otherwise we are creating a new one

		$edit = ( ! isset($_POST['field_id']) OR $_POST['field_id'] == '') ? FALSE : TRUE;

		// We need this as a variable as we'll unset the array index

		$group_id = $_POST['group_id'];

		// Check for required fields

		$error = array();
		$this->load->model('field_model');

		// little check in case they switched sites in MSM after leaving a window open.
		// otherwise the landing page will be extremely confusing
		if ( ! isset($_POST['site_id']) OR $_POST['site_id'] != $this->config->item('site_id'))
		{
			$error[] = $this->lang->line('site_id_mismatch');
		}

		if ($_POST['field_name'] == '')
		{
			$error[] = $this->lang->line('no_field_name');
		}
		else
		{
			// Is the field one of the reserved words?

			if (in_array($_POST['field_name'], $this->cp->invalid_custom_field_names()))
			{
				$error[] = $this->lang->line('reserved_word');
			}
		}

		if ($_POST['field_label'] == '')
		{
			$error[] = $this->lang->line('no_field_label');
		}

		// Does field name contain invalid characters?

		if (preg_match('/[^a-z0-9\_\-]/i', $_POST['field_name']))
		{
			$error[] = $this->lang->line('invalid_characters').': '.$_POST['field_name'];
		}

		// Is the field name taken?
		$this->db->select('COUNT(*) as count');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('field_name', $this->input->post('field_name'));

		if ($edit == TRUE)
		{
			$this->db->where('group_id !=', $group_id);
		}

		$query = $this->db->get('channel_fields');

		if ($query->row('count')  > 0)
		{
			$error[] = $this->lang->line('duplicate_field_name');
		}

		$field_type = $_POST['field_type'];

		// If they are setting a file type, ensure there is at least one upload directory available
		if ($field_type == 'file')
		{
			$this->load->model('tools_model');
			$upload_dir_prefs = $this->tools_model->get_upload_preferences();

			// count upload dirs
			if ($upload_dir_prefs->num_rows() == 0)
			{
				$error[] = $this->lang->line('please_add_upload');
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
		
		$native = array(
			'field_id', 'site_id', 'group_id',
			'field_name', 'field_label', 'field_instructions',
			'field_type', 'field_list_items', 'field_pre_populate',
			'field_pre_channel_id', 'field_pre_field_id',
			'field_related_id', 'field_related_orderby', 'field_related_sort', 'field_related_max',
			'field_ta_rows', 'field_maxl', 'field_required',
			'field_text_direction', 'field_search', 'field_is_hidden', 'field_fmt', 'field_show_fmt',
			'field_order', 'field_content_type'
		);
				
		$_posted = array();
		$_field_posted = preg_grep('/^'.$field_type.'_.*/', array_keys($_POST));
		$_keys = array_merge($native,  $_field_posted);

		foreach($_keys as $key)
		{
			if (isset($_POST[$key]))
			{
				$_posted[$key] = $this->input->post($key);
			}
		}

		// Get the field type settings
		$this->api_channel_fields->fetch_all_fieldtypes();
		$this->api_channel_fields->setup_handler($field_type);
		$ft_settings = $this->api_channel_fields->apply('save_settings', array($_posted));
		
		// Default display options
		foreach(array('smileys', 'glossary', 'spellcheck', 'formatting_btns', 'file_selector', 'writemode') as $key)
		{
			$tmp = $this->_get_ft_post_data($field_type, 'field_show_'.$key);
			$ft_settings['field_show_'.$key] = $tmp ? $tmp : 'n';
		}
		
		// Now that they've had a chance to mess with the POST array,
		// grab post values for the native fields (and check namespaced fields)
		foreach($native as $key)
		{
			$native_settings[$key] = $this->_get_ft_post_data($field_type, $key);
		}
		
		// Set some defaults
		$native_settings['field_related_id']		= ($tmp = $this->_get_ft_post_data($field_type, 'field_related_channel_id')) ? $tmp : '0';
		$native_settings['field_list_items']		= ($tmp = $this->_get_ft_post_data($field_type, 'field_list_items')) ? $tmp : '';
				
		$native_settings['field_text_direction']	= ($native_settings['field_text_direction'] !== FALSE) ? $native_settings['field_text_direction'] : 'ltr';
		$native_settings['field_show_fmt']			= ($native_settings['field_show_fmt'] !== FALSE) ? $native_settings['field_show_fmt'] : 'n';
		$native_settings['field_fmt']				= ($native_settings['field_fmt'] !== FALSE) ? $native_settings['field_fmt'] : 'xhtml';
		
		$native_settings['field_content_type']		= ($native_settings['field_content_type'] !== FALSE) ? $native_settings['field_content_type'] : 'any';
		
		if ($native_settings['field_list_items'] != '')
		{
			// This results in double encoding later on
			//$this->load->helper('string');
			//$native_settings['field_list_items'] = quotes_to_entities($native_settings['field_list_items']);
		}
		
		if ($native_settings['field_pre_populate'] == 'y')
		{
			$x = explode('_', $this->_get_ft_post_data($field_type, 'field_pre_populate_id'));

			$native_settings['field_pre_channel_id']	= $x['0'];
			$native_settings['field_pre_field_id'] = $x['1'];
		}
		
		// If they returned a native field value as part of their settings instead of changing the post array,
		// we'll merge those changes into our native settings
		
		foreach($ft_settings as $key => $val)
		{
			if (in_array($key, $native))
			{
				unset($ft_settings[$key]);
				$native_settings[$key] = $val;
			}
		}
		
		$native_settings['field_settings'] = base64_encode(serialize($ft_settings));
		
		// Construct the query based on whether we are updating or inserting
		if ($edit === TRUE)
		{
			$cp_message = $this->lang->line('custom_field_edited');

			if ( ! is_numeric($native_settings['field_id']))
			{
				return FALSE;
			}

			// Update the formatting for all existing entries
			if ($this->_get_ft_post_data($field_type, 'update_formatting') == 'y')
			{
				$this->db->update('channel_data', array('field_ft_'.$native_settings['field_id'] => $native_settings['field_fmt']));
			}

				
			// Send it over to drop old fields, add new ones, and modify as needed
			$this->api_channel_fields->edit_datatype(
								$native_settings['field_id'], 
								$field_type,
								$native_settings
				);


			unset($native_settings['group_id']);

			$this->db->where('field_id', $native_settings['field_id']);
			$this->db->where('group_id', $group_id);
			$this->db->update('channel_fields', $native_settings);

			// Update saved layouts if necessary
			
			$collapse = ($native_settings['field_is_hidden'] == 'y') ? 'true' : 'false';
			$buttons = ($ft_settings['field_show_formatting_btns'] == 'y') ? 'true' : 'false';
			
			$field_info[$native_settings['field_id']] = array(
								'visible'		=> 'true',
								'collapse'		=> $collapse,
								'htmlbuttons'	=> $buttons,
								'width'			=> '100%'
			);
			
			// Add to any custom layouts

			$query = $this->field_model->get_assigned_channels($group_id);
			
			if ($query->num_rows() > 0)
			{
				foreach ($query->result() as $row)
				{
					$channel_ids[] = $row->channel_id;
				}
				
				$this->load->library('layout');
				$this->layout->edit_layout_fields($field_info, $channel_ids);
			}
		}
		else
		{
			$cp_message = $this->lang->line('custom_field_created');

			if ($_POST['field_order'] == 0 OR $_POST['field_order'] == '')
			{
				$query = $this->db->query("SELECT count(*) AS count FROM exp_channel_fields WHERE group_id = '".$this->db->escape_str($group_id)."'");
				$_POST['field_order'] = $query->row('count')  + 1;
			}
			
			if ( ! $native_settings['field_ta_rows'])
			{
				$native_settings['field_ta_rows'] = 0;
			}

			// as its new, there will be no field id, unset it to prevent an empty string from attempting to pass
			unset($native_settings['field_id']);
			
			$this->db->insert('channel_fields', $native_settings);

			$insert_id = $this->db->insert_id();
			$native_settings['field_id'] = $insert_id;

			
			$this->api_channel_fields->add_datatype(
									$insert_id, 
									$native_settings
				);


			$this->db->update('channel_data', array('field_ft_'.$insert_id => $native_settings['field_fmt'])); 


			foreach (array('none', 'br', 'xhtml') as $val)
			{
				$f_data = array('field_id' => $insert_id, 'field_fmt' => $val);
				$this->db->insert('field_formatting', $f_data); 
			}
			
			$collapse = ($native_settings['field_is_hidden'] == 'y') ? 'true' : 'false';
			$buttons = ($ft_settings['field_show_formatting_btns'] == 'y') ? 'true' : 'false';
			
			$field_info['publish'][$insert_id] = array(
								'visible'		=> 'true',
								'collapse'		=> $collapse,
								'htmlbuttons'	=> $buttons,
								'width'			=> '100%'
			);
			
			// Add to any custom layouts

			$query = $this->field_model->get_assigned_channels($group_id);
			
			if ($query->num_rows() > 0)
			{
				foreach ($query->result() as $row)
				{
					$channel_ids[] = $row->channel_id;
				}
				
				$this->load->library('layout');
				$this->layout->add_layout_fields($field_info, $channel_ids);
			}
		}
		
		$_final_settings = array_merge($native_settings, $ft_settings);
		unset($_final_settings['field_settings']);
		
		$this->api_channel_fields->set_settings($native_settings['field_id'], $_final_settings);
		$this->api_channel_fields->setup_handler($native_settings['field_id']);
		$this->api_channel_fields->apply('post_save_settings', array($_posted));

		$this->functions->clear_caching('all', '', TRUE);

		$strlen = strlen($this->input->post('field_name'));

		if ($strlen > 32)
		{
			$this->session->set_flashdata('message_failure', $this->lang->line('field_name_too_lrg'));
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$field_id = $this->input->get_post('field_id');

		if ($field_id == '' OR ! is_numeric($field_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->helper('form');
		$this->lang->loadfile('admin_content');
		$this->load->model('field_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_field'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management', $this->lang->line('field_management'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=field_delete';
		$vars['form_extra'] = '';
		$vars['form_hidden']['field_id'] = $field_id;
		$vars['form_hidden']['group_id'] = $this->input->get('group_id');
		$vars['message'] = $this->lang->line('delete_field_confirmation');

		// Grab status with this id
		$items = $this->field_model->get_field($field_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->field_label;
		}

		$this->javascript->compile();
		$this->load->view('admin/preference_delete_confirm', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$field_id = $this->input->get_post('field_id');

		if ($field_id == '' OR ! is_numeric($field_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('field_model');
		$this->lang->loadfile('admin_content');

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

		$cp_message = $this->lang->line('field_deleted').NBS.$deleted['field_label'];

		$this->logger->log_action($cp_message);

		$this->functions->clear_caching('all', '', TRUE);

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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

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

		$this->load->helper('form');
		$this->load->library('table');
		$this->load->model('addons_model');
		$this->lang->loadfile('admin_content');

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->cp->set_variable('cp_page_title', $this->lang->line('formatting_options'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=field_group_management', $this->lang->line('field_management'));

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
		
		$this->javascript->compile();
		$this->load->view('admin/edit_formatting_options', $vars);
	}

 
 
 
 
	/** ---------------------------------------
	/**  Update Formatting Buttons
	/** ---------------------------------------*/
	function update_formatting_options()
	{
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

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
		
		return $this->field_edit();	
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('table');
		$this->load->model('status_model');
		$this->lang->loadfile('admin_content');

		$this->cp->set_variable('cp_page_title', $this->lang->line('status_groups'));

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}, 3: {sorter: false}},
			widgets: ["zebra"]
		}');

		$vars['message'] = $message;

		// Fetch category groups
		$vars['status_groups'] = $this->status_model->get_status_groups();

		$this->javascript->compile();
		
		$this->cp->set_right_nav(array('create_new_status_group' => BASE.AMP.'C=admin_content'.AMP.'M=status_group_edit'));
		
		$this->load->view('admin/status_group_management', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper('form');
		$this->load->model('status_model');
		$this->lang->loadfile('admin_content');
		$this->load->model('status_model');

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=status_group_management', $this->lang->line('status_management'));

		// Set default values
		$vars['group_id'] = '';
		$vars['group_name'] = '';
		$vars['form_hidden'] = array();

		// If we have the group_id variable it's an edit request, so fetch the status data
		$group_id = $this->input->get_post('group_id');

		if ($group_id != '')
		{
			$this->cp->set_variable('cp_page_title', $this->lang->line('edit_status_group'));

			$vars['form_hidden']['group_id'] = $group_id;

			$vars['submit_lang_key'] = 'update';

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
			$this->cp->set_variable('cp_page_title', $this->lang->line('create_new_status_group'));
			$vars['submit_lang_key'] = 'submit';
		}

		$this->javascript->compile();
		$this->load->view('admin/status_group_edit', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->helper('form');
		$this->lang->loadfile('admin_content');
		$this->load->model('status_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_group'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=status_management'.AMP.'group_id='.$group_id, $this->lang->line('status_management'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=status_group_delete';
		$vars['form_extra'] = '';
		$vars['form_hidden']['group_id'] = $group_id;
		$vars['message'] = $this->lang->line('delete_status_group_confirmation');

		// Grab category_groups locations with this id
		$items = $this->status_model->get_status_group($group_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->group_name;
		}

		$this->javascript->compile();
		$this->load->view('admin/preference_delete_confirm', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('status_model');
		$this->lang->loadfile('admin_content');

		$query = $this->status_model->get_status_group($group_id);

		if ($query->num_rows() == 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->status_model->delete_status_group($group_id);

		$cp_message = $this->lang->line('status_group_deleted').NBS.$query->row('group_name');

		$this->logger->log_action($cp_message);

		$this->functions->clear_caching('all', '', TRUE);

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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

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
			show_error($this->lang->line('illegal_characters'));
		}

		$this->load->model('status_model');
		$this->lang->loadfile('admin_content');

		// Is the group name taken?
		if ($this->status_model->is_duplicate_status_group_name($group_name, $group_id))
		{
			show_error($this->lang->line('taken_status_group_name'));
		}

		// Construct the query based on whether we are updating or inserting
		if ($edit == FALSE)
		{
			$this->status_model->insert_statuses($group_name, $this->status_color_open, $this->status_color_closed);

			$cp_message = $this->lang->line('status_group_created').NBS.$group_name;

			$this->logger->log_action($cp_message);

			$this->db->select('channel_id');
			$this->db->where('site_id', $this->config->item('site_id'));
			$channel_info = $this->db->get('channels');

			$query = $this->db->query("SELECT channel_id from exp_channels WHERE site_id = '".$this->db->escape_str($this->config->item('site_id'))."'");

			if ($channel_info->num_rows() > 0)
			{
				$cp_message .= $this->lang->line('assign_group_to_channel').NBS;

				if ($channel_info->num_rows() == 1)
				{
					$link = 'C=admin_content'.AMP.'M=channel_edit_group_assignments'.AMP.'channel_id='.$channel_info->row('channel_id');
					$cp_message .= '<a href="'.BASE.AMP.$link.'">'.$this->lang->line('click_to_assign_group').'</a>';
				}
				else
				{
					$link = 'C=admin_content'.AMP.'M=channel_management';
				}
			}
		}
		else
		{
			$this->status_model->update_statuses($group_name, $group_id, $this->status_color_open, $this->status_color_closed);

			$cp_message = $this->lang->line('status_group_updated').NBS.$group_name;
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR ! is_numeric($group_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->model('status_model');
		$this->lang->loadfile('admin_content');
		$this->load->library('table');

		$group_name = $this->status_model->get_status_group($group_id);

		$this->cp->set_variable('cp_page_title', $this->lang->line('status_group').':'.NBS.$group_name->row('group_name'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=status_group_management', $this->lang->line('status_management'));

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}, 3: {sorter: false}, 4: {sorter: false}},
			widgets: ["zebra"]
		}');

		$vars['message'] = $message;

		// Fetch status groups
		$vars['statuses'] = $this->status_model->get_statuses($group_id);

		$this->javascript->compile();
		
        $this->cp->set_right_nav(array('create_new_status' => BASE.AMP.'C=admin_content'.AMP.'M=status_edit'.AMP.'group_id='.$group_id));
		
		$this->load->view('admin/status_management', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$status_id = $this->input->get_post('status_id');

		if ($status_id != '' AND ! is_numeric($status_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->library('table');
		$this->load->helper('form');
		$this->lang->loadfile('admin_content');
		$this->load->model('status_model');

		$query = $this->status_model->get_status($status_id);

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=status_group_management', $this->lang->line('status_management'));

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
			$vars['highlight']	 	= '';
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
			$this->cp->set_variable('cp_page_title', $this->lang->line('status_group'));
		}
		else
		{
			$vars['form_hidden']['group_id'] = $query->row('group_id');
			$vars['submit_lang_key'] = 'update';
			$this->cp->set_variable('cp_page_title', $this->lang->line('status_group').':'.NBS.$vars['status']);
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

		$this->javascript->compile();
		$this->load->view('admin/status_edit', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$edit = ( ! $this->input->post('status_id')) ? FALSE : TRUE;

		if ($this->input->post('status') == '')
		{
			$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=status_group_management');
		}

		if (preg_match('/[^a-z0-9\_\-\+\s]/i', $this->input->post('status')))
		{
			show_error($this->lang->line('invalid_status_name'));
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
				show_error($this->lang->line('duplicate_status_name'));
			}

			$data['group_id'] = $_POST['group_id'];
			$data['site_id'] = $this->config->item('site_id');

			$this->db->insert('statuses', $data);
			
			$status_id = $this->db->insert_id();
			$cp_message = $this->lang->line('status_created');
		}
		else
		{
			$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_statuses WHERE status = '".$this->db->escape_str($_POST['status'])."' AND group_id = '".$this->db->escape_str($_POST['group_id'])."' AND status_id != '".$this->db->escape_str($_POST['status_id'])."'");

			if ($query->row('count')  > 0)
			{
				show_error($this->lang->line('duplicate_status_name'));
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

			$cp_message = $this->lang->line('status_updated');
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$status_id = $this->input->get_post('status_id');

		if ($status_id == '' OR ! is_numeric($status_id))
		{
			show_error(lang('not_authorized'));
		}

		$this->load->helper('form');
		$this->lang->loadfile('admin_content');
		$this->load->model('status_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_status'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=status_management', $this->lang->line('status_management'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=status_delete';
		$vars['form_extra'] = '';
		$vars['form_hidden']['status_id'] = $status_id;
		$vars['message'] = $this->lang->line('delete_status_confirmation');

		// Grab status with this id
		$items = $this->status_model->get_status($status_id);

		$vars['items'] = array();

		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->status;
		}

		$this->javascript->compile();
		$this->load->view('admin/preference_delete_confirm', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

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

		$this->session->set_flashdata('message_success', $this->lang->line('status_deleted'));
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=status_management'.AMP.'group_id='.$group_id);
	}

	// --------------------------------------------------------------------

	/**
	 * File Upload Preferences
	 *
	 * Creates the File Upload Preferences main page
	 *
	 * @access	public
	 * @return	void
	 */
	function file_upload_preferences($message = '')
	{
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('table');
		$this->load->model('tools_model');
		$this->lang->loadfile('admin_content');

		$this->cp->set_variable('cp_page_title', $this->lang->line('file_upload_prefs'));

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}},
			widgets: ["zebra"]
		}');

		$vars['message'] = $message;
		$vars['upload_locations'] = $this->tools_model->get_upload_preferences($this->session->userdata('member_group'));

		$this->javascript->compile();

		$this->cp->set_right_nav(array('create_new_upload_pref' => BASE.AMP.'C=admin_content'.AMP.'M=edit_upload_preferences'));
		
		$this->load->view('admin/file_upload_preferences', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * File Upload Create
	 *
	 * Creates the New File Upload Destination page
	 *
	 * @access	public
	 * @return	void
	 */
	function edit_upload_preferences()
	{
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper(array('form', 'snippets'));
		$this->lang->loadfile('admin_content');
		$this->load->library('form_validation');
		$this->load->library('table');
		$this->load->model('tools_model');
		$this->load->model('member_model');

		$id = $this->input->get_post('id');

		$type = ($id != '') ? 'edit' : 'new';

		$upload_directories = $this->tools_model->get_upload_preferences($this->session->userdata('member_group'), ($id == '') ? 'null' : $id);

		if ($upload_directories->num_rows() == 0)
		{
			if ($id != '')
			{
				show_error($this->lang->line('unauthorized_access'));
			}

			foreach ($upload_directories->list_fields() as $f)
			{
				$vars['field_'.$f] = '';
			}

			$vars['field_url'] = base_url(); // override blank with intelligent default
			$vars['field_server_path'] = str_replace(SYSDIR.'/', '', FCPATH); // override blank with intelligent default
			$vars['field_properties'] = "style=\"border: 0;\" alt=\"image\""; // override blank with intelligent default
		}
		else
		{
			foreach ($upload_directories->row_array() as $key => $val)
			{
				$vars['field_'.$key] = $val;
			}
		}

		$vars['form_hidden']['id'] = $vars['field_id'];
		$vars['form_hidden']['cur_name'] = $vars['field_name'];

		$config = array(
					   array(
							 'field'   => 'name',
							 'label'   => 'lang:upload_pref_name',
							 'rules'   => 'required'
						  ),
					   array(
							 'field'   => 'server_path',
							 'label'   => 'lang:server_path',
							 'rules'   => 'required'
						  ),
					   array(
							 'field'   => 'url',
							 'label'   => 'lang:url_to_upload_dir',
							 'rules'   => 'callback_not_http'
						  ),
					   array(
							 'field'   => 'allowed_types',
							 'label'   => 'lang:allowed_types',
							 'rules'   => 'required'
						  ),
					   array(
							 'field'   => 'max_size',
							 'label'   => 'lang:max_size',
							 'rules'   => 'numeric'
						  ),
					   array(
							 'field'   => 'max_height',
							 'label'   => 'lang:max_height',
							 'rules'   => 'numeric'
						  ),
					   array(
							 'field'   => 'max_width',
							 'label'   => 'lang:max_width',
							 'rules'   => 'numeric'
						  )
					);

		$this->form_validation->set_error_delimiters('<span class="notice">', '</span>');

		$this->form_validation->set_rules($config);

		if ($type == 'edit')
		{
			$this->cp->set_variable('cp_page_title', $this->lang->line('edit_file_upload_preferences'));
			$vars['lang_line'] = 'update';
		}
		else
		{
			$this->cp->set_variable('cp_page_title', $this->lang->line('new_file_upload_preferences'));
			$vars['lang_line'] = 'submit';
		}

		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=file_upload_preferences', $this->lang->line('file_upload_preferences'));

		// Which file types are allowed.  Assume images only unless explicitly set to 'all types'
		$vars['allowed_types'] = $upload_directories->row('allowed_types');

		// The remaining fields are all the same, setup a loop for them in the view
		$vars['upload_pref_fields'] = array('max_size', 'max_height', 'max_width', 'properties', 'pre_format', 'post_format', 'file_properties', 'file_pre_format', 'file_post_format');

		$vars['upload_groups'] = $this->member_model->get_upload_groups();
		$vars['banned_groups'] = array();

		if ($vars['upload_groups']->num_rows() > 0)
		{
			$this->db->select('member_group');
			
			if ($id != '')
			{
				$this->db->where('upload_id', $id);
			}

			$result = $this->db->get('upload_no_access');

			if ($result->num_rows() != 0)
			{
				foreach($result->result_array() as $row)
				{
					$vars['banned_groups'][] = $row['member_group'];
				}
			}
		}

		if ($this->form_validation->run() == FALSE)
		{
			$this->javascript->compile();
			$this->load->view('admin/file_upload_create', $vars);
		}
		else
		{
			$this->_update_upload_preferences();
		}
	}

	// --------------------------------------------------------------------

	/**
	 *  Delete Upload Preferences Confirm
	 *
	 * @access	public
	 * @return	mixed
	 */
	function delete_upload_preferences_conf()
	{
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$id = $this->input->get_post('id');

		if ( ! is_numeric($id))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper('form');
		$this->lang->loadfile('admin_content');

		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_upload_preference'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=file_upload_preferences', $this->lang->line('file_upload_preferences'));

		$vars['form_action'] = 'C=admin_content'.AMP.'M=delete_upload_preferences'.AMP.'id='.$id;
		$vars['form_extra'] = '';
		$vars['form_hidden']['id'] = $id;
		$vars['message'] = $this->lang->line('delete_upload_pref_confirmation');

		// Grab all upload locations with this id
		$this->db->where('id', $id);
		$items = $this->db->get('upload_prefs');
		$vars['items'] = array();
		
		foreach($items->result() as $item)
		{
			$vars['items'][] = $item->name;
		}

		$this->javascript->compile();
		$this->load->view('admin/preference_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 *  Delete Upload Preferences
	 *
	 * @access	public
	 * @return	null
	 */
	function delete_upload_preferences()
	{
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$id = $this->input->get_post('id');

		if ( ! is_numeric($id))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->model('tools_model');
		$this->lang->loadfile('admin_content');

		$name = $this->tools_model->delete_upload_preferences($id);

		$this->logger->log_action($this->lang->line('upload_pref_deleted').NBS.NBS.$name);

		// Clear database cache
		$this->functions->clear_caching('db');

		$this->session->set_flashdata('message_success', $this->lang->line('upload_pref_deleted').NBS.NBS.$name);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=file_upload_preferences');
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
			$this->form_validation->set_message('not_http', $this->lang->line('no_upload_dir_url'));
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Update upload preferences
	 *
	 * Processes the file upload preferences form
	 *
	 * @access	private
	 * @return	void
	 */
	function _update_upload_preferences()
	{
		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->model('admin_model');

		// If the $id variable is present we are editing an
		// existing field, otherwise we are creating a new one

		$edit = (isset($_POST['id']) AND $_POST['id'] != '' && is_numeric($_POST['id'])) ? TRUE : FALSE;

		$server_path = $this->input->post('server_path');
		$url = $this->input->post('url');

		if (substr($server_path, -1) != '/' AND substr($server_path, -1) != '\\')
		{
			$_POST['server_path'] .= '/';
		}

		if (substr($url, -1) != '/')
		{
			$_POST['url'] .= '/';
		}

		$error = array();

		// Is the name taken?
		if ($this->admin_model->unique_upload_name(strtolower($this->input->post('name')), strtolower($this->input->post('cur_name')), $edit))
		{
			show_error($this->lang->line('duplicate_dir_name'));
		}

		$id = $this->input->get_post('id');

		unset($_POST['id']);
		unset($_POST['cur_name']);
		unset($_POST['submit']); // submit button

		$data = array();
		$no_access = array();

		$this->db->delete('upload_no_access', array('upload_id' => $id));

		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 7) == 'access_')
			{
				if ($val == 'n')
				{
					$no_access[] = substr($key, 7);
				}
			}
			else
			{
				$data[$key] = $val;
			}
		}

		// Construct the query based on whether we are updating or inserting
		if ($edit === TRUE)
		{
			$this->db->update('upload_prefs', $data, array('id' => $id));
			$cp_message = $this->lang->line('preferences_updated');
		}
		else
		{
			$data['site_id'] = $this->config->item('site_id');

			$this->db->insert('upload_prefs', $data);
			$id = $this->db->insert_id();
			$cp_message = $this->lang->line('new_file_upload_created');
		}

		if (count($no_access) > 0)
		{
			foreach($no_access as $member_group)
			{
				$this->db->insert('upload_no_access', array('upload_id'=>$id, 'upload_loc'=>'cp', 'member_group'=>$member_group));
			}
		}

		$this->functions->clear_caching('db'); // Clear database cache

		$this->session->set_flashdata('message_success', $cp_message);
		$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=edit_upload_preferences'.AMP.'id='.$id);
	}

	// --------------------------------------------------------------------

	/**
	 * Default Ping Servers
	 *
	 * Creates the Default Ping Servers page
	 *
	 * @access	public
	 * @return	void
	 */
	function default_ping_servers($message = '', $id = '0')
	{
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ($id == 0 AND ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper(array('form', 'url'));
		$this->load->library('table');
		$this->lang->loadfile('admin_content');
		$this->load->model('admin_model');

		$vars['message'] = $message;

		$r = '';

		if ($id != 0)
		{
			$this->cp->set_variable('cp_page_title', $this->lang->line('ping_servers'));
			$vars['instructions'] = '';
		}
		else
		{
			$this->cp->set_variable('cp_page_title', $this->lang->line('default_ping_servers'));
			$vars['instructions'] = $this->lang->line('define_ping_servers');
		}

		$vars['form_hidden']['member_id'] = $id;

		$this->jquery->tablesorter('.mainTable', '{widgets: ["zebra"]}');

		$ping_servers = $this->admin_model->get_ping_servers(0);

		// ping protocols supported (currently only xmlrpc)
		$vars['protocols'] = array('xmlrpc'=>'xmlrpc');

		$vars['is_default_options'] = array('y'=>$this->lang->line('yes'), 'n'=>$this->lang->line('no'));

		$i = 1;

		$vars['ping_servers'] = array();

		if ($ping_servers->num_rows() > 0)
		{
			foreach ($ping_servers->result_array() as $row)
			{
				$vars['ping_servers'][$i]['server_id'] = $row['id'];
				$vars['ping_servers'][$i]['server_name'] = $row['server_name'];
				$vars['ping_servers'][$i]['server_url'] = $row['server_url'];
				$vars['ping_servers'][$i]['port'] = $row['port'];
				$vars['ping_servers'][$i]['ping_protocol'] = $row['ping_protocol'];
				$vars['ping_servers'][$i]['server_order'] = $row['server_order'];
				$vars['ping_servers'][$i]['is_default'] = $row['is_default'];
				$i++;
			}
		}

		$vars['blank_count'] = $i;

		$this->javascript->output('

			function setup_js_page() {
				$(".mainTable").tablesorter({widgets: ["zebra"]});
				
				$(".del_row, .order_arrows").show();
				$(".del_instructions").hide();

				$(".tag_order").css("cursor", "move");

				$(".del_row a").click(function(){
					$(this).parent().parent().remove();
					update_ping_servers("true");
					return false;
				});

				$(".mainTable .tag_order input").hide();
				
				$(".mainTable tbody").sortable({
					axis:"y",
					containment:"parent",
					placeholder:"tablesize",
					update: function(){

						$("input[name^=server_order]").each(function(i) {
							$(this).val(i+1);
						});

						update_ping_servers("false");
						$(".mainTable").trigger("applyWidgets");
					}
				});

				$("#ping_server_form").submit(function() {
					update_ping_servers("true");
					return false;
				});
			}

			function update_ping_servers(refresh) {
				$.post(
					EE.BASE+"&C=admin_content&M=save_ping_servers&refresh="+refresh,
					$("#ping_server_form").serializeArray(),
					function(res) {
						if ($(res).find("#ping_server_form").length > 0) {
							$("#ping_server_form").replaceWith($(res).find("#ping_server_form"));
							setup_js_page();
						}

						$.ee_notice("'.$this->lang->line('preferences_updated').'", {"type" : "success"});
					},
				"html");
			}

			setup_js_page();
		');

		$this->cp->add_to_head('<style type="text/css">.tablesize{height:45px!important;}</style>');

		$this->javascript->compile();
		$this->load->view('admin/default_ping_servers', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 *  Save ping servers
	 */
	function save_ping_servers()
	{
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->model('admin_model');

		$id = $this->input->get_post('member_id');

		$data = array();

		foreach ($_POST as $key => $val)
		{
			if (strncmp($key, 'server_name_', 12) == 0 && $val != '')
			{
				$n = substr($key, 12);

				$data[] = array(
								 'member_id'	 => 0,
								 'server_name'	=> $this->input->post('server_name_'.$n),
								 'server_url'	=> $this->input->post('server_url_'.$n),
								 'port'		  => $this->input->post('server_port_'.$n),
								 'ping_protocol' => $this->input->post('ping_protocol_'.$n),
								 'is_default'	=> $this->input->post('is_default_'.$n),
								 'server_order'  => $this->input->post('server_order_'.$n),
								 'site_id'		 => $this->config->item('site_id')
								);
			}
		}

		$this->admin_model->update_ping_servers(0, $data);

		$message = $this->lang->line('preferences_updated');

		$this->default_ping_servers($message);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper(array('form', 'url'));
		$this->load->library('table');
		$this->lang->loadfile('admin_content');
		$this->load->model('admin_model');

		$member_id = (int) $this->input->get_post('member_id');

		if ($member_id == 0 AND ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->cp->set_variable('cp_page_title', $this->lang->line('default_html_buttons'));
		
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

			$this->session->set_flashdata('message_success', $this->lang->line('preferences_updated'));
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

			$this->session->set_flashdata('message_success', $this->lang->line('preferences_updated'));
			$this->functions->redirect(BASE.AMP.'C=admin_content'.AMP.'M=default_html_buttons');
		}

		$vars['html_buttons'] = $this->admin_model->get_html_buttons(0); // recall it in case in insert happened
		$vars['i'] = 1;

		$this->javascript->compile();
		
		$this->load->view('admin/default_html_buttons', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		foreach($this->input->post('ajax_tag_order') as $order=>$tag_id)
		{
			$this->db->set('tag_order', $order);
			$this->db->where('id', $tag_id);
			$this->db->update('html_buttons');
		}

		$this->output->send_ajax_response($this->lang->line('preferences_updated'));
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

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

		$this->load->helper('form');
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
			show_error($this->lang->line('unauthorized_access'));
		}
		$vars['type'] = $type;

		$vars['form_action'] = 'C=admin_content'.AMP.'M=update_config';

		$f_data = $this->admin_model->get_config_fields($type);
		$subtext = $this->admin_model->get_config_field_subtext();

		/** -----------------------------
		/**	 Blast through the array
		/** -----------------------------*/

		// If we're dealing with a database configuration we need to pull the data
		// out of the DB config file.  To make thigs simple we will set the DB config
		// items as general config values
		if ($type == 'db_cfg')
		{
			require $this->config->database_path;

			if ( ! isset($active_group))
			{
				$active_group = 'expressionengine';
			}

			if (isset($db[$active_group]))
			{
				$db[$active_group]['pconnect'] = ($db[$active_group]['pconnect'] === TRUE) ? 'y' : 'n';
				$db[$active_group]['cache_on'] = ($db[$active_group]['cache_on'] === TRUE) ? 'y' : 'n';
				$db[$active_group]['db_debug'] = ($db[$active_group]['db_debug'] === TRUE) ? 'y' : 'n';

				$this->config->set_item('pconnect', $db[$active_group]['pconnect']);
				$this->config->set_item('cache_on', $db[$active_group]['cache_on']);
				$this->config->set_item('cachedir', $db[$active_group]['cachedir']);
				$this->config->set_item('db_debug', $db[$active_group]['db_debug']);
			}
		}

		foreach ($f_data as $name => $options)
		{
			$value = $this->config->item($name);

			$sub = '';
			$details = '';
			$selected = '';

			if (isset($subtext[$name]))
			{
				foreach ($subtext[$name] as $txt)
				{
					$sub .= $this->lang->line($txt);
				}
			}

			switch ($options[0])
			{
				case 's':
					// Select fields
					foreach ($options[1] as $k => $v)
					{
						$details[$k] = $this->lang->line($v);
					}
					$selected = $value;
					break;
				case 'r':
					// Radio buttons
					foreach ($options[1] as $k => $v)
					{
						// little cheat for some values popped into a build update
						if ($value === FALSE)
						{
							$checked = (isset($options['2']) && $k == $options['2']) ? TRUE : FALSE;
						}
						else
						{
							$checked = ($k == $value) ? TRUE : FALSE;
						}

						$details[] = array('name' => $name, 'value' => $k, 'id' => $name.'_'.$k, 'label' => $v, 'checked' => $checked);
					}
					break;
				case 't':
					// Textareas

					// The "kill_pipes" index instructs us to turn pipes into newlines
					if (isset($options['1']['kill_pipes']) && $options['1']['kill_pipes'] === TRUE)
					{
						$text = str_replace('|', NL, $value);
					}
					else
					{
						$text = $value;
					}

					$rows = (isset($options['1']['rows'])) ? $options['1']['rows'] : '20';

					$text = str_replace("\\'", "'", $text);

					$details = array('name' => $name, 'value' => $text, 'rows' => $rows, 'id' => $name);
					break;
				case 'f':
					// Function calls
					switch ($options['1'])
					{
						case 'language_menu'	:
							$options[0] = 's';
							$details = $this->admin_model->get_installed_language_packs();
							$selected = $value;
							break;
						case 'fetch_encoding'	:
							$options[0] = 's';
							$details = $this->admin_model->get_xml_encodings();
							$selected = $value;
							break;
						case 'site_404'			:
							$options[0] = 's';
							$details = array('' => $this->lang->line('none'));
							
							if (is_array($list = $this->admin_model->get_template_list()))
							{
								$details = array_merge($details, $list);
							}

							$selected = $value;
							break;
						case 'theme_menu'		:
							$options[0] = 's';
							$details = $this->admin_model->get_cp_theme_list();
							$selected = $value;
							break;
						case 'timezone'			:
							$options[0] = 's';
							foreach ($this->localize->zones as $k => $v)
							{
								$details[$k] = $this->lang->line($k);
							}
							$selected = $value;
							break;
					}
					break;
				case 'i':
					// Input fields
					$details = array('name' => $name, 'value' => str_replace("\\'", "'", $value), 'id' => $name);
					break;
			}

			$vars['fields'][$name] = array('type' => $options[0], 'value' => $details, 'subtext' => $sub, 'selected' => $selected);
		}

		// if this is an update, show the success message
		//$vars['alert'] = ($this->input->get_post('U')) ? $this->lang->line('preferences_updated') : FALSE;
		//$vars['return_loc'] = BASE.AMP.'C=admin_content'.AMP.'M='.$return_loc.AMP.'U=1';
		$vars['return_loc'] = BASE.AMP.'C=admin_content'.AMP.'M='.$return_loc;

		$this->cp->set_variable('cp_page_title', $this->lang->line($type));

		$this->javascript->compile();
		$this->load->view('admin/config_pages', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_admin') OR ! $this->cp->allowed_group('can_access_content_prefs'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_admin_channels'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$loc = $this->input->get_post('return_location');

		$this->config->update_site_prefs($_POST);

		if ($loc !== FALSE)
		{
			$this->session->set_flashdata('message_success', $this->lang->line('preferences_updated'));
			$this->functions->redirect($loc);
		}
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file admin_content.php */
/* Location: ./system/expressionengine/controllers/cp/admin_content.php */
