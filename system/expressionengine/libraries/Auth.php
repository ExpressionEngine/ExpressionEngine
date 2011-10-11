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

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Authentication Library
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

// ------------------------------------------------------------------------

/*
ExpressionEngine User Classes (* = current):

  1. Session
  2. Authentication*
  3. Permissions

Doing authentication securely relies heavily on handling user
passwords responsibly. Thanks to steadily increasing computing
power, cryptographic hashing algorithms evolve continuously.

To deal with this we continually try to upgrade the user. The
general authentication flow therefore becomes:

  1. Grab user info using a unique identifier.

  2. Determine the function used for their stored password.
	 We do this by looking at the length of the hash. This
	 also means that we can never support two algorithms of
	 the same length. Not a big problem.

  3. Determine if their old password hash was salted.
	 This is easy; we store the salt with their userdata.

  4. Hash the input password with the old salt and hash function.
	 If this fails we're done, the password was incorrect.

  5. Check if we can improve security of their password.
	 If it wasn't salted, we salt it. If we support a newer
	 hash function, we create a new salt and rehash the password.

EE Dev Note: In EE's db the password and salt column
should always be as long as the best available hash.

*/
class Auth {

	private $EE;
	public $errors = array();

	// Hashing algorithms to try with their respective
	// byte sizes. The byte sizes are used to identify
	// the hash function, so they must be unique!
	
	private $hash_algos = array(
		128		=> 'sha512',
		64		=> 'sha256',
		40		=> 'sha1',
		32		=> 'md5'
	);

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		$this->EE =& get_instance();
		
