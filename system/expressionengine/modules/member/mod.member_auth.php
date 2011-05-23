<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

// --------------------------------------------------------------------

/**
 * Member Management Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Member_auth extends Member {

	/**
	 * Login Page
	 *
	 * @param 	string 	number of pages to return back to in the 
	 *					exp_tracker cookie
	 */
	function profile_login_form($return = '-2')
	{
		$login_form = $this->_load_element('login_form');

		if ($this->EE->config->item('user_session_type') != 'c')
		{
			$login_form = $this->_deny_if('auto_login', $login_form);
		}
		else
		{
			$login_form = $this->_allow_if('auto_login', $login_form);
		}

		// match {form_declaration} or {form_declaration return="foo"}
		// [0] => {form_declaration return="foo"}
		// [1] => form_declaration return="foo"
		// [2] =>  return="foo"
		// [3] => "
		// [4] => foo
		preg_match("/".LD."(form_declaration"."(\s+return\s*=\s*(\042|\047)([^\\3]*?)\\3)?)".RD."/s",
					$login_form, $match);

		if (empty($match))
		{
			// don't even return the login template because the form will not work since
			// the template does not contain a {form_declaration}
			return;
		}

		$data['hidden_fields']['ACT']	= $this->EE->functions->fetch_action_id('Member', 'member_login');

		if (isset($match['4']))
		{
			$data['hidden_fields']['RET'] = (substr($match['4'], 0, 4) !== 'http') ? $this->EE->functions->create_url($match['4']) : $match['4'];
		}
		elseif ($this->in_forum == TRUE)
		{
			$data['hidden_fields']['RET'] = $this->forum_path;
		}
		else
		{
			$data['hidden_fields']['RET'] = ($return == 'self') ? $this->_member_path($this->request.'/'.$this->cur_id) : $return;
		}

		$data['hidden_fields']['FROM'] = ($this->in_forum === TRUE) ? 'forum' : '';
		$data['id']	  = 'member_login_form';

		$this->_set_page_title(lang('member_login'));

		return $this->_var_swap($login_form, array(
					$match['1'] => $this->EE->functions->form_declaration($data)));
	}

	// --------------------------------------------------------------------

	/**
	 * Member Login
	 */
	public function member_login()
	{
		$this->EE->load->library('auth');

		/* -------------------------------------------
		/* 'member_member_login_start' hook.
		/*  - Take control of member login routine
		/*  - Added EE 1.4.2
		*/
			$edata = $this->EE->extensions->call('member_member_login_start');
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		// No username/password?  Bounce them...
		$multi	  = $this->EE->input->get('multi');
		$username = $this->EE->input->post('username');
		$password = $this->EE->input->post('password');
		
		if ( ! $multi && ! ($username && $password))
		{
			return $this->EE->output->show_user_error('submission', array(
				lang('mbr_form_empty')
			));
		}

		// This should go in the auth lib.
		if ( ! $this->EE->auth->check_require_ip())
		{
			$this->EE->output->show_user_error('general', 
										array(lang('unauthorized_request')));
		}

		// Check password lockout status
		if (TRUE === $this->EE->session->check_password_lockout($username))
		{
			$line = lang('password_lockout_in_effect');

			$line = str_replace("%x", $this->EE->config->item('password_lockout_interval'), $line);

			$this->EE->output->show_user_error('general', array($line));
		}

		// Log me in.
		if ($this->EE->input->get('multi'))
		{	
			// Multiple Site Login
			$sess = $this->_do_multi_auth();
		}
		else
		{
			// Regular Login
			$sess = $this->_do_auth($username, $password);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Check against minimum username/password length
	 *
	 * @param 	object 	member auth object
	 * @param 	string 	username
	 * @param 	string 	password
	 * @return 	void 	a redirect on failure, or nothing
	 */
	private function _check_min_unpwd($member_obj, $username, $password)
	{
		$uml = $this->EE->config->item('un_min_len');
		$pml = $this->EE->config->item('pw_min_len');

		$ulen = strlen($username);
		$plen = strlen($password);

		if ($ulen < $uml OR $plen < $pml)
		{
			$trigger = '';
			if ($this->EE->input->get_post('FROM') == 'forum')
			{
				$this->basepath = $this->EE->input->get_post('mbase');
				$trigger =  $this->EE->input->get_post('trigger');
			}

			$path = 'unpw_update/'.$member_obj->member_id .'_'.$ulen.'_'.$plen;

			if ($trigger != '')
			{
				$path .= '/'.$trigger;
			}

			return $this->EE->functions->redirect($this->_member_path($path));
		}		
	}

	// --------------------------------------------------------------------

	/**
	 * Check multiple logins
	 *
	 * 
	 */
	private function _check_multiple_logins($auth_obj)
	{
		// Do we allow multiple logins on the same account?		
		if ($this->EE->config->item('allow_multi_logins') == 'n')
		{
			if ($auth_obj->has_other_session())
			{
				return FALSE;
			}
		} 

		return TRUE;
	}

	// --------------------------------------------------------------------

	private function _do_auth($username, $password)
	{
		$sess = $this->EE->auth->authenticate_username($username, $password);

		if ( ! $sess)
		{
			$this->EE->session->save_password_lockout($username);
			return $this->EE->output->show_user_error('general', 
											array(lang('not_authorized')));
		}

		// Banned
		if ($sess->is_banned())
		{
			return $this->EE->output->show_user_error('general', 
											array(lang('not_authorized')));
		}

		// Allow multiple logins?
		if ( ! $this->_check_multiple_logins($sess))
		{
			return $this->EE->output->show_user_error('general', 
											array(lang('not_authorized')));
		}

		// Check user/pass minimum length
		$this->_check_min_unpwd($sess, $username, $password);

		// Start Session
		// "Remember Me" is one year
		if (isset($_POST['auto_login']))
		{
			$sess->remember_me(60*60*24*365);
		}

		$sess->start_session();

		$this->_update_online_user_stats();

		$this->_build_success_message();
	}

	// --------------------------------------------------------------------

	/**
	 * Build Success Message
	 */
	private function _build_success_message()
	{
		// Build success message
		$site_name = ($this->EE->config->item('site_name') == '') ? lang('back') : stripslashes($this->EE->config->item('site_name'));

		$return = $this->EE->functions->remove_double_slashes($this->EE->functions->form_backtrack());

		// Is this a forum request?
		if ($this->EE->input->get_post('FROM') == 'forum')
		{
			if ($this->EE->input->get_post('board_id') !== FALSE && 
				is_numeric($this->EE->input->get_post('board_id')))
			{
				$query = $this->EE->db->select('board_label')
									  ->where('board_id', $this->EE->input->get_post('board_id'))
									  ->get('forum_boards');
			}
			else
			{
				$query = $this->EE->db->select('board_label')
									  ->where('board_id', (int) 1)
									  ->get('forum_boards');
			}

			$site_name	= $query->row('board_label') ;
		}

		// Build success message
		$data = array(	'title' 	=> lang('mbr_login'),
						'heading'	=> lang('thank_you'),
						'content'	=> lang('mbr_you_are_logged_in'),
						'redirect'	=> $return,
						'link'		=> array($return, $site_name)
					 );

		$this->EE->output->show_message($data);
	}

	// --------------------------------------------------------------------

	/**
	 * 
	 */
	private function _update_online_user_stats()
	{
		if ($this->EE->config->item('enable_online_user_tracking') == 'n' OR
			$this->EE->config->item('disable_all_tracking') == 'y')
		{
			return;
		}

		// Update stats
		$cutoff = $this->EE->localize->now - (15 * 60);
		$anon = ($this->EE->input->post('anon') == 1) ? 'n' : 'y';

		$in_forum = ($this->EE->input->get_post('FROM') == 'forum') ? 'y' : 'n';


		$this->EE->db->query("DELETE FROM exp_online_users WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' AND ((ip_address = '".$this->EE->input->ip_address()."' AND member_id = '0') OR date < $cutoff)");

		$data = array(
						'member_id'		=> $this->EE->session->userdata('member_id'),
						'name'			=> ($this->EE->session->userdata('screen_name') == '') ? $this->EE->session->userdata('username') : $this->EE->session->userdata('screen_name'),
						'ip_address'	=> $this->EE->input->ip_address(),
						'in_forum'		=> $in_forum,
						'date'			=> $this->EE->localize->now,
						'anon'			=> $anon,
						'site_id'		=> $this->EE->config->item('site_id')
					);

		$this->EE->db->where('ip_address', $this->EE->input->ip_address())
					 ->where('member_id', $data['member_id'])
					 ->update('online_users', $data);		
	}

	// --------------------------------------------------------------------

	/**
	 * Member Logout
	 */
	public function member_logout()
	{
		// Kill the session and cookies		
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->where('ip_address', $this->EE->input->ip_address());
		$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
		$this->EE->db->delete('online_users');		
		
		$this->EE->session->destroy();

		$this->EE->functions->set_cookie('read_topics');

		/* -------------------------------------------
		/* 'member_member_logout' hook.
		/*  - Perform additional actions after logout
		/*  - Added EE 1.6.1
		*/
			$edata = $this->EE->extensions->call('member_member_logout');
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		// Is this a forum redirect?
		$name = '';
		unset($url);

		if ($this->EE->input->get_post('FROM') == 'forum')
		{
			if ($this->EE->input->get_post('board_id') !== FALSE && 
				is_numeric($this->EE->input->get_post('board_id')))
			{
				$query = $this->EE->db->select("board_forum_url, board_label")
									  ->where('board_id', $this->EE->input->get_post('board_id'))
									  ->get('forum_boards');
			}
			else
			{
				$query = $this->EE->db->select('board_forum_url, board_label')
									  ->where('board_id', (int) 1)
									  ->get('forum_boards');
			}

			$url = $query->row('board_forum_url') ;
			$name = $query->row('board_label') ;
		}

		// Build success message
		$url	= ( ! isset($url)) ? $this->EE->config->item('site_url')	: $url;
		$name	= ( ! isset($url)) ? stripslashes($this->EE->config->item('site_name'))	: $name;

		$data = array(	'title' 	=> lang('mbr_login'),
						'heading'	=> lang('thank_you'),
						'content'	=> lang('mbr_you_are_logged_out'),
						'redirect'	=> $url,
						'link'		=> array($url, $name)
					 );

		$this->EE->output->show_message($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Member Forgot Password Form
	 *
	 * @param 	string 	pages to return back to
	 */
	public function forgot_password($ret = '-3')
	{
		$data = array(
			'id'				=> 'forgot_password_form',
			'hidden_fields'		=> array(
				'ACT'	=> $this->EE->functions->fetch_action_id('Member', 'retrieve_password'),
				'RET'	=> $ret,
				'FROM'	=> ($this->in_forum == TRUE) ? 'forum' : ''
			)
		);

		if ($this->in_forum === TRUE)
		{
			$data['hidden_fields']['board_id'] = $this->board_id;
		}

		$this->_set_page_title(lang('mbr_forgotten_password'));

		return $this->_var_swap($this->_load_element('forgot_form'),
										array(
												'form_declaration'		=>	$this->EE->functions->form_declaration($data)
											 )
										);
	}

	// --------------------------------------------------------------------

	/**
	 * Retreive Forgotten Password
	 */
	public function retrieve_password()
	{
		// Is user banned?
		if ($this->EE->session->userdata('is_banned') === TRUE)
		{
			return $this->EE->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Error trapping
		if ( ! $address = $this->EE->input->post('email'))
		{
			return $this->EE->output->show_user_error('submission', array(lang('invalid_email_address')));
		}

		$this->EE->load->helper('email');

		if ( ! valid_email($address))
		{
			return $this->EE->output->show_user_error('submission', array(lang('invalid_email_address')));
		}

		$address = strip_tags($address);

		// Fetch user data
		$query = $this->EE->db->select('member_id, username')
							  ->where('email', $address)
							  ->get('members');

		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('submission', array(lang('no_email_found')));
		}

		$member_id = $query->row('member_id') ;
		$username  = $query->row('username') ;

		// Kill old data from the reset_password field

		$time = time() - (60*60*24);

		$this->EE->db->where('date <', $time)
					 ->or_where('member_id', $member_id)
					 ->delete('reset_password');

		// Create a new DB record with the temporary reset code
		$rand = $this->EE->functions->random('alnum', 8);

		$data = array('member_id' => $member_id, 'resetcode' => $rand, 'date' => time());

		$this->EE->db->query($this->EE->db->insert_string('exp_reset_password', $data));

		// Buid the email message

		if ($this->EE->input->get_post('FROM') == 'forum')
		{
			if ($this->EE->input->get_post('board_id') !== FALSE && 
				is_numeric($this->EE->input->get_post('board_id')))
			{
				$query = $this->EE->db->select('board_forum_url, board_id, board_label')
									  ->where('board_id', $this->EE->input->get_post('board_id'))
									  ->get('forum_boards');
			}
			else
			{
				$query = $this->EE->db->select('board_forum_url, board_id, board_label')
									  ->where('board_id', (int) 1)
									  ->get('forum_boards');
			}

			$return		= $query->row('board_forum_url') ;
			$site_name	= $query->row('board_label') ;
			$board_id	= $query->row('board_id') ;
		}
		else
		{
			$site_name	= stripslashes($this->EE->config->item('site_name'));
			$return 	= $this->EE->config->item('site_url');
		}

		$forum_id = ($this->EE->input->get_post('FROM') == 'forum') ? '&r=f&board_id='.$board_id : '';

		$swap = array(
						'name'		=> $username,
						'reset_url'	=> $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Member', 'reset_password').'&id='.$rand.$forum_id,
						'site_name'	=> $site_name,
						'site_url'	=> $return
					 );

		$template = $this->EE->functions->fetch_email_template('forgot_password_instructions');
		$email_tit = $this->_var_swap($template['title'], $swap);
		$email_msg = $this->_var_swap($template['data'], $swap);

		// Instantiate the email class

		$this->EE->load->library('email');
		$this->EE->email->wordwrap = true;
		$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
		$this->EE->email->to($address);
		$this->EE->email->subject($email_tit);
		$this->EE->email->message($email_msg);

		if ( ! $this->EE->email->send())
		{
			return $this->EE->output->show_user_error('submission', array(lang('error_sending_email')));
		}

		// Build success message
		$data = array(	'title' 	=> lang('mbr_passwd_email_sent'),
						'heading'	=> lang('thank_you'),
						'content'	=> lang('forgotten_email_sent'),
						'link'		=> array($return, $site_name)
					 );

		$this->EE->output->show_message($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Reset the user's password
	 */
	public function reset_password()
	{
		// Is user banned?
		if ($this->EE->session->userdata('is_banned') === TRUE)
		{
			return $this->EE->output->show_user_error('general', array(lang('not_authorized')));
		}

		if ( ! $id = $this->EE->input->get_post('id'))
		{
			return $this->EE->output->show_user_error('submission', array(lang('mbr_no_reset_id')));
		}

		$time = time() - (60*60*24);

		// Get the member ID from the reset_password field

		$query = $this->EE->db->select('member_id')
							  ->where('resetcode', $id)
							  ->where('date >', $time)
							  ->get('reset_password');

		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('submission', array(lang('mbr_id_not_found')));
		}

		$member_id = $query->row('member_id') ;

		// Fetch the user data
		$query = $this->EE->db->select("username, email")
							  ->where('member_id', $member_id)
							  ->get('members');

		if ($query->num_rows() === 0)
		{
			return FALSE;
		}

		$address = $query->row('email') ;
		$username = $query->row('username') ;

		$rand = $this->EE->functions->random('alnum', 8);

		// Update member's password

		$this->EE->db->set('password', $this->EE->functions->hash($rand))
					 ->where('member_id', $member_id)
					 ->update('members');

		// Kill old data from the reset_password field
		$this->EE->db->where('date <', $time)
					 ->where('member_id', $member_id)
					 ->delete('reset_password');

		// Buid the email message
		if ($this->EE->input->get_post('r') == 'f')
		{
			if ($this->EE->input->get_post('board_id') !== FALSE && 
				is_numeric($this->EE->input->get_post('board_id')))
			{
				$query = $this->EE->db->select('board_forum_url, board_labe')
									  ->where('board_id', (int) $this->EE->input->get_post('board_id'))
									  ->get('forum_boards');
			}
			else
			{
				$query = $this->EE->db->select('board_forum_url, board_label')
									  ->where('board_id', (int) 1)
									  ->get('forum_boards');
			}

			$return		= $query->row('board_forum_url') ;
			$site_name	= $query->row('board_label') ;
		}
		else
		{
			$site_name = stripslashes($this->EE->config->item('site_name'));
			$return 	= $this->EE->config->item('site_url');
		}

		$swap = array(
						'name'		=> $username,
						'username'	=> $username,
						'password'	=> $rand,
						'site_name'	=> $site_name,
						'site_url'	=> $return
					 );

		$template = $this->EE->functions->fetch_email_template('reset_password_notification');
		$email_tit = $this->_var_swap($template['title'], $swap);
		$email_msg = $this->_var_swap($template['data'], $swap);

		// Instantiate the email class
		$this->EE->load->library('email');
		$this->EE->email->wordwrap = TRUE;
		$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
		$this->EE->email->to($address);
		$this->EE->email->subject($email_tit);
		$this->EE->email->message($email_msg);

		if ( ! $this->EE->email->send())
		{
			return $this->EE->output->show_user_error('submission', 
									array(lang('error_sending_email')));
		}

		// Build success message
		$site_name = ($this->EE->config->item('site_name') == '') ? lang('back') : stripslashes($this->EE->config->item('site_name'));

		$data = array(	'title' 	=> lang('mbr_login'),
						'heading'	=> lang('thank_you'),
						'content'	=> lang('password_has_been_reset'),
						'link'		=> array($return, $site_name)
					 );

		$this->EE->output->show_message($data);
	}
}
// END CLASS

/* End of file mod.member_auth.php */
/* Location: ./system/expressionengine/modules/member/mod.member_auth.php */