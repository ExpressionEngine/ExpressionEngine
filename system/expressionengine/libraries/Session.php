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
 * ExpressionEngine Core Session Class
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

  1. Session*
  2. Authentication
  3. Permissions

There are three validation types, set in the config file: 
 
  1. User cookies AND session ID (cs)
		
	This is the most secure way to run a site.  Three cookies are set:
	1. Session ID - This is a unique hash that is randomly generated when 
					someone logs in.
	2. Password hash - The encrypted password of the current user
	3. Unique ID - The permanent unique ID hash associated with the account.
	
	All three cookies expire when you close your browser OR when you have been 
	inactive longer than two hours (one hour in the control panel).
	
	Using this setting does NOT allow 'stay logged-in' capability, as each 
	session has a finite lifespan.

  2. Cookies only - no session ID (c)
	
	With this validation type, a session is not generated, therefore
	users can remain permanently logged in.
	
	This setting is obviously less secure because it does not provide a safety net
	if you share your computer or access your site from a public computer.
	It relies solely on the password/unique_id cookies.

  3. Session ID only (s).  
	
	Most compatible as it does not rely on cookies at all.  Instead, a URL 
	query string ID is used.
	
	No stay-logged in capability.  The session will expire after one hour of 
	inactivity, so in terms of security, it is preferable to number 2.
	
	NOTE: The control panel and public pages can each have their own session preference.
*/

class EE_Session {
	
	public $user_session_len	 = 7200;  // User sessions expire in two hours
	public $cpan_session_len	 = 3600;  // Admin sessions expire in one hour

	public $c_session			= 'sessionid';
	public $c_expire			= 'expiration';
	public $c_remember			= 'remember';
	public $c_anon				= 'anon';
	public $c_prefix			= '';
	
	public $sdata				= array();
	public $userdata		 	= array();
	public $tracker				= array();
	public $flashdata			= array();
	
	public $sess_crypt_key		= '';
	
	public $session_length		= '';
	public $validation_type  	= '';
	
	public $access_cp			= FALSE;
	public $cookies_exist		= FALSE;
	public $session_exists		= FALSE;
	
	// Garbage collection probability. Used to kill expired sessions.
	public $gc_probability		= 5;
	
	// Store data for just this page load.  
	// Multi-dimensional array with module/class name, 
	// e.g. $this->cache['module']['var_name']
	// Use set_cache() and cache() methods.
	public $cache				= array();

	protected $EE;

