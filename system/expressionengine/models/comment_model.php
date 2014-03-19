<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Comment Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Comment_model extends CI_Model {

	/**
	 * Recount Stats for Comments
	 *
	 * Fetches the full data for comments
	 *
	 * @param	array
	 * @param	array
	 * @return	array
	 */
	public function recount_entry_comments($entry_ids)
	{
		foreach(array_unique($entry_ids) as $entry_id)
		{
			$query = $this->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '".$this->db->escape_str($entry_id)."'");

			$comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;

			$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$this->db->escape_str($entry_id)."' AND status = 'o'");

			$this->db->query("UPDATE exp_channel_titles SET comment_total = '".($query->row('count') )."', recent_comment_date = '$comment_date' WHERE entry_id = '".$this->db->escape_str($entry_id)."'");
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Email Recipient Array
	 *
	 * @param	array
	 * @param	array
	 * @return	array
	 */
	public function fetch_email_recipients($entry_id, $subscriptions = array())
	{
		$recipients = array();

		$subscribed_members = array();
		$subscribed_emails = array();

		// No subscribers - skip!
		if (count($subscriptions))
		{
			// Do some work to figure out the user's name,
			// either based on their user id or on the comment
			// data (stored with their email)

			$subscription_map = array();

			foreach($subscriptions as $id => $row)
			{
				if ($row['member_id'])
				{
					$subscribed_members[] = $row['member_id'];
					$subscription_map[$row['member_id']] = $id;
				}
				else
				{
					$subscribed_emails[] = $row['email'];
					$subscription_map[$row['email']] = $id;
				}
			}

			if (count($subscribed_members))
			{
				$this->db->select('member_id, email, screen_name, smart_notifications');
				$this->db->where_in('member_id', $subscribed_members);
				$member_q = $this->db->get('members');

				if ($member_q->num_rows() > 0)
				{
					foreach ($member_q->result() as $row)
					{
						$sub_id = $subscription_map[$row->member_id];

						if ($row->smart_notifications == 'n' OR $subscriptions[$sub_id]['notification_sent'] == 'n')
						{
							$recipients[] = array($row->email, $sub_id, $row->screen_name);
						}
					}
				}
			}


			// Get all comments by these subscribers so we can grab their names

			if (count($subscribed_emails))
			{
				$this->db->select('DISTINCT(email), name, entry_id');
				$this->db->where('status', 'o');
				$this->db->where('entry_id', $entry_id);
				$this->db->where_in('email', $subscribed_emails);

				$comment_q = $this->db->get('comments');

				if ($comment_q->num_rows() > 0)
				{
					foreach ($comment_q->result() as $row)
					{
						// Check due to possiblity of two replies with same email but
						// different capitalization triggering an undefined index error
						if (isset($subscription_map[$row->email]))
						{
							$sub_id = $subscription_map[$row->email];
							$recipients[] = array($row->email, $sub_id, $row->name);
						}
					}
				}
			}

			unset($subscription_map);
		}


		// Mark it as unread
		// if smart notifications are turned on, will
		// will prevent further emails from being sent

		$this->subscription->mark_as_unread(array($subscribed_members, $subscribed_emails), TRUE);

		return $recipients;
	}
}

/* End of file comment_model.php */
/* Location: ./system/expressionengine/modules/comment/models/comment_model.php */