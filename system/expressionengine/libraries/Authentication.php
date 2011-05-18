<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
class Authentication {

	// Hashing algorithms to try with their respective
	// byte sizes. Previous versions of PHP and EE used
	// weaker hashing, so the code tries to update users
	// to the best that is available in their environment.
	
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
		
		// Even for md5 collisions usually happen above 1024 bits, so
		// we artifically limit their password to reasonable size.
		if ( ! $password OR strlen($password) > 250)
		{
			return FALSE;
		}
		
		
		$m_salt = $member->row('salt');
		$m_pass = $member->row('password');
		
		// the algo used for the stored pass
		$m_byte_size = strlen($m_pass);

		
		if ( ! array_key_exists($m_byte_size, $this->hash_algos))
		{
			// this is either a corrupt db or they moved to an
			// environment with less sophisticated hash support
			// @todo figure out how to error
			die('Fatal Error: No matching hash algorithm.');
		}
		
		
		// compare using the original algo
		$h_algo = $this->hash_algos[$m_byte_size];
		$h_pass = hash($m_salt.$password, $h_algo);
		
		if ($m_pass != $h_pass)
		{
			return FALSE;
		}
		
		
		// Officially a valid user, but are they as secure as possible?
		// ----------------------------------------------------------------
				
		reset($this->hash_algos);
		
		// Better algo available?
		if ($m_byte_size != key($this->hash_algos))
		{
			$m_salt = '';
			$h_algo = current($this->hash_algos);
			$m_byte_size = key($this->hash_algos);
		}
		
		// Not salted or changing algo?
		if ($m_salt == '')
		{
			$m_id = $member->row('member_id');
			
			// The salt should never be displayed, so any
			// visible ascii character is fair game.
			for ($i = 0; $i < $m_byte_size; $i++)
			{
				$m_salt .= chr(mt_rand(33, 126));
			}
			
			// We have everything, update them
			$h_pass = hash($m_salt.$password, $h_algo);
			
			$this->EE->db->where('member_id', $m_id);
			$this->EE->db->update('members', array(
				'salt'		=> $m_salt,
				'password'	=> $h_pass
			));
		}
		
		return TRUE;
	}
}

// END Authentication class


/* End of file Authentication.php */
/* Location: ./libraries/Authentication.php */