	/**
	 * Session Class Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		$ban_status = $this->_do_ban_check();

		$this->session_length = $this->_setup_session_length();

		// Set Default Session Values 
		// Set USER-DATA as GUEST until proven otherwise	
		$this->userdata = array(
			'username'			=> $this->EE->input->cookie('my_name'),
			'screen_name'		=> '',
			'email'				=> $this->EE->input->cookie('my_email'),
			'url'				=> $this->EE->input->cookie('my_url'),
			'location'			=> $this->EE->input->cookie('my_location'),
			'language'			=> '',
			'timezone'			=> ($this->EE->config->item('default_site_timezone') && $this->EE->config->item('default_site_timezone') != '') ? $this->EE->config->item('default_site_timezone') : $this->EE->config->item('server_timezone'),
			'daylight_savings'  => ($this->EE->config->item('default_site_dst') && $this->EE->config->item('default_site_dst') != '') ? $this->EE->config->item('default_site_dst') : $this->EE->config->item('daylight_savings'),
			'time_format'		=> ($this->EE->config->item('time_format') && $this->EE->config->item('time_format') != '') ? $this->EE->config->item('time_format') : 'us',
			'group_id'			=> '3',
			'access_cp'			=>  0,
			'last_visit'		=>  0,
			'is_banned'			=>  $ban_status,
			'ignore_list'		=>  array()
		);

		// Set SESSION data as GUEST until proven otherwise
		$this->sdata = array(
			'session_id' 		=>  0,
			'member_id'  		=>  0,
			'admin_sess' 		=>  0,
			'ip_address' 		=>  $this->EE->input->ip_address(),
			'user_agent' 		=>  substr($this->EE->input->user_agent(), 0, 120),
			'last_activity'		=>  0
		);
		
		// -------------------------------------------
		// 'sessions_start' hook.
		//  - Reset any session class variable
		//  - Override the whole session check
		//  - Modify default/guest settings
		//
			$edata = $this->EE->extensions->universal_call('sessions_start', $this);
			if ($this->EE->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		if ($this->EE->input->cookie($this->c_session))
		{
			$this->cookies_exist = TRUE;
			$this->sdata['session_id'] = $this->EE->input->cookie($this->c_session);			
		}
		else
		{
			if ($this->EE->input->get('S') && $this->EE->input->get('S') != 0)
			{
				$this->sdata['session_id'] = $this->EE->input->get('S');
			}
			elseif ($this->EE->uri->session_id != '')
			{
				$this->sdata['session_id'] = $this->EE->uri->session_id;
			}			
		}
		
		// Fetch remember me cookie
		if ($this->EE->input->cookie($this->c_remember))
		{
			$this->cookies_exist = TRUE;
		}

		// Set the Validation Type
		if (REQ == 'CP')
		{
			$this->validation = ( ! in_array($this->EE->config->item('admin_session_type'), array('cs', 'c', 's'))) ? 'cs' : $this->EE->config->item('admin_session_type');
		}
		else
		{
			$this->validation = ( ! in_array($this->EE->config->item('user_session_type'), array('cs', 'c', 's'))) ? 'cs' : $this->EE->config->item('user_session_type');
		}
		
		// Do session IDs exist?
		switch ($this->validation)
		{
			case 'cs'	: $session_id = ($this->sdata['session_id'] != '0' AND $this->cookies_exist == TRUE) ? TRUE : FALSE;
				break;
			case 'c'	: $session_id = ($this->cookies_exist) ? TRUE : FALSE;
				break;
			case 's'	: $session_id = ($this->sdata['session_id'] != '0') ? TRUE : FALSE;
				break;
		}
		
		// Fetch Session Data		
		// IMPORTANT: The session data must be fetched before the member data so don't move this.
		if ($session_id  === TRUE)
		{
			if ($this->fetch_session_data() === TRUE) 
			{
				$this->session_exists = TRUE;
			}
		}

		// Fetch Member Data
		$member_data_exists = ($this->fetch_member_data() === TRUE) ? TRUE : FALSE;
		
		// Update/Create Session						
		if ($session_id === FALSE OR $member_data_exists === FALSE)
		{ 
			$this->fetch_guest_data();
		}
		else
		{
			if ($this->session_exists === TRUE)
			{
				$this->update_session();
			}
			else
			{
				if ($this->validation == 'c')
				{
					$this->create_new_session($this->userdata['member_id']);
				}
				else
				{
					$this->fetch_guest_data();
				}
			}
		}
		
		// Update cookies
		$this->update_cookies();
		$this->_prep_flashdata();
		
		// Fetch "tracker" cookie
		if (REQ != 'CP')
		{					 
			$this->tracker = $this->tracker();
		}
		
		// Kill old sessions
		$this->delete_old_sessions(); 
		
		// Merge Session and User Data Arrays		
		// We merge these into into one array for portability
		$this->userdata = array_merge($this->userdata, $this->sdata);
		
		// -------------------------------------------
		// 'sessions_end' hook.
		//  - Modify the user's session/member data.
		//  - Additional Session or Login methods (ex: log in to other system)
		//
			$edata = $this->EE->extensions->universal_call('sessions_end', $this);
			if ($this->EE->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------
		
		// Garbage collection
		
		unset($this->sdata);
		unset($session_id);
		unset($ban_status);
		unset($member_data_exists);
	}

	// --------------------------------------------------------------------				

	/**
	 * Fetch all session data
	 *
	 * @return	array
	 */
	public function all_userdata()
	{
		return $this->userdata;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Check for banned data
	 */  
	public function ban_check($type = 'ip', $match = '')
	{
		switch ($type)
		{
			case 'ip'			: $ban = $this->EE->config->item('banned_ips');
								  $match = $this->EE->input->ip_address();
				break;
			case 'email'		: $ban = $this->EE->config->item('banned_emails');
				break;
			case 'username'		: $ban = $this->EE->config->item('banned_usernames');
				break;
			case 'screen_name'	: $ban = $this->EE->config->item('banned_screen_names');
				break;
		}
		
		if ($ban == '')
		{
			return FALSE;
		}
		
		foreach (explode('|', $ban) as $val)
		{
			if ($val == '*') continue;
			
			if (substr($val, -1) == '*')
			{
				$val = str_replace('*', '', $val);
				
				if (strncmp($match, $val, strlen($val)) == 0)
				{
					return TRUE;
				}
			}
			elseif (strncmp($val, '*', 1) == 0)
			{
				$val = str_replace('*', '', $val);
			
				if (substr($match, - strlen($val)) == $val)
				{
					return TRUE;
				}
			}
			elseif ($val == $match)
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get Session Cache 
	 *
	 * This method extracts a value from the session cache.
	 *
	 * @param 	string 	Super Class/Unique Identifier
	 * @param 	string 	Key to extract from the cache.
	 * @param 	mixed 	Default value to return if key doesn't exist
	 * @return 	mixed
	 */
	public function cache($class, $key, $default = FALSE)
	{
		return (isset($this->cache[$class][$key])) ? $this->cache[$class][$key] : $default; 
	}


	// --------------------------------------------------------------------		

	/**
	 * Check password lockout
	 */
	function check_password_lockout($username = '')
	{
		if ($this->EE->config->item('password_lockout') == 'n' OR 
			$this->EE->config->item('password_lockout_interval') == '')
		{
		 	return FALSE; 
		} 
		
		$interval = $this->EE->config->item('password_lockout_interval') * 60;
		
		$expire = time() - $interval;
		$p_where = "(user_agent ='{$this->EE->db->escape_str($this->userdata['user_agent'])}' OR username='{$this->EE->db->escape_str($username)}')";

		$lockout = $this->EE->db->select("COUNT(*) as count")
								->where('login_date > ', $expire)
								->where('ip_address', $this->EE->input->ip_address())
								->where($p_where)
								->get('password_lockout');
		
								
		
		return ($lockout->row('count') >= 4) ? TRUE : FALSE;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Create New Session
	 *
	 * @param 	int 		member_id 
	 * @param 	boolean		admin session or not
	 * @return 	string 		Session ID
	 */
	public function create_new_session($member_id, $admin_session = FALSE)
	{
		if ($this->validation == 'c' AND $this->access_cp == TRUE)
		{
			$this->sdata['admin_sess'] = 1;
		}
		else
		{
			$this->sdata['admin_sess'] 	= ($admin_session == FALSE) ? 0 : 1;  
		}
		
		$this->sdata['session_id'] 		= $this->EE->functions->random();  
		$this->sdata['ip_address']  	= $this->EE->input->ip_address();  
		$this->sdata['member_id']  		= (int) $member_id; 
		$this->sdata['last_activity']	= $this->EE->localize->now;  
		$this->sdata['user_agent']		= substr($this->EE->input->user_agent(), 0, 120);
		$this->userdata['member_id']	= (int) $member_id;  
		$this->userdata['session_id']	= $this->sdata['session_id'];
		$this->userdata['site_id']		= $this->EE->config->item('site_id');
		
		$this->EE->functions->set_cookie($this->c_session , $this->sdata['session_id'], $this->session_length);	
		
		$this->EE->db->query($this->EE->db->insert_string('exp_sessions', $this->sdata));	

		return $this->sdata['session_id'];
	}

	// --------------------------------------------------------------------
	 
	/**
	 * Delete old sessions if probability is met
	 *
	 * By default, the probability is set to 10 percent.
	 * That means sessions will only be deleted one
	 * out of ten times a page is loaded.
	 */
	public function delete_old_sessions()
	{
		$expire = $this->EE->localize->now - $this->session_length;
  
		srand(time());
  
		if ((rand() % 100) < $this->gc_probability) 
		{
			$this->EE->db->where('last_activity < ', $expire)
						 ->delete('sessions');
		}	
	}

	// --------------------------------------------------------------------		
	
	/**
	 * Delete old password lockout data
	 */		
	public function delete_password_lockout()
	{
		if ($this->EE->config->item('password_lockout') == 'n')
		{
		 	return FALSE; 
		} 
				
		$interval = $this->EE->config->item('password_lockout_interval') * 60;
		
		$expire = time() - $interval;
  
		srand(time());
  
		if ((rand() % 100) < $this->gc_probability) 
		{
			$this->EE->db->where('login_date <', $expire)
						 ->delete('password_lockout');
		}	
	}
	
	
	// --------------------------------------------------------------------
	 
	/**
	 * Destroy session. Essentially logging a user off.
	 */
	public function destroy()
	{
		$this->EE->db->where('session_id', $this->userdata['session_id']);
		$this->EE->db->delete('sessions');
		
		// Really should redirect after calling this
		// method, but if someone doesn't - we're safe
		$this->fetch_guest_data();
		
		$this->EE->functions->set_cookie($this->c_session);
		$this->EE->functions->set_cookie($this->c_remember);
		$this->EE->functions->set_cookie($this->c_expire);	
		$this->EE->functions->set_cookie($this->c_anon);
		$this->EE->functions->set_cookie('tracker'); 
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch guest data
	 */
	public function fetch_guest_data()
	{
		$qry = $this->EE->db->where('site_id', $this->EE->config->item('site_id'))
							->where('group_id', (int) 3)
							->get('member_groups');
			
		foreach ($qry->row_array() as $key => $val)
		{			
			$this->userdata[$key] = $val;				 
		}

		$this->userdata['total_comments']		= 0;				 
		$this->userdata['total_entries']		= 0;	 
		$this->userdata['private_messages']		= 0;
		$this->userdata['total_forum_posts']	= 0;
		$this->userdata['total_forum_topics']	= 0;
		$this->userdata['total_forum_replies']	= 0;
		$this->userdata['display_signatures']	= 'y';	  
		$this->userdata['display_avatars']		= 'y'; 
		$this->userdata['display_photos']		= 'y'; 
		$this->userdata['parse_smileys']		= 'y'; 

		// The following cookie info is only used with the forum module.
		// It enables us to track "read topics" with users who are not
		// logged in.
		
		// Cookie expiration:  One year
		$expire = (60*60*24*365);
		
		// Has the user been active before? If not we set the "last_activity" to the current time.
		$this->sdata['last_activity'] = ( ! $this->EE->input->cookie('last_activity')) ? $this->EE->localize->now : $this->EE->input->cookie('last_activity');
		
		// Is the "last_visit" cookie set?  If not, we set the last visit 
		// date to ten years ago. This is a kind of funky thing to do but 
		// it enables the forum to show all topics as unread. Since the 
		// last_visit stats are only available for logged-in members it 
		// doesn't hurt anything to set it this way for guests.
		if ( ! $this->EE->input->cookie('last_visit'))
		{
			$this->userdata['last_visit'] = $this->EE->localize->now-($expire*10);
			$this->EE->functions->set_cookie('last_visit', $this->userdata['last_visit'], $expire);		
		}
		else
		{
			$this->userdata['last_visit'] = $this->EE->input->cookie('last_visit');
		}

		// If the user has been inactive longer than the session length we'll
		// set the "last_visit" cooke with the "last_activity" date.
				
		if (($this->sdata['last_activity'] + $this->session_length) < $this->EE->localize->now) 
		{
			$this->userdata['last_visit'] = $this->sdata['last_activity'];
			$this->EE->functions->set_cookie('last_visit', $this->userdata['last_visit'], $expire);	
		}
		
		// Update the last activity with each page load
		$this->EE->functions->set_cookie('last_activity', $this->EE->localize->now, $expire);			
	}

	// --------------------------------------------------------------------		

	/**
	 * Fetch member data
	 */
	public function fetch_member_data()
	{
		if ($this->EE->config->item('enable_db_caching') == 'y' AND REQ == 'PAGE')
		{
			$this->EE->db->cache_off();
		}

		$member_query = $this->_do_member_query();

		if ($member_query->num_rows() == 0)
		{
			$this->_initialize_session();
			return FALSE;
		}
		
		// Turn the query rows into array values
		foreach ($member_query->row_array() as $key => $val)
		{
			if ($key != 'crypt_key')
			{
				$this->userdata[$key] = $val;
			}
			else
			{
				// we don't add the session encryption key to userdata, to avoid accidental disclosure
				if ($val == '')
				{
					// not set yet, so let's create one and udpate it for this user
					$this->sess_crypt_key = $this->EE->functions->random('encrypt', 16);
					$this->EE->db->update('members', array('crypt_key' => $this->sess_crypt_key), 
													 array('member_id' => (int) $member_query->row('member_id')));
				}
				else
				{
					$this->sess_crypt_key = $val;
				}
			}
		}
		
		// Create the array for the Ignore List
		$this->userdata['ignore_list'] = ($this->userdata['ignore_list'] == '') ? array() : explode('|', $this->userdata['ignore_list']);
		
		// Fix the values for forum posts and replies
		$this->userdata['total_forum_posts'] = $member_query->row('total_forum_topics')  + $member_query->row('total_forum_posts') ;
		$this->userdata['total_forum_replies'] = $member_query->row('total_forum_posts') ;
		
		$this->userdata['display_photos'] = $this->userdata['display_avatars'];
		
		//  Are users allowed to localize?
		if ($this->EE->config->item('allow_member_localization') == 'n')
		{
			$this->userdata['timezone'] = ($this->EE->config->item('default_site_timezone') && $this->EE->config->item('default_site_timezone') != '') ? $this->EE->config->item('default_site_timezone') : $this->EE->config->item('server_timezone');
			$this->userdata['daylight_savings'] = ($this->EE->config->item('default_site_dst') && $this->EE->config->item('default_site_dst') != '') ? $this->EE->config->item('default_site_dst') : $this->EE->config->item('daylight_savings');
			$this->userdata['time_format'] = ($this->EE->config->item('time_format') && $this->EE->config->item('time_format') != '') ? $this->EE->config->item('time_format') : 'us';
 		}
						
		// Assign Sites, Channel, Template, and Module Access Privs	
		if (REQ == 'CP')
		{
			$this->_setup_channel_privs();

			$this->_setup_module_privs();

			$this->_setup_template_privs();
			
			$this->_setup_assigned_sites();
		}
		
		
		// Does the member have admin privileges?
		
		if ($member_query->row('can_access_cp') == 'y')
		{
			$this->access_cp = TRUE;
		}
		else
		{
			$this->sdata['admin_sess'] = 0; 
		}
		
		// Update the session array with the member_id
		
		if ($this->validation == 'c')
		{
			$this->sdata['member_id'] = (int) $member_query->row('member_id');  
		}
			 
		// If the user has been inactive for longer than the session length
		// we'll update their last_visit item so that it contains the last_activity
		// date.  That way, we can show the exact time they were last visitng the site.

		if (($this->userdata['last_visit'] == 0) OR
			(($member_query->row('last_activity')  + $this->session_length) < $this->EE->localize->now))
		{	
			$last_act = ($member_query->row('last_activity') > 0) ? $member_query->row('last_activity')  : $this->EE->localize->now;
		
			$this->EE->db->where('member_id', (int) $this->sdata['member_id']);
			$this->EE->db->update('members', array('last_visit' 	=> $last_act,
													'last_activity' => $this->EE->localize->now));
		
			$this->userdata['last_visit'] = $member_query->row('last_activity') ;
		}		
						
		// Update member 'last activity' date field for this member.
		// We update this ever 5 minutes.  It's used with the session table
		// so we can update sessions
		
		if (($member_query->row('last_activity')  + 300) < $this->EE->localize->now)	 
		{
			$this->EE->db->where('member_id', (int) $this->sdata['member_id']);
			$this->EE->db->update('members', array('last_activity' => $this->EE->localize->now));
		}

		$member_query->free_result();

		if ($this->EE->config->item('enable_db_caching') == 'y' AND REQ == 'PAGE')
		{
			$this->EE->db->cache_on();
		}

		return TRUE;  
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch session data
	 *
	 * @return 	boolean
	 */
	public function fetch_session_data()
	{
		// Look for session.  Match the user's IP address and browser for added security.
		$this->EE->db->select('member_id, admin_sess, last_activity')
					 ->where('session_id', (string) $this->sdata['session_id'])
					 ->where('ip_address', $this->sdata['ip_address'])
					 ->where('user_agent', $this->sdata['user_agent']);

		$query = $this->EE->db->get('sessions');
		
		if ($query->num_rows() == 0 OR $query->row('member_id') == 0)
		{
			$this->_initialize_session();
		
			return FALSE;				
		}
		
		// Assign member ID to session array
		$this->sdata['member_id'] = (int) $query->row('member_id');
		
		// Is this an admin session?		
		$this->sdata['admin_sess'] = ($query->row('admin_sess') == 1) ? 1 : 0;
		
		// Log last activity
		$this->sdata['last_activity'] = $query->row('last_activity') ;
		
		// If session has expired, delete it and set session data to GUEST
		if ($this->validation != 'c')
		{
			if ($query->row('last_activity')  < ($this->EE->localize->now - $this->session_length))
			{
				$this->EE->db->delete('sessions', array(
							'session_id' => $this->sdata['session_id']));
				
				$this->_initialize_session();
				
				return FALSE;
			}
		}
			
		return TRUE;		
	}

	// --------------------------------------------------------------------

	/**
	 * Get flashdata by key
	 *
	 * @param	string
	 * @return	mixed
	 */
	public function flashdata($key = '')
	{
		return isset($this->flashdata[$key]) ? $this->flashdata[$key] : FALSE;
	}

	// --------------------------------------------------------------------	
	
	/** 
	 * Is the nation banned?
	 */
	public function nation_ban_check($show_error = TRUE)
	{
		if ($this->EE->config->item('require_ip_for_posting') != 'y' OR $this->EE->config->item('ip2nation') != 'y')
		{
			return FALSE;
		}

		$query = $this->EE->db->query("SELECT country FROM exp_ip2nation WHERE ip < INET_ATON('".$this->EE->db->escape_str($this->EE->input->ip_address())."') ORDER BY ip DESC LIMIT 0,1");
				
		if ($query->num_rows() == 1)
		{
			$res = $this->EE->db->select("COUNT(*) as count")
								->where('code', $query->row('country'))
								->where('banned', 'y')
								->get('ip2nation_countries');
			
			if ($res->row('count')  > 0)
			{
				if ($show_error == TRUE)
				{
					return $this->EE->output->fatal_error($this->EE->config->item('ban_message'), 0);					
				}

				return FALSE;
			}
		}
	}

	// --------------------------------------------------------------------	
	
	/**
	 * Save password lockout
	 */
	function save_password_lockout($username = '')
	{
		if ($this->EE->config->item('password_lockout') == 'n')
		{
		 	return FALSE; 
		} 

		$data = array(
						'login_date'	=> time(),
						'ip_address'	=> $this->EE->input->ip_address(),
						'user_agent'	=> $this->userdata['user_agent'],
						'username'		=> $username
					);
					
		$this->EE->db->insert('password_lockout', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Set Session Cache
	 *
	 * This method is a setter for the $cache class variable.
	 * Note, this is not persistent across requests
	 *
	 * @param 	string 	Super Class/Unique Identifier
	 * @param 	string 	Key for cached item
	 * @param 	mixed 	item to put in the cache
	 * @return 	object
	 */
	public function set_cache($class, $key, $val)
	{
		if ( ! isset($this->cache[$class]))
		{
			$this->cache[$class] = array();
		}

		$this->cache[$class][$key] = $val;
		return $this;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Set flashdata
	 *
	 * @param	mixed
	 * @return	mixed
	 */
	public function set_flashdata($key, $val = '')
	{
		if ( ! is_array($key))
		{
			$key = array($key => $val);
		}
		
		foreach($key as $k => $v)
		{
			$this->flashdata[':new:'.$k] = $v;
		}

		$this->_set_flash_cookie();
	}

	// --------------------------------------------------------------------		 

	/**
	 * Tracker
	 *
	 * This functions lets us store the visitor's last five pages viewed
	 * in a cookie.  We use this to facilitate redirection after logging-in,
	 * or other form submissions
	 */
	public function tracker()
	{	
		$tracker = $this->EE->input->cookie('tracker');

		if ($tracker != FALSE)
		{
			$regex = "#(http:\/\/|https:\/\/|www\.|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#i";

			if (preg_match($regex, $tracker))
			{
				return array();
			}
				
			if (stristr($tracker, ':') !== FALSE)
			{
				$tracker_parts = explode(':', $tracker);
				
				if (current($tracker_parts) != 'a' OR count($tracker_parts) < 3 OR ! is_numeric(next($tracker_parts)))
				{
					return array();
				}
			}
			
			$tracker = unserialize(stripslashes($tracker));
		}
		
		if ( ! is_array($tracker))
		{
			$tracker = array();
		}
				
		$uri = ($this->EE->uri->uri_string == '') ? 'index' : $this->EE->uri->uri_string;
		
		$uri = str_replace("\\", "/", $uri); 
		
		// If someone is messing with the URI we won't set the cookie
	
		if ( ! isset($_GET['ACT']) && preg_match('/[^a-z0-9\%\_\/\-]/i', $uri))
		{
			return array();
		}
		
		if ( ! isset($_GET['ACT']))
		{
			if ( ! isset($tracker['0']))
			{
				$tracker[] = $uri;
			}
			else
			{
				if (count($tracker) == 5)
				{
					array_pop($tracker);
				}

				if ($tracker['0'] != $uri)
				{
					array_unshift($tracker, $uri);
				}
			}
		}
		
		if (REQ == 'PAGE')
		{		
			$this->EE->functions->set_cookie('tracker', serialize($tracker), '0'); 
		}
		
		return $tracker;
	}

	// -------------------------------------------------------------------- 
	
	/**
	 * Update Cookies
	 */  
	function update_cookies()
	{
		if ($this->cookies_exist == TRUE AND $this->EE->input->cookie($this->c_expire))
		{
			$now 	= time() + 300;
			$expire = 60*60*24*365;
			
			if ($this->EE->input->cookie($this->c_expire) > $now)
			{ 
				$this->EE->functions->set_cookie($this->c_expire , time()+$expire, $expire);
			}
		}
	}

	// --------------------------------------------------------------------	

	/**
	 * Update Member session
	 */
	public function update_session()
	{
		$this->sdata['last_activity'] = $this->EE->localize->now;
		
		$this->EE->db->query($this->EE->db->update_string('exp_sessions', $this->sdata, "session_id ='".$this->EE->db->escape_str($this->sdata['session_id'])."'")); 

		// Update session ID cookie
		
		if ($this->validation != 's')
		{
			$this->EE->functions->set_cookie($this->c_session , $this->sdata['session_id'],  $this->session_length);	
		}
			
		// If we only require cookies for validation, set admin session.	
			
		if ($this->validation == 'c'  AND  $this->access_cp == TRUE)
		{			
			$this->sdata['admin_sess'] = 1;
		}	
		
		// We'll unset the "last activity" item from the session data array.
		// We do this to avoid a conflict with the "last_activity" item in the
		// userdata array since we'll be merging the two arrays in a later step
		unset($this->sdata['last_activity']);
	}  

	// --------------------------------------------------------------------
	
	/**
	 * Fetch a session item
	 *
	 * @param 	string 		Userdata item to return
	 * @param 	default 	value returned if the key isn't set
	 * @return 	mixed 		$default on failure, item on success
	 */	
	public function userdata($which, $default = FALSE)
	{  
		return ( ! isset($this->userdata[$which])) ? $default : $this->userdata[$which];
	}

	// --------------------------------------------------------------------

	/**
	 * Age flashdata
	 * 
	 * Removes old, marks current as old, etc
	 *
	 * @return	void
	 */
	public function _age_flashdata()
	{
		foreach($this->flashdata as $key => $val)
		{
			if (strpos($key, ':old:') !== 0)
			{
				if (strpos($key, ':new:') === 0)
				{
					$this->flashdata[substr($key, 5)] = $val;
				}
				else
				{
					$this->flashdata[':old:'.$key] = $val;
				}
			}
			
			unset($this->flashdata[$key]);
		}
		
		$this->_set_flash_cookie();
	}

	// --------------------------------------------------------------------	

	/**
	 * Do ban Check
	 *
	 * @return 	boolean	
	 */
	protected function _do_ban_check()
	{
		// Is the user banned?
		// We only look for banned IPs if it's not a control panel request.
		// We test for banned admins separately in the front controller
		$ban_status = FALSE;
		
		if (REQ != 'CP')
		{
			if ($this->ban_check('ip'))
			{
				switch ($this->EE->config->item('ban_action'))
				{
					case 'message' : 
						return $this->EE->output->fatal_error($this->EE->config->item('ban_message'), 0);
						break;
					case 'bounce'  : 
						$this->EE->functions->redirect($this->EE->config->item('ban_destination')); exit;
						break;
					default		: 
						$ban_status = TRUE;
						break;		
				}
			}
		}

		return $ban_status;
	}

	// -------------------------------------------------------------------- 

	/**
	 * Perform the big query to grab member data
	 *
	 * @return 	object 	database result.
	 */
	protected function _do_member_query()
	{
		// Query DB for member data.  Depending on the validation type we'll
		// either use the cookie data or the member ID gathered with the session query.
		
		$this->EE->db->from(array('members m', 'member_groups g'))
					 ->where('g.site_id', (int) $this->EE->config->item('site_id'))
					 ->where('m.group_id', ' g.group_id', FALSE);
		
		// remember me
		if ($this->sdata['member_id'] == 0 &&
			$this->validation == 'c' &&
			$this->EE->input->cookie($this->c_remember))
		{
			$this->EE->db->where('remember_me', $this->EE->input->cookie($this->c_remember));
		}
		else
		{
			$this->EE->db->where('member_id', (int) $this->sdata['member_id']);
		}
				
		return $this->EE->db->get();
	}

	// --------------------------------------------------------------------	

	/**
	 * Reset session data as GUEST
	 *
	 * @return 	void
	 */
	protected function _initialize_session()
	{  
		$this->sdata['session_id'] = 0;	
		$this->sdata['admin_sess'] = 0;
		$this->sdata['member_id']  = 0;
	}

	// --------------------------------------------------------------------

	/**
	 * Prep flashdata
	 *
	 * Grabs the cookie and validates the signature
	 *
	 * @return	void
	 */
	protected function _prep_flashdata()
	{		
		if ($cookie = $this->EE->input->cookie('flash'))
		{
			if (strlen($cookie) > 32)
			{
				$signature = substr($cookie, -32);
				$payload = substr($cookie, 0, -32);

				if (md5($payload.$this->sess_crypt_key) == $signature)
				{
					$this->flashdata = unserialize(stripslashes($payload));
					$this->_age_flashdata();
					
					return;
				}
			}
		}
		
		$this->flashdata = array();
	}

	// --------------------------------------------------------------------	

	/**
	 * Set signed flashdata cookie
	 *
	 * @return	void
	 */
	protected function _set_flash_cookie()
	{
		// Don't want to hash the crypt key by itself
		$payload = '';

		if (count($this->flashdata) > 0)
		{
			$payload = serialize($this->flashdata);
			$payload = $payload.md5($payload.$this->sess_crypt_key);
		}

		$this->EE->functions->set_cookie('flash' , $payload, 86500);
	}

	// --------------------------------------------------------------------	

	/**
	 * Setup Assigned Sites
	 *
	 * @return void
	 */
	protected function _setup_assigned_sites()
	{
		// Fetch Assigned Sites Available to User
		
		$assigned_sites = array();
		
		if ($this->userdata['group_id'] == 1)
		{
			$qry = $this->EE->db->select('site_id, site_label')
								->order_by('site_label')
								->get('sites');
		}
		else
		{
			// Groups that can access the Site's CP, see the site in the 'Sites' pulldown
			$qry = $this->EE->db->select('es.site_id, es.site_label')
								->from(array('sites es', 'member_groups mg'))
								->where('mg.site_id', ' es.site_id', FALSE)
								->where('mg.group_id', $this->userdata['group_id'])
								->where('mg.can_access_cp', 'y')
								->order_by('es.site_label')
								->get();
		}
		
		if ($qry->num_rows() > 0)
		{
			foreach ($qry->result() as $row)
			{
				$assigned_sites[$row->site_id] = $row->site_label;
			}
		}
		
		$this->userdata['assigned_sites'] = $assigned_sites;
	}

	// --------------------------------------------------------------------	

	/**
	 * Setup CP Channel Privileges
	 *
	 * @return void
	 */
	protected function _setup_channel_privs()
	{
		// Fetch channel privileges
		
		$assigned_channels = array();
	 
		if ($this->userdata['group_id'] == 1)
		{
			$this->EE->db->select('channel_id, channel_title');
			$this->EE->db->order_by('channel_title');
			$res = $this->EE->db->get_where('channels', 
											array('site_id' => $this->EE->config->item('site_id')));
		}
		else
		{
			$res = $this->EE->db->select('ec.channel_id, ec.channel_title')
								->from(array('channel_member_groups ecmg', 'channels ec'))
								->where('ecmg.channel_id', 'ec.channel_id',  FALSE)
								->where('ecmg.group_id', $this->userdata['group_id'])
								->where('site_id', $this->EE->config->item('site_id'))
								->order_by('ec.channel_title')
								->get();


		}
		
		if ($res->num_rows() > 0)
		{
			foreach ($res->result() as $row)
			{
				$assigned_channels[$row->channel_id] = $row->channel_title;
			}
		}
		
		$res->free_result();

		$this->userdata['assigned_channels'] = $assigned_channels;		
	}

	// --------------------------------------------------------------------	

	/**
	 * Setup Module Privileges
	 *
	 * @return void
	 */
	protected function _setup_module_privs()
	{
		$assigned_modules = array();
		
		$this->EE->db->select('module_id');
		$qry = $this->EE->db->get_where('module_member_groups',
										array('group_id' => $this->userdata['group_id']));
		
		if ($qry->num_rows() > 0)
		{
			foreach ($qry->result() as $row)
			{
				$assigned_modules[$row->module_id] = TRUE;
			}
		}

		$this->userdata['assigned_modules'] = $assigned_modules;
		
		$qry->free_result();		
	}

	// -------------------------------------------------------------------- 

	/**
	 * Setup Session Lengths
	 *
	 * This method allows the user to specify session TTLs in the config
	 * file so no 'hacking' of the class properties are needed.
	 *
	 * @return 	void
	 */
	protected function _setup_session_length()
	{
		$u_item = $this->EE->config->item('user_session_ttl');
		$cp_item = $this->EE->config->item('cp_session_ttl');

		$this->cpan_session_len = ($cp_item) ? $cp_item : $this->cpan_session_len;
		$this->user_session_len = ($u_item) ? $u_item : $this->user_session_len;
		
		return (REQ == 'CP') ? $this->cpan_session_len : $this->user_session_len;
	}

	// --------------------------------------------------------------------	

	/**
	 * Setup Template Privileges
	 *
	 * @return void
	 */
	protected function _setup_template_privs()
	{
		$assigned_template_groups = array();
		
		$this->EE->db->select('template_group_id');
		$qry = $this->EE->db->get_where('template_member_groups',
										array('group_id' => $this->userdata['group_id']));

		
		if ($qry->num_rows() > 0)
		{
			foreach ($qry->result() as $row)
			{
				$assigned_template_groups[$row->template_group_id] = TRUE;
			}
		}
			
		$this->userdata['assigned_template_groups'] = $assigned_template_groups;
		
		$qry->free_result();	
	}

	// --------------------------------------------------------------------	

}
// END CLASS

/* End of file Session.php */
/* Location: ./system/expressionengine/libraries/Session.php */