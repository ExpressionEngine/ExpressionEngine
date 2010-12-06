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
 * ExpressionEngine Login Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */ 
class Login extends CI_Controller {
	
	var $username = '';		// stores username on login failure

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();	

		$this->lang->loadfile('login');
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
		$this->login_form();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Login Form
	 *
	 * @access	public
	 * @param	string
	 * @return	null
	 */	
	function login_form()
	{
		// If an ajax request ends up here the user is probably logged out
		if ($this->input->server('HTTP_X_REQUESTED_WITH') && ($this->input->server('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest'))
		{
			$this->output->set_status_header(401);
			die('C=login');
		}
		
		$this->load->helper('form');
		
		$username = $this->session->flashdata('username');

		$vars = array(
			'return_path'	=> '',
			'focus_field'	=> ($username) ? 'password' : 'username',
			'username'		=> ($username) ? form_prep($username) : '',
			'message'		=> ($this->input->get('auto_expire')) ? $this->lang->line('session_auto_timeout') : $this->session->flashdata('message')
		);
		
		if ($this->input->get('BK'))
		{
			$vars['return_path'] = base64_encode($this->input->get('BK'));
		}
		else if ($this->input->get('return'))
		{
			$vars['return_path'] = $this->input->get('return');
		}
		
		$this->cp->set_variable('return_path',		SELF);
		$this->cp->set_variable('cp_page_title',	$this->lang->line('login'));

		$this->load->view('account/login', $vars);
	}  
	
	// --------------------------------------------------------------------

	/**
	 * Authenticate user
	 *
	 * @access	public
	 * @return	mixed
	 */	
	function authenticate()
	{	
		$is_ajax = ($this->input->get_post('is_ajax')) ? TRUE : FALSE;
			
		/** ----------------------------------------
		/**  No username/password?  Bounce them...
		/** ----------------------------------------*/
	
		if ( ! $this->input->post('username'))
		{
			$this->session->set_flashdata('message', $this->lang->line('no_username'));

			if ($is_ajax)
			{
				$resp['messageType'] = 'failure';
				$resp['message'] = $this->lang->line('no_username');

				$this->output->send_ajax_response($resp); exit;				
			}
			
			$this->functions->redirect(BASE.AMP.'C=login');
		}
				
		$username = $this->input->post('username');
		$this->session->set_flashdata('username', $username);
		
		if ( ! $this->input->get_post('password'))
		{
			$this->session->set_flashdata('message', $this->lang->line('no_password'));

			if ($is_ajax)
			{
				$resp['messageType'] = 'failure';
				$resp['message'] = $this->lang->line('no_password');

				$this->output->send_ajax_response($resp); exit;				
			}
			
			$this->functions->redirect(BASE.AMP.'C=login');
		}

		/* -------------------------------------------
		/* 'login_authenticate_start' hook.
		/*  - Take control of CP authentication routine
		/*  - Added EE 1.4.2
		*/
			$edata = $this->extensions->call('login_authenticate_start');
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		/** ----------------------------------------
		/**  Is IP and User Agent required for login?
		/** ----------------------------------------*/
	
		if ($this->config->item('require_ip_for_login') == 'y')
		{
			if ($this->session->userdata['ip_address'] == '' OR $this->session->userdata['user_agent'] == '')
			{
				$this->session->set_flashdata('message', $this->lang->line('unauthorized_request'));
				$this->functions->redirect(BASE.AMP.'C=login');
			}
		}
		
		/** ----------------------------------------
		/**  Check password lockout status
		/** ----------------------------------------*/
		
		if ($this->session->check_password_lockout($username) === TRUE)
		{
			$line = $this->lang->line('password_lockout_in_effect');
		
			$line = str_replace("%x", $this->config->item('password_lockout_interval'), $line);
		
			if ($is_ajax)
			{
				$resp = array(
					'messageType'	=> 'logout'
				);

				$this->output->send_ajax_response($resp); exit;
			}
		
			$this->session->set_flashdata('message', $line);
			$this->functions->redirect(BASE.AMP.'C=login');
		}
				
		//  Fetch member data
		$this->db->select('members.password, members.unique_id, members.member_id, members.group_id, member_groups.can_access_cp');
		$this->db->where('username', $this->input->post('username'));
		$this->db->where('member_groups.site_id', $this->config->item('site_id'));
		$this->db->where('members.group_id = '.$this->db->dbprefix('member_groups.group_id'));
		
		$query = $this->db->get(array('members', 'member_groups'));
		

		//  Invalid Username
		if ($query->num_rows() == 0)
		{
			$this->session->save_password_lockout($username);
			
			$this->session->set_flashdata('message', $this->lang->line('credential_missmatch'));
			
			if ($is_ajax)
			{
				$resp['messageType'] = 'failure';
				$resp['message'] = $this->lang->line('credential_missmatch');

				$this->output->send_ajax_response($resp); exit;				
			}
			
			$this->functions->redirect(BASE.AMP.'C=login');
		}
		
		/** ----------------------------------------
		/**  Check password
		/** ----------------------------------------*/
		$this->load->helper('security');

		$password = do_hash($this->input->post('password'));
		
		if ($query->row('password') != $password)
		{
			// To enable backward compatibility with pMachine we'll test to see 
			// if the password was encrypted with MD5.  If so, we will encrypt the
			// password using SHA1 and update the member's info.
			
			$password = do_hash($this->input->post('password'), 'md5');

			if ($query->row('password') == $password)
			{
				$password = do_hash($this->input->post('password'));
				
				$this->db->set('password', $password);
				$this->db->where('member_id', $query->row('member_id'));
				$this->db->update('members');
			}
			else
			{
				/** ----------------------------------------
				/**  Invalid password
				/** ----------------------------------------*/
					
				$this->session->save_password_lockout($username);

				$this->session->set_flashdata('message', $this->lang->line('credential_missmatch'));
				
				if ($is_ajax)
				{
					$resp['messageType'] = 'failure';
					$resp['message'] = $this->lang->line('credential_missmatch');

					$this->output->send_ajax_response($resp); exit;				
				}
				
				$this->functions->redirect(BASE.AMP.'C=login');
			}
		}
		
		
		/** ----------------------------------------
		/**  Is the user banned?
		/** ----------------------------------------*/
		
		// Super Admins can't be banned
		
		if ($query->row('group_id') != 1)
		{
			if ($this->session->ban_check())
			{
				return $this->output->fatal_error($this->lang->line('not_authorized'));
			}
		}
		
		/** ----------------------------------------
		/**  Is user allowed to access the CP?
		/** ----------------------------------------*/
		
		if ($query->row('can_access_cp') != 'y')
		{
			$this->session->set_flashdata('message', $this->lang->line('not_authorized'));
			$this->functions->redirect(BASE.AMP.'C=login');
		}
		
		/** --------------------------------------------------
		/**  Do we allow multiple logins on the same account?
		/** --------------------------------------------------*/
		
		if ($this->config->item('allow_multi_logins') == 'n')
		{
			// Kill old sessions first
		
			$this->session->gc_probability = 100;
			
			$this->session->delete_old_sessions();
		
			$expire = time() - $this->session->session_length;
			
			// See if there is a current session

			$this->db->select('ip_address, user_agent');
			$this->db->where('member_id', $query->row('member_id'));
			$this->db->where('last_activity >', $expire);
			$result = $this->db->get('sessions');

			// If a session exists, trigger the error message
								
			if ($result->num_rows() == 1)
			{
				if ($this->session->userdata['ip_address'] != $result->row('ip_address')  OR 
					$this->session->userdata['user_agent'] != $result->row('user_agent')  )
				{
					$this->session->set_flashdata('message', $this->lang->line('multi_login_warning'));
					$this->functions->redirect(BASE.AMP.'C=login');
				}				
			} 
		}  
		
		/** ----------------------------------------
		/**  Is the UN/PW the correct length?
		/** ----------------------------------------*/
		
		// If the admin has specfified a minimum username or password length that
		// is longer than the current users's data we'll have them update their info.
		// This will only be an issue if the admin has changed the un/password requiremements
		// after member accounts already exist.
		
		$uml = $this->config->item('un_min_len');
		$pml = $this->config->item('pw_min_len');
		
		$ulen = strlen($this->input->post('username'));
		$plen = strlen($this->input->post('password'));
		
		if ($ulen < $uml OR $plen < $pml)
		{
			return $this->_un_pw_update_form();
		}
		
		/** ----------------------------------------
		/**  Set cookies
		/** ----------------------------------------*/
		
		// Set cookie expiration to one year if the "remember me" button is clicked
		$expire = ( ! isset($_POST['remember_me'])) ? '0' : 60*60*24*365;
		
		if ($this->config->item('admin_session_type') != 's')
		{
			$this->functions->set_cookie($this->session->c_expire , time()+$expire, $expire);
			$this->functions->set_cookie($this->session->c_uniqueid , $query->row('unique_id') , $expire);		
			$this->functions->set_cookie($this->session->c_password , $password,  $expire);	
			$this->functions->set_cookie($this->session->c_anon , 1,  $expire);
		}
		
		if ( isset($_POST['site_id']) && is_numeric($_POST['site_id']))
		{
			$this->functions->set_cookie('cp_last_site_id', $this->input->post('site_id'), 0);
		}
		
		/** ----------------------------------------
		/**  Create a new session
		/** ----------------------------------------*/
		$session_id = $this->session->create_new_session($query->row('member_id') , TRUE);

		/* -------------------------------------------
		/* 'cp_member_login' hook.
		/*  - Additional processing when a member is logging into CP
		*/
			$edata = $this->extensions->call('cp_member_login', $query->row());
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
			
		/** ----------------------------------------
		/**  Log the login
		/** ----------------------------------------*/
		
		// We'll manually add the username to the Session array so
		// the LOG class can use it.
		$this->session->userdata['username']  = $this->input->post('username');
		
		$this->logger->log_action($this->lang->line('member_logged_in'));
		
		/** ----------------------------------------
		/**  Delete old password lockouts
		/** ----------------------------------------*/
		
		$this->session->delete_password_lockout();

		/** ----------------------------------------
		/**  Redirect the user to the CP home page
		/** ----------------------------------------*/
		
		if ($this->input->post('return_path'))
		{
			$return_path = BASE.AMP.base64_decode($this->input->post('return_path'));
		}
		else
		{
			$return_path = BASE.AMP.'C=homepage';
		}
		
		if ($is_ajax)
		{
			$resp = array(
				'xid'			=> XID_SECURE_HASH,
				'session_id'	=> $this->session->sdata['session_id'],
				'messageType'	=> 'success',
				'message'		=> $this->lang->line('logged_back_in')
			);
			
			$this->output->send_ajax_response($resp); exit;
		}

		$this->functions->redirect($return_path);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Username/password update form
	 *
	 * This form gets shown if a user tries to log-in after the admin has
	 * changed the length requirements for a username or password.  It
	 * permits the user to update their un/pw so it conforms to the new
	 * requirements
	 *
	 * @access	private
	 * @param	string
	 * @return	mixed
	 */
	function _un_pw_update_form($message = '')
	{
		$this->lang->loadfile('member');
		$this->load->helper('security');
				
		$vars['cp_page_title'] = $this->lang->line('login');
		
		$uml = $this->config->item('un_min_len');
		$pml = $this->config->item('pw_min_len');
		
		$ulen = strlen($this->input->post('username'));
		$plen = strlen($this->input->post('password'));
	
		$vars['message'] = array();
		
		$vars['message'][] = $message;
		
		$vars['message'][] = $this->lang->line('access_notice');
		$vars['username'] = $this->input->post('username');
		$vars['new_username_required'] = FALSE;
		$vars['new_username'] = ($this->input->get_post('new_username') !== FALSE) ? $this->input->get_post('new_username') : '';
		$vars['password'] = $this->input->post('password');
		$vars['new_password_required'] = FALSE;
		$vars['new_password'] = ($this->input->get_post('new_password') !== FALSE) ? $this->input->get_post('new_password') : '';	
		
		$vars['hidden'] = array(
			'username'	=> $this->input->post('username'),
			'password'	=> base64_encode($this->input->post('password'))
		);
		
		if ($ulen < $uml)
		{
			$vars['new_username_required'] = TRUE;
			$vars['notices']['un_len'] = sprintf($this->lang->line('un_len'), $uml);
			$vars['notices']['yun_len'] = sprintf($this->lang->line('yun_len'), $ulen);
		}
		
		if ($plen < $pml)
		{
			$vars['new_password_required'] = TRUE;
			$vars['notices']['pw_len'] = sprintf($this->lang->line('pw_len'), $pml);
			$vars['notices']['ypw_len'] = sprintf($this->lang->line('ypw_len'), $plen);
		}
		
		$this->load->view('account/update_un_pw', $vars);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Update the username/password
	 *
	 * This function performs the update once the update form is submitted
	 *
	 * @access	public
	 * @return	mixed
	 */
	function update_un_pw()
	{
		$this->lang->loadfile('member');
		
		$missing = FALSE;
		
		if ( ! isset($_POST['new_username']) AND  ! isset($_POST['new_password']))
		{
			$missing = TRUE;
		}
			
		if ($missing === TRUE)
		{
			return $this->_un_pw_update_form($this->lang->line('all_fields_required'));
		}
		
		/** ----------------------------------------
		/**  Check password lockout status
		/** ----------------------------------------*/
		
		if ($this->session->check_password_lockout($this->input->post('username')) === TRUE)
		{		
			$line = str_replace("%x", $this->config->item('password_lockout_interval'), $this->lang->line('password_lockout_in_effect'));	
				
			return $this->_un_pw_update_form($line);
		}
						
		/** ----------------------------------------
		/**  Fetch member data
		/** ----------------------------------------*/
		
		$this->db->select('member_id, group_id');
		$this->db->where('username', $this->input->post('username'));
		$this->db->where('password', do_hash(base64_decode($this->input->post('password'))));
		$query = $this->db->get('members');
			
		$member_id = $query->row('member_id') ;
			
		/** ----------------------------------------
		/**  Invalid Username or Password
		/** ----------------------------------------*/
		if ($query->num_rows() == 0)
		{
			$this->session->save_password_lockout($this->input->post('username'));
			return $this->_un_pw_update_form($this->lang->line('invalid_existing_un_pw'));
		}
		
		/** ----------------------------------------
		/**  Is the user banned?
		/** ----------------------------------------*/
		
		// Super Admins can't be banned
		
		if ($query->row('group_id')  != 1)
		{
			if ($this->session->ban_check())
			{
				return $this->output->fatal_error($this->lang->line('not_authorized'));
			}
		}
				
		/** -------------------------------------
		/**  Instantiate validation class
		/** -------------------------------------*/

		if ( ! class_exists('EE_Validate'))
		{
			require APPPATH.'libraries/Validate'.EXT;
		}
		
		$new_un  = ($this->input->post('new_username')) ? $this->input->post('new_username') : '';
		$new_pw  = ($this->input->post('new_password')) ? $this->input->post('new_password') : '';
		$new_pwc = ($this->input->post('new_password_confirm')) ? $this->input->post('new_password_confirm') : '';
			
		$VAL = new EE_Validate(
								array( 
										'val_type'			=> 'new',
										'fetch_lang' 		=> TRUE, 
										'require_cpw' 		=> FALSE,
									 	'enable_log'		=> FALSE,
										'username'			=> $new_un,
										'password'			=> $new_pw,
									 	'password_confirm'	=> $new_pwc,
									 	'cur_password'		=> $this->input->post('password')
									 )
							);
		
		if ($this->input->post('new_username') && $this->input->post('new_username') != '')
		{
			if ($this->input->post('username') == $new_un)
			{
				$un_exists = FALSE;
			}
			else
			{
				$un_exists = TRUE;
			}			
		}
		
		$pw_exists = (isset($_POST['new_password']) AND $_POST['new_password'] != '') ? TRUE : FALSE;
				
		if ($un_exists)
		{
			$VAL->validate_username();			
		}
		if ($pw_exists)
		{
			$VAL->validate_password();			
		}
		
		/** -------------------------------------
		/**  Display error is there are any
		/** -------------------------------------*/
		 if (count($VAL->errors) > 0)
		 {
		 	$er = '';
		 	
		 	foreach ($VAL->errors as $val)
		 	{
		 		$er .= $val.BR;
		 	}
		
			return $this->_un_pw_update_form($er);
		 }
		 
		 
		if ($un_exists)
		{
			$this->db->set('username', $this->input->post('new_username'));
			$this->db->where('member_id', $member_id);
			$this->db->update('members');
		}	
						
		if ($pw_exists)
		{
			$this->load->helper('security');
			$this->db->set('password', do_hash($this->input->post('new_password')));
			$this->db->where('member_id', $member_id);
			$this->db->update('members');
		}
		
		$this->session->set_flashdata('message', $this->lang->line('unpw_updated'));
		$this->functions->redirect(BASE.AMP.'C=login'.AMP.'M=login_form');			
	}
	
	// --------------------------------------------------------------------

	/**
	 * Log-out
	 *
	 * @access	public
	 * @return	null
	 */
	function logout()
	{
		if ($this->session->userdata('group_id') == 3) 
		{
			$this->functions->redirect(SELF);
		}

		$this->db->where('ip_address', $this->input->ip_address());
		$this->db->where('member_id', $this->session->userdata('member_id'));
		$this->db->delete('online_users');

		$this->db->where('session_id', $this->session->userdata['session_id']);
		$this->db->delete('sessions');
				
		$this->functions->set_cookie($this->session->c_uniqueid);		
		$this->functions->set_cookie($this->session->c_password);	
		$this->functions->set_cookie($this->session->c_session);	
		$this->functions->set_cookie($this->session->c_expire);	
		$this->functions->set_cookie($this->session->c_anon);
		$this->functions->set_cookie('read_topics');  
		$this->functions->set_cookie('tracker');  

		$this->logger->log_action($this->lang->line('member_logged_out'));

		if ($this->input->get('auto_expire'))
		{
			$this->functions->redirect(BASE.AMP.'C=login&auto_expire=true');
		}


		/* -------------------------------------------
		/* 'cp_member_logout' hook.
		/*  - Perform additional actions after logout
		/*  - Added EE 1.6.1
		*/
			$edata = $this->extensions->call('cp_member_logout');
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		$this->functions->redirect(SELF);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Forgotten password form
	 *
	 * @access	public
	 * @param	string
	 * @return	mixed
	 */
	function forgotten_password_form()
	{
		$this->load->helper('form');
		$message = $this->session->flashdata('message');
		
		$variables = array(	'email'			=> ( ! $this->input->post('email')) ? '' : $this->input->get_post('email'),
							'message' 		=> $message,
							'cp_page_title'	=> $this->lang->line('forgotten_password')
						  );
						  
		$this->load->view('account/forgot_password', $variables);
	}  
	
	// --------------------------------------------------------------------

	/**
	 * Request a forgotten password
	 *
	 * Accepts an email address as input, which gets looked up in the DB.
	 * An email is sent to the user with a confirmation link
	 *
	 * @access	public
	 * @return	mixed
	 */
	function request_new_password()
	{
		if ( ! $address = $this->input->post('email'))
		{
			$this->functions->redirect(BASE.AMP.'C=login'.AMP.'M=forgotten_password_form');
		}
		
		$this->cp->set_variable('cp_page_title', $this->lang->line('new_password_request'));
		
		$address = strip_tags($address);
		
		// Fetch user data
		
		$this->db->select('member_id, username');
		$this->db->where('email', $address);
		$query = $this->db->get('members');
		
		if ($query->num_rows() == 0)
		{
			$this->session->set_flashdata('message', $this->lang->line('no_email_found'));
			$this->functions->redirect(BASE.AMP.'C=login'.AMP.'M=forgotten_password_form');
		}
		
		$member_id = $query->row('member_id') ;
		$username  = $query->row('username') ;
		
		// Kill old data from the reset_password field
		
		$time = time() - (60*60*24);
		
		$this->db->where('date <', $time);
		$this->db->or_where('member_id', $member_id);
		$this->db->delete('reset_password');
		
		// Create a new DB record with the temporary reset code
		
		$rand = $this->functions->random('alnum', 8);
				
		$data = array('member_id' => $member_id, 'resetcode' => $rand, 'date' => time());
		 
		$this->db->query($this->db->insert_string('exp_reset_password', $data));
		
		// Buid the email message
		$swap = array(
						'name'		=> $username,
						'reset_url'	=> $this->config->item('cp_url')."?D=cp&C=login&M=reset_password&id=".$rand,
						'site_name'	=> stripslashes($this->config->item('site_name')),
						'site_url'	=> $this->config->item('site_url')
					 );
					
		$template = $this->functions->fetch_email_template('forgot_password_instructions');
		$message_title = $this->_var_swap($template['title'], $swap);
		$message = $this->_var_swap($template['data'], $swap);


		// Instantiate the email class
			 
		$this->load->library('email');
		$this->email->wordwrap = true;
		$this->email->from($this->config->item('webmaster_email'), $this->config->item('webmaster_name'));	
		$this->email->to($address); 
		$this->email->subject($message_title);	
		$this->email->message($message);	

		$vars['message_success'] = '';
		$vars['message_error'] = '';

		if ( ! $this->email->send())
		{
			$vars['message_error'] = $this->lang->line('error_sending_email');
		} 
		else 
		{	
			$vars['message_success'] = $this->lang->line('forgotten_email_sent');
		}

		$this->load->view('account/request_new_password', $vars);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Rest the password
	 *
	 * This function is called when a user clicks the confirmation link
	 * in the email they are sent when they request a new password
	 *
	 * @access	public
	 * @param	string
	 * @return	mixed
	 */
	function reset_password()
	{
		if ( ! $id = $this->input->get('id'))
		{
			$this->functions->redirect(BASE.AMP.'C=login');
		}
		
		$time = time() - (60*60*24);
					
		// Get the member ID from the reset_password field	
		
		$this->db->select('member_id');
		$this->db->where('resetcode', $id);
		$this->db->where('date >', $time);
		$query = $this->db->get('reset_password');
		
		if ($query->num_rows() == 0)
		{
			$this->functions->redirect(BASE.AMP.'C=login');
		}
		
		$member_id = $query->row('member_id') ;
				
		// Fetch the user data
		
		$this->db->select('username, email');
		$this->db->where('member_id', $member_id);
		$query = $this->db->get('members');
		
		if ($query->num_rows() == 0)
		{
			$this->functions->redirect(BASE.AMP.'C=login');
		}
		
		$address	= $query->row('email') ;
		$username  = $query->row('username') ;
				
		$rand = $this->functions->random('alnum', 8);
		
		// Update member's password
		$this->load->helper('security');
		$this->db->set('password', do_hash($rand));
		$this->db->where('member_id', $member_id);
		$this->db->update('members');
		
		// Kill old data from the reset_password field
		
		$this->db->where('date <', $time);
		$this->db->or_where('member_id', $member_id);
		$this->db->delete('reset_password');
				
		// Buid the email message
		$swap = array(
						'name'		=> $username,
						'username'	=> $username,
						'password'	=> $rand,
						'site_name'	=> stripslashes($this->config->item('site_name')),
						'site_url'	=> $this->config->item('site_url')
					 );


		$template = $this->functions->fetch_email_template('reset_password_notification');
		$message_title = $this->_var_swap($template['title'], $swap);
		$message = $this->_var_swap($template['data'], $swap);					
		 
		// Instantiate the email class
			 
		$this->load->library('email');
		$this->email->wordwrap = true;
		$this->email->from($this->config->item('webmaster_email'), $this->config->item('webmaster_name'));
		$this->email->to($address); 
		$this->email->subject($message_title);	
		$this->email->message($message);	
		
		if ( ! $this->email->send())
		{
			$res = $this->lang->line('error_sending_email');
		} 
		else
		{	
			$res = $this->lang->line('password_has_been_reset');
		}
		
		$this->session->set_flashdata('message', $res);
		$this->functions->redirect(BASE.AMP.'C=login');
	}
	
	// --------------------------------------------------------------------
	
	/**
	*  Replace variables
	*/
	function _var_swap($str, $data)
	{
		if ( ! is_array($data))
		{
			return FALSE;
		}

		foreach ($data as $key => $val)
		{
			$str = str_replace('{'.$key.'}', $val, $str);
		}

		return $str;
	}

	// --------------------------------------------------------------------
	
	/**
	 *	Refresh XID
	 *
	 *	This method is hit by users who are logged in and using cookies only
	 *	As their session type.  we'll silently refresh their XIDs in the background
	 *	Instead of forcing them to log back in each time.
	 *	This method will keep the user logged in indefinitely, as the session type is
	 *	already set to cookies.  If we didn't do this, they would simply be redirected to 
	 *	the control panel home page.
	 *			
	 */
	function refresh_xid()
	{
		// the only way we will be hitting this is through an ajax request.
		// Any other way is monkeying with URLs.  I have no patience for URL monkiers.
		if ( ! AJAX_REQUEST)
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		// No CP access?  No reason to be here.  buh bye
		if ( ! isset($this->session->userdata['can_access_cp']) OR $this->session->userdata['can_access_cp'] != 'y')
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$resp = array(
			'xid'	=> XID_SECURE_HASH
		);
		
		$this->output->send_ajax_response($resp);
	}
}
// END CLASS

/* End of file login.php */
/* Location: ./system/expressionengine/controllers/cp/login.php */