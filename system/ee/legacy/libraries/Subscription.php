<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Subscription Class
 *
 * @package		ExpressionEngine
* @subpackage	Libraries
* @category	Subscription
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class EE_Subscription {

	var $hash;
	var $module;				// module, also used as table name
	var $anonymous	= FALSE;	// allow anonymous subscriptions? if true, table must have email column

	var $publisher	= array();

	var $table;

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
	 * Check if they're subscribed
	 *
	 * @access	public
	 * @param	mixed	identifiers
	 * @return	bool
	 */
	function is_subscribed($identifiers = FALSE)
	{
		$user = $this->_prep($identifiers);

		if ( ! $user)
		{
			return FALSE;
		}

		list($member_ids, $emails) = $user;

		if ( ! count($member_ids) && ! count($emails))
		{
			return;
		}

		$func = 'where_in';

		if (count($member_ids))
		{
			ee()->db->where_in('member_id', $member_ids);
			$func = 'or_where_in';
		}

		if (count($emails))
		{
			ee()->db->$func('email', $emails);
		}

		ee()->db->select('member_id');
		ee()->db->where($this->publisher);
		$query = ee()->db->get($this->table);

		if ($query->num_rows() > 0)
		{
			return TRUE;
		}

		return FALSE;
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
		$rand = '';
		$user = $this->_prep($identifiers);

		if ( ! $user)
		{
			return;
		}

		$existing_ids = array();
		$existing_emails = array();

		$subscriptions = $this->get_subscriptions();

		foreach($subscriptions as $row)
		{
			if ($row['member_id'])
			{
				$existing_ids[] = $row['member_id'];
			}
			else
			{
				$existing_emails[] = $row['email'];
			}
		}


		list($member_ids, $emails) = $user;

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
				$rand = $id.ee()->functions->random('alnum', 8);

				$data[] = array_merge($default, array(
					'hash'				=> $rand,
					'member_id'			=> $id,
					'email'				=> '',
					'subscription_date'	=> ee()->localize->now
				));
			}

			// Add emails
			foreach($new_emails as $email)
			{
				$rand = ee()->functions->random('alnum', 15);

				$data[] = array_merge($default, array(
					'hash'				=> $rand,
					'member_id'			=> 0,
					'email'				=> $email,
					'subscription_date'	=> ee()->localize->now
				));
			}

			// Batch it in case there are lots of them
            ee()->db->insert_batch($this->table, $data);
		}

		// Refresh existing subscriptions if there were any
		// @todo update subscription date
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
	function unsubscribe($identifiers = FALSE, $hash = FALSE)
	{
		if ($hash != '')
		{
			ee()->db->where('hash', $hash);
		}
		else
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
				ee()->db->where_in('member_id', $member_ids);
				$func = 'or_where_in';
			}

			if (count($emails))
			{
				ee()->db->$func('email', $emails);
			}
		}

		ee()->db->where($this->publisher);
		ee()->db->delete($this->table);
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
		ee()->db->where($this->publisher);
		ee()->db->delete($this->table);
	}

	// --------------------------------------------------------------------

	/**
	 * Get subscription totals
	 *
	 * @access	public
	 * @param	string	The db field identifier
	 * @param	array	Array of identifier values
	 * @return	Array	Total unique subscribers per publisher identifier
	 */
	public function get_subscription_totals($identifier, $identifier_ids)
	{
		$subscriber_query = ee()->db->select("COUNT(*) AS total")
			->select($identifier)
			->where_in($identifier, $identifier_ids)
			->group_by($identifier)
			->get($this->table);

		if ($subscriber_query->num_rows() == 0)
		{
			return array();
		}

		$return = array();

		foreach ($subscriber_query->result_array() as $subscription_total)
		{
			$return[$subscription_total['entry_id']] = $subscription_total['total'];
		}

		return $return;
	}

	// --------------------------------------------------------------------

	/**
	 * Get subscribers
	 *
	 * @access	public
	 * @param	bool	Return array with member ids instead of looking up their emails (used internally)
	 * @param	bool	Whether or not to join the member table and fetch screen names
	 * @return	mixed	Array of email addresses
	 */
	function get_subscriptions($ignore = FALSE, $include_screen_names = FALSE)
	{
		$emails		= array();
		$member_ids	= array();

		// Grab them all
		if ($this->anonymous)
		{
			ee()->db->select($this->table.'.email');
		}

		if ($ignore)
		{
			if (is_numeric($ignore) && $ignore != 0)
			{
				ee()->db->where($this->table.'.member_id !=', $ignore);
			}
			elseif ($this->anonymous)
			{
				ee()->db->where($this->table.'.email !=', $ignore);
			}
		}

		ee()->db->select("{$this->table}.subscription_id, {$this->table}.member_id, {$this->table}.notification_sent, {$this->table}.hash");
		ee()->db->from($this->table);

		if ($include_screen_names)
		{
			ee()->db->select('m.screen_name');
			ee()->db->join('members AS m', "m.member_id = {$this->table}.member_id", 'left');
		}

		ee()->db->where($this->publisher);
		$query = ee()->db->get();

		if ( ! $query->num_rows())
		{
			return array();
		}

		$return = array();

		foreach($query->result_array() as $subscription)
		{
			if ($subscription['member_id'] != 0)
			{
				$return[$subscription['subscription_id']] = $subscription;
			}
			elseif ($this->anonymous && $subscription['email'])
			{
				$return[$subscription['subscription_id']] = $subscription;
			}
		}

		return $return;
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
			ee()->db->where_in('member_id', $member_ids);
			$func = 'or_where_in';
		}

		if (count($emails))
		{
			ee()->db->$func('email', $emails);
		}

		ee()->db->set('notification_sent', $new_state);

		ee()->db->where($this->publisher);
		ee()->db->update($this->table);
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
		// They're logged in!
		if (ee()->session->userdata('member_id') != 0)
		{
			return array('member_id' => ee()->session->userdata('member_id'));
		}
		// my_email cookie is set
		elseif (ee()->session->userdata('email'))
		{
			return array('email' => ee()->session->userdata('email'));
		}

		// anonymous
		return FALSE;
	}
}

// END Subscription class

// EOF
