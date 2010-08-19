<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * Member Management Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Member_auth extends Member {

	/** ----------------------------------
	/**  Member_auth Profile Constructor
	/** ----------------------------------*/
	function Member_auth()
	{
	}


	/** ----------------------------------------
	/**  Login Page
	/** ----------------------------------------*/

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
		preg_match("/".LD."(form_declaration"."(\s+return\s*=\s*(\042|\047)([^\\3]*?)\\3)?)".RD."/s", $login_form, $match);

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
			$data['hidden_fields']['RET']	= ($return == 'self') ? $this->_member_path($this->request.'/'.$this->cur_id) : $return;
		}

		$data['hidden_fields']['FROM'] = ($this->in_forum === TRUE) ? 'forum' : '';
		$data['id']	  = 'member_login_form';

		$this->_set_page_title($this->EE->lang->line('member_login'));

		return $this->_var_swap($login_form, array($match['1'] => $this->EE->functions->form_declaration($data)));
	}


	/** ----------------------------------------
	/**  Member Login
	/** ----------------------------------------*/
	function member_login()
	{
		/** ----------------------------------------
		/**  Is user banned?
		/** ----------------------------------------*/

		if ($this->EE->session->userdata['is_banned'] == TRUE)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		$this->EE->lang->loadfile('login');


		/* -------------------------------------------
		/* 'member_member_login_start' hook.
		/*  - Take control of member login routine
		/*  - Added EE 1.4.2
		*/
			$edata = $this->EE->extensions->call('member_member_login_start');
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/


		/** ----------------------------------------
		/**  Error trapping
		/** ----------------------------------------*/

		$errors = array();

		/** ----------------------------------------
		/**  No username/password?  Bounce them...
		/** ----------------------------------------*/

		if ( ! $this->EE->input->get('multi') && ( ! $this->EE->input->get_post('username') OR ! $this->EE->input->get_post('password')))
		{
			$this->EE->output->show_user_error('submission', array($this->EE->lang->line('mbr_form_empty')));
		}

		/** ----------------------------------------
		/**  Is IP and User Agent required for login?
		/** ----------------------------------------*/

		if ($this->EE->config->item('require_ip_for_login') == 'y')
		{
			if ($this->EE->session->userdata['ip_address'] == '' OR $this->EE->session->userdata['user_agent'] == '')
			{
				$this->EE->output->show_user_error('general', array($this->EE->lang->line('unauthorized_request')));
			}
		}

		/** ----------------------------------------
		/**  Check password lockout status
		/** ----------------------------------------*/

		if ($this->EE->session->check_password_lockout($this->EE->input->get_post('username')) === TRUE)
		{
			$line = $this->EE->lang->line('password_lockout_in_effect');

			$line = str_replace("%x", $this->EE->config->item('password_lockout_interval'), $line);

			$this->EE->output->show_user_error('general', array($line));
		}

		/** ----------------------------------------
		/**  Fetch member data
		/** ----------------------------------------*/
		if ( ! $this->EE->input->get('multi'))
		{
			$sql = "SELECT exp_members.password, exp_members.unique_id, exp_members.member_id, exp_members.group_id
					FROM	exp_members, exp_member_groups
					WHERE  username = '".$this->EE->db->escape_str($this->EE->input->post('username'))."'
					AND	exp_members.group_id = exp_member_groups.group_id
					AND		exp_member_groups.site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'";

			$query = $this->EE->db->query($sql);

		}
		else
		{
			if ($this->EE->config->item('allow_multi_logins') == 'n' OR ! $this->EE->config->item('multi_login_sites') OR $this->EE->config->item('multi_login_sites') == '')
			{
				return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
			}

			// Current site in list.  Original login site.
			if ($this->EE->input->get('cur') === FALSE OR $this->EE->input->get_post('orig') === FALSE OR $this->EE->input->get('orig_site_id') === FALSE)
			{
				return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
			}

			// Kill old sessions first

			$this->EE->session->gc_probability = 100;

			$this->EE->session->delete_old_sessions();

			// Set cookie expiration to one year if the "remember me" button is clicked

			$expire = ( ! isset($_POST['auto_login'])) ? '0' : 60*60*24*365;

			// Check Session ID

			$query = $this->EE->db->query("SELECT exp_members.member_id, exp_members.password, exp_members.unique_id
							FROM		exp_sessions, exp_members
							WHERE  	exp_sessions.session_id  = '".$this->EE->db->escape_str($this->EE->input->get('multi'))."'
							AND		exp_sessions.member_id = exp_members.member_id
							AND		exp_sessions.last_activity > $expire");

			if ($query->num_rows() == 0)
			{
				return;
			}

			// Set Various Cookies

			$this->EE->functions->set_cookie($this->EE->session->c_anon);
			$this->EE->functions->set_cookie($this->EE->session->c_expire , time()+$expire, $expire);
			$this->EE->functions->set_cookie($this->EE->session->c_uniqueid , $query->row('unique_id') , $expire);
			$this->EE->functions->set_cookie($this->EE->session->c_password , $query->row('password') ,  $expire);

			if ($this->EE->config->item('user_session_type') == 'cs' OR $this->EE->config->item('user_session_type') == 's')
			{
				$this->EE->functions->set_cookie($this->EE->session->c_session , $this->EE->input->get('multi'), $this->EE->session->session_length);
			}

			// -------------------------------------------
			// 'member_member_login_multi' hook.
			//  - Additional processing when a member is logging into multiple sites
			//
				$edata = $this->EE->extensions->call('member_member_login_multi', $query->row());
				if ($this->EE->extensions->end_script === TRUE) return;
			//
			// -------------------------------------------

			// Check if there are any more sites to log into

			$sites	= explode('|',$this->EE->config->item('multi_login_sites'));
			$next	= ($this->EE->input->get('cur') + 1 != $this->EE->input->get('orig')) ? $this->EE->input->get('cur') + 1 : $this->EE->input->get_post('cur') + 2;

			if ( ! isset($sites[$next]))
			{
				// We're done.
				$data = array(	'title' 	=> $this->EE->lang->line('mbr_login'),
								'heading'	=> $this->EE->lang->line('thank_you'),
								'content'	=> $this->EE->lang->line('mbr_you_are_logged_in'),
								'redirect'	=> $sites[$this->EE->input->get('orig')],
								'link'		=> array($sites[$this->EE->input->get('orig')], $this->EE->lang->line('back'))
								 );

				// Pull preferences for the original site

				if (is_numeric($this->EE->input->get('orig_site_id')))
				{
					$query = $this->EE->db->query("SELECT site_name, site_id FROM exp_sites WHERE site_id = '".$this->EE->db->escape_str($this->EE->input->get('orig_site_id'))."'");

					if ($query->num_rows() == 1)
					{
						$final_site_name = $query->row('site_name');
						$final_site_id = $query->row('site_id');

						$this->EE->config->site_prefs($final_site_name, $final_site_id);
					}
				}

				$this->EE->output->show_message($data);
			}
			else
			{
				// Next Site

				$next_url = $sites[$next].'?ACT='.$this->EE->functions->fetch_action_id('Member', 'member_login').
							'&multi='.$this->EE->input->get('multi').'&cur='.$next.'&orig='.$this->EE->input->get_post('orig').'&orig_site_id='.$this->EE->input->get('orig_site_id');

				return $this->EE->functions->redirect($next_url);
			}
		}


		/** ----------------------------------------
		/**  Invalid Username
		/** ----------------------------------------*/
		if ($query->num_rows() == 0)
		{
			$this->EE->session->save_password_lockout($this->EE->input->get_post('username'));

			$this->EE->output->show_user_error('submission', array($this->EE->lang->line('credential_missmatch')));
		}

		/** ----------------------------------------
		/**  Is the member account pending?
		/** ----------------------------------------*/
		if ($query->row('group_id')  == 4)
		{
			$this->EE->output->show_user_error('general', array($this->EE->lang->line('mbr_account_not_active')));
		}

		/** ----------------------------------------
		/**  Check password
		/** ----------------------------------------*/
		$this->EE->load->helper('security');
		$password = do_hash($this->EE->input->post('password'));

		if ($query->row('password') != $password)
		{
			// To enable backward compatibility with pMachine we'll test to see
			// if the password was encrypted with MD5.  If so, we will encrypt the
			// password using SHA1 and update the member's info.

			$password = do_hash($this->EE->input->post('password'), 'md5');

			if ($query->row('password')  == $password)
			{
				$password = do_hash($this->EE->input->post('password'));

				$sql = "UPDATE exp_members
						SET	password = '".$password."'
						WHERE  member_id = '".$query->row('member_id') ."' ";

				$this->EE->db->query($sql);
			}
			else
			{
				/** ----------------------------------------
				/**  Invalid password
				/** ----------------------------------------*/

				$this->EE->session->save_password_lockout($this->EE->input->get_post('username'));

				$errors[] = $this->EE->lang->line('credential_missmatch');
			}
		}

		/** --------------------------------------------------
		/**  Do we allow multiple logins on the same account?
		/** --------------------------------------------------*/

		if ($this->EE->config->item('allow_multi_logins') == 'n')
		{
			// Kill old sessions first

			$this->EE->session->gc_probability = 100;

			$this->EE->session->delete_old_sessions();

			$expire = time() - $this->EE->session->session_length;

			// See if there is a current session

			$result = $this->EE->db->query("SELECT ip_address, user_agent
								  FROM	exp_sessions
								  WHERE  member_id  = '".$query->row('member_id') ."'
								  AND	last_activity > $expire
								  AND	 site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'");

			// If a session exists, trigger the error message

			if ($result->num_rows() == 1)
			{
				if ($this->EE->session->userdata['ip_address'] != $result->row('ip_address')  OR
					$this->EE->session->userdata['user_agent'] != $result->row('user_agent')  )
				{
					$errors[] = $this->EE->lang->line('multi_login_warning');
				}
			}
		}

		/** ----------------------------------------
		/**  Are there errors to display?
		/** ----------------------------------------*/

		if (count($errors) > 0)
		{
			return $this->EE->output->show_user_error('submission', $errors);
		}

		/** ----------------------------------------
		/**  Is the UN/PW the correct length?
		/** ----------------------------------------*/

		// If the admin has specfified a minimum username or password length that
		// is longer than the current users's data we'll have them update their info.
		// This will only be an issue if the admin has changed the un/password requiremements
		// after member accounts already exist.

		$uml = $this->EE->config->item('un_min_len');
		$pml = $this->EE->config->item('pw_min_len');

		$ulen = strlen($this->EE->input->post('username'));
		$plen = strlen($this->EE->input->post('password'));

		if ($ulen < $uml OR $plen < $pml)
		{
			$trigger = '';
			if ($this->EE->input->get_post('FROM') == 'forum')
			{
				$this->basepath = $this->EE->input->get_post('mbase');
				$trigger =  $this->EE->input->get_post('trigger');
			}

			$path = 'unpw_update/'.$query->row('member_id') .'_'.$ulen.'_'.$plen;

			if ($trigger != '')
			{
				$path .= '/'.$trigger;
			}

			return $this->EE->functions->redirect($this->_member_path($path));
		}


		/** ----------------------------------------
		/**  Set cookies
		/** ----------------------------------------*/

		// Set cookie expiration to one year if the "remember me" button is clicked

		$expire = ( ! isset($_POST['auto_login'])) ? '0' : 60*60*24*365;

		$this->EE->functions->set_cookie($this->EE->session->c_expire , time()+$expire, $expire);
		$this->EE->functions->set_cookie($this->EE->session->c_uniqueid , $query->row('unique_id') , $expire);
		$this->EE->functions->set_cookie($this->EE->session->c_password , $password,  $expire);

		// Does the user want to remain anonymous?

		if ( ! isset($_POST['anon']))
		{
			$this->EE->functions->set_cookie($this->EE->session->c_anon , 1,  $expire);

			$anon = 'y';
		}
		else
		{
			$this->EE->functions->set_cookie($this->EE->session->c_anon);

			$anon = '';
		}

		/** ----------------------------------------
		/**  Create a new session
		/** ----------------------------------------*/

		$this->EE->session->create_new_session($query->row('member_id') );
		$this->EE->session->userdata['username']  = $this->EE->input->get_post('username');

		// -------------------------------------------
		// 'member_member_login_single' hook.
		//  - Additional processing when a member is logging into single site
		//
			$edata = $this->EE->extensions->call('member_member_login_single', $query->row());
			if ($this->EE->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		/** ----------------------------------------
		/**  Update stats
		/** ----------------------------------------*/

		$cutoff		= $this->EE->localize->now - (15 * 60);

		$this->EE->db->query("DELETE FROM exp_online_users WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' AND ((ip_address = '".$this->EE->input->ip_address()."' AND member_id = '0') OR date < $cutoff)");

		$data = array(
						'member_id'		=> $this->EE->session->userdata('member_id'),
						'name'			=> ($this->EE->session->userdata['screen_name'] == '') ? $this->EE->session->userdata['username'] : $this->EE->session->userdata['screen_name'],
						'ip_address'	=> $this->EE->input->ip_address(),
						'date'			=> $this->EE->localize->now,
						'anon'			=> $anon,
						'site_id'		=> $this->EE->config->item('site_id')
					);

		$this->EE->db->query($this->EE->db->update_string('exp_online_users', $data, array("ip_address" => $this->EE->input->ip_address(), "member_id" => $data['member_id'])));

		/** ----------------------------------------
		/**  Delete old password lockouts
		/** ----------------------------------------*/

		$this->EE->session->delete_password_lockout();

		/** ----------------------------------------
		/**  Multiple Site Logins
		/** ----------------------------------------*/

		if ($this->EE->config->item('allow_multi_logins') == 'y' && $this->EE->config->item('multi_login_sites') != '')
		{
			// Next Site
			$sites		=  explode('|',$this->EE->config->item('multi_login_sites'));
			$current	= $this->EE->functions->fetch_site_index();

			if (count($sites) > 1 && in_array($current, $sites))
			{
				$orig = array_search($current, $sites);
				$next = ($orig == '0') ? '1' : '0';

				$next_url = $sites[$next].'?ACT='.$this->EE->functions->fetch_action_id('Member', 'member_login').
							'&multi='.$this->EE->session->userdata['session_id'].'&cur='.$next.'&orig='.$orig.'&orig_site_id='.$this->EE->input->get('orig_site_id');

				return $this->EE->functions->redirect($next_url);
			}
		}

		/** ----------------------------------------
		/**  Build success message
		/** ----------------------------------------*/

		$site_name = ($this->EE->config->item('site_name') == '') ? $this->EE->lang->line('back') : stripslashes($this->EE->config->item('site_name'));

		$return = $this->EE->functions->remove_double_slashes($this->EE->functions->form_backtrack());

		/** ----------------------------------------
		/**  Is this a forum request?
		/** ----------------------------------------*/

		if ($this->EE->input->get_post('FROM') == 'forum')
		{
			if ($this->EE->input->get_post('board_id') !== FALSE && is_numeric($this->EE->input->get_post('board_id')))
			{
				$query	= $this->EE->db->query("SELECT board_label FROM exp_forum_boards WHERE board_id = '".$this->EE->db->escape_str($this->EE->input->get_post('board_id'))."'");
			}
			else
			{
				$query	= $this->EE->db->query("SELECT board_label FROM exp_forum_boards WHERE board_id = '1'");
			}

			$site_name	= $query->row('board_label') ;
		}

		/** ----------------------------------------
		/**  Build success message
		/** ----------------------------------------*/

		$data = array(	'title' 	=> $this->EE->lang->line('mbr_login'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('mbr_you_are_logged_in'),
						'redirect'	=> $return,
						'link'		=> array($return, $site_name)
					 );

		$this->EE->output->show_message($data);
	}



	/** ----------------------------------------
	/**  Member Logout
	/** ----------------------------------------*/
	function member_logout()
	{
		/** ----------------------------------------
		/**  Kill the session and cookies
		/** ----------------------------------------*/
		
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->where('ip_address', $this->EE->input->ip_address());
		$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
		$this->EE->db->delete('online_users');		
		
		$this->EE->db->where('session_id', $this->EE->session->userdata['session_id']);
		$this->EE->db->delete('sessions');		


		$this->EE->functions->set_cookie($this->EE->session->c_uniqueid);
		$this->EE->functions->set_cookie($this->EE->session->c_password);
		$this->EE->functions->set_cookie($this->EE->session->c_session);
		$this->EE->functions->set_cookie($this->EE->session->c_expire);
		$this->EE->functions->set_cookie($this->EE->session->c_anon);
		$this->EE->functions->set_cookie('read_topics');
		$this->EE->functions->set_cookie('tracker');

		/* -------------------------------------------
		/* 'member_member_logout' hook.
		/*  - Perform additional actions after logout
		/*  - Added EE 1.6.1
		*/
			$edata = $this->EE->extensions->call('member_member_logout');
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		/** ----------------------------------------
		/**  Is this a forum redirect?
		/** ----------------------------------------*/

		$name = '';
		unset($url);

		if ($this->EE->input->get_post('FROM') == 'forum')
		{
			if ($this->EE->input->get_post('board_id') !== FALSE && is_numeric($this->EE->input->get_post('board_id')))
			{
				$query	= $this->EE->db->query("SELECT board_forum_url, board_label FROM exp_forum_boards WHERE board_id = '".$this->EE->db->escape_str($this->EE->input->get_post('board_id'))."'");
			}
			else
			{
				$query	= $this->EE->db->query("SELECT board_forum_url, board_label FROM exp_forum_boards WHERE board_id = '1'");
			}

			$url	= $query->row('board_forum_url') ;
			$name	= $query->row('board_label') ;
		}

		/** ----------------------------------------
		/**  Build success message
		/** ----------------------------------------*/
		$url	= ( ! isset($url)) ? $this->EE->config->item('site_url')	: $url;
		$name	= ( ! isset($url)) ? stripslashes($this->EE->config->item('site_name'))	: $name;

		$data = array(	'title' 	=> $this->EE->lang->line('mbr_login'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('mbr_you_are_logged_out'),
						'redirect'	=> $url,
						'link'		=> array($url, $name)
					 );

		$this->EE->output->show_message($data);
	}





	/** ----------------------------------------
	/**  Member Forgot Password Form
	/** ----------------------------------------*/
	function forgot_password($ret = '-3')
	{
		$data['id']				= 'forgot_password_form';
		$data['hidden_fields']	= array(
										'ACT'	=> $this->EE->functions->fetch_action_id('Member', 'retrieve_password'),
										'RET'	=> $ret,
										'FROM'	=> ($this->in_forum == TRUE) ? 'forum' : ''
									  );

		if ($this->in_forum === TRUE)
		{
			$data['hidden_fields']['board_id'] = $this->board_id;
		}

		$this->_set_page_title($this->EE->lang->line('mbr_forgotten_password'));

		return $this->_var_swap($this->_load_element('forgot_form'),
										array(
												'form_declaration'		=>	$this->EE->functions->form_declaration($data)
											 )
										);
	}




	/** ----------------------------------------
	/**  Retreive Forgotten Password
	/** ----------------------------------------*/
	function retrieve_password()
	{
		/** ----------------------------------------
		/**  Is user banned?
		/** ----------------------------------------*/

		if ($this->EE->session->userdata['is_banned'] == TRUE)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		/** ----------------------------------------
		/**  Error trapping
		/** ----------------------------------------*/

		if ( ! $address = $this->EE->input->post('email'))
		{
			return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('invalid_email_address')));
		}

		$this->EE->load->helper('email');

		if ( ! valid_email($address))
		{
			return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('invalid_email_address')));
		}

		$address = strip_tags($address);

		// Fetch user data

		$sql = "SELECT member_id, username FROM exp_members WHERE email ='".$this->EE->db->escape_str($address)."'";

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('no_email_found')));
		}

		$member_id = $query->row('member_id') ;
		$username  = $query->row('username') ;

		// Kill old data from the reset_password field

		$time = time() - (60*60*24);

		$this->EE->db->query("DELETE FROM exp_reset_password WHERE date < $time OR member_id = '$member_id'");

		// Create a new DB record with the temporary reset code

		$rand = $this->EE->functions->random('alnum', 8);

		$data = array('member_id' => $member_id, 'resetcode' => $rand, 'date' => time());

		$this->EE->db->query($this->EE->db->insert_string('exp_reset_password', $data));

		// Buid the email message

		if ($this->EE->input->get_post('FROM') == 'forum')
		{
			if ($this->EE->input->get_post('board_id') !== FALSE && is_numeric($this->EE->input->get_post('board_id')))
			{
				$query	= $this->EE->db->query("SELECT board_forum_url, board_id, board_label FROM exp_forum_boards WHERE board_id = '".$this->EE->db->escape_str($this->EE->input->get_post('board_id'))."'");
			}
			else
			{
				$query	= $this->EE->db->query("SELECT board_forum_url, board_id, board_label FROM exp_forum_boards WHERE board_id = '1'");
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
			return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('error_sending_email')));
		}

		/** ----------------------------------------
		/**  Build success message
		/** ----------------------------------------*/

		$data = array(	'title' 	=> $this->EE->lang->line('mbr_passwd_email_sent'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('forgotten_email_sent'),
						'link'		=> array($return, $site_name)
					 );

		$this->EE->output->show_message($data);
	}




	/** ----------------------------------------
	/**  Reset the user's password
	/** ----------------------------------------*/
	function reset_password()
	{
		/** ----------------------------------------
		/**  Is user banned?
		/** ----------------------------------------*/

		if ($this->EE->session->userdata['is_banned'] == TRUE)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		if ( ! $id = $this->EE->input->get_post('id'))
		{
			return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('mbr_no_reset_id')));
		}

		$time = time() - (60*60*24);

		// Get the member ID from the reset_password field

		$query = $this->EE->db->query("SELECT member_id FROM exp_reset_password WHERE resetcode ='".$this->EE->db->escape_str($id)."' and date > $time");

		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('mbr_id_not_found')));
		}

		$member_id = $query->row('member_id') ;

		// Fetch the user data

		$sql = "SELECT username, email FROM exp_members WHERE member_id ='$member_id'";

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$address	= $query->row('email') ;
		$username  = $query->row('username') ;

		$rand = $this->EE->functions->random('alnum', 8);

		// Update member's password

		$sql = "UPDATE exp_members SET password = '".$this->EE->functions->hash($rand)."' WHERE member_id = '$member_id'";

		$this->EE->db->query($sql);

		// Kill old data from the reset_password field

		$this->EE->db->query("DELETE FROM exp_reset_password WHERE date < $time OR member_id = '$member_id'");

		// Buid the email message


		if ($this->EE->input->get_post('r') == 'f')
		{
			if ($this->EE->input->get_post('board_id') !== FALSE && is_numeric($this->EE->input->get_post('board_id')))
			{
				$query	= $this->EE->db->query("SELECT board_forum_url, board_label FROM exp_forum_boards WHERE board_id = '".$this->EE->db->escape_str($this->EE->input->get_post('board_id'))."'");
			}
			else
			{
				$query	= $this->EE->db->query("SELECT board_forum_url, board_label FROM exp_forum_boards WHERE board_id = '1'");
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
		$this->EE->email->wordwrap = true;
		$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
		$this->EE->email->to($address);
		$this->EE->email->subject($email_tit);
		$this->EE->email->message($email_msg);

		if ( ! $this->EE->email->send())
		{
			return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('error_sending_email')));
		}

		/** ----------------------------------------
		/**  Build success message
		/** ----------------------------------------*/

		$site_name = ($this->EE->config->item('site_name') == '') ? $this->EE->lang->line('back') : stripslashes($this->EE->config->item('site_name'));

		$data = array(	'title' 	=> $this->EE->lang->line('mbr_login'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('password_has_been_reset'),
						'link'		=> array($return, $site_name)
					 );

		$this->EE->output->show_message($data);
	}




}
// END CLASS

/* End of file mod.member_auth.php */
/* Location: ./system/expressionengine/modules/member/mod.member_auth.php */