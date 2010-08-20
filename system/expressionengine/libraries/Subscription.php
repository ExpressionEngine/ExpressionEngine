<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class EE_Subscription {

	var $hash;
	var $module;				// module, also used as table name
	var $anonymous	= FALSE;	// allow anonymous subscriptions? if true, table must have email column

	var $publisher	= array();
	
	var $table;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function EE_Subscription()
	{
		// Get EE superobject reference
		$this->EE =& get_instance();
	}
	
	// --------------------------------------------------------------------

	/**
	 * init the library
	 *
	 * @access	public
	 * @return	void
	 */
	function init($module, $publisher = array(), $anonymous = FALSE)
	{
		$this->module	 = $module;
		$this->publisher = $publisher;
		$this->anonymous = $anonymous;
		
		$this->table	 = $module.'_subscriptions';
	}

	// --------------------------------------------------------------------
	
	/**
	 * Mark post as read
	 *
	 * @access	public
	 * @return	void
	 */
	function mark_as_read($identifiers = FALSE, $skip_prep = FALSE)
	{
		$this->_mark($identifiers, 'n', $skip_prep);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Mark post as unread
	 *
	 * @access	public
	 * @return	void
	 */
	function mark_as_unread($identifiers = FALSE, $skip_prep = FALSE)
	{
		$this->_mark($identifiers, 'y', $skip_prep);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Add subscriptions for current post
	 *
	 * @access	public
	 * @return	void
	 */
	function subscribe($identifiers = FALSE, $mark_existing = TRUE)
	{
		$user = $this->_prep($identifiers);
		
		if ( ! $user)
		{
			return;
		}
		
		list($member_ids, $emails) = $user;
		list($existing_ids, $existing_emails) = $this->get_subscribers(TRUE, TRUE);
		
		// Handle duplicates
		$new_member_ids = array_diff($member_ids, $existing_ids);
		$new_emails = array_diff($emails, $existing_emails);
		
		if (count($new_member_ids) OR count($new_emails))
		{
			$data	 = array();
			$default = $this->publisher;
			
			// Add member ids
			foreach($new_member_ids as $id)
			{
				$rand = $id.$this->EE->functions->random('alnum', 8);
				
				$data[] = array_merge($default, array(
					'hash'				=> $rand,
					'member_id'			=> $id,
					'subscription_date'	=> $this->EE->localize->now
				));
			}
			
			// Add emails
			foreach($new_emails as $email)
			{
				$rand = $this->EE->functions->random('alnum', 15);
				
				$data[] = array_merge($default, array(
					'hash'				=> $rand,
					'email'				=> $email,
					'subscription_date'	=> $this->EE->localize->now
				));
			}
			
			// Batch it in case there are lots of them
            $this->EE->db->insert_batch($this->table, $data);
		}
		
		// Refresh existing subscriptions if there were any
		if ($mark_existing)
		{
			$member_ids = array_intersect($member_ids, $existing_ids);
			$emails = array_intersect($emails, $existing_emails);
			
			$dupes = array($member_ids, $emails);
			$this->mark_as_read($dupes, TRUE);
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Remove subscriptions for current post
	 *
	 * @access	public
	 * @return	void
	 */
	function unsubscribe($identifiers = FALSE, $all_entries = FALSE)
	{
		$user = $this->_prep($identifiers);
		
		if ( ! $user)
		{
			return;
		}
		
		list($member_ids, $emails) = $user;
		
		if ( ! count($member_ids) && ! count($emails))
		{
			return;
		}
		
		$func = 'where_in';
		
		if (count($member_ids))
		{
			$this->EE->db->where_in('member_id', $member_ids);
			$func = 'or_where_in';
		}
		
		if (count($emails))
		{
			$this->EE->db->$func('email', $emails);
		}
		
		if ( ! $all_entries)
		{
			$this->EE->db->where($this->publisher);
		}
		$this->EE->db->delete($this->table);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Remove subscriptions to all posts
	 *
	 * Use when deleting a member
	 *
	 * @access	public
	 * @return	void
	 */
	function unsubscribe_all($identifiers = FALSE)
	{
		$this->unsubscribe($identifiers, TRUE);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Remove all subscriptions for a publisher
	 *
	 * Call this when removing posts to avoid cluttering up the subscription table
	 *
	 * @access	public
	 * @return	void
	 */
	function delete_subscriptions()
	{
		$this->EE->db->where($this->publisher);
		$this->EE->db->delete($this->table);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get subscribers
	 *
	 * @access	public
	 * @param	bool	Return array with member ids instead of looking up their emails (used internally)
	 * @return	mixed	Array of email addresses
	 */
	function get_subscribers($mark_as_unread = TRUE, $return_member_ids = FALSE)
	{
		$emails		= array();
		$member_ids	= array();
		
		// Grab them all
		if ($this->anonymous)
		{
			$this->EE->db->select('email');
		}
		
		$this->EE->db->select('member_id');
		$this->EE->db->where($this->publisher);
		$query = $this->EE->db->get($this->table);
		
		foreach($query->result_array() as $subscription)
		{
			if ($subscription['member_id'])
			{
				$member_ids[] = $subscription['member_id'];
			}
			else if ($this->anonymous && isset($subscription['email']))
			{
				$emails[] = $subscription['email'];
			}
		}
		
		if ($mark_as_unread)
		{
			$this->mark_as_unread(array($member_ids, $emails), TRUE);
		}
		
		// Return as if coming from _prep?
		if ($return_member_ids)
		{
			return array($member_ids, $emails);
		}
		
		// Grab member emails from the members table - is this too slow?
		if (count($member_ids))
		{
			$this->EE->db->select('email');
			$this->EE->db->where_in('member_id', $member_ids);
			$query = $this->EE->db->get('members');
			
			if ($query->num_rows())
			{
				$member_emails = array_map('array_pop', $query->result_array());
				$emails = array_unique(array_merge($emails, $member_emails));
			}
		}
		
		return $emails;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Prep user data
	 *
	 * Figure out the member ids and email addresses we're working with
	 *
	 * @access	private
	 * @param	mixed	Values to identify the subscriber(s)
	 * @return	mixed
	 */
	function _prep($identifiers = FALSE)
	{
		static $current_user = '';
		
		$emails		= array();
		$member_ids	= array();
		
		// No user specified? Use the current one
		if ($identifiers == FALSE)
		{
			if ($current_user === '')
			{
				$current_user = $this->_get_current_user();
			}
			
			// get_current_user returns false if it can't
			// find an existing identifier
			if ($current_user === FALSE)
			{
				return FALSE;
			}			
			
			$array = key($current_user).'s';
			${$array}[] = current($current_user);
		}
		else
		{
			if ( ! is_array($identifiers))
			{
				$identifiers = array($identifiers);
			}

			foreach($identifiers as $email_or_id)
			{
				if ( ! is_numeric($email_or_id))
				{
					if ($this->anonymous == TRUE)
					{
						$emails[] = $email_or_id;
					}
				}
				else
				{
					$member_ids[] = $email_or_id;
				}
			}
		}
		
		return array($member_ids, $emails);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Mark a subscription as read / unread
	 *
	 * @access	private
	 * @param	mixed	Values to identify the subscriber(s)
	 * @param	string	New subscription_sent status (y | n)
	 * @param	bool	Skip call to _prep (used internally)
	 * @return	void
	 */
	function _mark($identifiers, $new_state, $skip_prep = FALSE)
	{
		if ( ! $skip_prep)
		{
			$identifiers = $this->_prep($identifiers);
			
			if ( ! $identifiers)
			{
				return;
			}
		}
		
		list($member_ids, $emails) = $identifiers;
		
		if ( ! count($member_ids) && ! count($emails))
		{
			return;
		}
		
		$func = 'where_in';
		
		if (count($member_ids))
		{
			$this->EE->db->where_in('member_id', $member_ids);
			$func = 'or_where_in';
		}
		
		if (count($emails))
		{
			$this->EE->db->$func('email', $emails);
		}
		
		$this->EE->db->set('notification_sent', $new_state);
		
		$this->EE->db->where($this->publisher);
		$this->EE->db->update($this->table);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Identify the current user
	 *
	 * @access	private
	 * @return	mixed
	 */
	function _get_current_user()
	{
		$this->EE->load->helper('cookie');
		
		$hash = '';
		$cookie = get_cookie('subscription');
		
		// They're logged in!
		if ($this->EE->session->userdata('member_id') != 0)
		{
			if ($cookie)
			{
				delete_cookie('subscription');
			}
			
			return array('member_id' => $this->EE->session->userdata('member_id'));
		}
		// my_email cookie is set
		elseif ($this->EE->session->userdata('email'))
		{
			if ($cookie)
			{
				delete_cookie('subscription');
			}
			
			return array('email' => $this->EE->session->userdata('email'));
		}
		
		// Check for a hash in the url
		$hash = $this->EE->input->get('subscription', TRUE);
		
		if ($hash OR $cookie)
		{
			return $this->_identify_from_hash($hash);
		}
		
		return FALSE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Identify the user from a subscription hash
	 *
	 * @access	private
	 * @param	string	a subscription hash
	 * @return	mixed
	 */
	function _identify_from_hash($hash = '')
	{
		$cookie = get_cookie('subscription', TRUE);
		$cookie = $cookie ? explode('|', $cookie) : array();
		
		if ($hash)
		{
			$this->EE->db->where('hash', $hash);
		}
		elseif (count($cookie))
		{
			$this->EE->db->where_in('hash', $cookie);
		}
		else
		{
			return FALSE;
		}
		
		$query = $this->EE->db->get($this->table);
		
		if ($query->num_rows())
		{
			// Found one - track them in the cookie
			if ($hash)
			{
				$cookie[] = $hash;
				$cookie = implode('|', array_unique($cookie));
				
				set_cookie('subscription', $cookie, 60*60*24*365);
			}
			
			if ($user['member_id'])
			{
				return array('member_id' => $user['member_id']);
			}
			
			if ($this->anonymous && $user['email'])
			{
				return array('email' => $user['email']);
			}
		}
		
		return FALSE;
	}
	
}

// END Subscription class

/* End of file Subscription.php */
/* Location: ./system/expressionengine/libraries/Subscription.php */