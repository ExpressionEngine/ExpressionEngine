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
 * @category	Library
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
/**
 * Dealing with users has three parts:
 *
 * 1. Session
 *		- Ties a request to a unique user
 *		- Handles persistent information about that user
 *
 * 2. Authentication
 *		- Handles logging in and out
 *		- Passes user off to sessions on success
 *
 * 3. Permissions (todo: library)
 *		- Deals with group-ing users
 *		- Handles more granular user access
 *		- Currently handled mostly by userdata (can_* and group_id)
 *
 */
class Auth {

	private $EE;

	// Hashing algorithms to try with their respective
	// byte sizes. Previous versions of PHP and EE used
	// weaker hashing, so the code tries to update users
	// to the best that is available in their environment.
	// The byte sizes are used to identify the has, so they
	// must be unique!
	
	// Dev Note: In EE's db the password and salt column
	// should always be as long as the best available algo.
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
		$this->hash_algos = array($this->hash_algos, hash_algos());
	}
	
	// --------------------------------------------------------------------

	/**
	 * Authenticate with an id
	 *
	 * @access	public
	 */
	public function authenticate_id($id, $password)
	{
		$member = $this->EE->db->get('members', array('member_id' => $id));
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
		$member = $this->EE->db->get('members', array('email' => $email));
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
		$member = $this->EE->db->get('members', array('username' => $username));
		return $this->_authenticate($member, $password);
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

		// No hash function specified? Use the best one
		// we have access to in this environment.
		if ($salt === FALSE)
		{
			$salt = '';

			for ($i = 0; $i < $h_byte_size; $i++)
			{
				// The salt should never be displayed, so any
				// visible ascii character is fair game.
				$salt .= chr(mt_rand(33, 126));
			}
		}
		
		return array(
			'salt'		=> $salt,
			'password'	=> hash($salt.$password, $this->hash_algos[$h_byte_size])
		);
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
		if ($member->num_rows() !== 1)
		{
			return FALSE;
		}
		
		$m_salt = $member->row('salt');
		$m_pass = $member->row('password');
		
		// hash using the algo used for this password
		$h_byte_size = strlen($m_pass);
		$hashed_pair = $this->hash_password($m_pass, $m_salt, $h_byte_size);
		
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
}
// END Auth class


class Auth_result {

	private $EE;
	private $group;
	private $member;
	
	function __construct(array $member)
	{
		$this->EE =& get_instance();
		
		$this->member = $member;
	}
	
	// --------------------------------------------------------------------
	
	public function has_permission($perm)
	{
		return ($this->group($perm) === 'y');
	}
	
	// --------------------------------------------------------------------
	
	public function member($key, $default = FALSE)
	{
		return isset($this->member->$key) ? $this->member->$key : $default;
	}
	
	// --------------------------------------------------------------------
	
	public function group($key, $default = FALSE)
	{
		if ( ! is_object($this->group))
		{
			$group_q = $this->EE->db->get_where('member_groups', array(
				'group_id' => $this->member('group_id')
			));
			
			$this->group = $group_q->row();
			
			$group_q->free_result();
		}
		
		return isset($this->group->$key) ? $this->group->$key : $default;
	}
	
	// --------------------------------------------------------------------
	
	public function is_banned()
	{
		if ($this->member('group_id') != 1)
		{
			return $this->EE->session->ban_check();
		}
		
		return FALSE;
	}
	
	// --------------------------------------------------------------------
	
	public function hook_data()
	{
		$obj = clone $this->member;
		$obj->can_access_cp = $this->group->has_permission('can_access_cp');
	}
	
	public function create_session()
	{
		
	}
}
// END Auth_member class


/* End of file Authentication.php */
/* Location: ./libraries/Authentication.php */