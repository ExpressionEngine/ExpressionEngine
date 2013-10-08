<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Login extends CP_Controller {

	var $username = '';		// stores username on login failure

	/**
	 * Constructor
	 */
	public function __construct()
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
	public function index()
	{
		// We don't want to allow access to the login screen to someone
		// who is already logged in.
		if ($this->session->userdata('member_id') !== 0)
		{
			return $this->functions->redirect(BASE);
		}

		// If an ajax request ends up here the user is probably logged out
		if (AJAX_REQUEST)
		{
			//header('X-EERedirect: C=login');
			header('X-EEBroadcast: modal');
			die('Logged out');
		}

		$username = $this->session->flashdata('username');

		$this->view->return_path = '';
		$this->view->focus_field = ($username) ? 'password' : 'username';
		$this->view->username = ($username) ? form_prep($username) : '';
		$this->view->message = ($this->input->get('auto_expire')) ? lang('session_auto_timeout') : $this->session->flashdata('message');

		if ($this->input->get('BK'))
		{
			$this->view->return_path = base64_encode($this->input->get('BK'));
		}
		else if ($this->input->get('return'))
		{
			$this->view->return_path = $this->input->get('return');
		}

		$this->view->cp_page_title = lang('login');

		$this->view->render('account/login');
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

		if (isset($_POST['remember_me']))
		{
			$incoming->remember_me();
		}

		if (is_numeric($this->input->post('site_id')))
		{
			$this->functions->set_cookie('cp_last_site_id', $this->input->post('site_id'), 0);
		}

		$incoming->start_session(TRUE);

		// Redirect the user to the CP home page
		// ----------------------------------------------------------------

		$base = BASE;

		if ($this->config->item('admin_session_type') == 's')
		{
			$base = preg_replace('/S=\d+/', 'S='.$incoming->session_id(), BASE);
		}
		elseif ($this->config->item('admin_session_type') == 'cs')
		{
			$base = preg_replace('/S=\d+/', 'S='.$this->session->userdata['fingerprint'], BASE);
		}

		$return_path = $base.AMP.'C=homepage';

		if ($this->input->post('return_path'))
		{
			$return_path = $base.AMP.base64_decode($this->input->post('return_path'));
		}

		if (AJAX_REQUEST)
		{
			$this->output->send_ajax_response(array(
				'base'			=> $base,
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
	public function _un_pw_update_form($message = '')
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
	public function update_un_pw()
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
	public function logout()
	{
		if ($this->session->userdata('group_id') == 3)
		{
			$this->functions->redirect(BASE.AMP.'C=login');
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
			$this->extensions->call('cp_member_logout');
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		$this->functions->redirect(BASE.AMP.'C=login');
	}

	// --------------------------------------------------------------------

	/**
	 * Forgotten password form
	 *
	 * Present a form to the user asking them for the e-mail address associated
	 * with the account they are attempting to log in to.  If an e-mail address
	 * is found in post, then the form will be populated with said e-mail.  Loads
	 * the account/forgot_password view.
	 *
	 * @access	public
	 * @return	null
	 */
	public function forgotten_password_form()
	{
		if ($this->session->userdata('member_id') !== 0)
		{
			return $this->functions->redirect(BASE);
		}

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
	 * An email is sent to the user with a confirmation link, provided
	 * the email is found in the database and is attached to a valid
	 * member with access to the CP.
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function send_reset_token()
	{
		if ($this->session->userdata('member_id') !== 0)
		{
			return $this->functions->redirect(BASE);
		}

		if ( ! $address = $this->input->post('email'))
		{
			$this->functions->redirect(BASE.AMP.'C=login'.AMP.'M=forgotten_password_form');
		}

		$address = strip_tags($address);

		// Fetch user data
		$this->db->select('member_id, username, screen_name');
		$this->db->where('email', $address);
		$query = $this->db->get('members');

		// Show a success email even if the email doesn't exist so spammers
		// don't know if an email exists or not
		if ($query->num_rows() == 0)
		{
			$vars['message_success'] = lang('forgotten_email_sent');
			return $this->load->view('account/show_reset_password_message', $vars);
		}

		$member_id = $query->row('member_id');
		$name  = ($query->row('screen_name') == '') ? $query->row('username') : $query->row('screen_name');

		// Clean out any old reset codes.
		$a_day_ago = time() - (60*60*24);
		$this->db->where('date <', $a_day_ago);
		$this->db->or_where('member_id', $member_id);
		$this->db->delete('reset_password');

		// Create a new DB record with the temporary reset code
		$rand = $this->functions->random('alnum', 8);
		$data = array('member_id' => $member_id, 'resetcode' => $rand, 'date' => time());
		$this->db->query($this->db->insert_string('exp_reset_password', $data));

		// Buid the email message
		$swap = array(
			'name'		=> $name,
			'reset_url'	=> reduce_double_slashes($this->config->item('cp_url')."?S=0&D=cp&C=login&M=reset_password&resetcode=".$rand),
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

		$vars['cp_page_title'] = lang('forgotten_password');
		$this->load->view('account/show_reset_password_message', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Reset Password
	 *
	 * This function is called when a user clicks the confirmation link
	 * in the email they are sent when they request a new password.  Needs
	 * to have the resetcode in the $_GET array, otherwise it will redirect
	 * the user to the login page.
	 * 	It presents the user with a form to enter and confirm a new password in.
	 * Submission of the form takes the user back here, where the $_POST data
	 * is validated and, if valid, the user's password is reset.  They are then
	 * presented with a success page and a link back to login.
	 *
	 * @access	public
	 * @param	string
	 * @return	mixed
	 */
	public function reset_password()
	{
		if ($this->session->userdata('member_id') !== 0)
		{
			return $this->functions->redirect(BASE);
		}

		if ($this->session->userdata('is_banned') === TRUE)
		{
			return show_error(lang('unauthorized_request'));
		}

		// Side note 'get_post' actually means 'fetch from post or get'.  It
		// will check both.  Yes, that's a terribly obfuscating method name.
		// In any case, the resetcode could be in either post or get, so
		// check both.  If we don't find it, send them away, quietly.
		if( ! ($resetcode = $this->input->get_post('resetcode')))
		{
			return $this->functions->redirect(BASE . AMP . 'c=login');
		}

		// Validate their reset code.  Make sure it matches a valid
		// member.
		$a_day_ago = time() - (60*60*24);
		$member_id_query = $this->db->select('member_id')
			->where('resetcode', $resetcode)
			->where('date >', $a_day_ago)
			->get('reset_password');


		// If we don't find a valid token, then they
		// shouldn't be here.  Show em an error.
		if ($member_id_query->num_rows() === 0)
		{
			return show_error(lang('id_not_found'));
		}

		$member_id = $member_id_query->row('member_id');

		if ( ! empty($_POST))
		{
			$this->load->library('form_validation');
			$this->lang->loadfile('myaccount');

			// Put username into $_POST for valid_password validation
			$_POST['username'] = $this->db->select('username')
				->where('member_id', $member_id)
				->get('members')
				->row('username');

			$this->form_validation->set_rules('password', 'lang:new_password', 'valid_password|required');
			$this->form_validation->set_rules('password_confirm', 'lang:new_password_confirm', 'matches[password]|required');

			if($this->form_validation->run() !== FALSE)
			{
				// Update the member row with the new password.
				$this->load->library('auth');
				$this->auth->update_password(
					$member_id,
					$this->input->post('password')
				);

				// Invalidate the old token.  While we're at it, may as well wipe out expired
				// tokens too, just to keep them from building up.
				$this->db->where('date <', $a_day_ago)
					->or_where('member_id', $member_id)
					->delete('reset_password');

				// Show them a success message with a link back to the login
				// screen, the only place they could have come from.
				$vars = array(
					'message_success' => lang('successfully_changed_password'),
					'cp_page_title' => lang('password_changed')
				);

				return $this->load->view('account/show_reset_password_message', $vars);
			}
		}

		// Show them the form.  If we have any errors from an attempt
		// to validate a previous submission, then they will be
		// shown using "form_error()" which has access to them through
		// the form_validation singleton.
		$message = $this->session->flashdata('message');
		$variables = array(
			'resetcode'			=> $resetcode,
			'password'			=> '',
			'password_confirm'  => '',
			'message' 		=> $message,
			'cp_page_title'	=> lang('enter_new_password')
		);
		$this->load->view('account/reset_password', $variables);
	}

	// --------------------------------------------------------------------

	/**
	 *  Replace variables
	 */
	private function _var_swap($str, $data)
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
	public function refresh_xid()
	{
		// the only way we will be hitting this is through an ajax request.
		// Any other way is monkeying with URLs.  I have no patience for URL monkiers.
		if ( ! AJAX_REQUEST OR ! $this->cp->allowed_group('can_access_cp'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->output->send_ajax_response(array(
			'xid'	  => XID_SECURE_HASH,
			'message' => 'refresh'
		));
	}

	// --------------------------------------------------------------------

	/**
	 *	Return to login
	 *
	 * Helper function to send them to a login error screen
	 */
	private function _return_to_login($lang_key)
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