		// Remove any hash algos that we don't have
		// access to in this environment
		$this->hash_algos = array_intersect($this->hash_algos, hash_algos());
	}
	
	// --------------------------------------------------------------------

	/**
	 * Authenticate with an id
	 *
	 * @access	public
	 */
	public function authenticate_id($id, $password)
	{
		$member = $this->EE->db->get_where('members', array('member_id' => $id));
		return $this->_authenticate($member, $password);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Authenticate with email
	 *
	 * @access	public
	 */
	public function authenticate_email($email, $password)
	{
		$member = $this->EE->db->get_where('members', array('email' => $email));
		return $this->_authenticate($member, $password);
	}

	// --------------------------------------------------------------------

	/**
	 * Authenticate with username
	 *
	 * @access	public
	 */
	public function authenticate_username($username, $password)
	{
		$member = $this->EE->db->get_where('members', array('username' => $username));
		return $this->_authenticate($member, $password);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Authenticate from basic http auth
	 *
	 * @access	public
	 */
	public function authenticate_http_basic($not_allowed_groups = array(),
											$realm='Authentication Required')
	{
		$always_disallowed = array(2, 3, 4);

		$not_allowed_groups = array_merge($not_allowed_groups, $always_disallowed);

		if ( ! $this->_retrieve_http_basic())
		{
			@header('WWW-Authenticate: Basic realm="'.$realm.'"');
			$this->EE->output->set_status_header(401);
			@header("Date: ".gmdate("D, d M Y H:i:s")." GMT");
			exit("HTTP/1.0 401 Unauthorized");
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Authenticate from digest http auth
	 *
	 * @access	public
	 */
	public function authenticate_http_digest()
	{
		die('@todo');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Run through the majority of the authentication checks
	 * 
	 * @return array(
	 *		username: from POST
	 *		password: from POST
	 *		incoming: from Auth library, using username and password
	 *	)
	 * 
	 * Your best option is to use:
	 * 		list($username, $password, $incoming) = $this->_verify()
	 * 
	 * If an error results, the lang key will be added to $this->(auth->)errors[]
	 * and this method will return FALSE
	 */
	public function verify()
	{
		$username = $this->EE->input->post('username');

		// No username/password?  Bounce them...
		if ( ! $username)
		{
			$this->errors[] = 'no_username';
			return FALSE;
		}

		$this->EE->session->set_flashdata('username', $username);

		if ( ! $this->EE->input->get_post('password'))
		{
			$this->errors[] = 'no_password';
			return FALSE;
		}

		// If this is being called from the CP, use the hook
		if (REQ == 'CP')
		{
			/* -------------------------------------------
			/* 'login_authenticate_start' hook.
			/*  - Take control of CP authentication routine
			/*  - Added EE 1.4.2
			*/
				$edata = $this->EE->extensions->call('login_authenticate_start');
				if ($this->EE->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
		}

		// Is IP and User Agent required for login?	
		if ( ! $this->EE->auth->check_require_ip())
		{
			$this->errors[] = 'unauthorized_request';
			return FALSE;
		}

		// Check password lockout status
		if ($this->EE->session->check_password_lockout($username) === TRUE)
		{
			$this->EE->lang->loadfile('login');
			
			$line = lang('password_lockout_in_effect');
			$line = sprintf($line, $this->EE->config->item('password_lockout_interval'));

			if (AJAX_REQUEST)
			{
				$this->EE->output->send_ajax_response(array(
					'messageType'	=> 'logout'
				));
			}

			$this->EE->session->set_flashdata('message', $line);
			$this->EE->functions->redirect(BASE.AMP.'C=login');
		}


		//  Check credentials
		// ----------------------------------------------------------------

		$password = $this->EE->input->post('password');
		$incoming = $this->EE->auth->authenticate_username($username, $password);

		// Not even close
		if ( ! $incoming)
		{
			$this->EE->session->save_password_lockout($username);
			$this->errors[] = 'credential_missmatch';
			return FALSE;
		}

		// Banned
		if ($incoming->is_banned())
		{
			return $this->EE->output->fatal_error(lang('not_authorized'));
		}

		// No cp access
		if (REQ == 'CP' && ! $incoming->has_permission('can_access_cp'))
		{
			$this->errors[] = 'not_authorized';
			return FALSE;
		}

		// Do we allow multiple logins on the same account?		
		if ($this->EE->config->item('allow_multi_logins') == 'n')
		{
			if ($incoming->has_other_session())
			{
				$this->errors[] = 'multi_login_warning';
				return FALSE;
			}
		}
		
		return array($username, $password, $incoming);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Check Required IP
	 *
	 * @access	public
	 * @return 	boolean
	 */
	public function check_require_ip()
	{
		if ($this->EE->config->item('require_ip_for_login') == 'y')
		{
			if ($this->EE->session->userdata('ip_address') == '' OR 
				$this->EE->session->userdata('user_agent') == '')
			{
				return FALSE;
			}
		}

		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Hash Password
	 *
	 * Call it with just a password to generate a new hash/salt pair.
	 * Call with an existing salt and hash_size if you need to compare
	 * to an old password. The latter is mostly internal, you probably
	 * want one of the authenticate_* methods instead.
	 *
	 * @access	public
	 */
	public function hash_password($password, $salt = FALSE, $h_byte_size = FALSE)
	{
		// Even for md5, collisions usually happen above 1024 bits, so
		// we artifically limit their password to reasonable size.
		if ( ! $password OR strlen($password) > 250)
		{
			return FALSE;
		}
		
		// No hash function specified? Use the best one
		// we have access to in this environment.
		if ($h_byte_size === FALSE)
		{
			reset($this->hash_algos);
			$h_byte_size = key($this->hash_algos);
		}
		elseif ( ! isset($this->hash_algos[$h_byte_size]))
		{
			// What are they feeding us? This can happen if
			// they move servers and the new environment is
			// less secure. Nothing we can do but fail. Hard.
			
			die('Fatal Error: No matching hash algorithm.');
		}

		// No salt? (not even blank), we'll regenerate
		if ($salt === FALSE)
		{
			$salt = '';

			// The salt should never be displayed, so any
			// visible ascii character is fair game.
			for ($i = 0; $i < $h_byte_size; $i++)
			{
				$salt .= chr(mt_rand(33, 126));
			}
		}
		elseif (strlen($salt) !== $h_byte_size)
		{
			// they passed us a salt that isn't the right length,
			// this can happen if old code resets a new password
			// ignore it
			$salt = '';
		}
		
		return array(
			'salt'		=> $salt,
			'password'	=> hash($this->hash_algos[$h_byte_size], $salt.$password)
		);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Update Username
	 *
	 * @access	public
	 */
	public function update_username($member_id, $username)
	{
		$this->EE->db->where('member_id', (int) $member_id);
		$this->EE->db->set('username', $username);
		$this->EE->db->update('members');
		
		return (bool) $this->EE->db->affected_rows();
	}
	// --------------------------------------------------------------------

	/**
	 * Update Password
	 *
	 * @access	public
	 */
	public function update_password($member_id, $password)
	{
		$hashed_pair = $this->hash_password($password);
		
		if ($hashed_pair === FALSE)
		{
			return FALSE;
		}
		
		$this->EE->db->where('member_id', (int) $member_id);
		$this->EE->db->update('members', $hashed_pair);
		
		return (bool) $this->EE->db->affected_rows();
	}

	// --------------------------------------------------------------------

	/**
	 * Authenticate
	 *
	 * @access	private
	 */
	private function _authenticate(CI_DB_result $member, $password)
	{
		$always_disallowed = array(4);

		if ($member->num_rows() !== 1)
		{
			return FALSE;
		}

		if (in_array($member->row('group_id'), $always_disallowed))
		{
			return $this->EE->output->show_user_error('general', lang('mbr_account_not_active'));
		}

		$m_salt = $member->row('salt');
		$m_pass = $member->row('password');
		
		// hash using the algo used for this password
		$h_byte_size = strlen($m_pass);
		$hashed_pair = $this->hash_password($password, $m_salt, $h_byte_size);
		
		if ($hashed_pair === FALSE OR $m_pass !== $hashed_pair['password'])
		{
			return FALSE;
		}
		
		
		// Officially a valid user, but are they as secure as possible?
		// ----------------------------------------------------------------
		
		reset($this->hash_algos);
		
		// Not hashed or better algo available?
		if ( ! $m_salt OR $h_byte_size != key($this->hash_algos))
		{
			$m_id = $member->row('member_id');
			$this->update_password($m_id, $password);
		}
		
		$authed = new Auth_result($member->row());
		$member->free_result();
		
		return $authed;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Retrieve Basic HTTP Credentials
	 *
	 * @access	private
	 */
	private function _retrieve_http_basic()
	{
		//  Find Username, Please
		// ----------------------------------------------------------------
		
		if (isset($_SERVER['PHP_AUTH_USER']))
		{
			$user = $_SERVER['PHP_AUTH_USER'];
		}
		elseif (isset($_ENV['REMOTE_USER']))
		{
			$user = $_ENV['REMOTE_USER'];
		}
		elseif ( @getenv('REMOTE_USER'))
		{
			$user = getenv('REMOTE_USER');
		}
		elseif (isset($_ENV['AUTH_USER']))
		{
			$user = $_ENV['AUTH_USER'];
		}
		elseif ( @getenv('AUTH_USER'))
		{
			$user = getenv('AUTH_USER');
		}
		
		
		//  Find Password, Please
		// ----------------------------------------------------------------
		
		if (isset($_SERVER['PHP_AUTH_PW']))
		{
			$pass = $_SERVER['PHP_AUTH_PW'];
		}
		elseif (isset($_ENV['REMOTE_PASSWORD']))
		{
			$pass = $_ENV['REMOTE_PASSWORD'];
		}
		elseif ( @getenv('REMOTE_PASSWORD'))
		{
			$pass = getenv('REMOTE_PASSWORD');
		}
		elseif (isset($_ENV['AUTH_PASSWORD']))
		{
			$pass = $_ENV['AUTH_PASSWORD'];
		}
		elseif ( @getenv('AUTH_PASSWORD'))
		{
			$pass = getenv('AUTH_PASSWORD');
		}
		
		// Authentication for IIS
		// ----------------------------------------------------------------
		
		if ( ! isset ($user) OR ! isset($pass) OR (empty($user) && empty($pass)))
		{
			if ( isset($_SERVER['HTTP_AUTHORIZATION']) && substr($_SERVER['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr($HTTP_AUTHORIZATION, 6)));
			}
			elseif ( ! empty($_ENV) && isset($_ENV['HTTP_AUTHORIZATION']) && substr($_ENV['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr($_ENV['HTTP_AUTHORIZATION'], 6)));
			}
			elseif (@getenv('HTTP_AUTHORIZATION') && substr(getenv('HTTP_AUTHORIZATION'), 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr(getenv('HTTP_AUTHORIZATION'), 6)));
			}
		}
		
		//  Authentication for FastCGI
		// ----------------------------------------------------------------
		
		if ( ! isset ($user) OR ! isset($pass) OR (empty($user) && empty($pass)))
		{	
			if ( ! empty($_ENV) && isset($_ENV['Authorization']) && substr($_ENV['Authorization'], 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr($_ENV['Authorization'], 6)));
			}
			elseif (@getenv('Authorization') && substr(getenv('Authorization'), 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr(getenv('Authorization'), 6)));
			}
		}
		
		if ( ! isset ($user) OR ! isset($pass) OR (empty($user) && empty($pass)))
		{
			return FALSE;
		}

		// Check password Lockout
		if ($this->EE->session->check_password_lockout($user) === TRUE)
		{
			return FALSE;	
		}

		return ($this->authenticate_username($user, $pass)) ? TRUE : FALSE;
	}
}
// END Auth class


class Auth_result {

	private $group;
	private $member;
	private $session_id;
	private $remember_me = 0;
	private $EE;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	member query row
	 * @param	int		session id if using multi login
	 */
	function __construct(stdClass $member)
	{
		$this->EE =& get_instance();
		$this->member = $member;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Group data getter
	 *
	 * @access	public
	 */
	public function group($key, $default = FALSE)
	{
		if ( ! is_object($this->group))
		{
			$group_q = $this->EE->db->get_where('member_groups', array(
				'group_id' => $this->member('group_id'),
				'site_id' => $this->EE->config->item('site_id'),
			));
			
			$this->group = $group_q->row();
			
			$group_q->free_result();
		}
		
		return isset($this->group->$key) ? $this->group->$key : $default;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Multi-login check
	 *
	 * @access	public
	 */
	public function has_other_session()
	{
		// Kill old sessions first
		$this->EE->session->gc_probability = 100;
		$this->EE->session->delete_old_sessions();
	
		$expire = time() - $this->EE->session->session_length;
		
		// See if there is a current session
		$this->EE->db->select('ip_address, user_agent');
		$this->EE->db->where('member_id', $this->member('member_id'));
		$this->EE->db->where('last_activity >', $expire);
		$result = $this->EE->db->get('sessions');
		
		// If a session exists, trigger the error message
		if ($result->num_rows() == 1)
		{
			$ip = $this->EE->session->userdata['ip_address'];
			$ua = $this->EE->session->userdata['user_agent'];
			
			if ($ip != $result->row('ip_address') OR 
				$ua != $result->row('user_agent'))
			{
				$result->free_result();
				return TRUE;
			}
		}
		
		$result->free_result();
		return FALSE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Simplified permission checks
	 *
	 * @access	public
	 */
	public function has_permission($perm)
	{
		return ($this->group($perm) === 'y');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Ban check
	 *
	 * @access	public
	 */
	public function is_banned()
	{
		if ($this->member('group_id') != 1)
		{
			return $this->EE->session->ban_check();
		}
		
		return FALSE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Member data getter
	 *
	 * @access	public
	 */
	public function member($key, $default = FALSE)
	{
		return isset($this->member->$key) ? $this->member->$key : $default;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Remember me expiration setter
	 *
	 * @access	public
	 */
	function remember_me($expire)
	{
		$this->remember_me = $expire;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Start session
	 *
	 * Handles all of the checks and cookie stuff
	 *
	 * @access	public
	 */
	public function start_session($cp_sess = FALSE)
	{
		$remember = '';
		$multi = $this->session_id ? TRUE : FALSE;
		$sess_type = $cp_sess ? 'admin_session_type' : 'user_session_type';
		
		if ($this->EE->config->item($sess_type) != 's')
		{
			$expire = $this->remember_me;
			
			$this->EE->functions->set_cookie(
				$this->EE->session->c_anon, 1, $expire
			);
			$this->EE->functions->set_cookie(
				$this->EE->session->c_expire, time()+$expire, $expire
			);
			
			// (un)set remember me
			if ($expire)
			{
				$remember = $this->EE->functions->random('unique', 32);
				
				$this->EE->functions->set_cookie(
					$this->EE->session->c_remember, $remember, $expire
				);
			}
			else
			{
				$this->EE->functions->set_cookie($this->EE->session->c_remember);
			}
		}		
		
		// update the remember me column
		$this->EE->db->where('member_id', $this->member('member_id'));
		$this->EE->db->update('members', array(
			'remember_me' => $remember
		));
		
		if ($multi)
		{
			// multi login - we have a session
			$this->EE->functions->set_cookie(
				$this->EE->session->c_session,
				$this->session_id,
				$this->EE->session->session_length
			);

			$this->EE->session->userdata['session_id'] = $this->session_id;
		}
		else
		{
			// Create a new session
			$this->session_id = $this->EE->session->create_new_session(
				$this->member('member_id'), $cp_sess
			);
		}
		
		if ($cp_sess === TRUE)
		{
			// -------------------------------------------
			// 'cp_member_login' hook.
			//  - Additional processing when a member is logging into CP
			//
				$edata = $this->EE->extensions->call('cp_member_login', $this->_hook_data());
				if ($this->EE->extensions->end_script === TRUE) return;
			//
			// -------------------------------------------
			
			// Log the login

			// We'll manually add the username to the Session array so
			// the logger class can use it.
			$this->EE->session->userdata['username'] = $this->member('username');
			$this->EE->logger->log_action(lang('member_logged_in'));
		}
		elseif ($multi)
		{
			// -------------------------------------------
			// 'member_member_login_multi' hook.
			//  - Additional processing when a member is logging into multiple sites
			//
				$edata = $this->EE->extensions->call('member_member_login_multi', $this->_hook_data());
				if ($this->EE->extensions->end_script === TRUE) return;
			//
			// -------------------------------------------
		}
		else
		{
			// -------------------------------------------
			// 'member_member_login_single' hook.
			//  - Additional processing when a member is logging into single site
			//
				$edata = $this->EE->extensions->call('member_member_login_single', $this->_hook_data());
				if ($this->EE->extensions->end_script === TRUE) return;
			//
			// -------------------------------------------
		}

		// Delete old password lockouts		
		$this->EE->session->delete_password_lockout();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Session id getter session
	 *
	 * Only works after the session has been started
	 *
	 * @access	public
	 */
	public function session_id()
	{
		return $this->session_id;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Session id setter
	 *
	 * Tells start_session to latch onto an existing session for
	 * the multi site login
	 *
	 * @access	public
	 */
	public function use_session_id($session_id)
	{
		$this->session_id = $session_id;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Hook data utility method
	 *
	 * We cannot change the hook parameter without lots of warning, so
	 * this is a silly workaround. Doing a clone aslo isolates the hook
	 * from the rest of the code, which I like.
	 *
	 * @access	private
	 */
	private function _hook_data()
	{
		$obj = clone $this->member;
		$obj->session_id = $this->session_id;
		$obj->can_access_cp = $this->has_permission('can_access_cp');
		
		return $obj;
	}
}
// END Auth_member class


/* End of file Authentication.php */
/* Location: ./libraries/Authentication.php */