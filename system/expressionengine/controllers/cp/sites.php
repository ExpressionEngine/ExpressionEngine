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
 * ExpressionEngine Sites CP Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Sites extends CP_Controller {

	var $version 			= '2.1.7';
	var $build_number		= '20140715';
	var $allow_new_sites 	= FALSE;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		$this->lang->loadfile('sites');

		/** --------------------------------
		/**  Is the MSM enabled?
		/** --------------------------------*/

		if ($this->config->item('multiple_sites_enabled') !== 'y')
        {
			show_error(lang('unauthorized_access'));
        }

		/** --------------------------------
		/**  Are they trying to switch?
		/** --------------------------------*/

		$site_id = $this->input->get_post('site_id');

		if ($this->router->fetch_method() == 'index' && $site_id && is_numeric($site_id))
		{
			ee()->cp->switch_site($site_id);
			return;
		}

		if ($this->router->fetch_method() != 'index')
		{
			$this->load->library('sites');
			$this->lang->loadfile('sites_cp');
			$this->lang->loadfile('admin_content');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */
	function index($message = '')
	{
		if ( count($this->session->userdata('assigned_sites')) == 0 )
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('sites');
		$this->load->library('table');
		$this->lang->loadfile('sites_cp');

		$vars['sites'] = $this->session->userdata('assigned_sites');

		$this->view->cp_page_title = lang('switch_site');
		$this->cp->set_breadcrumb(BASE.AMP.'C=sites', lang('site_management'));

		$vars['can_admin_sites'] = $this->cp->allowed_group('can_admin_sites');

		$vars['message'] = $message;

		$this->cp->set_right_nav(array('edit_sites' => BASE.AMP.'C=sites'.AMP.'M=manage_sites'));

		$this->cp->render('sites/switch', $vars);
	}

	// --------------------------------------------------------------------

	// ===========================
	// = Administative Functions =
	// ===========================

	// --------------------------------------------------------------------

	/**
	 * Site Overview
	 *
	 * Displays the Site Management page
	 *
	 * @access	public
	 * @return	void
	 */
	function manage_sites($message = '')
	{
		if ( ! $this->cp->allowed_group('can_admin_sites'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');
		$this->load->model('site_model');

		$this->view->cp_page_title = lang('site_management');

		$vars['msm_version'] = $this->version;
		$vars['msm_build_number'] = $this->build_number;

		$this->jquery->tablesorter('.mainTable', '{
			widgets: ["zebra"]
		}');

		if ($created_id = $this->input->get('created_id'))
		{
			$this->db->select('site_label');
			$this->db->where('site_id', $created_id);

			$query = $this->db->get('sites');
			$message = lang('site_created').': &nbsp;'.$query->row('site_label');
		}
		elseif ($updated_id = $this->input->get('updated_id'))
		{
			$this->db->select('site_label');
			$this->db->where('site_id', $updated_id);

			$query = $this->db->get('sites');
			$message = lang('site_updated').': &nbsp;'.$query->row('site_label');
		}

		$vars['site_data'] = $this->site_model->get_site();
		$vars['message'] = $message;

		$this->cp->set_right_nav(array('create_new_site' => BASE.AMP.'C=sites'.AMP.'M=add_edit_site'));

		$this->cp->render('sites/list_sites', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Create / Update Site
	 *
	 * Create or Update Form
	 *
	 * @access	public
	 * @return	void
	 */
	function add_edit_site()
	{
		if ( ! $this->cp->allowed_group('can_admin_sites'))
		{
			show_error(lang('unauthorized_access'));
		}

		$site_id = $this->input->get('site_id');

		$title = ($site_id) ? lang('edit_site') : lang('create_new_site');
		$this->view->cp_page_title = $title;

		$this->load->model(array(
			'site_model',
			'file_upload_preferences_model',
			'channel_model',
			'template_model'
		));
		$this->load->helper(array('form', 'snippets'));
		$this->lang->loadfile('filemanager');

		$values = array(
			'site_id'			=> '',
			'site_label'		=> '',
			'site_name'			=> '',
			'site_description'	=> ''
		);

		if ($site_id)
		{
			$query = $this->site_model->get_site($site_id);

			if ($query->num_rows() == 0)
			{
				return FALSE;
			}

			$values = array_merge($values, $query->row_array());
		}

		if ($values['site_id'] == '')
		{
			$this->lang->loadfile('content');
			$this->lang->loadfile('design');

			// Get channels from the model
			$vars['channels'] = $this->channel_model->get_channels(
				'all',
				array('channel_title, channel_id', 'site_id')
			);

			$vars['channel_options'] = array(
				'nothing'		=> lang('do_nothing'),
				'move'			=> lang('move_channel_move_data'),
				'duplicate'		=> lang('duplicate_channel_no_data'),
				'duplicate_all'	=> lang('duplicate_channel_all_data')
			);

			// Get upload directories
			$vars['upload_directories'] = $this->file_upload_preferences_model->get_file_upload_preferences(1, NULL, TRUE, array('order_by' => array('site_id' => 'asc')));

			$vars['upload_directory_options'] = array(
				'nothing'		=> lang('do_nothing'),
				'move'			=> lang('move_upload_destination'),
				'duplicate'		=> lang('duplicate_upload_destination')
			);

			// Get Template Groups
			$vars['template_groups'] = $this->template_model->get_template_groups('all');

			$vars['template_group_options'] = array(
				'nothing'		=> lang('do_nothing'),
				'move'			=> lang('move_template_group'),
				'duplicate'		=> lang('duplicate_template_group')
			);

			// Get Global Variables (site information)
			$sites = $this->site_model->get_site();
			$sites_map = array();
			foreach ($sites->result_array() as $site_info)
			{
				$sites_map[$site_info['site_id']] = $site_info;
			}
			ksort($sites_map);
			$vars['sites'] = $sites_map;

			$vars['global_variable_options'] = array(
				'nothing'		=> lang('do_nothing'),
				'move'			=> lang('move_global_variables'),
				'duplicate'		=> lang('duplicate_global_variables')
			);
		}

		$vars['values'] = $values;
		$vars['form_hidden'] = $site_id ? array('site_id' => $site_id) : NULL;
		$vars['form_url'] = 'C=sites'.AMP.'M=update_site';

		if ($site_id)
		{
			$vars['form_url'] .= AMP.'site_id='.$site_id;
		}

		$this->cp->render('sites/edit_form', $vars);
	}


	function _add_edit_validation()
	{
		$edit = ($this->input->post('site_id') && is_numeric($_POST['site_id'])) ? TRUE : FALSE;
		// Check for required fields


		$this->load->library('form_validation');

		$config = array(
					   array(
							 'field'   => 'site_name',
							 'label'   => 'lang:site_name',
							 'rules'   => 'required|callback__valid_shortname|callback__duplicate_shortname'
							),
					   array(
							 'field'   => 'site_label',
							 'label'   => 'lang:site_label',
							 'rules'   => 'required'
							)
					);

		$this->form_validation->set_error_delimiters('<span class="notice">', '</span>');
		$this->form_validation->set_rules($config);

		if ($edit == FALSE)
		{
			$this->form_validation->set_rules('general_error', 'lang:general_error', 'callback__general_error');
		}

	}

	function _valid_shortname($str)
	{
		if (preg_match('/[^a-z0-9\-\_]/i', $str))
		{
			$this->form_validation->set_message('_valid_shortname', lang('invalid_short_name'));
			return FALSE;
		}

		return TRUE;
	}

	function _duplicate_shortname($str)
	{
		// Short Name Taken Already?

		$sql = "SELECT COUNT(*) AS count FROM exp_sites WHERE site_name = '".$this->db->escape_str($_POST['site_name'])."'";

		if ($this->input->get('site_id') !== FALSE)
		{
			$sql .= " AND site_id != '".$this->db->escape_str($this->input->get('site_id'))."'";
		}

		$query = $this->db->query($sql);

		if ($query->row('count')  > 0)
		{
			$this->form_validation->set_message('_duplicate_shortname', lang('site_name_taken'));
			return FALSE;
		}

		return TRUE;
	}

	function _general_error($str)
	{
		if ( ! file_exists(APPPATH.'language/'.$this->config->item('deft_lang').'/email_data.php'))
		{
			$this->form_validation->set_message('_general_error', lang('unable_to_locate_specialty'));
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update or create a site
	 *
	 * Inserts or updates the site settings
	 *
	 * @access	public
	 * @return	void
	 */
	function update_site()
	{
		if ( ! $this->cp->allowed_group('can_admin_sites'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->_add_edit_validation();

		if ($this->form_validation->run() == FALSE)
		{
			return $this->add_edit_site();
		}

		// If the $site_id variable is present we are editing
		$edit = ($this->input->post('site_id') && is_numeric($_POST['site_id'])) ? TRUE : FALSE;
		$do_comments = $this->db->table_exists('comments');

		$error = array();

		if ($edit == FALSE)
		{
			if ( ! file_exists(APPPATH.'language/'.$this->config->item('deft_lang').'/email_data.php'))
			{
				show_error(lang('unable_to_locate_specialty'));
			}
			else
			{
				require(APPPATH.'language/'.$this->config->item('deft_lang').'/email_data.php');
			}
		}

		// Create/Update Site
		$data = array(
			'site_name'					=> $this->input->post('site_name'),
			'site_label'				=> $this->input->post('site_label'),
			'site_description'			=> $this->input->post('site_description'),
			'site_bootstrap_checksums'	=> ''
		);

		if ($edit == FALSE)
		{
			// This is ugly, but the proper defaults are done by the config lib below
			$others = array('system_preferences', 'mailinglist_preferences', 'member_preferences', 'template_preferences', 'channel_preferences');

			$this->load->model('addons_model');

			if ($this->addons_model->module_installed('pages'))
			{
				$others[] = 'pages';
			}

			foreach($others as $field)
			{
				$data['site_'.$field] = '';
			}

			$this->db->insert('sites', $data);

			$insert_id = $this->db->insert_id();
			$site_id = $insert_id;

			$success_msg = lang('site_created');
		}
		else
		{
			// Grab old data
			$old = $this->db->get_where(
				'sites',
				array('site_id' => $this->input->post('site_id'))
			);

			// Short name change, possibly need to update the template file folder
			if ($old->row('site_name') != $this->input->post('site_name'))
			{
				$prefs = $old->row('site_template_preferences');
				$prefs = unserialize(base64_decode($prefs));

				if ($basepath = $prefs['tmpl_file_basepath'])
				{
					$basepath = preg_replace("#([^/])/*$#", "\\1/", $basepath);		// trailing slash

					if (@is_dir($basepath.$old->row('site_name')))
					{
						@rename($basepath.$old->row('site_name'), $basepath.$_POST['site_name']);
					}
				}
			}

			$this->db->update(
				'sites',
				$data,
				array('site_id' => $this->input->post('site_id'))
			);

			$site_id = $_POST['site_id'];

			$success_msg = lang('site_updated');
		}

		$this->logger->log_action($success_msg.NBS.NBS.$_POST['site_label']);

		// Site Specific Stats Created
		if ($edit === FALSE)
		{
			$query = $this->db->get_where('stats', array('site_id' => '1'));

			foreach ($query->result_array() as $row)
			{
				$data = $row;
				$data['site_id'] = $site_id;
				$data['last_entry_date'] = 0;
				$data['last_cache_clear'] = 0;

				unset($data['stat_id']);

				$this->db->insert('stats', $data);
			}
		}

		// New Prefs Creation
		if ($edit === FALSE)
		{
			foreach(array('system', 'channel', 'template', 'mailinglist', 'member') as $type)
			{
				$prefs = array();

				foreach($this->config->divination($type) as $value)
				{
					$prefs[$value] = $this->config->item($value);

					$prefs['save_tmpl_files']	 = 'n';
					$prefs['tmpl_file_basepath'] = '';
				}

				$this->config->update_site_prefs($prefs, $site_id);
			}
		}

		// Create HTML Buttons for New Site
		if ($edit == FALSE)
		{
			$query = $this->db->get_where(
				'html_buttons',
				array(
					'site_id' => $this->config->item('site_id'),
					'member_id' => 0
				)
			);

			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					unset($row['id']);
					$row['site_id'] = $site_id;
					$this->db->insert('html_buttons', $row);
				}
			}
		}

		// Create Specialty Templates for New Site
		if ($edit == FALSE)
		{
			$Q = array();
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'offline_template', '', '".addslashes(offline_template())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'message_template', '', '".addslashes(message_template())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'admin_notify_reg', '".addslashes(trim(admin_notify_reg_title()))."', '".addslashes(admin_notify_reg())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'admin_notify_entry', '".addslashes(trim(admin_notify_entry_title()))."', '".addslashes(admin_notify_entry())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'admin_notify_mailinglist', '".addslashes(trim(admin_notify_mailinglist_title()))."', '".addslashes(admin_notify_mailinglist())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'admin_notify_comment', '".addslashes(trim(admin_notify_comment_title()))."', '".addslashes(admin_notify_comment())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'mbr_activation_instructions', '".addslashes(trim(mbr_activation_instructions_title()))."', '".addslashes(mbr_activation_instructions())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'forgot_password_instructions', '".addslashes(trim(forgot_password_instructions_title()))."', '".addslashes(forgot_password_instructions())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'validated_member_notify', '".addslashes(trim(validated_member_notify_title()))."', '".addslashes(validated_member_notify())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'decline_member_validation', '".addslashes(trim(decline_member_validation_title()))."', '".addslashes(decline_member_validation())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'mailinglist_activation_instructions', '".addslashes(trim(mailinglist_activation_instructions_title()))."', '".addslashes(mailinglist_activation_instructions())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'comment_notification', '".addslashes(trim(comment_notification_title()))."', '".addslashes(comment_notification())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'comments_opened_notification', '".addslashes(trim(comments_opened_notification_title()))."', '".addslashes(comments_opened_notification())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'private_message_notification', '".addslashes(trim(private_message_notification_title()))."', '".addslashes(private_message_notification())."')";
			$Q[] = "insert into exp_specialty_templates(site_id, template_name, data_title, template_data) values ('".$this->db->escape_str($site_id)."', 'pm_inbox_full', '".addslashes(trim(pm_inbox_full_title()))."', '".addslashes(pm_inbox_full())."')";

			foreach($Q as $sql)
			{
				$this->db->query($sql);
			}
		}

		// New Member Groups
		if ($edit == FALSE)
		{
			$query = $this->db->get_where(
				'member_groups',
				array('site_id' => $this->config->item('site_id'))
			);

			foreach ($query->result_array() as $row)
			{
				$data = $row;
				$data['site_id'] = $site_id;

				$this->db->insert('member_groups', $data);
			}
		}

		// Moving of Data?
		if ($edit == FALSE)
		{
			$channel_ids = array();
			$moved	= array();
			$entries	= array();
			$upload_updates = array();

			foreach($_POST as $key => $value)
			{
				// Channels Moving
				if (substr($key, 0, strlen('channel_')) == 'channel_' &&
					$value != 'nothing' && is_numeric(substr($key, strlen('channel_'))))
				{
					$old_channel_id = substr($key, strlen('channel_'));

					if ($value == 'move')
					{
						$moved[$old_channel_id] = '';

						// Update the channels tables
						$tables = array('channels', 'channel_titles', 'channel_data');

						// Are we updating comments?
						if ($do_comments == TRUE)
						{
							$tables[] = 'comments';
						}

						foreach ($tables as $table)
						{
							$this->db->update(
								$table,
								array('site_id' => $site_id),
								array('channel_id' => $old_channel_id)
							);
						}

						$channel_ids[$old_channel_id] = $old_channel_id; // Stats, Groups, For Later
					}
					elseif($value == 'duplicate' OR $value == 'duplicate_all')
					{
						$query = $this->db->get_where(
							'channels',
							array('channel_id' => $old_channel_id)
						);

						if ($query->num_rows() == 0)
						{
							continue;
						}

						$row = $query->row_array();

						// Uniqueness checks

						foreach(array('channel_name', 'channel_title') AS $check)
						{
							$count = $this->db->where('site_id', $site_id)
									->like($check, $row[$check], 'after')
									->count_all_results('channels');

							if ($count  > 0)
							{
								$row[$check] = $row[$check].'-'.($count + 1);
							}
						}

						$row['site_id']   = $site_id;
						unset($row['channel_id']);

						$this->db->insert('channels', $row);
						$channel_ids[$old_channel_id] = $this->db->insert_id();

						// exp_channel_member_groups

						$query = $this->db->select('group_id')
							->get_where(
								'channel_member_groups',
								array('channel_id' => $old_channel_id)
							);

						if ($query->num_rows() > 0)
						{
							foreach($query->result_array() as $row)
							{
								$this->db->insert(
									'channel_member_groups',
									array(
										'channel_id' => $channel_ids[$old_channel_id],
								  		'group_id'	=> $row['group_id']
								  	)
								);
							}
						}

						/** -----------------------------------------
						/**  Duplicating Entries Too
						/**  - Duplicates, Entries, Data.
						/**  - We try to reassigen relationships further down during $moved processing
						/**  - Forum Topics and Pages are NOT duplicated
						/** -----------------------------------------*/

						if ($value == 'duplicate_all')
						{
							$moved[$old_channel_id] = '';

							$query = $this->db->get_where(
								'channel_titles',
								array('channel_id' => $old_channel_id)
							);

							$entries[$old_channel_id] = array();

							foreach($query->result_array() as $row)
							{
								$old_entry_id		= $row['entry_id'];
								$row['site_id']		= $site_id;
								unset($row['entry_id']);
								$row['channel_id']	= $channel_ids[$old_channel_id];

								$this->db->insert('channel_titles', $row);
								$entries[$old_channel_id][$old_entry_id] = $this->db->insert_id();
							}

							$query = $this->db->get_where(
								'channel_data',
								array('channel_id' => $old_channel_id)
							);

							foreach($query->result_array() as $row)
							{
								$row['site_id']		= $site_id;
								$row['entry_id']	= $entries[$old_channel_id][$row['entry_id']];
								$row['channel_id']	= $channel_ids[$old_channel_id];

								$this->db->insert('channel_data', $row);
							}

							if ($do_comments == TRUE)
							{
								$query = $this->db->get_where(
									'comments',
									array('channel_id' => $old_channel_id)
								);
							}

							if ($do_comments == TRUE && $query->num_rows() > 0)
							{
								$comments = array();
								unset($query->result[0]['comment_id']);
								$fields = array_keys($query->row_array(0));
								unset($fields['0']);

								foreach ($query->result_array() as $row)
								{
									unset($row['comment_id']);
									$row['site_id']		= $site_id;
									$row['entry_id']	= $entries[$old_channel_id][$row['entry_id']];
									$row['channel_id']	= $channel_ids[$old_channel_id];
									$row['edit_date']	= ($row['edit_date'] == '') ? 0 : $row['edit_date'];

									$comments[] = $row;
								}

								// do inserts in batches so the data movement isn't _completely_ insane...
								for ($i = 0, $total = count($comments); $i < $total; $i = $i + 100)
								{
									$this->db->insert_batch(
										'comments',
										array_slice($comments, $i, 100)
									);
								}

								unset($comments);
							}

							if ( ! empty($entries[$old_channel_id]))
							{
								$query = $this->db->where_in('entry_id', array_flip($entries[$old_channel_id]))
									->get('category_posts');

								foreach($query->result_array() as $row)
								{
									$row['entry_id'] = $entries[$old_channel_id][$row['entry_id']];
									$this->db->insert('category_posts', $row);
								}
							}
						}
					}
				}

				// Upload Directory Moving
				if (substr($key, 0, strlen('upload_')) == 'upload_' &&
					$value != 'nothing' && is_numeric(substr($key, strlen('upload_'))))
				{
					$upload_id = substr($key, strlen('upload_'));

					if ($value == 'move')
					{
						$data = array('site_id' => $site_id);

						$this->db->where('id', $this->db->escape_str($upload_id));
						$this->db->update('upload_prefs', $data);

						$this->db->where('upload_location_id', $this->db->escape_str($upload_id));
						$this->db->update('file_dimensions', $data);
					}
					else
					{
						$query = $this->db->get_where(
							'upload_prefs',
							array('id' => $upload_id)
						);

						if ($query->num_rows() == 0)
						{
							continue;
						}

						$row = $query->row_array();

						// Uniqueness checks

						foreach(array('name') AS $check)
						{
							$count = $this->db->where('site_id', $site_id)
								->like($check, $row[$check], 'after')
								->count_all_results('upload_prefs');

							if ($count > 0)
							{
								$row[$check] = $row[$check].'-'.($count + 1);
							}
						}

						$row['site_id']  = $site_id;
						unset($row['id']);

						$this->db->insert('upload_prefs', $row);

						$new_upload_id = $this->db->insert_id();

						$upload_updates[$upload_id] = $new_upload_id;

						$disallowed_query = $this->db->select('member_group, upload_loc')
							->get_where(
								'upload_no_access',
								array('upload_id' => $upload_id)
							);

						if ($disallowed_query->num_rows() > 0)
						{
							foreach($disallowed_query->result_array() as $row)
							{
								$this->db->insert(
									'upload_no_access',
									array(
										'upload_id' 	=> $new_upload_id,
										'upload_loc' 	=> $row['upload_loc'],
										'member_group' 	=> $row['member_group']
									)
								);
							}
						}

						// Get image manipulations to duplicate
						$this->db->where('upload_location_id', $this->db->escape_str($upload_id));
						$image_manipulations = $this->db->get('file_dimensions')->result_array();

						// Duplicate image manipulations
						foreach ($image_manipulations as $row)
						{
							unset($row['id']);

							// Set new site ID and upload location ID
							$row['site_id'] = $site_id;
							$row['upload_location_id'] = $new_upload_id;

							$this->db->insert('file_dimensions', $row);
						}
					}
				}

				// Global Template Variables
				if (substr($key, 0, strlen('global_variables_')) == 'global_variables_' &&
					$value != 'nothing' && is_numeric(substr($key, strlen('global_variables_'))))
				{
					$move_site_id = substr($key, strlen('global_variables_'));

					if ($value == 'move')
					{
						$this->db->update(
							'global_variables',
							array('site_id' => $site_id),
							array('site_id' => $move_site_id)
						);
					}
					else
					{
						$query = $this->db->get_where(
							'global_variables',
							array('site_id' => $move_site_id)
						);

						if ($query->num_rows() == 0)
						{
							continue;
						}

						$row = $query->row_array();

						foreach($query->result_array() as $row)
						{
							// Uniqueness checks

							foreach(array('variable_name') AS $check)
							{
								$count = $this->db->where('site_id', $site_id)
									->like($check, $row[$check], 'after')
									->count_all_results('global_variables');

								if ($count > 0)
								{
									$row[$check] = $row[$check].'-'.($count + 1);
								}
							}

							$row['site_id']		= $site_id;
							unset($row['variable_id']);

							$this->db->insert('global_variables', $row);
						}
					}
				}

				// Template Group and Template Moving
				if (substr($key, 0, strlen('template_group_')) == 'template_group_' &&
					$value != 'nothing' && is_numeric(substr($key, strlen('template_group_'))))
				{
					$group_id = substr($key, strlen('template_group_'));

					if ($value == 'move')
					{
						foreach (array('templates', 'template_groups') as $table)
						{
							$this->db->update(
								$table,
								array('site_id' => $site_id),
								array('group_id' => $group_id)
							);
						}
					}
					else
					{
						$query = $this->db->get_where(
							'template_groups',
							array('group_id' => $group_id)
						);

						if ($query->num_rows() == 0)
						{
							continue;
						}

						$row = $query->row_array();

						// Uniqueness checks
						foreach(array('group_name') AS $check)
						{
							$count = $this->db->where('site_id', $site_id)
								->like($check, $row[$check], 'after')
								->count_all_results('template_groups');

							if ($count > 0)
							{
								$row[$check] = $row[$check].'-'.($count + 1);
							}
						}

						// Create New Group
						$row['site_id'] = $site_id;
						unset($row['group_id']);

						$this->db->insert('template_groups', $row);

						$new_group_id = $this->db->insert_id();

						// Member Group Access to Template Groups
						$query = $this->db->get_where(
							'template_member_groups',
							array('template_group_id' => $query->row('group_id'))
						);

						if ($query->num_rows() > 0)
						{
							foreach($query->result_array() as $row)
							{
								$this->db->insert(
									'template_member_groups',
									array(
										'template_group_id'	=> $new_group_id,
										'group_id' 			=> $row['group_id']
									)
								);
							}
						}

						// Create Templates for New Template Group
						$query = $this->db->get_where(
							'templates',
							array('group_id' => $group_id)
						);

						if ($query->num_rows() == 0)
						{
							continue;
						}

						foreach($query->result_array() as $row)
						{
							$original_id		= $row['template_id'];
							$row['site_id']		= $site_id;
							$row['group_id']	= $new_group_id;
							unset($row['template_id']);

							$this->db->insert('templates', $row);

							$new_template_id = $this->db->insert_id();

							// Template/Page Access
							$access_query = $this->db->get_where(
								'template_no_access',
								array('template_id' => $original_id)
							);

							if ($query->num_rows() > 0)
							{
								foreach($access_query->result_array() as $access_row)
								{
									$this->db->insert(
										'exp_template_no_access',
										array(
											'template_id'	=> $new_template_id,
											'member_group' 	=> $access_row['member_group']
										)
									);
								}
							}
						}
					}
				}
			}

			// Additional Channel Moving Work - Stats/Groups
			if (count($channel_ids) > 0)
			{
				$status			 = array();
				$fields			 = array();
				$categories		 = array();
				$category_groups = array();
				$field_match	 = array();
				$cat_field_match = array();

				// Load DB Forge, we'll need this for some alters later on
				$this->load->dbforge();

				foreach($channel_ids as $old_channel => $new_channel)
				{
					$query = $this->db->select('cat_group, status_group, field_group')
						->get_where(
							'channels',
							array('channel_id' => $new_channel)
						);

					$row = $query->row_array();

					// Duplicate Status Group
					$status_group = $query->row('status_group');

					if ( ! empty($status_group))
					{
						if ( ! isset($status[$status_group]))
						{
							$squery = $this->db->select('group_name')
								->get_where(
									'status_groups',
									array('group_id' => $status_group)
								);

							$row = $squery->row_array();

							// Uniqueness checks
							foreach(array('group_name') AS $check)
							{
								$count = $this->db->where('site_id', $site_id)
									->like($check, $row[$check], 'after')
									->count_all_results('status_groups');

								if ($count  > 0)
								{
									$row[$check] = $row[$check].'-'.($count + 1);
								}
							}

							$this->db->insert(
								'status_groups',
								array(
									'site_id'		=> $site_id,
									'group_name' 	=> $row['group_name']
								)
							);

							$status[$status_group ] = $this->db->insert_id();

							$squery = $this->db->get_where(
								'statuses',
								array('group_id' => $status_group)
							);

							if ($squery->num_rows() > 0)
							{
								foreach($squery->result_array() as $row)
								{
									$row['site_id'] 	= $site_id;
									unset($row['status_id']);
									$row['group_id']	= $status[$status_group];

									$this->db->insert('statuses', $row);
								}
							}
						}

						// Update Channel With New Group ID
						$this->db->update(
							'exp_channels',
							array('status_group' => $status[$status_group]),
							array('channel_id' => $new_channel)
						);
					}

					// Duplicate Field Group
					$field_group = $query->row('field_group');

					if ( ! empty($field_group))
					{
						if ( ! isset($fields[$field_group]))
						{
							$fquery = $this->db->select('group_name')
								->get_where(
									'field_groups',
									array('group_id' => $field_group)
								);

							$fq_group_name = $fquery->row('group_name');

							// Uniqueness checks
							foreach(array('group_name') AS $check)
							{
								$count = $this->db->where('site_id', $site_id)
									->like($check, $fquery->row($check), 'after')
									->count_all_results('field_groups');

								if ($count > 0)
								{
									$fq_group_name = $fquery->row($check).'-'.($count + 1);
								}
							}

							$this->db->insert(
								'field_groups',
								array(
									'site_id'		=> $site_id,
									'group_name' 	=> $fq_group_name
								)
							);

							$fields[$field_group] = $this->db->insert_id();

							// New Fields Created for New Field Group
							$fquery = $this->db->get_where(
								'channel_fields',
								array('group_id' => $field_group)
							);

							if ($fquery->num_rows() > 0)
							{
								foreach($fquery->result_array() as $row)
								{
									$format_query = $this->db->select('field_fmt')
										->get_where(
											'field_formatting',
											array('field_id' => $row['field_id'])
										);

									$old_field_id 		= $row['field_id'];
									$row['site_id'] 	= $site_id;
									unset($row['field_id']);
									$row['group_id']	= $fields[$field_group];

									// Uniqueness checks
									foreach(array('field_name', 'field_label') AS $check)
									{
										$count = $this->db->where(array(
												'site_id'	=> $site_id,
												'group_id'	=> $field_group
											))
											->like($check, $row[$check], 'after')
											->count_all_results('channel_fields');

										if ($count > 0)
										{
											$row[$check] = $row[$check].'-'.($count + 1);
										}
									}

									$this->db->insert('channel_fields', $row);

									$field_id = $this->db->insert_id();

									$field_match[$old_field_id] = $field_id;

									// Channel Data Field Creation, Whee!
									if ($row['field_type'] == 'date' OR $row['field_type'] == 'relationship')
									{
										$columns = array(
											'field_id_'.$field_id => array(
												'type' 			=> 'int',
												'constraint' 	=> 10,
												'null' 			=> FALSE,
												'default' 		=> 0
											),
											'field_ft_'.$field_id => array(
												'type'			=> 'tinytext',
												'null'			=> TRUE
											)
										);

										if ($row['field_type'] == 'date')
										{
											$columns['field_dt_'.$field_id] = array(
												'type'			=> 'varchar',
												'constraint'	=> 8
											);
										}

										$this->dbforge->add_column('channel_data', $columns);
									}
									elseif($row['field_type'] == 'grid')
									{
										$this->load->library('api');
										$this->api->instantiate('channel_fields');
										$this->api_channel_fields->fetch_installed_fieldtypes();

										$this->load->model('grid_model');

										$this->load->dbforge();
										ee()->dbforge->add_column(
											'channel_data',
											array(
												'field_id_' . $field_id => array(
													'type'			=> 'int',
													'constraint'	=> 10
												),
												'field_ft_' . $field_id => array(
													'type'			=> 'tinytext'
												)
											)
										);

										$this->grid_model->create_field($field_id, 'channel');

										$columns = $this->grid_model->get_columns_for_field($old_field_id, 'channel');
										foreach($columns as $column)
										{
											unset($column['col_id']);
											$column['field_id'] = $field_id;
											$column['col_settings'] = json_encode($column['col_settings']);
											$this->grid_model->save_col_settings($column);
										}
									}
									else
									{
										$columns = array(
											'field_id_'.$field_id => array(
												'type' 			=> 'text',
												'null' 			=> TRUE,
											),
											'field_ft_'.$field_id => array(
												'type'			=> 'tinytext',
												'null'			=> TRUE
											)
										);

										switch ($row['field_content_type'])
										{
											case 'numeric':
												$columns['field_id_'.$field_id]['type'] = 'float';
												$columns['field_id_'.$field_id]['default'] = 0;
												break;
											case 'integer':
												$columns['field_id_'.$field_id]['type'] = 'int';
												$columns['field_id_'.$field_id]['default'] = 0;
												break;
										}

										$this->dbforge->add_column('channel_data', $columns);

										// Replace NULL values
										if ($type == 'text')
										{
											$this->db->update(
												'channel_data',
												array("field_id_{$field_id}" => '')
											);
										}
									}

									// Duplicate Each Fields Formatting Options Too
									if ($format_query->result_array() > 0)
									{
										foreach($format_query->result_array() as $format_row)
										{
											$this->db->insert(
												'field_formatting',
												array(
													'field_id'  => $field_id,
											  		'field_fmt' => $format_row['field_fmt']
												)
											);
										}
									}
								}
							}
						}

						// Update Channel With New Group ID
						//  Synce up a few new fields in the channel table
						$channel_results = $this->db->select('search_excerpt')
							->get_where(
								'channels',
								array('channel_id' => $old_channel)
							);

						$channel_data['search_excerpt'] = '';

						if ($channel_results->num_rows() > 0)
						{
							$channel_row = $channel_results->row_array();

							if (isset($field_match[$channel_row['search_excerpt']]))
							{
								$channel_data['search_excerpt'] = $field_match[$channel_row['search_excerpt']];
							}
						}

						$this->db->update(
							'channels',
							array(
								'field_group' => $fields[$field_group],
								'search_excerpt' => (int) $channel_data['search_excerpt']
							),
							array('channel_id' => $new_channel)
						);

						// Moved Channel?  Need Old Field Group
						if (isset($moved[$old_channel]))
						{
							$moved[$old_channel] = $field_group ;
						}
					}

					// Duplicate Category Group(s)
					$cat_group = $query->row('cat_group');

					if ( ! empty($cat_group))
					{
						$new_insert_group = array();

						foreach(explode('|', $query->row('cat_group') ) as $cat_group)
						{
							if (isset($category_groups[$cat_group]))
							{
								$new_insert_group[] = $category_groups[$cat_group];

								continue;
							}

							$gquery = $this->db->select('group_name')
								->get_where(
									'category_groups',
									array('group_id' => $cat_group)
								);

							if ($gquery->num_rows() == 0)
							{
								continue;
							}

							$gquery_row = $gquery->row();

							// Uniqueness checks
							foreach(array('group_name') AS $check)
							{
								$count = $this->db->where('site_id', $site_id)
									->like($check, $gquery->row($check), 'after')
									->count_all_results('category_groups');

								if ($count > 0)
								{
									$gquery_row->$check = $gquery->row($check).'-'.($count + 1);
								}
							}

							$gquery_row->site_id   = $site_id;
							unset($gquery_row->group_id);

							$this->db->insert('category_groups', $gquery_row);

							$category_groups[$cat_group] = $this->db->insert_id();

							$new_insert_group[] = $category_groups[$cat_group];

							// Custom Category Fields
							$fquery = $this->db->get_where(
								'category_fields',
								array('group_id' => $cat_group)
							);

							if ($fquery->num_rows() > 0)
							{
								foreach($fquery->result_array() as $row)
								{
									// Uniqueness checks

									foreach(array('field_name') AS $check)
									{
										$count = $this->db->where('site_id', $site_id)
											->like($check, $row[$check], 'after')
											->count_all_results('category_fields');

										if ($count > 0)
										{
											$row[$check] = $row[$check].'-'.($count + 1);
										}
									}

									$old_field_id = $row['field_id'];

									$row['site_id'] 	= $site_id;
									unset($row['field_id']);
									$row['group_id']	= $category_groups[$cat_group];

									$this->db->insert('category_fields', $row);

									$field_id = $this->db->insert_id();

									$cat_field_match[$old_field_id] = $field_id;

									// Custom Catagory Field Data Creation, Whee!
									$columns = array(
										'field_id_'.$field_id => array(
											'type' 			=> 'text',
											'null' 			=> TRUE,
										),
										'field_ft_'.$field_id => array(
											'type'			=> 'varchar',
											'constraint'	=> 40,
											'null'			=> TRUE,
											'default'		=> 'none'
										)
									);
									$this->dbforge->add_column('category_field_data', $columns);

									$this->db->update(
										'category_field_data',
										array("field_ft_{$field_id}" => $row['field_default_fmt'])
									);
								}
							}

							// New Categories Created for New Category Group
							$cquery = $this->db->get_where(
								'categories',
								array('group_id' => $cat_group)
							);

							if ($cquery->num_rows() > 0)
							{
								foreach($cquery->result_array() as $row)
								{
									$fields_query = $this->db->get_where(
										'category_field_data',
										array('cat_id' => $row['cat_id'])
									);

									// Uniqueness checks
									foreach(array('cat_url_title') AS $check)
									{
										$count = $this->db->where('site_id', $site_id)
											->like($check, $row[$check], 'after')
											->count_all_results('categories');

										if ($count > 0)
										{
											$row[$check] = $row[$check].'-'.($count + 1);
										}
									}

									$old_cat_id 		= $row['cat_id'];

									$row['site_id'] 	= $site_id;
									unset($row['cat_id']);
									$row['group_id']	= $category_groups[$cat_group];
									$row['parent_id']	= ($row['parent_id'] == '0' OR ! isset($categories[$row['parent_id']])) ? '0' : $categories[$row['parent_id']];

									$this->db->insert('categories', $row);

									$cat_id = $this->db->insert_id();

									$categories[$old_cat_id] = $cat_id;

									// Duplicate Field Data Too
									if ($fields_query->num_rows() > 0)
									{
										$fields_query_row = $fields_query->row_array();

										$fields_query_row['site_id']	= $site_id;
										$fields_query_row['group_id']	= $category_groups[$cat_group];
										$fields_query_row['cat_id']		= $cat_id;

										foreach ($fquery->result_array() as $fq_row)
										{
											if ($fields_query_row["field_id_{$fq_row['field_id']}"] != '')
											{
												$fields_query_row['field_id_'.$cat_field_match[$fq_row['field_id']]] = $fields_query_row["field_id_{$fq_row['field_id']}"];
												$fields_query_row["field_id_{$fq_row['field_id']}"] = '';
											}
										}

										$this->db->insert('category_field_data', $fields_query_row);
									}
								}
							}
						}

						$new_insert_group = implode('|', $new_insert_group);
					}
					else
					{
						$new_insert_group = '';
					}

					// Update Channel With New Group ID
					$this->db->update(
						'channels',
						array('cat_group' => $new_insert_group),
						array('channel_id' => $new_channel)
					);
				}


				/** -----------------------------------------
				/**  Move Data Over For Moveed Channels/Entries
				/**  - Find Old Fields from Old Site Field Group, Move Data to New Fields, Zero Old Fields
				/**  - Reassign Categories for New Channels Based On $categories array
				/** -----------------------------------------*/

				if (count($moved) > 0)
				{
					$moved_relationships = array();

					// Relationship Field Checking? - For 'duplicate_all' for channels, NOT enabled
					if (count($entries) > 0)
					{
						$complete_entries = array();

						foreach($entries as $old_channel => $its_entries)
						{
							$complete_entries = array_merge($complete_entries, $its_entries);
						}

						$rel_check = (empty($complete_entries)) ? FALSE : TRUE;

						// Find Relationships for Old Entry IDs That Have Been Moveed
						if ($rel_check)
						{
							$query = $this->db->where_in('parent_id', array_flip($complete_entries))
								->get('relationships');

							if ($query->num_rows() > 0)
							{
								foreach($query->result_array() as $row)
								{
									// Only If Child Moveed As Well...

									if (isset($complete_entries[$row['child_id']]))
									{
										$old_rel_id = $row['relationship_id'];
										unset($row['relationship_id']);
										$row['child_id'] = $complete_entries[$row['child_id']];
										$row['parent_id'] = $complete_entries[$row['parent_id']];

										$this->db->insert('relationships', $row);

										$moved_relationships[$old_rel_id] = $this->db->insert_id();
									}
								}
							}
						}
					}

					// Moving Field Data for Moved Entries
					// We need to change the directory for any moved fields - if the directory was duplicated
					// Create the string here- then replace the placeholder with the correct field id

					if (count($upload_updates))
					{
						$file_string = 'CASE ';

						foreach ($upload_updates as $old_dir => $new_dir)
						{
							$file_string .= "WHEN field_id_a8bxdee LIKE '{filedir_".$old_dir."}%' THEN REPLACE(field_id_a8bxdee, '{filedir_".$old_dir."}', '{filedir_".$new_dir."}') ";
						}

						$file_string .= 'ELSE field_id_a8bxdee END ';

					}
					else
					{
						$file_string = 'field_id_a8bxdee ';
					}


					foreach($moved as $channel_id => $field_group)
					{
						$query = $this->db->select('field_id, field_type')
							->get_where(
								'channel_fields',
								array('group_id' => $field_group)
							);

						if (isset($entries[$channel_id]))
						{
							$channel_id = $channel_ids[$channel_id]; // Moved Entries, New Channel ID Used
						}

						if ($query->num_rows() > 0)
						{
							$related_fields = array();

							foreach($query->result_array() as $row)
							{
								if ( ! isset($field_match[$row['field_id']])) continue;


								if ($row['field_type'] == 'file')
								{
									$this->db->query("UPDATE exp_channel_data
										SET `field_id_".$this->db->escape_str($field_match[$row['field_id']])."` = ".
										str_replace('a8bxdee', $row['field_id'], $file_string).
											"WHERE channel_id = '".$this->db->escape_str($channel_id)."'");

									$this->db->set('field_id_'.$row['field_id'], NULL);
									$this->db->where('channel_id', $channel_id)
										->update('channel_data');
								}
								else
								{
									// Set the new field to be the same as the old field
									$this->db->set(
										"field_id_{$field_match[$row['field_id']]}",
										'`field_id_'.$row['field_id'].'`',
										FALSE
									);

									$null_type = ($row['field_type'] == 'date' OR $row['field_type'] == 'relationship' OR $row['field_type'] == 'grid') ? 0 : NULL;
									$this->db->set('field_id_'.$row['field_id'], $null_type);
									$this->db->where('channel_id', $channel_id)
										->update('channel_data');
								}

								$this->db->set(
									"field_ft_{$field_match[$row['field_id']]}",
									'`field_ft_'.$row['field_id'].'`',
									FALSE
								);
								$this->db->set('field_ft_'.$row['field_id'], NULL);
								$this->db->where('channel_id', $channel_id)
									->update('channel_data');

								if ($row['field_type'] == 'date')
								{
									// Set the new field to be the same as the old field
									$this->db->set(
										"field_dt_{$field_match[$row['field_id']]}",
										'`field_dt_'.$row['field_id'].'`',
										FALSE
									);
									$this->db->set('field_dt_'.$row['field_id'], NULL);
									$this->db->where('channel_id', $channel_id)
										->update('channel_data');
								}

								if ($row['field_type'] == 'relationship')
								{
									$related_fields[] = 'field_ft_'.$field_match[$row['field_id']];  // We used this for moved relationships, see above
								}
							}

							// Modifying Field Data for Related Entries
							if (count($related_fields) > 0 && count($moved_relationships) > 0)
							{
								$query = $this->db->query('SELECT '.implode(',', $related_fields).' FROM exp_channel_data
									 WHERE ('.implode(" != 0 OR ", $related_fields).')');

								if ($query->num_rows() > 0)
								{
									foreach($query->result_array() as $row)
									{
										foreach($row as $key => $value)
										{
											if ($value != '0' && isset($moved_relationships[$value]))
											{
												$this->db->update(
													'channel_data',
													array($key => $moved_relationships[$value]),
													array($key => $value)
												);
											}
										}
									}
								}
							}
						}

						// Category Reassignment
						$query = $this->db->select('cp.entry_id')
							->from('category_posts cp')
							->join('channel_titles wt', 'wt.entry_id = cp.entry_id')
							->where('wt.channel_id', $channel_id)
							->get();

						if ($query->num_rows() > 0)
						{
							$entry_ids = array();

							foreach($query->result_array() as $row)
							{
								$entry_ids[] = $row['entry_id'];
							}

							foreach($categories as $old_cat => $new_cat)
							{
								$this->db->where_in('entry_id', $entry_ids)
									->update(
										'category_posts',
										array('cat_id' => $new_cat),
										array('cat_id' => $old_cat)
									);
							}
						}
					}
				}
			}
		}

		// Check to see if there's a status group
		if ($edit === FALSE)
		{
			$this->load->model('status_model');
			$count = $this->status_model->get_status_groups($site_id)->row('count');

			if ($count > 0)
			{
				$this->status_model->insert_statuses('Statuses', $site_id);
			}
		}

		// Refresh Sites List
		$assigned_sites = array();

		if ($this->session->userdata['group_id'] == 1)
		{
			$result = $this->db->select('site_id, site_label')
				->order_by('site_label')
				->get('sites');
		}
		elseif ($this->session->userdata['assigned_sites'] != '')
		{
			$result = $this->db->select('site_id, site_label')
				->where_in('site_id', explode('|', $this->session->userdata['assigned_sites']))
				->order_by('site_label')
				->get('sites');
		}

		if (($this->session->userdata['group_id'] == 1 OR $this->session->userdata['assigned_sites'] != '') && $result->num_rows() > 0)
		{
			foreach ($result->result_array() as $row)
			{
				$assigned_sites[$row['site_id']] = $row['site_label'];
			}
		}

		$this->session->userdata['assigned_sites'] = $assigned_sites;

		// Update site stats
		$original_site_id = $this->config->item('site_id');

		$this->config->set_item('site_id', $site_id);

		if ($do_comments === TRUE)
		{
			$this->stats->update_comment_stats();
		}

		$this->stats->update_member_stats();
		$this->stats->update_channel_stats();

		$this->config->set_item('site_id', $original_site_id);

		// View Sites List
		if ($edit === TRUE)
		{
			$this->functions->redirect(BASE.AMP.'C=sites'.AMP.'M=manage_sites&updated_id='.$site_id);
		}
		else
		{
			$this->functions->redirect(BASE.AMP.'C=sites'.AMP.'M=manage_sites&created_id='.$site_id);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Site Delete Confirmation
	 *
	 *
	 * @access	public
	 * @return	mixed
	 */
	function site_delete_confirm()
	{
		if ( ! $this->cp->allowed_group('can_admin_sites'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $site_id = $this->input->get_post('site_id'))
		{
			return FALSE;
		}

		if ($site_id == 1)
		{
			return FALSE;
		}

		$this->db->select('site_label');
		$query = $this->db->get_where('sites', array('site_id' => $site_id));

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$this->view->cp_page_title = lang('delete_site');

		$vars['site_id'] = $site_id;
		$vars['message'] = lang('delete_site_confirmation');
		$vars['site_label'] = $query->row()->site_label;

		$this->cp->render('sites/delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Site Delete Confirmation
	 *
	 *
	 * @access	public
	 * @return	mixed
	 */
	function delete_site()
	{
		if ( ! $this->cp->allowed_group('can_admin_sites'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $site_id = $this->input->post('site_id'))
		{
			return FALSE;
		}

		if ( ! is_numeric($site_id))
		{
			return FALSE;
		}

		if ($site_id == 1)
		{
			return FALSE;
		}

		$query = $this->db->select('site_label')
			->get_where(
				'sites',
				array('site_id' => $site_id)
			);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$this->logger->log_action(lang('site_deleted').':'.NBS.NBS.$query->row('site_label') );

		$this->db->select('entry_id');
		$this->db->where('site_id', $site_id);
		$query = $this->db->get('channel_titles');

		$entries = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$entries[] = $row->entry_id;
			}
		}

		// Just like a gossipy so-and-so, we will now destroy relationships! Category post is also toast.
		if (count($entries) > 0)
		{
			// delete leftovers in category_posts
			$this->db->where_in('entry_id', $entries);
			$this->db->delete('category_posts');

			// delete parents
			$this->db->where_in('parent_id', $entries);
			$this->db->delete('relationships');

			// are there children?
			$this->db->select('relationship_id');
			$this->db->where_in('child_id', $entries);
			$child_results = $this->db->get('relationships');

			if ($child_results->num_rows() > 0)
			{
				// gather related fields
				$this->db->select('field_id');
				$this->db->where('field_type', 'relationship');
				$fquery = $this->db->get('channel_fields');

				// We have children, so we need to do a bit of housekeeping
				// so parent entries don't continue to try to reference them
				$cids = array();

				foreach ($child_results->result_array() as $row)
				{
					$cids[] = $row['relationship_id'];
				}

				foreach($fquery->result_array() as $row)
				{
					$this->db->where_in('field_id_'.$row['field_id'], $cids);
					$this->db->update('channel_data', array('field_id_'.$row['field_id'] => 0));
				}
			}

			// aaaand delete
			$this->db->where_in('child_id', $entries);
			$this->db->delete('relationships');
		}

		// Delete Channel Custom Field Columns for Site
		// Save the field ids in an array so we can delete the associated field formats
		$this->load->dbforge();
		$this->load->library('smartforge');
		$nuked_field_ids = array();

		$query = $this->db->select('field_id, field_type')
			->get_where(
				'channel_fields',
				array('site_id' => $site_id)
			);

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$this->smartforge->drop_column('channel_data', 'field_id_'.$row['field_id']);
				$this->smartforge->drop_column('channel_data', 'field_ft_'.$row['field_id']);

				$nuked_field_ids[] = $row['field_id'];

				if ($row['field_type'] == 'date')
				{
					$this->smartforge->drop_column('channel_data', 'field_dt_'.$row['field_id']);
				}
				elseif ($row['field_type'] == 'grid')
				{
					$this->db->where('field_id', $row['field_id'])
								->delete('grid_columns');
					$this->dbforge->drop_table('channel_grid_field_' . $row['field_id']);
				}
			}
		}

		// Delete any related field formatting options
		if ( ! empty($nuked_field_ids))
		{
			$this->db->where_in('field_id', $nuked_field_ids);
			$this->db->delete('field_formatting');
		}

		// Delete Category Custom Field Columns for Site
		$query = $this->db->select('field_id')
			->get_where(
				'category_fields',
				array('site_id' => $site_id)
			);

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$field_id = $row['field_id'];
				$this->smartforge->drop_column('category_field_data', 'field_id_'.$field_id);
				$this->smartforge->drop_column('category_field_data', 'field_ft_'.$field_id);
			}
		}

		// Delete Upload Permissions for Site
		$query = $this->db->select('id')
			->get_where(
				'upload_prefs',
				array('site_id' => $site_id)
			);

		$upload_ids = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$upload_ids[] = $row['id'];
			}

			$this->db->where_in('upload_id', $upload_ids)
				->delete('upload_no_access');
		}

		// Delete Everything Having to Do with the Site
		$tables = array(
			'exp_categories',
			'exp_category_fields',
			'exp_category_field_data',
			'exp_category_groups',
			'exp_comments',
			'exp_cp_log',
			'exp_field_groups',
			'exp_file_dimensions',
			'exp_global_variables',
			'exp_html_buttons',
			'exp_member_groups',
			'exp_member_search',
			'exp_online_users',
			'exp_referrers',
			'exp_search',
			'exp_search_log',
			'exp_sites',
			'exp_snippets',
			'exp_specialty_templates',
			'exp_stats',
			'exp_statuses',
			'exp_status_groups',
			'exp_templates',
			'exp_template_groups',
			'exp_upload_prefs',
			'exp_channels',
			'exp_channel_data',
			'exp_channel_fields',
			'exp_channel_titles',
		);

		foreach($tables as $table)
		{
			if ($this->db->table_exists($table) === FALSE) continue;  // For a few modules that can be uninstalled

			$this->db->delete($table, array('site_id' => $site_id));
		}

		// Refresh Sites List
		$assigned_sites = array();

		if ($this->session->userdata['group_id'] == 1)
		{
			$result = $this->db->select('site_id, site_label')
				->order_by('site_label')
				->get('sites');
		}
		elseif ($this->session->userdata['assigned_sites'] != '')
		{
			$result = $this->db->query("SELECT site_id, site_label FROM exp_sites WHERE site_id IN (".$this->db->escape_str(explode('|', $this->session->userdata['assigned_sites'])).") ORDER BY site_label");
		}

		if (($this->session->userdata['group_id'] == 1 OR $this->session->userdata['assigned_sites'] != '') && $result->num_rows() > 0)
		{
			foreach ($result->result_array() as $row)
			{
				$assigned_sites[$row['site_id']] = $row['site_label'];
			}
		}

		$this->session->userdata['assigned_sites'] = $assigned_sites;
		$this->functions->redirect(BASE.AMP.'C=sites'.AMP.'M=manage_sites');
	}
}

/* End of file sites.php */
/* Location: ./system/expressionengine/controllers/cp/sites.php */
