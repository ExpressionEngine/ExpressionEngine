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

// --------------------------------------------------------------------

/**
 * ExpressionEngine "My Account" Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class MyAccount extends CP_Controller {

	var $id			= '';
	var $username	= '';
	var $self_edit	= TRUE;
	var $unique_dates = array();
	var $extension_paths = array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if (FALSE === ($this->id = $this->auth_id()))
		{
			show_error(lang('unauthorized_access'));
		}

		// Load the language files
		$this->lang->loadfile('myaccount');
		$this->lang->loadfile('member');
		$this->load->model('member_model');

		// Fetch username/screen name
		$query = $this->member_model->get_member_data($this->id, array('username', 'screen_name'));

		if ($query->num_rows() == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		if ($this->cp->allowed_group('can_edit_html_buttons'))
		{
			$this->javascript->output('
				$("#myaccountHtmlButtonsLink").show(); // JS only feature, its hidden by default
			');
		}

		$this->view->message = '';
		$this->view->id = $this->id;

		$this->username = ($query->row('screen_name')  == '') ? $query->row('username') : $query->row('screen_name');
		$this->view->member_username = $this->username;

		// Set self_edit to determine whether or not someone else is editing
		$this->self_edit = ((int) $this->id === (int) $this->session->userdata('member_id')) ? TRUE : FALSE;
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
		$vars['cp_page_title'] = lang('my_account');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$query = $this->member_model->get_member_data($this->id, array('email', 'ip_address', 'join_date', 'last_visit', 'total_entries', 'total_comments', 'last_entry_date', 'last_comment_date', 'last_forum_post_date', 'total_forum_topics', 'total_forum_posts'));

		if ($query->num_rows() > 0)
		{
			$vars['username'] = $this->username;

			$vars['fields'] = array(
				'email'				=> mailto($query->row('email'), $query->row('email')),
				'join_date'			=> $this->localize->human_time($query->row('join_date')),
				'last_visit'		=> ($query->row('last_visit') == 0 OR $query->row('last_visit') == '') ? '--' : $this->localize->human_time($query->row('last_visit')),
				'total_entries'		=> $query->row('total_entries'),
				'total_comments'	=> $query->row('total_comments'),
				'last_entry_date'	=> ($query->row('last_entry_date') == 0 OR $query->row('last_entry_date') == '') ? '--' : $this->localize->human_time($query->row('last_entry_date')),
				'last_comment_date' => ($query->row('last_comment_date') == 0 OR $query->row('last_comment_date') == '') ? '--' : $this->localize->human_time($query->row('last_comment_date')),
				'user_ip_address'	=> $query->row('ip_address')
			);

			if ($this->config->item('forum_is_installed') == "y")
			{
				$fields['last_forum_post_date'] = ($query->row('last_forum_post_date') == 0) ? '--' : $this->localize->human_time($query->row('last_forum_post_date'));
				$fields['total_forum_topics']	= $query->row('total_forum_topics');
				$fields['total_forum_replies']	= $query->row('total_forum_posts');
				$fields['total_forum_posts']	= $query->row('total_forum_posts') + $query->row('total_forum_topics');
			}
		}

		$this->cp->render('account/index', $vars);
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
		if ($this->self_edit)
		{
			if ( ! class_exists('EE_Messages'))
			{
				require APPPATH.'libraries/Messages.php';
			}

			$MESS = new EE_Messages;

			$vars['private_messaging_menu'] = $MESS->menu_array();
		}

		$vars['can_admin_members'] = $this->cp->allowed_group('can_admin_members');
		$vars['allow_localization'] = ($this->config->item('allow_member_localization') == 'y' OR $this->session->userdata('group_id') == 1) ? TRUE : FALSE;
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

			$vars['login_as_member'] = ($this->session->userdata('group_id') == 1 && $this->id != $this->session->userdata('member_id')) ? TRUE : FALSE;
			$vars['can_delete_members'] = ($this->cp->allowed_group('can_delete_members') AND $this->id != $this->session->userdata('member_id')) ? TRUE : FALSE;
		}

		// default additional_nav lists are empty
		$vars['additional_nav'] = array(
			'personal_settings' => array(),
			'utilities' => array(),
			'private_messages' => array(),
			'customize_cp' => array(),
			'channel_preferences' => array(),
			'administrative_options' => array()
		);

		// -------------------------------------------
		// 'myaccount_nav_setup' hook.
		//  - Add items to the My Account nav
		//  - return must be an associative array using a pre-defined key
		//
		if ($this->extensions->active_hook('myaccount_nav_setup') === TRUE)
		{
			$vars['additional_nav'] = array_merge_recursive(
				$vars['additional_nav'],
				$this->extensions->call('myaccount_nav_setup')
			);
		}
		//
		// -------------------------------------------

		// make sure we have usable URLs in additional_nav
		$this->load->model('addons_model');
		foreach ($vars['additional_nav'] as $additional_nav_key => $additional_nav_links)
		{
			if (count($additional_nav_links))
			{
				foreach ($additional_nav_links as $additional_nav_link_text => $additional_nav_link_link)
				{
					if (is_array($additional_nav_link_link))
					{
						// create the link
						if ($this->addons_model->extension_installed($additional_nav_link_link['extension']))
						{
							$vars['additional_nav'][$additional_nav_key][$additional_nav_link_text] = BASE.AMP.'C=myaccount'.AMP.'M=custom_screen'.AMP.'extension='.$additional_nav_link_link['extension'].AMP.'method='.$additional_nav_link_link['method'];
						}
						// don’t create the link if the extension doesn't exist
						else
						{
							unset($vars['additional_nav'][$additional_nav_key][$additional_nav_link_text]);
						}
					}
				}
			}
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
		// Whose profile are we editing?
		$id = ( ! $this->input->get_post('id')) ? $this->session->userdata('member_id') : $this->input->get_post('id');

		if ( ! ctype_digit((string) $id))
		{
			return FALSE;
		}

		// Is the user authorized to edit the profile?
		if ($id != $this->session->userdata('member_id'))
		{
			if ( ! $this->cp->allowed_group('can_admin_members'))
			{
				return FALSE;
			}

			// Only Super Admins can view Super Admin profiles
			$query = $this->member_model->get_member_data($id, array('group_id'));

			if ($query->num_rows() != 1 OR ($query->row()->group_id == 1 && $this->session->userdata('group_id') != 1))
			{
				return FALSE;
			}
		}

		return $id;
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Profile Form
	 */
	function edit_profile()
	{
		$this->load->language('calendar');

		$vars['cp_page_title'] = lang('edit_profile');

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

		$vars['bday_y_options'][''] = lang('year');

		for ($i = date('Y', $this->localize->now); $i > 1904; $i--)
		{
		  $vars['bday_y_options'][$i] = $i;
		}

		$vars['bday_m_options'] = array(
			''	 => lang('month'),
			'01' => lang('cal_january'),
			'02' => lang('cal_february'),
			'03' => lang('cal_march'),
			'04' => lang('cal_april'),
			'05' => lang('cal_mayl'),
			'06' => lang('cal_june'),
			'07' => lang('cal_july'),
			'08' => lang('cal_august'),
			'09' => lang('cal_september'),
			'10' => lang('cal_october'),
			'11' => lang('cal_november'),
			'12' => lang('cal_december')
		);

		$vars['bday_d_options'][''] = lang('day');

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

			$resrow = $result->row_array();

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

		$this->cp->render('account/edit_profile', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Update member profile
	 */
	function update_profile()
	{
		// validate for unallowed blank values
		if (empty($_POST))
		{
			show_error(lang('unauthorized_access'));
		}

		$id = $_POST['id'];

		unset($_POST['id']);
		unset($_POST['edit_profile']);

		$_POST['url'] = ($_POST['url'] == 'http://') ? '' : $_POST['url'];

		$fields = array(
			'bday_y',
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
			$this->load->helper('date');
			$year = ($data['bday_y'] != '') ? $data['bday_y'] : date('Y');
			$mdays = days_in_month($data['bday_m'], $year);

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

		$this->session->set_flashdata('message_success', lang('profile_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_profile'.$id);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Email preferences form
	  */
	function email_settings()
	{
		$this->load->helper('snippets');

		$vars['cp_page_title'] = lang('email_settings');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		// Fetch member data
		$query = $this->member_model->get_member_data($this->id, array('email', 'accept_admin_email', 'accept_user_email', 'notify_by_default', 'notify_of_pm', 'smart_notifications'));

		$vars['form_hidden']['id'] = $this->id;

		foreach ($query->row_array() as $key => $val)
		{
			$vars[$key] = $val;
		}

		$vars['checkboxes'] = array('accept_admin_email', 'accept_user_email', 'notify_by_default', 'notify_of_pm', 'smart_notifications');

		$this->cp->render('account/email_settings', $vars);
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
			show_error(lang('unauthorized_access'));
		}

		// what's this users current email?
		$query = $this->member_model->get_member_data($this->id, array('email'));
		$current_email = $query->row('email');

		$this->VAL = $this->_validate_user(array(
			'require_cpw'	=> ($current_email != $this->input->post('email')) ? TRUE : FALSE,
			'email'			=> $this->input->post('email'),
			'cur_email'		=> $current_email,
			'cur_password'	=> $this->input->post('current_password')
		));

		$this->VAL->validate_email();

		if (count($this->VAL->errors) > 0)
		{
			show_error($this->VAL->show_errors());
		}

		// Assign the query data
		$data = array(
			'email'				 	=> $this->input->post('email'),
			'accept_admin_email' 	=> (isset($_POST['accept_admin_email'])) ? 'y' : 'n',
			'accept_user_email'	 	=> (isset($_POST['accept_user_email']))  ? 'y' : 'n',
			'notify_by_default'	 	=> (isset($_POST['notify_by_default']))  ? 'y' : 'n',
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

		$this->session->set_flashdata('message_success', lang('settings_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=email_settings'.$id);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Edit preferences form
	  */
	function edit_preferences()
	{
		$this->load->helper('snippets');

		$vars['cp_page_title'] = lang('edit_preferences');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		// Fetch member data
		$query = $this->member_model->get_member_data($this->id, array('display_avatars', 'display_signatures', 'accept_messages', 'parse_smileys'));

		foreach ($query->row_array() as $key => $val)
		{
			$vars[$key] = $val;
		}

		$vars['form_hidden']['id'] = $this->id;

		$vars['checkboxes'] = array('accept_messages', 'display_avatars', 'display_signatures', 'parse_smileys');

		$this->cp->render('account/edit_preferences', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Update	 Preferences
	  */
	function update_preferences()
	{
		// validate for unallowed blank values
		if (empty($_POST))
		{
			show_error(lang('unauthorized_access'));
		}

		$data = array(
			'accept_messages'		=> (isset($_POST['accept_messages'])) ? 'y' : 'n',
			'display_avatars'		=> (isset($_POST['display_avatars'])) ? 'y' : 'n',
			'display_signatures'	=> (isset($_POST['display_signatures']))  ? 'y' : 'n',
			'parse_smileys'			=> (isset($_POST['parse_smileys']))  ? 'y' : 'n'
		);

		$this->member_model->update_member($this->id, $data);

		$this->session->set_flashdata('message_success', lang('settings_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_preferences'.AMP.'id='.$this->id);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Username/Password form
	  */
	function username_password()
	{
		$vars['cp_page_title'] = lang('username_and_password');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		// Fetch member data
		$query = $this->member_model->get_member_data($this->id, array('username', 'screen_name'));

		$vars['form_hidden']['id'] = $this->id;

		$vars['username']	= $query->row('username');
		$vars['screen_name'] = $query->row('screen_name');

		$vars['allow_username_change'] = ($this->session->userdata['group_id'] != '1' AND $this->config->item('allow_username_change') == 'n') ? FALSE : TRUE;

		$vars['self_edit'] = $this->self_edit;

		$this->cp->render('account/username_password', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Update username and password
	  */
	function update_username_password()
	{
		if ($this->config->item('allow_username_change') != 'y' &&
			$this->session->userdata('group_id') != 1)
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
			show_error(lang('unauthorized_access'));
		}

		// If the screen name field is empty, we'll assign is from the username field.
		if ($_POST['screen_name'] == '')
		{
			$_POST['screen_name'] = $_POST['username'];
		}

		// Fetch member data
		$query = $this->member_model->get_member_data($this->id, array('username', 'screen_name'));

		$this->VAL = $this->_validate_user(array(
			'username'			=> $this->input->post('username'),
			'cur_username'		=> $query->row('username'),
			'screen_name'		=> $this->input->post('screen_name'),
			'cur_screen_name'	=> $query->row('screen_name'),
			'password'			=> $this->input->post('password'),
			'password_confirm'	=> $this->input->post('password_confirm'),
			'cur_password'		=> $this->input->post('current_password')
		));

		$this->VAL->validate_screen_name();

		if ($this->config->item('allow_username_change') == 'y' OR
			$this->session->userdata('group_id') == 1)
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
		if ($query->row('screen_name') != $_POST['screen_name'] &&
			$this->config->item('forum_is_installed') == "y")
		{
			$this->db->where('forum_last_post_author_id', $this->id);
			$this->db->update(
				'forums',
				array('forum_last_post_author' => $this->input->post('screen_name'))
			);

			$this->db->where('mod_member_id', $this->id);
			$this->db->update(
				'forum_moderators',
				array('mod_member_name' => $this->input->post('screen_name'))
			);
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
			$this->load->library('auth');

			$this->auth->update_password($this->id, $this->input->post('password'));

			if ($this->self_edit)
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

		$this->session->set_flashdata('message_success', lang('settings_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=username_password'.AMP.'id='.$this->id);
	}

	// --------------------------------------------------------------------

	/**
	 * Validate either a user, or a Super Admin editing the user
	 * @param  array $validation_data Validation data to be sent to EE_Validate
	 * @return EE_Validate	Validation object returned from EE_Validate
	 */
	private function _validate_user($validation_data)
	{
		//	Validate submitted data
		if ( ! class_exists('EE_Validate'))
		{
			require APPPATH.'libraries/Validate.php';
		}

		$defaults = array(
			'member_id'		=> $this->id,
			'val_type'		=> 'update', // new or update
			'fetch_lang'	=> FALSE,
			'require_cpw'	=> TRUE,
			'enable_log'	=> TRUE,
		);

		$validation_data = array_merge($defaults, $validation_data);

		// Are we dealing with a Super Admin editing someone else's account?
		if ( ! $this->self_edit AND $this->session->userdata('group_id') == 1)
		{
			// Validate Super Admin's password
			$this->load->library('auth');
			$auth = $this->auth->authenticate_id(
				$this->session->userdata('member_id'),
				$this->input->post('current_password')
			);

			if ($auth === FALSE)
			{
				show_error(lang('invalid_password'));
			}

			// Make sure we don't verify the actual member's existing password
			$validation_data['require_cpw'] = FALSE;
		}

		return new EE_Validate($validation_data);
	}

	// --------------------------------------------------------------------

	/**
	  *	 HTML buttons
	  */
	function html_buttons()
	{
		// Is the user authorized to access the publish page? And does the user have
		// at least one channel assigned? If not, show the no access message
		if ( ! $this->cp->allowed_group('can_access_publish', 'can_edit_html_buttons'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');
		$this->lang->loadfile('admin');
		$this->lang->loadfile('admin_content');

		$this->load->model('admin_model');

		$vars['cp_page_title'] = lang('html_buttons');
		$vars['form_hidden'] = array(
								'button_submit'	=>	TRUE,
								'id'			=>	$this->id);

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$this->cp->add_js_script(array('file' => 'cp/account_html_buttons'));

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
			$this->session->set_flashdata('message_success', lang('html_buttons_updated'));
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

		$vars['member_id'] = $this->id;
		$vars['i'] = 1;

		$this->cp->render('account/html_buttons', $vars);
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
			show_error(lang('unauthorized_access'));
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
			show_error(lang('unauthorized_access'));
		}

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
	  * Theme builder
	  *
	  * OK, well, the title is misleading.	Eventually, this will be a full-on
	  * theme builder.	Right now it just lets users choose from among pre-defined CSS files
	  */
	function cp_theme()
	{
		$this->load->helper('date');
		$this->load->model('admin_model');

		$vars['cp_page_title'] = lang('cp_theme');
		$this->cp->add_to_head('<meta http-equiv="pragma" content="no-cache">');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden']['id'] = $this->id;

		if ($this->id != $this->session->userdata('member_id'))
		{
			$member_data = $this->member_model->get_member_data($this->id);
			$member_data = $member_data->row();
			$vars['cp_theme'] = ($member_data->cp_theme == '') ? $this->config->item('cp_theme') : $member_data->cp_theme;
		}
		else
		{
			$vars['cp_theme'] = ($this->session->userdata['cp_theme'] == '') ? $this->config->item('cp_theme') : $this->session->userdata['cp_theme'];
		}

		$vars['cp_theme_options'] =	 $this->admin_model->get_cp_theme_list();

		$this->cp->render('account/cp_theme', $vars);
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
			show_error(lang('unauthorized_access'));
		}

		$this->member_model->update_member($this->id, array('cp_theme'=> $this->input->post('cp_theme')));

		$this->session->set_flashdata('message_success', lang('preferences_updated'));

		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=cp_theme'.AMP.'id='.$this->id);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Subscriptions
	  */
	function subscriptions()
	{
		$this->load->helper('snippets');
		$this->load->library('table');
		$this->load->library('pagination');
		$this->load->library('members');
		$this->cp->get_installed_modules();

		$vars['cp_page_title'] = lang('subscriptions');

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
			show_error(lang('unauthorized_access'));
		}

		$email = $query->row('email') ;

		$subscription_data = $this->members->get_member_subscriptions($this->id, $rownum, $perpage);

		$vars['subscriptions'] = $subscription_data['result_array'];

		$id = ($this->id != $this->session->userdata('member_id')) ? AMP.'id='.$this->id : '';

		// Pagination stuff
		$config['base_url'] = BASE.AMP.'C=myaccount'.AMP.'M=subscriptions'.$id;
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

		$this->cp->render('account/subscriptions', $vars);
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
			show_error(lang('unauthorized_access'));
		}

		// validate for unallowed blank values
		if (empty($_POST)) {
			show_error(lang('unauthorized_access'));
		}

		$email = $query->row('email');

		$this->load->library('subscription');

		foreach ($_POST['toggle'] as $key => $val)
		{
			switch (substr($val, 0, 1))
			{
				case "b":
					$this->subscription->init('comment', array('entry_id' => substr($val, 1)), TRUE);
					$this->subscription->unsubscribe($this->id);
					break;
				case "f":
					$this->db->delete('forum_subscriptions', array('topic_id' => substr($val, 1)));
					break;
			}
		}

		$this->subscriptions(lang('subscriptions_removed'));
	}

	// --------------------------------------------------------------------

	/**
	  *	 Localization settings
	  */
	function localization()
	{
		if ($this->config->item('allow_member_localization') == 'n' AND $this->session->userdata('group_id') != 1)
		{
			show_error(lang('localization_disallowed'));
		}

		$this->load->model('language_model');

		$vars['cp_page_title'] = lang('localization_settings');

		if ($this->input->get_post('U'))
		{
			$vars['message'] = lang('localization_updated');
		}

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden']['id'] = $this->id;

		$fields = array('timezone', 'language', 'date_format', 'time_format', 'include_seconds');

		// Fetch profile data
		$query = $this->member_model->get_member_data($this->id, $fields);

		$values = array();
		foreach ($fields as $val)
		{
			$values[$val] = $query->row($val);
		}
		$values['default_site_timezone'] = $values['timezone']; // Key differentiation with the config

		// Fetch the admin config values in order to populate the form with
		// the same options
		$this->load->model('admin_model');
		$config_fields = ee()->config->prep_view_vars('localization_cfg', $values);

		// Cleaning up some design oddness: removing labels from the radios
		foreach ($config_fields['fields'] as $field => &$data)
		{
			if ($data['type'] == 'r')
			{
				for ($i = 0; $i < count($data['value']); $i++)
				{
					$data['value'][$i]['id'] = '';
				}
			}
		}

		// Cleanup the key differentiation
		$vars['timezone'] = str_replace('default_site_timezone', 'timezone', $config_fields['fields']['default_site_timezone']['value']);
		unset($config_fields['fields']['default_site_timezone']);

		$vars = array_merge($config_fields, $vars);

		$vars['language'] = $values['language'];
		if ($vars['language'] == '')
		{
			$vars['language'] = ($this->config->item('deft_lang') && $this->config->item('deft_lang') != '') ? $this->config->item('deft_lang') : 'english';
		}
		$vars['language_options'] = $this->language_model->language_pack_names();

		$this->cp->render('account/localization', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Localization update
	  */
	function localization_update()
	{
		if ($this->config->item('allow_member_localization') == 'n' AND $this->session->userdata('group_id') != 1)
		{
			show_error(lang('localization_disallowed'));
		}

		// validate for unallowed blank values
		if (empty($_POST)) {
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('site_model');

		$data['language']	= $this->security->sanitize_filename($this->input->post('language'));
		$data['timezone']	= $this->input->post('timezone');
		$data['date_format'] = $this->input->post('date_format');
		$data['time_format'] = $this->input->post('time_format');
		$data['include_seconds'] = $this->input->post('include_seconds');

		if ( ! is_dir(APPPATH.'language/'.$data['language']))
		{
			show_error(lang('localization_disallowed'));
		}

		$this->member_model->update_member($this->id, $data);

		$this->session->set_flashdata('message_success', lang('settings_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=localization'.AMP.'id='.$this->id.AMP.'U=1');
	}

	// --------------------------------------------------------------------

	/**
	  * Edit Signature Form
	  */
	function edit_signature()
	{
		$vars['cp_page_title'] = lang('edit_signature');

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
			$vars['max_size'] = str_replace('%x', $max_w, lang('max_image_size'));
			$vars['max_size'] = str_replace('%y', $max_h, $vars['max_size']);
			$vars['max_size'] .= ' - '.$max_kb.'KB';

			$vars['sig_image'] = TRUE;

			if ($query->row('sig_img_filename')	 == '')
			{
				$vars['sig_img_filename'] = lang('no_image_exists');
			}
			else
			{
				$vars['sig_image_remove'] = TRUE;
				$vars['sig_img_filename'] = '<img src="'.$this->config->slash_item('sig_img_url').$query->row('sig_img_filename') .'" border="0" width="'.$query->row('sig_img_width') .'" height="'.$query->row('sig_img_height') .'" title="'.lang('signature_image').'"	 alt="'.lang('signature_image').'" />';
			}
		}

		$this->cp->render('account/edit_signature', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  * Update signature
	  */
	function update_signature()
	{
		$signature = $this->input->post('signature');

		// Do we have what we need in $_POST?
		if ( ! ee()->input->post('signature'))
		{
			return ee()->functions->redirect(cp_url('myaccount/edit_signature'));
		}

		$maxlength = ($this->config->item('sig_maxlength') == 0)
			? 10000
			: $this->config->item('sig_maxlength');

		if (strlen($signature) > $maxlength)
		{
			show_error(sprintf(lang('sig_too_big'), $maxlength));
		}

		$this->member_model->update_member(
			$this->id,
			array('signature' => $signature)
		);

		// Is there an image to upload or remove?
		if ((isset($_FILES['userfile']) && $_FILES['userfile']['name'] != '')
			OR isset($_POST['remove']))
		{
			return $this->upload_signature_image();
		}

		$params = array();
		if ($id = ee()->input->get_post('id'))
		{
			$params['id'] = $id;
		}

		$this->session->set_flashdata('message_success', lang('signature_updated'));
		$this->functions->redirect(cp_url('myaccount/edit_signature', $params));
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
			show_error(lang('avatars_not_enabled'));
		}

		$this->load->language('number');

		$vars['cp_page_title'] = lang('edit_avatar');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden']['id'] = $this->id;

		// Are we a superadmin & looking at our profile, or another users?
		if ($this->id != $this->session->userdata('member_id'))
		{
			$member_avatar = $this->member_model->get_member_data($this->id, array('avatar_filename', 'avatar_width', 'avatar_height', 'screen_name'));

			$cur_avatar_url = '';
			$avatar_width	= '';
			$avatar_height	= '';

			if ($member_avatar->row('avatar_filename') == '')
			{
				// there ain't no avatar
				$vars['avatar'] = sprintf(
					lang('no_user_avatar'),
					$member_avatar->row('screen_name')
				);

			}
			else
			{
				// ain't ain't a word
				$cur_avatar_url = ($member_avatar->row('avatar_filename') != '') ? $this->config->slash_item('avatar_url').$member_avatar->row('avatar_filename') : '';
				$avatar_width   = $member_avatar->row('avatar_filename') ? $member_avatar->row('avatar_width') : '';
				$avatar_height  = $member_avatar->row('avatar_filename') ? $member_avatar->row('avatar_height') : '';
				$vars['avatar'] = '<img src="'.$cur_avatar_url.'" border="0" width="'.$avatar_width.'" height="'.$avatar_height.'" alt="'.lang('my_avatar').'" title="'.lang('my_avatar').'" />';
			}
		}
		else
		{
			// We already grab this data for the sidebar, so we'll use those values
			$cur_avatar_url = $this->session->cache('cp_sidebar', 'cp_avatar_path');
			$avatar_width	= $this->session->cache('cp_sidebar', 'cp_avatar_width');
			$avatar_height	= $this->session->cache('cp_sidebar', 'cp_avatar_height');

			if ( ! $cur_avatar_url)
			{
				$vars['avatar'] = lang('no_avatar');
			}
			else
			{
				$vars['avatar'] = '<img src="'.$cur_avatar_url.'" border="0" width="'.$avatar_width.'" height="'.$avatar_height.'" alt="'.lang('my_avatar').'" title="'.lang('my_avatar').'" />';
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
		$vars['max_size'] = str_replace('%x', $max_w, lang('max_image_size'));
		$vars['max_size'] = str_replace('%y', $max_h, $vars['max_size']);
		$vars['max_size'] .= ' - '.$max_kb.lang('kilobyte_abbr');

		$vars['avatar_image_remove'] = ($this->config->item('allow_avatar_uploads') == 'y' AND $cur_avatar_url != '') ? TRUE : FALSE;

		$this->cp->render('account/edit_avatar', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  * Edit Photo Form
	  */
	function edit_photo()
	{
		// Are avatars enabled?
		if ($this->config->item('enable_photos') == 'n')
		{
			show_error(lang('photos_not_enabled'));
		}

		$this->load->language('number');

		$vars['cp_page_title'] = lang('edit_photo');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden']['id'] = $this->id;

		// Fetch photo data
		$query = $this->member_model->get_member_data($this->id, array('photo_filename', 'photo_width', 'photo_height'));

		if ($query->row('photo_filename')  == '')
		{
			$cur_photo_url = '';
			$photo_width	= '';
			$photo_height	= '';
			$vars['photo'] = lang('no_photo_exists');
		}
		else
		{
			$cur_photo_url = $this->config->slash_item('photo_url').$query->row('photo_filename') ;
			$photo_width	= $query->row('photo_width') ;
			$photo_height	= $query->row('photo_height') ;
			$vars['photo'] = '<img src="'.$cur_photo_url.'" border="0" width="'.$photo_width.'" height="'.$photo_height.'" alt="'.lang('my_photo').'" title="'.lang('my_photo').'" />';
		}

		// Set the default image meta values

		$max_kb = ($this->config->item('photo_max_kb') == '' OR $this->config->item('photo_max_kb') == 0) ? 50 : $this->config->item('photo_max_kb');
		$max_w	= ($this->config->item('photo_max_width') == '' OR $this->config->item('photo_max_width') == 0) ? 100 : $this->config->item('photo_max_width');
		$max_h	= ($this->config->item('photo_max_height') == '' OR $this->config->item('photo_max_height') == 0) ? 100 : $this->config->item('photo_max_height');
		$vars['max_size'] = str_replace('%x', $max_w, lang('max_image_size'));
		$vars['max_size'] = str_replace('%y', $max_h, $vars['max_size']);
		$vars['max_size'] .= ' - '.$max_kb.lang('kilobyte_abbr');;

		$vars['remove_photo'] = ($cur_photo_url != '') ? TRUE : FALSE;

		$this->cp->render('account/edit_photo', $vars);
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
			show_error(lang('avatars_not_enabled'));
		}

		$this->load->library('table');
		$this->load->library('pagination');

		$vars['cp_page_title'] = lang('browse_avatars');

		$vars['form_hidden']['id'] = $this->id;
		$vars['form_hidden']['folder'] = $this->input->get_post('folder');
		$vars['pagination'] = '';

		$this->javascript->output('
			$("#browse_avatar_form img").css("cursor", "pointer");

			$("input:radio").css("visibility", "hidden");

			$("input.submit").hide();

			$("#browse_avatar_form img").click(function() {
				var checkid = $(this).attr("alt");
				document.getElementById(checkid).checked = true;
				$("#browse_avatar_form").submit();
			});
		');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		// Define the paths

		$avatar_path 	= $this->config->slash_item('avatar_path').$this->security->sanitize_filename($this->input->get_post('folder')).'/';
		$avatar_url 	= $this->config->slash_item('avatar_url').$this->security->sanitize_filename($this->input->get_post('folder')).'/';

		$avatars = $this->_get_avatars($avatar_path);

		$total_count = count($avatars);

		// Did we succeed?

		if (count($avatars) == 0)
		{
			show_error(lang('avatars_not_found'));
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

		$this->table->set_template(array(
						'table_open' => '<table style="width:100%">',
						'cell_alt_start' => '<td style="width:30%">'
					));

		$this->cp->render('account/browse_avatars', $vars);
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
			show_error(lang('avatars_not_enabled'));
		}

		if ($this->input->get_post('avatar') === FALSE OR $this->input->get_post('folder') === FALSE)
		{
			return $this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=browse_avatars'.AMP.'folder='.$this->input->get_post('folder'));
		}

		$folder = $this->security->sanitize_filename($this->input->get_post('folder'));
		$file	= $this->security->sanitize_filename($this->input->get_post('avatar'));

		$basepath	= $this->config->slash_item('avatar_path');
		$avatar		= $avatar	= $folder.'/'.$file;

		$allowed = $this->_get_avatars($basepath.$folder);

		if ( ! in_array($file, $allowed) OR $folder == 'upload')
		{
			show_error(lang('avatars_not_found'));
		}

		// Fetch the avatar meta-data

		if ( ! function_exists('getimagesize'))
		{
			show_error(lang('image_assignment_error'));
		}

		$vals = @getimagesize($basepath.$avatar);
		$width	= $vals['0'];
		$height = $vals['1'];

		$this->member_model->update_member($this->id, array('avatar_filename' => $avatar, 'avatar_width' => $width, 'avatar_height' => $height));

		$id = ($this->input->get_post('id')) ? AMP.'id='.$this->input->get_post('id') : '';

		$this->session->set_flashdata('message_success', lang('avatar_updated'));
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
		$this->session->set_flashdata('message_success', lang($updated));
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
			show_error(lang('unauthorized_access'));
		}

		$this->member_model->update_member($this->session->userdata('member_id'), array('notepad'=>$this->input->get_post('notepad')));

		$this->session->set_flashdata('notepad_message', lang('mbr_notepad_updated'));
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
			show_error(lang('unauthorized_access'));
		}

		$vars['cp_page_title'] = lang('administrative_options');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$query = $this->member_model->get_member_data($this->id, array('ip_address', 'in_authorlist', 'group_id'));

		foreach ($query->row_array() as $key => $val)
		{
			$vars[$key] = $val;
		}

		$vars['form_hidden']['id'] = $this->id;

		// Member groups assignment
		if ($this->cp->allowed_group('can_admin_mbr_groups'))
		{
			$vars['group_id_options'] = array();

			$query = $this->member_model->get_member_groups('is_locked');

			if ($this->session->userdata('group_id') == 1 && $this->self_edit)
			{
				// Can't demote ourselves; Super Admin is the only way
				$vars['group_id_options'][1] = $query->row(0)->group_title;
			}
			else
			{
				$show_locked = $this->session->userdata('group_id') == 1 ? TRUE : FALSE;

				foreach ($query->result() as $row)
				{
					if ($row->is_locked == 'n' OR $show_locked OR $row->group_id == $this->session->userdata('group_id'))
					{
						$vars['group_id_options'][$row->group_id] = $row->group_title;
					}
				}
			}
		}

		$this->cp->render('account/member_preferences', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Update Member Preferences options
	  */
	function member_preferences_update()
	{
		if ( ! $this->cp->allowed_group('can_admin_members'))
		{
			show_error(lang('unauthorized_access'));
		}

		// validate for unallowed blank values
		if (empty($_POST)) {
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('site_model');

		$data['in_authorlist'] = ($this->input->post('in_authorlist') == 'y') ? 'y' : 'n';

		if ($this->input->post('group_id'))
		{
			$data['group_id'] = $this->input->post('group_id');

			if ( ! $this->cp->allowed_group('can_admin_mbr_groups'))
			{
				show_error(lang('unauthorized_access'));
			}

			if ($this->session->userdata('group_id') == '1')
			{
				if ($data['group_id'] != '1' && $this->self_edit)
				{
					show_error(lang('super_admin_demotion_alert'));
				}
			}
			else
			{
				// Get unlocked groups
				$query = $this->member_model->get_member_groups('', array('is_locked'=>'n'));

				foreach ($query->result() as $row)
				{
					$unlocked_groups[] = $row->group_id;
				}

				$query = $this->member_model->get_member_data($this->id, array('group_id'));

				// We need to bail if...
				if ($query->num_rows() != 1								// the target doesn't exist,
					OR $query->row()->group_id == 1						// the target is a Super Admin,
					OR ! in_array($data['group_id'], $unlocked_groups)  // the target group isn't unlocked, or
					OR $data['group_id'] == '1')						// Super Admins are somehow unlocked (!)
				{
					show_error(lang('unauthorized_access'));
				}
			}
		}

		$this->member_model->update_member($this->id, $data);

		$this->session->set_flashdata('message_success', lang('administrative_options_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=member_preferences'.AMP.'id='.$this->id);
	}

	// --------------------------------------------------------------------

	/**
	  * Quick links
	  */
	function quicklinks()
	{
		if ($this->session->userdata['group_id'] != 1 AND ($this->id != $this->session->userdata('member_id')))
		{
			show_error(lang('only_self_qucklink_access'));
		}

		$this->load->library('table');

		$vars['cp_page_title'] = lang('quicklinks_manager');
		$vars['form_hidden']['id'] = $this->id;

		$this->jquery->tablesorter('.mainTable', '{widgets: ["zebra"]}');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		if ($this->input->get('U'))
		{
			$vars['message'] = lang('quicklinks_updated');
		}

		$vars['quicklinks'] = $this->member_model->get_member_quicklinks($this->id);

		$vars['blank_count'] = count($vars['quicklinks'])+1;

		$this->cp->render('account/quicklinks', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *	 Save quick links
	  */
	function quicklinks_update()
	{
		if ($this->session->userdata['group_id'] != 1 AND ($this->id != $this->session->userdata('member_id')))
		{
			show_error(lang('unauthorized_access'));
		}

		// validate for unallowed blank values
		if (empty($_POST)) {
			show_error(lang('unauthorized_access'));
		}

		unset($_POST['quicklinks_update']); // submit button
		unset($_POST['id']);

		$safety = array();
		$dups	= FALSE;

		foreach ($_POST as $key => $val)
		{
			if (strncmp($key, 'title_', 6) == 0 && $val != '')
			{
				// XSS clean the title
				$_POST[$key] = $val = $this->security->xss_clean($val);

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
		$this->session->set_flashdata('message_success', lang('quicklinks_updated'));
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=quicklinks'.AMP.'id='.$this->id.AMP.'U=1');
	}

	// --------------------------------------------------------------------

	/**
	  * Quicktab Manager
	  */
	function main_menu_manager()
	{
		$this->load->library('table');

		$vars['cp_page_title'] = lang('main_menu_manager');

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden'] = array();

		if ($this->session->userdata('group_id') != 1 &&
			$this->id != $this->session->userdata('member_id'))
		{
			show_error(lang('only_self_main_menu_manager_access'));
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

		$this->cp->render('account/main_menu_manager', $vars);
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
			show_error(lang('only_self_main_menu_manager_access'));
		}

		$this->load->library('table');

		$vars = array();

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$link = str_replace(array('/', '--'), array('&', '='), $this->input->get('link', TRUE));
		$linkt = base64_decode($this->input->get('linkt', TRUE));
		$linkt = strip_tags($this->security->xss_clean($linkt));

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
									  lang('main_menu_manager_updated'));
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
			show_error(lang('unauthorized_access'));
		}

		// validate for unallowed blank values
		if (empty($_POST))
		{
			show_error(lang('unauthorized_access'));
		}

		unset($_POST['quicktabs_submit']); // submit button

		$safety = array();
		$dups	= FALSE;

		foreach ($_POST as $key => $val)
		{
			if (strncmp($key, 'title_', 6) == 0 && $val != '')
			{
				// XSS clean the title
				$_POST[$key] = $val = strip_tags($this->security->xss_clean($val));

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
										lang('main_menu_manager_updated'));
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
			show_error(lang('unauthorized_access'));
		}

		if (count($this->functions->fetch_assigned_channels()) == 0)
		{
			show_error(lang('no_channels_assigned_to_user'));
		}

		if (count($this->session->userdata['assigned_channels']) == 0)
		{
			show_error(lang('no_channels_assigned_to_user'));
		}

		$this->load->library('table');
		$this->load->model('channel_model');

		$vars['cp_page_title'] = lang('bookmarklet');

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
				show_error(lang('no_fields_assigned_to_channel'));
			}

			$query = $this->channel_model->get_channel_fields($query->row('field_group'));

			if ($query->num_rows() == 0)
			{
				show_error(lang('no_channels_assigned_to_user'));
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
			$field_id = 'field_id_'.$this->input->post('field_id');

			$path = cp_url(
				'content_publish/entry_form',
				array(
					'Z'          => 1,
					'BK'         => 1,
					'channel_id' => $channel_id
				)
			);

			$type = (isset($_POST['safari'])) ? "window.getSelection()" : "document.selection?document.selection.createRange().text:document.getSelection()";

			$vars['bm_link'] = "javascript:bm=$type;void(bmentry=window.open('".$path."title='+encodeURI(document.title)+'&tb_url='+encodeURI(window.location.href)+'&".$field_id."='+encodeURI(bm),'bmentry',''))";
		}

		$this->cp->render('account/bookmarklet', $vars);
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
			require APPPATH.'libraries/Messages.php';
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
	function ignore_list()
	{
		$this->load->helper('snippets');
		$this->load->library('table');

		$vars['cp_page_title'] = lang('ignore_list');
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

		$vars = array_merge($this->_account_menu_setup(), $vars);

		$vars['form_hidden']['id'] = $this->id;
		$vars['form_hidden']['toggle[]'] = '';

		// Save any incoming data
		if (isset($_POST['id']))
		{
			$vars['message'] = lang('ignore_list_updated');

			$query = $this->member_model->get_member_data($this->id, array('ignore_list'));

			$ignored = ($query->row('ignore_list')	== '') ? array() : array_flip(explode('|', $query->row('ignore_list') ));

			if ($this->input->post('daction') == '')
			{
				if ( ! ($member_ids = $this->input->post('toggle')))
				{
					show_error(lang('unauthorized_access'));
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
					show_error(lang('unauthorized_access'));
				}

				$query = $this->member_model->get_member_by_screen_name($screen_name);

				if ($query->num_rows() == 0)
				{
					show_error(lang('invalid_screen_name_message'));
				}

				if ($this->session->userdata('member_id') == $query->row('member_id'))
				{
					show_error(lang('can_not_ignore_self'));
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

		$this->cp->render('account/ignore_list', $vars);
	}

	/**
	  *	 Update Sidebar
	  */
	function update_sidebar_status()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->output->enable_profiler(FALSE);

		$show['show_sidebar'] = ($this->input->get_post('show') == 'false') ? 'n' : 'y';

		$this->db->where('member_id', $this->session->userdata['member_id'] );
		$this->db->update('members', $show);

		$resp['messageType'] = 'success';
		$resp['message'] = lang('sidebar_updated');
		$this->output->send_ajax_response($resp);

	}

	// --------------------------------------------------------------------

	/**
	 * Custom My Account Screens
	 */
	public function custom_screen()
	{
		list($vars, $extension, $method, $method_save) = $this->_custom_action();

		// Automatically push to the $method+'_save' method
		$vars['action'] = 'C=myaccount'.AMP.'M=custom_screen_save'.AMP.'extension='.$extension.AMP.'method='.$method.AMP.'method_save='.$method_save;

		// load the view wrapper
		$this->cp->render('account/custom_screen', $vars);
	}

	// -------------------------------------------------------------------------

	/**
	 * Method called when a custom screen added with the myaccount_nav_setup
	 * hook is called
	 */
	public function custom_screen_save()
	{
		list($vars, $extension, $method, $method_save) = $this->_custom_action('method_save');

		// Redirect back
		$this->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=custom_screen'.AMP.'extension='.$extension.AMP.'method='.$method.AMP.'method_save='.$method_save.AMP.'id='.$this->id);
	}

	// -------------------------------------------------------------------------

	public function custom_action()
	{
		list($vars, $extension, $method, $method_save) = $this->_custom_action();

		if (AJAX_REQUEST)
		{
			echo $vars['content'];
		}
		else
		{
			return $vars['content'];
		}
	}

	// -------------------------------------------------------------------------

	/**
	 * Abstraction of the custom screen page that takes care of figuring out the
	 * name of the extension, the methods that should be called, what files to
	 * load, and what method to call
	 *
	 * @param  string $method_choice The method to call,
	 *		either 'method' or 'method_save'
	 * @return Array containing four items:
	 *		$vars: Variables to pass to view
	 *		$extension: Extension name (should not include '_ext' or 'ext.')
	 *		$method: Extension's method called to display settings
	 *		$method_save: Extension's method called when the form is submit
	 */
	private function _custom_action($method_choice = 'method')
	{
		$vars = $this->_account_menu_setup();
		$vars['form_hidden']['id'] = $this->id;

		// get the module & method
		$extension 	= strtolower($this->input->get_post('extension'));
		$method 	= strtolower($this->input->get_post('method'));

		// Check for a method_save get variable, if it doesn't exist, assume
		// it's the method name with _save at the end (e.g. method_save)
		$method_save	= ($this->input->get_post('method_save')) ?
			strtolower($this->input->get_post('method_save')) :
			$method.'_save';

		$class_name = ucfirst($extension).'_ext';
		$file_name	= 'ext.'.$extension.'.php';

		$this->_load_extension_paths($extension);

		// Include the Extension
		include_once($this->extension_paths[$extension].$file_name);

		$this->load->add_package_path($this->extension_paths[$extension], FALSE);

		// Validate method choice parameter
		$method_choice = (in_array($method_choice, array('method', 'method_save'))) ?
			$method_choice :
			'method';

		$EXTENSION = new $class_name();
		$this->lang->loadfile($extension, '', FALSE); // Don't show errors
		if (method_exists($EXTENSION, $$method_choice) === TRUE)
		{
			// get the content back from the extension
			$vars['content'] = $EXTENSION->$$method_choice($this->id);
		}
		else
		{
			show_error(sprintf(lang('unable_to_execute_method'), $file_name));
		}

		$this->load->remove_package_path($this->extension_paths[$extension]);

		return array($vars, $extension, $method, $method_save);
	}

	// -------------------------------------------------------------------------

	/**
	 * Make sure the extension paths have been cached
	 *
	 * @param  string $extension The name of the extension to load the path of
	 * @return void
	 */
	private function _load_extension_paths($extension)
	{
		// Have we encountered this one before?
		if ( ! isset($this->extension_paths[$extension]))
		{
			// First or third party?
			foreach (array(PATH_MOD, PATH_THIRD) as $tmp_path)
			{
				if (file_exists($tmp_path.$extension.'/ext.'.$extension.'.php'))
				{

					$this->extension_paths[$extension] = $tmp_path.$extension.'/';
					break;
				}
			}

			// Include file
			if ( ! class_exists($extension.'_ext'))
			{
				if ( ! isset($this->extension_paths[$extension]))
				{
					show_error(sprintf(lang('unable_to_load_module'), 'ext.'.$extension.'.php'));
				}
			}
		}
	}

}

/* End of file myaccount.php */
/* Location: ./system/expressionengine/controllers/cp/myaccount.php */
