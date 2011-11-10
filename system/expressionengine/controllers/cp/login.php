<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
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

		$this->load->library('auth');
		$this->lang->loadfile('login');
	}
	
	// --------------------------------------------------------------------

	/**
	 * Main login form
	 *
	 * @access	public
	 * @return	void
	 */	
	function index()
	{
		// If an ajax request ends up here the user is probably logged out
		if (AJAX_REQUEST)
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
			'message'		=> ($this->input->get('auto_expire')) ? lang('session_auto_timeout') : $this->session->flashdata('message')
		);
		
		if ($this->input->get('BK'))
		{
			$vars['return_path'] = base64_encode($this->input->get('BK'));
		}
		else if ($this->input->get('return'))
		{
			$vars['return_path'] = $this->input->get('return');
		}
		
		$this->cp->set_variable('return_path', SELF);
		$this->cp->set_variable('cp_page_title', lang('login'));

		$this->load->view('account/login', $vars);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Login Form
	 *
	 * This method boggles the mind. Deprecated!
	 *
	 * @return	void
	 */	
	public function login_form()
	{
		$this->index();
	}  
	
	// --------------------------------------------------------------------

	/**
	 * Authenticate user
	 *
	 * @return	mixed
	 */	
	public function authenticate()
	{
		// Run through basic verifications: authenticate, username and 
		// password both exist, not banned, IP checking is okay, run hook
		if ( ! ($verify_result = $this->auth->verify()))
		{
			// In the event it's a string, send it to return to login
			$this->_return_to_login(implode(', ', $this->auth->errors));
		}
		list($username, $password, $incoming) = $verify_result;
		$member_id = $incoming->member('member_id');
		
		
		// Is the UN/PW the correct length?
		// ----------------------------------------------------------------
		
		// If the admin has specfified a minimum username or password length that
		// is longer than the current users's data we'll have them update their info.
		// This will only be an issue if the admin has changed the un/password requiremements
		// after member accounts already exist.
		
		$uml = $this->config->item('un_min_len');
		$pml = $this->config->item('pw_min_len');
		
		$ulen = strlen($username);
		$plen = strlen($password);
		
		if ($ulen < $uml OR $plen < $pml)
		{
			return $this->_un_pw_update_form();
		}
		
		
		// Set cookies and start session
		// ----------------------------------------------------------------
		
		// Kill existing flash cookie
		$this->functions->set_cookie('flash');
		
		// "Remember Me" is one year
		if (isset($_POST['remember_me']))
		{
			$incoming->remember_me(60*60*24*365);
		}
		
		if (is_numeric($this->input->post('site_id')))
		{
			$this->functions->set_cookie('cp_last_site_id', $this->input->post('site_id'), 0);
		}
		
		$incoming->start_session(TRUE);

		// Redirect the user to the CP home page
		// ----------------------------------------------------------------
		
		$base = BASE;

		if ($this->config->item('admin_session_type') != 'c')
		{
			$base = preg_replace('/S=\d+/', 'S='.$incoming->session_id(), BASE);
		}

		$return_path = $base.AMP.'C=homepage';
		
		if ($this->input->post('return_path'))
		{
			$return_path = $base.AMP.base64_decode($this->input->post('return_path'));
		}
		
		if (AJAX_REQUEST)
		{
			$this->output->send_ajax_response(array(
				'xid'			=> XID_SECURE_HASH,
				'session_id'	=> $incoming->session_id(),
				'messageType'	=> 'success',
				'message'		=> lang('logged_back_in')
			));
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

		$uml = $this->config->item('un_min_len');
		$pml = $this->config->item('pw_min_len');
		
		$ulen = strlen($this->input->post('username'));
		$plen = strlen($this->input->post('password'));
		
		$new_un = ($this->input->get_post('new_username') !== FALSE) ? $this->input->get_post('new_username') : '';
		$new_pw = ($this->input->get_post('new_password') !== FALSE) ? $this->input->get_post('new_password') : '';

		$data = array(
			'cp_page_title'	=> lang('login'),
			'message'		=> array(
				$message,
				lang('access_notice')
			),
			'username'		=> $this->input->post('username'),
			'new_username_required'	=> FALSE,
			'new_username'	=> $new_un,
			'password'		=> $this->input->post('password'),
			'new_password_required'	=> FALSE,
			'new_password'	=> $new_pw,
			'hidden'		=> array(
				'username'	=> $this->input->post('username'),
				'password'	=> base64_encode($this->input->post('password'))
			)
		);	
					
		if ($ulen < $uml)
		{
			$data['new_username_required'] = TRUE;
			$data['notices']['un_len'] = sprintf(lang('un_len'), $uml);
			$data['notices']['yun_len'] = sprintf(lang('yun_len'), $ulen);
		}
		
		if ($plen < $pml)
		{
			$data['new_password_required'] = TRUE;
			$data['notices']['pw_len'] = sprintf(lang('pw_len'), $pml);
			$data['notices']['ypw_len'] = sprintf(lang('ypw_len'), $plen);
		}
		
		$this->load->view('account/update_un_pw', $data);
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
			return $this->_un_pw_update_form(lang('all_fields_required'));
		}
		
		// Run through basic verifications: authenticate, username and 
		// password both exist, not banned, IP checking is okay
		if ( ! ($verify_result = $this->auth->verify()))
		{
			// In the event it's a string, send it to return to login
			$this->_return_to_login(implode(', ', $this->auth->errors));
		}
		list($username, $password, $incoming) = $verify_result;
		$member_id = $incoming->member('member_id');
		
		$new_un  = (string) $this->input->post('new_username');
		$new_pw  = (string) $this->input->post('new_password');
		$new_pwc = (string) $this->input->post('new_password_confirm');
		
		// Make sure validation library is available
		if ( ! class_exists('EE_Validate'))
		{
			require APPPATH.'libraries/Validate.php';
		}
		
		// Load it up with the information needed
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
		
		$un_exists = FALSE;
		
		if ($new_un !== '')
		{
			$un_exists = ($this->input->post('username') === $new_un) ? FALSE : TRUE;
		}
		
		$pw_exists = ($new_pw !== '' AND $new_pwc !== '') ? TRUE : FALSE;
		
		if ($un_exists)
		{
			$VAL->validate_username();
		}

		if ($pw_exists)
		{
			$VAL->validate_password();
		}
		
		// Display error is there are any
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
			$this->auth->update_username($member_id, $new_un);
		}
		
		if ($pw_exists)
		{
			$this->auth->update_password($member_id, $new_pw);
		}
		
		// Send them back to login with updated username and password
		$this->session->set_flashdata('message', lang('unpw_updated'));
		$this->functions->redirect(BASE.AMP.'C=login');
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

		$this->session->destroy();
		
		$this->functions->set_cookie('read_topics');  

		$this->logger->log_action(lang('member_logged_out'));

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
		
		$variables = array(
			'email'			=> ( ! $this->input->post('email')) ? '' : $this->input->get_post('email'),
			'message' 		=> $message,
			'cp_page_title'	=> lang('forgotten_password')
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
		
		$this->cp->set_variable('cp_page_title', lang('new_password_request'));
		
		$address = strip_tags($address);
		
		// Fetch user data
		
		$this->db->select('member_id, username');
		$this->db->where('email', $address);
		$query = $this->db->get('members');
		
		if ($query->num_rows() == 0)
		{
			$this->session->set_flashdata('message', lang('no_email_found'));
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
			$vars['message_error'] = lang('error_sending_email');
		} 
		else 
		{	
			$vars['message_success'] = lang('forgotten_email_sent');
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

		$this->load->library('auth');
		
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
		
		$member_id = $query->row('member_id');
				
		// Fetch the user data
		
		$this->db->select('username, email');
		$this->db->where('member_id', $member_id);
		$query = $this->db->get('members');
		
		if ($query->num_rows() == 0)
		{
			$this->functions->redirect(BASE.AMP.'C=login');
		}
		
		$address  = $query->row('email');
		$username = $query->row('username');
		
		
		$len = $this->config->item('pw_min_len');
		
		if ($len < 8)
		{
			$len = 8;
		}
		
		$rand = $this->functions->random('alnum', $len);
		
		// add one of each character we require
		if ($this->config->item('require_secure_passwords') == 'y')
		{
			$alpha = range('a', 'z');
			$number = rand(0, 9);
			
			shuffle($alpha);
			
			$rand .= $number.$alpha[0].strtoupper($alpha[1]);
		}
		
		
		
		// Update member's password
		$update = $this->auth->update_password($member_id, $rand);
		
		if (FALSE === $update)
		{
			show_error(lang('unauthorized_access'));
		}
		
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
		$this->email->wordwrap = TRUE;
		$this->email->from($this->config->item('webmaster_email'), $this->config->item('webmaster_name'));
		$this->email->to($address); 
		$this->email->subject($message_title);	
		$this->email->message($message);	
		
		if ( ! $this->email->send())
		{
			$res = lang('error_sending_email');
		} 
		else
		{	
			$res = lang('password_has_been_reset');
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
		if ( ! AJAX_REQUEST OR ! $this->cp->allowed_group('can_access_cp'))
		{
			show_error(lang('unauthorized_access'));
		}
		
		$this->output->send_ajax_response(array(
			'xid'	=> XID_SECURE_HASH
		));
	}
	
	// --------------------------------------------------------------------
	
	/**
	 *	Return to login
	 *
	 * Helper function to send them to a login error screen
	 */
	function _return_to_login($lang_key)
	{
		if (AJAX_REQUEST)
		{
			$this->output->send_ajax_response(array(
				'messageType'	=> 'failure',
				'message'		=> lang($lang_key)
			));
		}
		
		$this->session->set_flashdata('message', lang($lang_key));
		$this->functions->redirect(BASE.AMP.'C=login');
	}
}
// END CLASS

/* End of file login.php */
/* Location: ./system/expressionengine/controllers/cp/login.php */