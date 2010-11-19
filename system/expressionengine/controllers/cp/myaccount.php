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

// --------------------------------------------------------------------

/**
 * ExpressionEngine "My Account" Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class MyAccount extends CI_Controller {

	var $id			= '';
	var $username	= '';
	var $unique_dates = array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if (FALSE === ($this->id = $this->auth_id()))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// Load the language files
		$this->lang->loadfile('myaccount');
		$this->lang->loadfile('member');
		$this->load->model('member_model');

		// Fetch username/screen name
		$query = $this->member_model->get_member_data($this->id, array('username', 'screen_name'));

		if ($query->num_rows() == 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ($this->cp->allowed_group('can_edit_html_buttons'))
		{
			$this->javascript->output('
				$("#myaccountHtmlButtonsLink").show(); // JS only feature, its hidden by default
			');
		}

		$this->cp->set_variable('message', '');
		$this->cp->set_variable('id', $this->id);

		$this->username = ($query->row('screen_name')  == '') ? $query->row('username') : $query->row('screen_name');
		$this->cp->set_variable('member_username', $this->username);
	}

	// --------------------------------------------------------------------

	/**
	 * My Account main page
	 *
	 * @access	public
	 * @return	void
	 */
	function index()
	{
		$vars['cp_page_title'] = $this->lang->line('my_account');

		$this->javascript->output('');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$query = $this->member_model->get_member_data($this->id, array('email', 'ip_address', 'join_date', 'last_visit', 'total_entries', 'total_comments', 'last_entry_date', 'last_comment_date', 'last_forum_post_date', 'total_forum_topics', 'total_forum_posts'));

		if ($query->num_rows() > 0)
		{
			foreach ($query->row_array() as $key => $val)
			{
				$$key = $val;
			}

			$vars['username'] = $this->username;

			$vars['fields'] = array(
								'email'				=> mailto($email, $email),
								'join_date'			=> $this->localize->set_human_time($join_date),
								'last_visit'		=> ($last_visit == 0 OR $last_visit == '') ? '--' : $this->localize->set_human_time($last_visit),
								'total_entries'		=> $total_entries,
								'total_comments'	=> $total_comments,
								'last_entry_date'	=> ($last_entry_date == 0 OR $last_entry_date == '') ? '--' : $this->localize->set_human_time($last_entry_date),
								'last_comment_date' => ($last_comment_date == 0 OR $last_comment_date == '') ? '--' : $this->localize->set_human_time($last_comment_date),
								'user_ip_address'	=> $ip_address
							);

			if ($this->config->item('forum_is_installed') == "y")
			{
				$fields['last_forum_post_date'] = ($last_forum_post_date == 0) ? '--' : $this->localize->set_human_time($last_forum_post_date);
				$fields['total_forum_topics']	= $total_forum_topics;
				$fields['total_forum_replies']	= $total_forum_posts;
				$fields['total_forum_posts']	= $total_forum_posts + $total_forum_topics;
			}
		}

		$this->load->view('account/index', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Account Menu Setup
	 *
	 * This function handles the common items needed for all "My Account" page menus
	 *
	 * @access	private
	 * @return	array
	 */
	function _account_menu_setup()
	{
		//	Private Messaging
		$vars['private_messaging_menu'] = array();
		if ($this->id == $this->session->userdata['member_id'])
		{
			if ( ! class_exists('EE_Messages'))
			{
				require APPPATH.'libraries/Messages'.EXT;
			}

			$MESS = new EE_Messages;

			$vars['private_messaging_menu'] = $MESS->menu_array();
		}

		$vars['can_admin_members'] = $this->cp->allowed_group('can_admin_members');
		$vars['allow_localization'] = FALSE;
		$vars['login_as_member'] = FALSE;
		$vars['can_delete_members'] = FALSE;

		// member administration options
		if ($this->cp->allowed_group('can_admin_members'))
		{
			$vars['member_email'] = ($this->id != $this->session->userdata('member_id')) ? TRUE : FALSE;

			$vars['resend_activation_email'] = FALSE;

			if ($this->id != $this->session->userdata('member_id') &&	$this->config->item('req_mbr_activation') == 'email' && $this->cp->allowed_group('can_admin_members'))
			{
				$query = $this->member_model->get_member_data($this->id, array('group_id'));

				if ($query->row('group_id')	 == '4')
				{
					$this->lang->loadfile('members');
					$vars['resend_activation_email'] = TRUE;
				}
			}

			$vars['allow_localization'] = ($this->config->item('allow_member_localization') == 'y' OR $this->session->userdata('group_id') == 1) ? TRUE : FALSE;
			$vars['login_as_member'] = ($this->session->userdata('group_id') == 1 && $this->id != $this->session->userdata('member_id')) ? TRUE : FALSE;
			$vars['can_delete_members'] = ($this->cp->allowed_group('can_delete_members') AND $this->id != $this->session->userdata('member_id')) ? TRUE : FALSE;
		}
		
		return $vars;
	}

	// --------------------------------------------------------------------

	/**
	 * Auth ID
	 *
	 * Validate user and get the member ID number
	 *
	 * @access	public
	 * @return	mixed
	 */
	function auth_id()
	{
		// Who's profile are we editing?

		$id = ( ! $this->input->get_post('id')) ? $this->session->userdata('member_id') : $this->input->get_post('id');

		// Is the user authorized to edit the profile?

		if ($id != $this->session->userdata('member_id'))
		{
			if ( ! $this->cp->allowed_group('can_admin_members'))
			{
				return FALSE;
			}

			// Only Super Admins can view Super Admin profiles

			if ($id == 1 AND $this->session->userdata['group_id'] != 1)
			{
				return FALSE;
			}
		}

		if ( ! is_numeric($id))
		{
			return FALSE;
		}

		return $id;
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Profile Form
	 */
	function edit_profile($message = '')
	{
		$this->load->helper('form');
		$this->load->language('calendar');

		$vars['cp_page_title'] = $this->lang->line('edit_profile');

		$this->javascript->output('');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['screen_name']	= '';
		$vars['email']			= '';
		$vars['url']			= '';
		$vars['custom_profile_fields'] = array();

		// Fetch profile data
		$query = $this->member_model->get_member_data($this->id, array('url', 'location', 'occupation', 'interests', 'aol_im', 'yahoo_im', 'msn_im', 'icq', 'bio', 'bday_y', 'bday_m', 'bday_d'));

		foreach ($query->row_array() as $key => $val)
		{
			$vars[$key] = $val;
		}

		$vars['form_hidden']['id'] = $this->id;

		// Birthday Options
		$vars['bday_d_options'] = array();

		$vars['bday_y_options'][''] = $this->lang->line('year');
		
		for ($i = date('Y', $this->localize->now); $i > 1904; $i--)
		{
		  $vars['bday_y_options'][$i] = $i;
		}

		$vars['bday_m_options'] = array(
							''	 => $this->lang->line('month'),
							'01' => $this->lang->line('cal_january'),
							'02' => $this->lang->line('cal_february'),
							'03' => $this->lang->line('cal_march'),
							'04' => $this->lang->line('cal_april'),
							'05' => $this->lang->line('cal_mayl'),
							'06' => $this->lang->line('cal_june'),
							'07' => $this->lang->line('cal_july'),
							'08' => $this->lang->line('cal_august'),
							'09' => $this->lang->line('cal_september'),
							'10' => $this->lang->line('cal_october'),
							'11' => $this->lang->line('cal_november'),
							'12' => $this->lang->line('cal_december')
						);

		$vars['bday_d_options'][''] = $this->lang->line('day');
		
		for ($i = 1; $i <= 31; $i++)
		{
		  $vars['bday_d_options'][$i] = $i;
		}

		if ($vars['url'] == '')
		{
		  $vars['url'] = 'http://';
		}

		// Extended profile fields
		$query = $this->member_model->get_all_member_fields();

		if ($query->num_rows() > 0)
		{
			$this->load->helper('snippets');

			$result = $this->member_model->get_all_member_data($this->id);

			if ($result->num_rows() > 0)
			{
				foreach ($result->row_array() as $key => $val)
				{
					$$key = $val;
				}
			}

			$resrow = $result->result_array();

			$resrow = $resrow[0]; // @confirrm: end of a long, long, long stretch of work, but not sure why its returning into index 0...

			$vars['custom_profile_fields'] = array();

			foreach ($query->result_array() as $row)
			{
				$field_data = ( ! isset( $resrow['m_field_id_'.$row['m_field_id']])) ? '' :
										 $resrow['m_field_id_'.$row['m_field_id']];

				$required  = ($row['m_field_required'] == 'n') ? '' : required().NBS;

				if ($row['m_field_type'] == 'textarea') // Textarea fieled types
				{
					$rows = ( ! isset($row['m_field_ta_rows'])) ? '10' : $row['m_field_ta_rows'];

					$vars['custom_profile_fields'][] = form_label($required.$row['m_field_label'], 'm_field_id_'.$row['m_field_id']).form_textarea(array('name'=>'m_field_id_'.$row['m_field_id'], 'class'=>'field','id'=>'m_field_id_'.$row['m_field_id'], 'rows'=>$rows, 'value'=>$field_data));
				}
				elseif ($row['m_field_type'] == 'select') // Drop-down lists
				{
					$dropdown_options = array();
					foreach (explode("\n", trim($row['m_field_list_items'])) as $v)
					{
						$v = trim($v);
						$dropdown_options[$v] = $v;
					}

					$vars['custom_profile_fields'][] = form_label($required.$row['m_field_label'], 'm_field_id_'.$row['m_field_id']).form_dropdown('m_field_id_'.$row['m_field_id'], $dropdown_options, $field_data, 'id="m_field_id_'.$row['m_field_id'].'"');
				}
				elseif ($row['m_field_type'] == 'text') // Text input fields
				{
					$vars['custom_profile_fields'][] = form_label($required.$row['m_field_label'], 'm_field_id_'.$row['m_field_id']).form_input(array('name'=>'m_field_id_'.$row['m_field_id'], 'id'=>'m_field_id_'.$row['m_field_id'], 'class'=>'field', 'value'=>$field_data, 'maxlength'=>$row['m_field_maxl']));
				}
			}
		}

		$this->load->view('account/edit_profile', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Update member profile
	 */
	function update_profile()
	{
		// validate for unallowed blank values
		if (empty($_POST)) {
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$id = $_POST['id'];

		unset($_POST['id']);
		unset($_POST['edit_profile']);

		$_POST['url'] = ($_POST['url'] == 'http://') ? '' : $_POST['url'];
		
		$fields = array(	'bday_y',
							'bday_m',
							'bday_d',
							'url',
							'location',
							'occupation',
							'interests',
							'aol_im',
							'icq',
							'yahoo_im',
							'msn_im',
							'bio'
						);

		$data = array();

		foreach ($fields as $val)
		{
			if (isset($_POST[$val]))
			{
				$data[$val] = $_POST[$val];
			}

			unset($_POST[$val]);
		}

		if (is_numeric($data['bday_d']) && is_numeric($data['bday_m']))
		{
			$year = ($data['bday_y'] != '') ? $data['bday_y'] : date('Y');
			$mdays = $this->localize->fetch_days_in_month($data['bday_m'], $year);

			if ($data['bday_d'] > $mdays)
			{
				$data['bday_d'] = $mdays;
			}
		}
		
		if (count($data) > 0)
		{
			$this->member_model->update_member($this->id, $data);
		}

		if (count($_POST) > 0)
		{
			$this->member_model->update_member_data($this->id, $_POST);
		}

		if ($data['location'] != "" OR $data['url'] != "")
		{
			if ($this->db->table_exists('comments'))
			{
				$d = array(
						'location'	=> $data['location'],
						'url'		=> $data['url']
					);
				
				$this->db->where('author_id', $this->id);
				$this->db->update('comments', $d);
			}
		}
		
		$id = ($id == '') ? '' : AMP.'id='.$id;

		$this->session->set_flashdata('message_success', $this->lang->line('profile_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_profile'.$id);			
	}

	// --------------------------------------------------------------------

	/**
	  *	 Email preferences form
	  */
	function email_settings($message = '')
	{
		$this->load->helper(array('form', 'snippets'));

		$vars['cp_page_title'] = $this->lang->line('email_settings');

		$this->javascript->output('');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		// Fetch member data
		$query = $this->member_model->get_member_data($this->id, array('email', 'accept_admin_email', 'accept_user_email', 'notify_by_default', 'notify_of_pm', 'smart_notifications'));

		$vars['form_hidden']['id'] = $this->id;

		foreach ($query->row_array() as $key => $val)
		{
			$vars[$key] = $val;
		}

		$vars['checkboxes'] = array('accept_admin_email', 'accept_user_email', 'notify_by_default', 'notify_of_pm', 'smart_notifications');

		$this->load->view('account/email_settings', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  * Update Email Preferences
	  */
	function update_email()
	{
		// validate for unallowed blank values
		if (empty($_POST)) 
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// if this is a super admin changing stuff, don't worry
		// about this db call since it won't be used anyhow
		$current_email = '';
		if ($this->session->userdata('group_id') != 1)
		{
			// what's this users current email?
			$query = $this->member_model->get_member_data($this->id, array('email'));
			$current_email = $query->row('email');
		}

		//	Validate submitted data
		if ( ! class_exists('EE_Validate'))
		{
			require APPPATH.'libraries/Validate'.EXT;
		}

		$this->VAL = new EE_Validate(
								array(
										'member_id'			=> $this->id,
										'val_type'			=> 'update', // new or update
										'fetch_lang'		=> FALSE,
										'require_cpw'		=> ($current_email != $_POST['email']) ? TRUE :FALSE,
										'enable_log'		=> TRUE,
										'email'				=> $this->input->post('email'),
										'cur_email'			=> $current_email,
										'cur_password'		=> $this->input->post('password')
									 )
							);

		$this->VAL->validate_email();

		if (count($this->VAL->errors) > 0)
		{
			show_error($this->VAL->show_errors());
		}

		// Assign the query data
		$data = array(
						'email'				 =>	 $this->input->post('email'),
						'accept_admin_email'	=> (isset($_POST['accept_admin_email'])) ? 'y' : 'n',
						'accept_user_email'	 => (isset($_POST['accept_user_email']))  ? 'y' : 'n',
						'notify_by_default'	 => (isset($_POST['notify_by_default']))  ? 'y' : 'n',
						'notify_of_pm'			=> (isset($_POST['notify_of_pm']))	? 'y' : 'n',
						'smart_notifications'	=> (isset($_POST['smart_notifications']))  ? 'y' : 'n'
					  );

		$this->member_model->update_member($this->id, $data);

		$this->cp->get_installed_modules();
		
		if (isset($this->cp->installed_modules['comment']))
		{
			//	Update comments and log email change
			if ($current_email != $_POST['email'])
			{
				$this->db->where('author_id', $this->id);
				$this->db->update('comments', array('email' => $this->input->post('email')));

				$this->logger->log_action($this->VAL->log_msg);
			}			
		}

		$id = ($this->id != $this->session->userdata('member_id')) ? AMP.'id='.$this->id : '';

		$this->session->set_flashdata('message_success', $this->lang->line('settings_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=email_settings'.$id);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Edit preferences form
	  */
	function edit_preferences()
	{
		$this->load->helper(array('form', 'snippets'));

		$vars['cp_page_title'] = $this->lang->line('edit_preferences');

		$this->javascript->output('');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		// Fetch member data
		$query = $this->member_model->get_member_data($this->id, array('display_avatars', 'display_signatures', 'accept_messages', 'parse_smileys'));

		foreach ($query->row_array() as $key => $val)
		{
			$vars[$key] = $val;
		}

		$vars['form_hidden']['id'] = $this->id;

		$vars['checkboxes'] = array('accept_messages', 'display_avatars', 'display_signatures', 'parse_smileys');

		$this->load->view('account/edit_preferences', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Update	 Preferences
	  */
	function update_preferences()
	{
		// validate for unallowed blank values
		if (empty($_POST)) {
			show_error($this->lang->line('unauthorized_access'));
		}

		$data = array(
						'accept_messages'		=> (isset($_POST['accept_messages'])) ? 'y' : 'n',
						'display_avatars'		=> (isset($_POST['display_avatars'])) ? 'y' : 'n',
						'display_signatures'	=> (isset($_POST['display_signatures']))  ? 'y' : 'n',
						'parse_smileys'			=> (isset($_POST['parse_smileys']))  ? 'y' : 'n'
					  );

		$this->member_model->update_member($this->id, $data);

		$this->session->set_flashdata('message_success', $this->lang->line('settings_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_preferences'.AMP.'id='.$this->id);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Username/Password form
	  */
	function username_password($message = '')
	{
		$this->load->helper('form');

		$vars['cp_page_title'] = $this->lang->line('username_and_password');
		$vars['cp_messages'] = array($message);

		$this->javascript->output('');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		// Fetch member data
		$query = $this->member_model->get_member_data($this->id, array('username', 'screen_name'));

		$vars['form_hidden']['id'] = $this->id;

		$vars['username']	= $query->row('username');
		$vars['screen_name'] = $query->row('screen_name');

		$vars['allow_username_change'] = ($this->session->userdata['group_id'] != '1' AND $this->config->item('allow_username_change') == 'n') ? FALSE : TRUE;

		$this->load->view('account/username_password', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Update username and password
	  */
	function update_username_password()
	{
		if ($this->config->item('allow_username_change') != 'y' AND $this->session->userdata('group_id') != 1)
		{
			if ($_POST['current_password'] == '')
			{
				$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=username_password'.AMP.'id='.$this->id);
			}

			$_POST['username'] = $_POST['current_username'];
		}

		// validate for unallowed blank values
		if (empty($_POST)) 
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// If the screen name field is empty, we'll assign is from the username field.
		if ($_POST['screen_name'] == '')
		{
			$_POST['screen_name'] = $_POST['username'];
		}

		// Validate submitted data

		if ( ! class_exists('EE_Validate'))
		{
			require APPPATH.'libraries/Validate'.EXT;
		}

		// Fetch member data
		$query = $this->member_model->get_member_data($this->id, array('username', 'screen_name'));

		$this->VAL = new EE_Validate(
								array(
										'member_id'			=> $this->id,
										'val_type'			=> 'update', // new or update
										'fetch_lang'		=> FALSE,
										'require_cpw'		=> TRUE,
										'enable_log'		=> TRUE,
										'username'			=> $_POST['username'],
										'cur_username'		=> $query->row('username'),
										'screen_name'		=> $_POST['screen_name'],
										'cur_screen_name'	=> $query->row('screen_name'),
										'password'			=> $_POST['password'],
										'password_confirm'	=> $_POST['password_confirm'],
										'cur_password'		=> $this->input->post('current_password')
									 )
							);

		$this->VAL->validate_screen_name();

		if ($this->config->item('allow_username_change') == 'y' OR $this->session->userdata('group_id') == 1)
		{
			$this->VAL->validate_username();
		}

		if ($_POST['password'] != '')
		{
			$this->VAL->validate_password();
		}

		// Display errors if there are any

		if (count($this->VAL->errors) > 0)
		{
			show_error($this->VAL->show_errors());
		}

		// Update "last post" forum info if needed

		if ($query->row('screen_name') != $_POST['screen_name'] AND $this->config->item('forum_is_installed') == "y")
		{
			$this->db->where('forum_last_post_author_id', $this->id);
			$this->db->update('forums', array('forum_last_post_author' => 
												$this->input->post('screen_name')));
			
			$this->db->where('mod_member_id', $this->id);
			$this->db->update('forum_moderators', array('mod_member_name' => 
													$this->input->post('screen_name')));
		}

		// Assign the query data

		$data['screen_name'] = $_POST['screen_name'];

		if ($this->config->item('allow_username_change') == 'y' OR $this->session->userdata('group_id') == 1)
		{
			$data['username'] = $_POST['username'];
		}

		// Was a password submitted?

		$pw_change = FALSE;

		if ($_POST['password'] != '')
		{
			$this->load->helper('security');
			$data['password'] = do_hash($_POST['password']);

			if ($this->id == $this->session->userdata('member_id'))
			{
				$pw_change = TRUE;
			}
		}

		$this->member_model->update_member($this->id, $data);

		$this->cp->get_installed_modules();
		
		if (isset($this->cp->installed_modules['comment']))
		{
			if ($query->row('screen_name') != $_POST['screen_name'])
			{
				$query = $this->member_model->get_member_data($this->id, array('screen_name'));

				$screen_name = ($query->row('screen_name')	!= '') ? $query->row('screen_name')	 : '';

				// Update comments with current member data

				$data = array('name' => ($screen_name != '') ? $screen_name : $_POST['username']);

				$this->db->where('author_id', $this->id);
				$this->db->update('comments', $data);
			}			
		}
		
		// Write log file
		$this->logger->log_action($this->VAL->log_msg);

		$message = $this->lang->line('settings_updated');

		if ($pw_change)
		{
			$message .= BR.$this->lang->line('password_change_warning');

			$this->session->set_flashdata('message_success', $message);
			$this->functions->redirect(BASE.AMP.'C=login');
		}
		
		$this->session->set_flashdata('message_success', $message);
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=username_password'.AMP.'id='.$this->id);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Ping servers
	  */
	function ping_servers()
	{
		// Is the user authorized to access the publish page? And does the user have
		// at least one channel assigned? If not, show the no access message
		if ( ! $this->cp->allowed_group('can_access_publish') OR ! count($this->functions->fetch_assigned_channels()) > 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper('form');
		$this->load->library('table');
		$this->lang->loadfile('admin_content');
		$this->load->model('admin_model');

		$vars['cp_page_title'] = $this->lang->line('ping_servers');
		$vars['form_hidden'] = array();

		$ping_servers = $this->admin_model->get_ping_servers($this->id);

		// This user have any ping servers? If not, grab the defaults
		if ($ping_servers->num_rows() == 0)
		{
			$ping_servers = $this->admin_model->get_ping_servers(0);
		}

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
					update_ping_servers("false");
					return false;
				});

				$(".mainTable .tag_order input").hide();
				
				$(".mainTable tbody").sortable({
					axis:"y",
					containment:"parent",
					placeholder:"tablesize",
					update: function() {

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
					"'.str_replace('&amp;', '&', BASE).'&C=myaccount&M=save_ping_servers&refresh="+refresh,
					$("#ping_server_form").serializeArray(),
					function(res) {
						if ($(res).find("#ping_server_form").length > 0) {
							$("#ping_server_form").replaceWith($(res).find("#ping_server_form"));
							setup_js_page();

							$.ee_notice("'.$this->lang->line('preferences_updated').'");
						}
						else {
							res = eval(\'(\' + res + \')\');
							$.ee_notice(res.message);
						}

					});
			}

			setup_js_page();
		');

		$this->javascript->compile();

		$this->cp->add_to_head('<style type="text/css">.tablesize{height:45px!important;}</style>');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$this->load->view('account/ping_servers', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 *	Save ping servers
	 */
	function save_ping_servers()
	{
		// validate for unallowed blank values
		if (empty($_POST)) {
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->model('admin_model');

		$data = array();

		foreach ($_POST as $key => $val)
		{
			if (strncmp($key, 'server_name_', 12) == 0 && $val != '')
			{
				$n = substr($key, 12);

				$data[] = array(
								 'member_id'	 => $this->id,
								 'server_name'	=> $this->input->post('server_name_'.$n),
								 'server_url'	=> $this->input->post('server_url_'.$n),
								 'port'		  => $this->input->post('server_port_'.$n),
								 'ping_protocol' => $this->input->post('ping_protocol_'.$n),
								 'is_default'	=> $this->input->post('is_default_'.$n),
								 'server_order'	 => $this->input->post('server_order_'.$n),
								 'site_id'		 => $this->config->item('site_id')
								);
			}
		}

		if (count($_POST) > 0)
		{
			$this->admin_model->update_ping_servers($this->id, $data);
		}

		if ($this->input->get_post('refresh') == "true")
		{
			// Ajax refresh - only show the minimal view
			$this->load->vars(array('EE_view_disable' => TRUE));
			$this->ping_servers();
		}
		else
		{
			$this->session->set_flashdata('message_success', $this->lang->line('preferences_updated'));
			$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=ping_servers');
		}
	}

	// --------------------------------------------------------------------

	/**
	  *	 HTML buttons
	  */
	function html_buttons($message = '')
	{
		// Is the user authorized to access the publish page? And does the user have
		// at least one channel assigned? If not, show the no access message
		if ( ! $this->cp->allowed_group('can_access_publish') OR
			 ! $this->cp->allowed_group('can_edit_html_buttons'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->load->helper(array('form', 'url'));
		$this->load->library('table');
		$this->lang->loadfile('admin');
		$this->lang->loadfile('admin_content');

		$this->load->model('admin_model');

		$vars['cp_page_title'] = $this->lang->line('html_buttons');
		$vars['form_hidden'] = array(
								'button_submit'	=>	TRUE,
								'id'			=>	$this->id);

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['cp_messages'] = array($message);
		
		$this->cp->add_js_script(array('file' => 'cp/account_html_buttons'));
		$this->javascript->compile();

		$this->cp->add_to_head('<style type="text/css">.cp_button{display:none;}</style>');

		// load the systems's predefined buttons
		include_once(APPPATH.'config/html_buttons.php');
		$vars['predefined_buttons'] = $predefined_buttons;

		// any predefined buttons?
		$button = $this->input->get_post('button');

		$html_buttons = $this->admin_model->get_html_buttons($this->id, FALSE); // don't include defaults on this request
		$button_count = $html_buttons->num_rows();

		if ($button != '')
		{
			// If we're here it means a link was followed. Since this means the $_POST won't be included
			// with existing "pre defined" buttons, we need to check of the user has any buttons yet, and
			// include the defaults if they do not

			if ($button_count == 0)
			{
				$buttons = $this->admin_model->get_html_buttons();
				
				foreach ($buttons->result_array() as $data)
				{
					unset($data['id']); // unsetting from default id for insertion
					$data['member_id'] = $this->id; // override member id from default to this user for insertion
					$this->admin_model->update_html_buttons($this->id, array($data), FALSE);
				}
			}

			// all buttons also share these settings
			$predefined_buttons[$button] = array(
						'member_id'		=> $this->id,
						'site_id'		=> $this->config->item('site_id'),
						'tag_name'		=> stripslashes($predefined_buttons[$button]['tag_name']),
						'tag_open'		=> stripslashes($predefined_buttons[$button]['tag_open']),
						'tag_close'		=> stripslashes($predefined_buttons[$button]['tag_close']),
						'accesskey'		=> stripslashes($predefined_buttons[$button]['accesskey']),
						'tag_order'		=> $button_count++,
						'tag_row'		=> 1,
						'classname'		=> stripslashes($predefined_buttons[$button]['classname']),
				);
			
			$this->admin_model->update_html_buttons($this->id, array($predefined_buttons[$button]), FALSE);

			$id = ($this->input->get('id')) ? AMP.'id='.$this->input->get('id') : '';
			
			// Redirect to remove the button name from the query string.  Reloading the page can lead to
			// adding buttons you don't want, and that's just ugliness.  
			$this->session->set_flashdata('message_success', $this->lang->line('html_buttons_updated'));
			$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=html_buttons'.$id);
		}
		elseif (is_numeric($this->id) AND $this->id != 0 AND $this->input->post('button_submit') != '')
		{
			$data = array();
			foreach ($_POST as $key => $val)
			{
				if (strncmp($key, 'tag_name_', 9) == 0 && $val != '')
				{
					$n = substr($key, 9);

					$data[] = array(
									'member_id' => $this->id,
									'tag_name'	=> $this->input->post('tag_name_'.$n),
									'tag_open'	=> $this->input->post('tag_open_'.$n),
									'tag_close' => $this->input->post('tag_close_'.$n),
									'accesskey' => $this->input->post('accesskey_'.$n),
									'tag_order' => ($this->input->post('tag_order_'.$n) != '') ? $this->input->post('tag_order_'.$n) : $button_count++,
									'tag_row'	=> 1, // $_POST['tag_row_'.$n],
									'site_id'	 => $this->config->item('site_id'),
									'classname'	 => "btn_".str_replace(array(' ', '<', '>', '[', ']', ':', '-', '"', "'"), '', $this->input->post('tag_name_'.$n))
									);
				}
			}

			$this->admin_model->update_html_buttons($this->id, $data);
		}

		$vars['html_buttons'] = $this->admin_model->get_html_buttons($this->id);
		$button_count = $vars['html_buttons']->num_rows();

		if ($button_count == 0)
		{
			// user doesn't have any, let's grab the default buttons (user 0 in html_buttons)
			$vars['html_buttons'] = $this->admin_model->get_html_buttons(0);
		}

		$vars['i'] = 1;

		$this->load->view('account/html_buttons', $vars);
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
		// validate for unallowed blank values
		if ( ! $this->input->get_post('button_id') OR 
			 ! $this->cp->allowed_group('can_edit_html_buttons')) 
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->model('admin_model');
		$this->admin_model->delete_html_button($this->input->get_post('button_id'));
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
		// validate for unallowed blank values
		if (empty($_POST) OR ! $this->cp->allowed_group('can_edit_html_buttons'))
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
	  * Theme builder
	  *
	  * OK, well, the title is misleading.	Eventually, this will be a full-on
	  * theme builder.	Right now it just lets users choose from among pre-defined CSS files
	  */
	function cp_theme()
	{
		$this->load->helper(array('form', 'date'));
		$this->load->model('admin_model');

		$vars['cp_page_title'] = $this->lang->line('cp_theme');
		$this->cp->add_to_head('<meta http-equiv="pragma" content="no-cache">');

		$this->javascript->output('');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden']['id'] = $this->id;

		$vars['cp_theme'] = ($this->session->userdata['cp_theme'] == '') ? $this->config->item('cp_theme') : $this->session->userdata['cp_theme'];

		$vars['cp_theme_options'] =	 $this->admin_model->get_cp_theme_list();

		$this->load->view('account/cp_theme', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Save Theme
	  */
	function save_theme()
	{
		// validate for unallowed blank values
		if (empty($_POST)) 
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->member_model->update_member($this->id, array('cp_theme'=> $this->input->post('cp_theme')));

		$this->session->set_flashdata('message_success', $this->lang->line('preferences_updated'));

		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=cp_theme'.AMP.'id='.$this->id);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Subscriptions
	  */
	function subscriptions($message = '')
	{
		$this->load->helper(array('form', 'snippets', 'url', 'string'));
		$this->load->library('table');
		$this->load->library('pagination');
		$this->load->library('members');
		$this->cp->get_installed_modules();

		$vars['cp_page_title'] = $this->lang->line('subscriptions');
		$vars['cp_messages'] = array($message);

		$this->jquery->tablesorter('.mainTable', '{
			headers: {3: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->javascript->output('
			$(".toggle_all").toggle(
				function(){
					$("input.toggle").each(function() {
						this.checked = true;
					});
				}, function (){
					var checked_status = this.checked;
					$("input.toggle").each(function() {
						this.checked = false;
					});
				}
			);
		');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		// Set some base values
		$vars['subscriptions']	= array();
		$total_count			= 0;
		$perpage				= 50;
		$rownum					= ($this->input->get_post('per_page') == '') ? 0 : $this->input->get_post('per_page');


		$vars['form_hidden']['id'] = $this->id;

		$query = $this->member_model->get_member_data($this->id, array('email'), $perpage);

		if ($query->num_rows() != 1)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$email = $query->row('email') ;

		$subscription_data = $this->members->get_member_subscriptions($this->id, $rownum);

		$vars['subscriptions'] = $subscription_data['result_array'];

		// Pagination stuff
		$config['base_url'] = BASE.AMP.'C=myaccount'.AMP.'M=subscriptions';
		$config['total_rows'] = $subscription_data['total_results'];
		$config['per_page'] = $perpage;
		$config['page_query_string'] = TRUE;
		$config['full_tag_open'] = '<p id="paginationLinks">';
		$config['full_tag_close'] = '</p>';
		$config['prev_link'] = '<img src="'.$this->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$config['next_link'] = '<img src="'.$this->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$config['first_link'] = '<img src="'.$this->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$config['last_link'] = '<img src="'.$this->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';

		$this->pagination->initialize($config);
		$vars['pagination'] = $this->pagination->create_links();

		$this->load->view('account/subscriptions', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Unsubscribe to subscriptions
	  */
	function unsubscribe()
	{
		if ( ! $this->input->post('toggle'))
		{
			$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=subscriptions'.AMP.'id='.$this->id);
		}

		$query = $this->member_model->get_member_data($this->id, array('email'));

		if ($query->num_rows() != 1)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// validate for unallowed blank values
		if (empty($_POST)) {
			show_error($this->lang->line('unauthorized_access'));
		}

		$email = $query->row('email');
		
		$this->load->library('subscription');

		foreach ($_POST['toggle'] as $key => $val)
		{
			switch (substr($val, 0, 1))
			{
				case "b"	: 	$this->subscription->init('comment', array('entry_id' => substr($val, 1)), TRUE);
								$this->subscription->unsubscribe($this->id);
					break;
				case "f"	: $this->db->delete('forum_subscriptions', array('topic_id' => substr($val, 1))); 
					break;
			}
		}

		$this->subscriptions($this->lang->line('subscriptions_removed'));
	}

	// --------------------------------------------------------------------

	/**
	  *	 Localization settings
	  */
	function localization($message = '')
	{
		if ($this->config->item('allow_member_localization') == 'n' AND $this->session->userdata('group_id') != 1)
		{
			show_error($this->lang->line('localization_disallowed'));
		}

		$this->load->helper(array('form', 'date'));
		$this->load->model('language_model');

		$vars['cp_page_title'] = $this->lang->line('localization_settings');

		if ($this->input->get_post('U'))
		{
			$vars['message'] = $this->lang->line('localization_updated');
		}

		$this->javascript->output('');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden']['id'] = $this->id;

		$fields = array('timezone', 'daylight_savings', 'language', 'time_format');
		
		// Fetch profile data
		$query = $this->member_model->get_member_data($this->id, $fields);
		
		foreach ($fields as $val)
		{
			{
				$vars[$val] = $query->row($val);
			}
		}

		if ($vars['timezone'] == '')
		{
			$vars['timezone'] = ($this->config->item('default_site_timezone') && $this->config->item('default_site_timezone') != '') ? $this->config->item('default_site_timezone') : 'UTC';
		}
		
		if ($vars['time_format'] == '')
		{
			$vars['time_format'] = ($this->config->item('time_format') && $this->config->item('time_format') != '') ? $this->config->item('time_format') : 'us';
		}		

		$vars['time_format_options']['us'] = $this->lang->line('united_states');
		$vars['time_format_options']['eu'] = $this->lang->line('european');
		
		$vars['daylight_savings_y'] = ($vars['daylight_savings'] == 'y') ? TRUE : FALSE;
		$vars['daylight_savings_n'] = ($vars['daylight_savings'] == 'y') ? FALSE : TRUE;

		if ($vars['language'] == '')
		{
			$vars['language'] = ($this->config->item('deft_lang') && $this->config->item('deft_lang') != '') ? $this->config->item('deft_lang') : 'english';
		}

		$vars['language_options'] = $this->language_model->language_pack_names();

		$this->load->view('account/localization', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Localization update
	  */
	function localization_update()
	{
		if ($this->config->item('allow_member_localization') == 'n' AND $this->session->userdata('group_id') != 1)
		{
			show_error($this->lang->line('localization_disallowed'));
		}

		// validate for unallowed blank values
		if (empty($_POST)) {
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->model('site_model');
		$this->load->library('security');

		$data['language']	= $this->security->sanitize_filename($this->input->post('language'));
		$data['timezone']	= $this->input->post('timezones');
		$data['time_format'] = $this->input->post('time_format');
		$data['daylight_savings'] = ($this->input->post('daylight_savings') == 'y') ? 'y' : 'n';

		if ( ! is_dir(APPPATH.'language/'.$data['language']))
		{
			show_error($this->lang->line('localization_disallowed'));
		}

		$this->member_model->update_member($this->id, $data);

		$config = $this->member_model->get_localization_default(TRUE);

		//	Update Config Values
		if ($config['member_id'] == $this->id)
		{
			unset($config['member_id']);
			$query = $this->site_model->get_site_system_preferences($this->config->item('site_id'));

			$prefs = unserialize(base64_decode($query->row('site_system_preferences')));

			foreach($config as $key => $value)
			{
				$prefs[$key] = $value;
			}

			$this->site_model->update_site_system_preferences($prefs, $this->config->item('site_id'));
		}

		$this->session->set_flashdata('message_success', $this->lang->line('settings_updated'));


		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=localization'.AMP.'id='.$this->id.AMP.'U=1');
	}

	// --------------------------------------------------------------------

	/**
	  * Edit Signature Form
	  */
	function edit_signature()
	{
		$this->load->helper('form');

		$vars['cp_page_title'] = $this->lang->line('edit_signature');

		$this->javascript->output('');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden']['id'] = $this->id;

		$query = $this->member_model->get_member_data($this->id, array('signature', 'sig_img_filename', 'sig_img_width', 'sig_img_height'));

		$vars['signature'] = $query->row('signature');

		$vars['sig_image'] = FALSE;
		$vars['sig_image_remove'] = FALSE;

		if ($this->config->item('sig_allow_img_upload') == 'y')
		{
			$max_kb = ($this->config->item('sig_img_max_kb') == '' OR $this->config->item('sig_img_max_kb') == 0) ? 50 : $this->config->item('sig_img_max_kb');
			$max_w	= ($this->config->item('sig_img_max_width') == '' OR $this->config->item('sig_img_max_width') == 0) ? 100 : $this->config->item('sig_img_max_width');
			$max_h	= ($this->config->item('sig_img_max_height') == '' OR $this->config->item('sig_img_max_height') == 0) ? 100 : $this->config->item('sig_img_max_height');
			$vars['max_size'] = str_replace('%x', $max_w, $this->lang->line('max_image_size'));
			$vars['max_size'] = str_replace('%y', $max_h, $vars['max_size']);
			$vars['max_size'] .= ' - '.$max_kb.'KB';

			$vars['sig_image'] = TRUE;

			if ($query->row('sig_img_filename')	 == '')
			{
				$vars['sig_img_filename'] = $this->lang->line('no_image_exists');
			}
			else
			{
				$vars['sig_image_remove'] = TRUE;
				$vars['sig_img_filename'] = '<img src="'.$this->config->slash_item('sig_img_url').$query->row('sig_img_filename') .'" border="0" width="'.$query->row('sig_img_width') .'" height="'.$query->row('sig_img_height') .'" title="'.$this->lang->line('signature_image').'"	 alt="'.$this->lang->line('signature_image').'" />';
			}
		}

		$this->load->view('account/edit_signature', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  * Update signature
	  */
	function update_signature()
	{
		$signature = $this->input->post('signature');

		$maxlength = ($this->config->item('sig_maxlength') == 0) ? 10000 : $this->config->item('sig_maxlength');

		if (strlen($signature) > $maxlength)
		{
			show_error(str_replace('%x', $maxlength, $this->lang->line('sig_too_big')));
		}

		$this->member_model->update_member($this->id, array('signature' => $signature));

		// Is there an image to upload or remove?
		if ((isset($_FILES['userfile']) AND $_FILES['userfile']['name'] != '') OR isset($_POST['remove']))
		{
			return $this->upload_signature_image();
		}
		
		$id = ($this->input->get_post('id')) ? AMP.'id='.$this->input->get_post('id') : '';

		$this->session->set_flashdata('message_success', $this->lang->line('signature_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_signature'.$id);
	}

	// --------------------------------------------------------------------

	/**
	  * Edit Avatar Form
	  */
	function edit_avatar()
	{
		// Are avatars enabled?
		if ($this->config->item('enable_avatars') == 'n')
		{
			show_error($this->lang->line('avatars_not_enabled'));
		}

		$this->load->helper('form');
		$this->load->language('number');

		$vars['cp_page_title'] = $this->lang->line('edit_avatar');

		$this->javascript->output('');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden']['id'] = $this->id;
		
		// Are we a superadmin & looking at our profile, or another users?
		if ($this->id != $this->session->userdata('member_id'))
		{
			$member_avatar = $this->member_model->get_member_data($this->id, array('avatar_filename', 'avatar_width', 'avatar_height', 'screen_name'));
			
			if ($member_avatar->row('avatar_filename') == '')
			{
				// there ain't no avatar
				$cur_avatar_url = '';
				$avatar_width	= '';
				$avatar_height	= '';
				$vars['avatar'] = sprintf(
							$this->lang->line('no_user_avatar'),
							$member_avatar->row('screen_name')
					);				
				
			}
			else
			{
				// ain't ain't a word
				$cur_avatar_url = ($member_avatar->row('avatar_filename') != '') ? $this->config->slash_item('avatar_url').$member_avatar->row('avatar_filename') : '';
				$avatar_width   = $member_avatar->row('avatar_filename') ? $member_avatar->row('avatar_width') : '';
				$avatar_height  = $member_avatar->row('avatar_filename') ? $member_avatar->row('avatar_height') : '';
				$vars['avatar'] = '<img src="'.$cur_avatar_url.'" border="0" width="'.$avatar_width.'" height="'.$avatar_height.'" alt="'.$this->lang->line('my_avatar').'" title="'.$this->lang->line('my_avatar').'" />';
			}
		}
		else
		{
			// We already grab this data for the sidebar, so we'll use those values
			if ( ! $this->load->_ci_cached_vars['cp_avatar_path'])
			{
				$cur_avatar_url = '';
				$avatar_width	= '';
				$avatar_height	= '';
				$vars['avatar'] = $this->lang->line('no_avatar');
			}
			else
			{
				$cur_avatar_url = $this->load->_ci_cached_vars['cp_avatar_path'];
				$avatar_width	= $this->load->_ci_cached_vars['cp_avatar_width'];
				$avatar_height	= $this->load->_ci_cached_vars['cp_avatar_height'];
				$vars['avatar'] = '<img src="'.$cur_avatar_url.'" border="0" width="'.$avatar_width.'" height="'.$avatar_height.'" alt="'.$this->lang->line('my_avatar').'" title="'.$this->lang->line('my_avatar').'" />';
			}			
		}

		// Are there pre-installed avatars? We'll make a list of all folders in the "avatar" folder,
		// then check each one to see if they contain images.  If so we will add it to the list

		$vars['i'] = 0;

		$this->load->helper('directory');
		
		$vars['avatar_dirs'] = directory_map($this->config->slash_item('avatar_path'), 2);
		
		if (is_array($vars['avatar_dirs']))
		{
			$vars['avatar_dirs'] = array_filter($vars['avatar_dirs'], 'is_array');	// only grab subfolders
			unset($vars['avatar_dirs']['uploads']); // remove user uploaded avatars
		}
		else
		{
			$vars['avatar_dirs'] = array();
		}

		// Set the default image meta values

		$max_kb = ($this->config->item('avatar_max_kb') == '' OR $this->config->item('avatar_max_kb') == 0) ? 50 : $this->config->item('avatar_max_kb');
		$max_w	= ($this->config->item('avatar_max_width') == '' OR $this->config->item('avatar_max_width') == 0) ? 100 : $this->config->item('avatar_max_width');
		$max_h	= ($this->config->item('avatar_max_height') == '' OR $this->config->item('avatar_max_height') == 0) ? 100 : $this->config->item('avatar_max_height');
		$vars['max_size'] = str_replace('%x', $max_w, $this->lang->line('max_image_size'));
		$vars['max_size'] = str_replace('%y', $max_h, $vars['max_size']);
		$vars['max_size'] .= ' - '.$max_kb.$this->lang->line('kilobyte_abbr');

		$vars['avatar_image_remove'] = ($this->config->item('allow_avatar_uploads') == 'y' AND $cur_avatar_url != '') ? TRUE : FALSE;

		$this->load->view('account/edit_avatar', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  * Edit Photo Form
	  */
	function edit_photo($message = '')
	{
		// Are avatars enabled?
		if ($this->config->item('enable_photos') == 'n')
		{
			show_error($this->lang->line('photos_not_enabled'));
		}

		$this->load->helper('form');
		$this->load->language('number');

		$vars['cp_page_title'] = $this->lang->line('edit_photo');
		$vars['cp_messages'] = array($message);

		$this->javascript->output('');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden']['id'] = $this->id;

		// Fetch photo data
		$query = $this->member_model->get_member_data($this->id, array('photo_filename', 'photo_width', 'photo_height'));

		if ($query->row('photo_filename')  == '')
		{
			$cur_photo_url = '';
			$photo_width	= '';
			$photo_height	= '';
			$vars['photo'] = $this->lang->line('no_photo_exists');
		}
		else
		{
			$cur_photo_url = $this->config->slash_item('photo_url').$query->row('photo_filename') ;
			$photo_width	= $query->row('photo_width') ;
			$photo_height	= $query->row('photo_height') ;
			$vars['photo'] = '<img src="'.$cur_photo_url.'" border="0" width="'.$photo_width.'" height="'.$photo_height.'" alt="'.$this->lang->line('my_photo').'" title="'.$this->lang->line('my_photo').'" />';
		}

		// Set the default image meta values

		$max_kb = ($this->config->item('photo_max_kb') == '' OR $this->config->item('photo_max_kb') == 0) ? 50 : $this->config->item('photo_max_kb');
		$max_w	= ($this->config->item('photo_max_width') == '' OR $this->config->item('photo_max_width') == 0) ? 100 : $this->config->item('photo_max_width');
		$max_h	= ($this->config->item('photo_max_height') == '' OR $this->config->item('photo_max_height') == 0) ? 100 : $this->config->item('photo_max_height');
		$vars['max_size'] = str_replace('%x', $max_w, $this->lang->line('max_image_size'));
		$vars['max_size'] = str_replace('%y', $max_h, $vars['max_size']);
		$vars['max_size'] .= ' - '.$max_kb.$this->lang->line('kilobyte_abbr');;

		$vars['remove_photo'] = ($cur_photo_url != '') ? TRUE : FALSE;

		$this->load->view('account/edit_photo', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  * Browse Avatars
	  */
	function browse_avatars()
	{
		// Are avatars enabled?
		if ($this->config->item('enable_avatars') == 'n')
		{
			show_error($this->lang->line('avatars_not_enabled'));
		}

		$this->load->helper('form');
		$this->load->library('table');
		$this->load->library('pagination');
		$this->load->library('security');

		$vars['cp_page_title'] = $this->lang->line('browse_avatars');

		$vars['form_hidden']['id'] = $this->id;
		$vars['form_hidden']['folder'] = $this->input->get_post('folder');
		$vars['pagination'] = '';

		$this->javascript->output('
			$(".browseAvatar img").css("cursor", "pointer");

			$("input:radio").css("visibility", "hidden");

			$("input.submit").hide();

			$(".browseAvatar img").click(function() {
				var checkid = $(this).attr("alt");
				document.getElementById(checkid).checked = true;
				$("#browse_avatar_form").submit();
			});
		');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		// Define the paths

		$avatar_path 	= $this->config->slash_item('avatar_path').$this->security->sanitize_filename($this->input->get_post('folder')).'/';
		$avatar_url 	= $this->config->slash_item('avatar_url').$this->security->sanitize_filename($this->input->get_post('folder')).'/';

		$avatars = $this->_get_avatars($avatar_path);

		$total_count = count($avatars);

		// Did we succeed?

		if (count($avatars) == 0)
		{
			show_error($this->lang->line('avatars_not_found'));
		}

		// Pagination stuff
		$config['base_url'] = BASE.AMP.'C=myaccount'.AMP.'M=browse_avatars'.AMP.'id='.$this->id.AMP.'folder='.$this->input->get_post('folder');
		$config['total_rows'] = $total_count;
		$config['per_page'] = 9;
		$config['page_query_string'] = TRUE;
		$config['full_tag_open'] = '<p id="paginationLinks">';
		$config['full_tag_close'] = '</p>';
		$config['prev_link'] = '<img src="'.$this->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$config['next_link'] = '<img src="'.$this->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$config['first_link'] = '<img src="'.$this->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$config['last_link'] = '<img src="'.$this->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';

		$this->pagination->initialize($config);
		$vars['pagination'] = $this->pagination->create_links();

		$vars['avatars'] = array();
		$offset = ($this->input->get('per_page') != '') ? $this->input->get('per_page') : 0;
		$avatars = array_slice($avatars, $offset , 9);

		foreach ($avatars as $image)
		{
			$vars['avatars'][] = "<img src='".$avatar_url.$image."' alt='".$image."' border='0' /><br /><input id='".$image."' type='radio' name='avatar' value='".$image."' />";
		}

		$this->table->set_template(array('table_open' => '<table class="browseAvatar">'));

		$this->load->view('account/browse_avatars', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Select Avatar From	 Library
	  */
	function select_avatar()
	{
		// Are avatars enabled?
		if ($this->config->item('enable_avatars') == 'n')
		{
			show_error($this->lang->line('avatars_not_enabled'));
		}

		if ($this->input->get_post('avatar') === FALSE OR $this->input->get_post('folder') === FALSE)
		{
			return $this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=browse_avatars'.AMP.'folder='.$this->input->get_post('folder'));
		}

		$this->load->library('security');
		
		$folder = $this->security->sanitize_filename($this->input->get_post('folder'));
		$file	= $this->security->sanitize_filename($this->input->get_post('avatar'));

		$basepath	= $this->config->slash_item('avatar_path');
		$avatar		= $avatar	= $folder.'/'.$file;

		$allowed = $this->_get_avatars($basepath.$folder);

		if ( ! in_array($file, $allowed) OR $folder == 'upload')
		{
			show_error($this->lang->line('avatars_not_found'));
		}

		// Fetch the avatar meta-data

		if ( ! function_exists('getimagesize'))
		{
			show_error($this->lang->line('image_assignment_error'));
		}

		$vals = @getimagesize($basepath.$avatar);
		$width	= $vals['0'];
		$height = $vals['1'];

		$this->member_model->update_member($this->id, array('avatar_filename' => $avatar, 'avatar_width' => $width, 'avatar_height' => $height));

		$id = ($this->input->get_post('id')) ? AMP.'id='.$this->input->get_post('id') : '';

		$this->session->set_flashdata('message_success', $this->lang->line('avatar_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_avatar'.$id);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Get all Avatars from a Folder
	  */
	function _get_avatars($avatar_path)
	{
		// Is this a valid avatar folder?

		$extensions = array('.gif', '.jpg', '.jpeg', '.png');

		if ( ! @is_dir($avatar_path) OR ! $fp = @opendir($avatar_path))
		{
			return array();
		}

		// Grab the image names

		$avatars = array();

		while (FALSE !== ($file = readdir($fp)))
		{
			if (FALSE !== ($pos = strpos($file, '.')))
			{
				if (in_array(substr($file, $pos), $extensions))
				{
					$avatars[] = $file;
				}
			}
		}

		closedir($fp);

		return $avatars;
	}

	// --------------------------------------------------------------------

	/**
	  *	 Upload Avatar
	  */
	function upload_avatar()
	{
		return $this->_upload_image('avatar');
	}

	// --------------------------------------------------------------------

	/**
	  *	 Upload Profile Photo
	  */
	function upload_photo()
	{
		return $this->_upload_image('photo');
	}

	// --------------------------------------------------------------------

	/**
	  *	 Upload Signature
	  */
	function upload_signature_image()
	{
		return $this->_upload_image('sig_img');
	}

	// --------------------------------------------------------------------

	/**
	 * Upload Image
	 *
	 * @access private
	 * @param string	Type of member image to upload
	 * @return void
	 */
	function _upload_image($type = 'avatar')
	{
		$this->load->library('members');
		$upload = $this->members->upload_member_images($type, $this->id);

		if (is_array($upload))
		{
			switch ($upload[0])
			{
				case 'success':
					$edit_image = $upload[1];
					$updated = $upload[2];
					break;
				case 'page':
					$args = (isset($upload[2])) ? $upload[2] : array();
					return call_user_func_array(array($this, $upload[1]), $args);
			}			
		}

		// Success message
		$this->session->set_flashdata('message_success', $this->lang->line($updated));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M='.$edit_image.AMP.'id='.$this->id);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Update notepad
	  */
	function notepad_update()
	{
		// validate for unallowed blank values
		if (empty($_POST)) 
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->member_model->update_member($this->session->userdata('member_id'), array('notepad'=>$this->input->get_post('notepad')));
		
		$this->session->set_flashdata('notepad_message', $this->lang->line('mbr_notepad_updated'));
		$this->functions->redirect(BASE.AMP.$this->input->post('redirect_to'));
	}

	// --------------------------------------------------------------------

	/**
	  *	 Administrative options
	  */
	function member_preferences()
	{
		if ( ! $this->cp->allowed_group('can_admin_members'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper('form');
		$vars['cp_page_title'] = $this->lang->line('administrative_options');

		$this->javascript->output('');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$query = $this->member_model->get_member_data($this->id, array('ip_address', 'in_authorlist', 'group_id', 'localization_is_site_default'));

		foreach ($query->row_array() as $key => $val)
		{
			$vars[$key] = $val;
		}

		$vars['form_hidden']['id'] = $this->id;

		// Member groups assignment

		if ($this->cp->allowed_group('can_admin_mbr_groups'))
		{
			if ($this->session->userdata['group_id'] != 1)
			{
				$query = $this->member_model->get_member_groups('', array('is_locked'=>'n'));
			}
			else
			{
				$query = $this->member_model->get_member_groups();
			}

			$vars['group_id_options'] = array();

			if ($query->num_rows() > 0)
			{
				foreach ($query->result() as $row)
				{
					// If the current user is not a Super Admin
					// we'll limit the member groups in the list

					if ($this->session->userdata['group_id'] != 1)
					{
						if ($row->group_id == 1)
						{
							continue;
						}
					}

					$vars['group_id_options'][$row->group_id] = $row->group_title;
				}
			}
		}

		$this->load->view('account/member_preferences', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Update Member Preferences options
	  */
	function member_preferences_update()
	{
		if ( ! $this->cp->allowed_group('can_admin_members'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// validate for unallowed blank values
		if (empty($_POST)) {
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->model('site_model');

		$data['in_authorlist'] = ($this->input->post('in_authorlist') == 'y') ? 'y' : 'n';
		$data['localization_is_site_default'] = ($this->input->post('localization_is_site_default') == 'y') ? 'y' : 'n';

		if ($this->input->post('group_id'))
		{
			if ( ! $this->cp->allowed_group('can_admin_mbr_groups'))
			{
				show_error($this->lang->line('unauthorized_access'));
			}

			$data['group_id'] = $this->input->post('group_id');

			if ($_POST['group_id'] == '1')
			{
				if ($this->session->userdata['group_id'] != '1')
				{
					show_error($this->lang->line('unauthorized_access'));
				}
			}
			else
			{
				if ($this->session->userdata('member_id') == $this->id)
				{
					show_error($this->lang->line('super_admin_demotion_alert'));
				}
			}
		}

		// If this member is set to be the default localization, wipe 'em all
		if ($data['localization_is_site_default'] == 'y') 
		{
			$this->db->where('localization_is_site_default', 'y');
			$this->db->update('members', array('localization_is_site_default' => 'n'));
		}
		
		$this->member_model->update_member($this->id, $data);

		$config = $this->member_model->get_localization_default();

		//	Update Config Values

		$query = $this->site_model->get_site_system_preferences($this->config->item('site_id'));

		$prefs = unserialize(base64_decode($query->row('site_system_preferences')));

		foreach($config as $key => $value)
		{
			$prefs[$key] = $value;
		}

		$this->site_model->update_site_system_preferences($prefs, $this->config->item('site_id'));

		$this->session->set_flashdata('message_success', $this->lang->line('administrative_options_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=member_preferences'.AMP.'id='.$this->id);
	}

	// --------------------------------------------------------------------

	/** 
	  * Quick links
	  */
	function quicklinks($message = '')
	{
		if ($this->session->userdata['group_id'] != 1 AND ($this->id != $this->session->userdata('member_id')))
		{
			show_error($this->lang->line('only_self_qucklink_access'));
		}

		$this->load->library('table');
		$this->load->helper('form');

		$vars['cp_page_title'] = $this->lang->line('quicklinks_manager');
		$vars['cp_messages'] = array($message);
		$vars['form_hidden']['id'] = $this->id;

		$this->jquery->tablesorter('.mainTable', '{widgets: ["zebra"]}');

		$this->javascript->output('');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		if ($this->input->get('U'))
		{
			$vars['message'] = $this->lang->line('quicklinks_updated');
		}

		$vars['quicklinks'] = $this->member_model->get_member_quicklinks($this->id);

		$vars['blank_count'] = count($vars['quicklinks'])+1;

		$this->load->view('account/quicklinks', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Save quick links
	  */
	function quicklinks_update()
	{
		if ($this->session->userdata['group_id'] != 1 AND ($this->id != $this->session->userdata('member_id')))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// validate for unallowed blank values
		if (empty($_POST)) {
			show_error($this->lang->line('unauthorized_access'));
		}

		unset($_POST['quicklinks_update']); // submit button
		unset($_POST['id']);

		$safety = array();
		$dups	= FALSE;

		foreach ($_POST as $key => $val)
		{
			if (strncmp($key, 'title_', 6) == 0 && $val != '')
			{
				$i = $_POST['order_'.substr($key, 6)];

				if ($i == '' OR $i == 0)
				{
					$_POST['order_'.substr($key, 6)] = 1;
				}

				if ( ! isset($safety[$i]))
				{
					$safety[$i] = true;
				}
				else
				{
					$dups = TRUE;
				}
			}
		}

		if ($dups)
		{
			$i = 1;

			foreach ($_POST as $key => $val)
			{
				if (strncmp($key, 'title_', 6) == 0 && $val != '')
				{
					$_POST['order_'.substr($key, 6)] = $i;

					$i++;
				}
			}
		}

		// Compile the data

		$data = array();

		foreach ($_POST as $key => $val)
		{
			if (strncmp($key, 'title_', 6) == 0 && $val != '')
			{
				$n = substr($key, 6);

				$i = $_POST['order_'.$n];

				$data[$i] = $i.'|'.$_POST['title_'.$n].'|'.$_POST['link_'.$n].'|'.$_POST['order_'.$n]."\n";
			}
		}

		sort($data);

		$str = '';

		foreach ($data as $key => $val)
		{
			$str .= substr(strstr($val, '|'), 1);
		}

		$this->member_model->update_member($this->id, array('quick_links' => trim($str)));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=quicklinks'.AMP.'id='.$this->id.AMP.'U=1');
	}

	// --------------------------------------------------------------------

	/**
	  * Quicktab Manager
	  */
	function main_menu_manager()
	{
		$this->load->library('table');
		$this->load->helper(array('form'));

		$vars['cp_page_title'] = $this->lang->line('main_menu_manager');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden'] = array();

		if ($this->session->userdata('group_id') != 1 && 
			$this->id != $this->session->userdata('member_id'))
		{
			show_error($this->lang->line('only_self_main_menu_manager_access'));
		}

		// Build the rows of previously saved links
		$query = $this->member_model->get_member_data($this->id, array('quick_tabs'));

		$i = 1;
		$vars['quicktabs'] = array();

		if ($query->row('quick_tabs')  != '')
		{
			$xtabs = explode("\n", $query->row('quick_tabs') );

			$total_tabs = count($xtabs);

			foreach ($xtabs as $row)
			{
				$x = explode('|', $row);

				$vars['quicktabs'][$i]['title'] = (isset($x['0'])) ? $x['0'] : '';
				$vars['quicktabs'][$i]['order'] = $i;
				$vars['form_hidden']['link_'.$i] = (isset($x['1'])) ? $x['1'] : '';

				$i++;
			}
		}

		if (count($vars['quicktabs']) > 0)
		{
			$this->jquery->tablesorter('.mainTable', '{
				headers: {6: {sorter: false}},
				widgets: ["zebra"]
			}');
		}

		$this->javascript->output('');

		$this->javascript->compile();

		$this->load->view('account/main_menu_manager', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  * Quicktab Manager
	  */
	function main_menu_manager_add()
	{
		if ($this->session->userdata('group_id') != 1 && 
			$this->id != $this->session->userdata('member_id'))
		{
			show_error($this->lang->line('only_self_main_menu_manager_access'));
		}

		$this->load->library('table');
		$this->load->helper(array('form'));

		$vars = array();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$link = BASE.AMP.str_replace(array('/', '--'), array('&', '='), $this->input->get('link', TRUE));
		$linkt = base64_decode($this->input->get('linkt', TRUE));

		if ($link == '')
		{
			return $this->main_menu_manager();
		}

		// Build the rows of previously saved links
		$query = $this->member_model->get_member_data($this->id, array('quick_tabs'));

		$i = 1;
		$quicktabs = array();

		if ($query->row('quick_tabs')  != '')
		{
			$xtabs = explode("\n", $query->row('quick_tabs') );

			$total_tabs = count($xtabs);

			foreach ($xtabs as $row)
			{
				$quicktabs[$i] = $row;
				$i++;
			}
		}

		// add in the new quicktab
		$quicktabs[$i] = "$linkt|$link|$i";

		$str = implode ("\n", $quicktabs);

		$this->member_model->update_member($this->id, array('quick_tabs' => trim($str)));

		$this->session->set_flashdata('message_success',
									  $this->lang->line('main_menu_manager_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=main_menu_manager'.AMP.'id='.$this->id);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Save Quicktabs
	  */
	function main_menu_update()
	{
		if ($this->session->userdata['group_id'] != 1 && 
		   ($this->id != $this->session->userdata('member_id')))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// validate for unallowed blank values
		if (empty($_POST)) 
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		unset($_POST['quicktabs_submit']); // submit button

		$safety = array();
		$dups	= FALSE;

		foreach ($_POST as $key => $val)
		{
			if (strncmp($key, 'title_', 6) == 0 && $val != '')
			{
				$i = $_POST['order_'.substr($key, 6)];

				if ($i == '' OR $i == 0)
				{
					$_POST['order_'.substr($key, 6)] = 1;
				}

				if ( ! isset($safety[$i]))
				{
					$safety[$i] = true;
				}
				else
				{
					$dups = TRUE;
				}
			}
		}

		if ($dups)
		{
			$i = 1;

			foreach ($_POST as $key => $val)
			{
				if (strncmp($key, 'title_', 6) == 0 && $val != '')
				{
					$_POST['order_'.substr($key, 6)] = $i;
					$i++;
				}
			}
		}

		// Compile the data

		$data = array();

		foreach ($_POST as $key => $val)
		{
			if (strncmp($key, 'title_', 6) == 0 && $val != '')
			{
				$n = substr($key, 6);

				$i = $_POST['order_'.$n];

				$data[$i] = $i.'|'.$_POST['title_'.$n].'|'.$_POST['link_'.$n].'|'.$_POST['order_'.$n]."\n";
			}
		}

		natcasesort($data);

		$str = '';

		foreach ($data as $key => $val)
		{
			$str .= substr(strstr($val, '|'), 1);
		}

		$this->member_model->update_member($this->id, array('quick_tabs' => trim($str)));

		$this->session->set_flashdata('message_success', 
										$this->lang->line('main_menu_manager_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=main_menu_manager'.AMP.'id='.$this->id);
	}

	// --------------------------------------------------------------------

	/**
	  * Bookmarklet Form
	  */
	function bookmarklet()
	{
		// Is the user authorized to access the publish page? And does the user
		// have at least one channel assigned? If not, show the no access message
		if ( ! $this->cp->allowed_group('can_access_publish'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if (count($this->functions->fetch_assigned_channels()) == 0)
		{
			show_error($this->lang->line('no_channels_assigned_to_user'));
		}

		if (count($this->session->userdata['assigned_channels']) == 0)
		{
			show_error($this->lang->line('no_channels_assigned_to_user'));
		}

		$this->load->library('table');
		$this->load->helper(array('form'));
		$this->load->model('channel_model');

		$vars['cp_page_title'] = $this->lang->line('bookmarklet');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden']['id'] = $this->id;

		$vars['step'] = 1; // start at step 1

		if ($this->input->post('channel_id') != '')
		{
			$vars['step'] = 2; // start at step 1

			$bm_name = strip_tags($_POST['bm_name']);
			$bm_name = preg_replace("/[\'\"\?\/\.\,\|\$\#\+]/", "", $bm_name);
			$bm_name = preg_replace("/\s+/", "_", $bm_name);
			$bm_name = stripslashes($bm_name);

			$query = $this->channel_model->get_channel_info($this->input->post('channel_id'), array('field_group'));

			if ($query->num_rows() == 0)
			{
				show_error($this->lang->line('no_fields_assigned_to_channel'));
			}

			$query = $this->channel_model->get_channel_fields($query->row('field_group'));

			if ($query->num_rows() == 0)
			{
				show_error($this->lang->line('no_channels_assigned_to_user'));
			}

			// setup the fields
			foreach ($query->result() as $row)
			{
				$vars['field_id_options'][$row->field_id] = $row->field_label;
			}

			$vars['form_hidden']['bm_name'] = $bm_name;
			$vars['form_hidden']['channel_id'] = $this->input->post('channel_id');
		}

		if ($this->input->post('field_id') != '')
		{
			$vars['step'] = 3;
			$vars['bm_name'] = $this->input->post('bm_name');
			$channel_id = $this->input->post('channel_id');
			$field_id  = 'field_id_'.$this->input->post('field_id');

            $s = ($this->config->item('admin_session_type') != 'c') ? $this->session->userdata('session_id') : 0;
			$path = $this->config->item('cp_url')."?S={$s}".AMP.'D=cp&C=content_publish&M=entry_form&Z=1&BK=1&channel_id='.$channel_id.'&';
						
			$type = (isset($_POST['safari'])) ? "window.getSelection()" : "document.selection?document.selection.createRange().text:document.getSelection()";

			$vars['bm_link'] = "javascript:bm=$type;void(bmentry=window.open('".$path."title='+encodeURI(document.title)+'&tb_url='+encodeURI(window.location.href)+'&".$field_id."='+encodeURI(bm),'bmentry',''))";
		}

		$this->javascript->compile();
		$this->load->view('account/bookmarklet', $vars);
	}

	/** -----------------------------------
	/**	 Private Messages Manager
	/** -----------------------------------*/
	function messages()
	{
		$id = ( ! $this->input->get_post('id')) ? $this->session->userdata['member_id'] : $this->input->get_post('id');

		if ($id != $this->session->userdata['member_id'])
		{
			return false;
		}

		if ( ! class_exists('EE_Messages'))
		{
			require APPPATH.'libraries/Messages'.EXT;
		}

		$MESS = new EE_Messages;
		$MESS->manager();

		// If both the title and the crumb variables are empty, then we have something that
		// does not need to be put in the member wrapper, like a popup.  So, we just return
		// the return_date variable and be done with it.

		if ($MESS->title != '' && $MESS->crumb != '')
		{
			return $this->account_wrapper($MESS->title, $MESS->crumb, $MESS->return_data);
		}

		return $MESS->return_data;
	}

	/**
	  *	 Ignore List
	  */
	function ignore_list($message = '')
	{
		$this->load->helper(array('form', 'snippets', 'url', 'string'));
		$this->load->library('table');

		$vars['cp_page_title'] = $this->lang->line('ignore_list');
		$vars['message'] = '';

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->javascript->output('
			$(".toggle_all").toggle(
				function(){
					$("input.toggle").each(function() {
						this.checked = true;
					});
				}, function (){
					var checked_status = this.checked;
					$("input.toggle").each(function() {
						this.checked = false;
					});
				}
			);

			$("#add_member").hide();

			$(".cp_button").show();
			$(".cp_button a").click(function () {$("#add_member").slideDown();return false;});
		');

		$this->javascript->compile();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden']['id'] = $this->id;
		$vars['form_hidden']['toggle[]'] = '';

		// Save any incoming data
		if (isset($_POST['id']))
		{
			$vars['message'] = $this->lang->line('ignore_list_updated');

			$query = $this->member_model->get_member_data($this->id, array('ignore_list'));

			$ignored = ($query->row('ignore_list')	== '') ? array() : array_flip(explode('|', $query->row('ignore_list') ));

			if ($this->input->post('daction') == '')
			{
				if ( ! ($member_ids = $this->input->post('toggle')))
				{
					show_error($this->lang->line('unauthorized_access'));
				}

				foreach ($member_ids as $member_id)
				{
					unset($ignored[$member_id]);
				}
			}
			else
			{
				$screen_name = $this->input->post('name');

				if ($screen_name == '')
				{
					show_error($this->lang->line('unauthorized_access'));
				}

				$query = $this->member_model->get_member_by_screen_name($screen_name);

				if ($query->num_rows() == 0)
				{
					show_error($this->lang->line('invalid_screen_name_message'));
				}

				if ($this->session->userdata('member_id') == $query->row('member_id'))
				{
					show_error($this->lang->line('can_not_ignore_self'));
				}

				if ( ! isset($ignored[$query->row('member_id')]))
				{
					$ignored[$query->row('member_id')] = $query->row('member_id') ;
				}
			}

			$ignored_list = implode('|', array_keys($ignored));
			$this->member_model->update_member($this->id, array('ignore_list' => $ignored_list));
		}

		$query = $this->member_model->get_member_ignore_list($this->id);

		$num_ignored = $query->num_rows();

		$vars['ignored_members'] = array();

		if ($num_ignored > 0)
		{
			foreach ($query->result() as $row)
			{
				
				$member_name = (TRUE === ($this->id = $this->auth_id())) ? '<a href="'.BASE.AMP.'C=myaccount'.AMP.'M=ignore_list'.'">'.$row->screen_name.'</a>' : $row->screen_name;

				$vars['ignored_members'][$row->member_id]['member_id'] = $row->member_id;
				$vars['ignored_members'][$row->member_id]['member_name'] = $member_name;
			}
		}

		$this->load->view('account/ignore_list', $vars);
	}
	
	/**
	  *	 Update Sidebar
	  */
	function update_sidebar_status()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error($this->lang->line('unauthorized_access'));
		}
	
		$this->output->enable_profiler(FALSE);

		$show['show_sidebar'] = ($this->input->get_post('show') == 'false') ? 'n' : 'y';

		$this->db->where('member_id', $this->session->userdata['member_id'] );
		$this->db->update('members', $show); 
		
		$resp['messageType'] = 'success';
		$resp['message'] = $this->lang->line('sidebar_updated');
		$this->output->send_ajax_response($resp); 

	}
}

/* End of file myaccount.php */
/* Location: ./system/expressionengine/controllers/cp/myaccount.php */