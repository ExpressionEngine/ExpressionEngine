<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Comment Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Comment_model extends CI_Model {

	
	/**
	 * Get Comment IDs
	 *
	 * Fetches the comment ids that match the submitted filter data
	 *
	 * @param	array
	 * @param	mixed
	 * @param	mixed
	 * @return	obj
	 */
	public function get_comment_ids($where, $entry_id = array(), 
									$order = array())
	{
		
		if ( ! is_array($entry_id))
		{
			$entry_id = ( $entry_id == '' OR ! ctype_digit($entry_id)) ? array() : array($entry_id);
		}
		
		$this->db->select('comments.comment_id');		

		//  If we are sorting by the entry title or the channel name- we need to pull in more tables
		//  Ditto for search in titles
		$title_included = FALSE;
		
		//  If the can ONLY edit their own comments- need to bring in title table to limit on author
		$own_entries_only = FALSE;
		
		if (( ! $this->cp->allowed_group('can_moderate_comments') && 
			  ! $this->cp->allowed_group('can_edit_all_comments')) && 	
				$this->cp->allowed_group('can_edit_own_comments'))
		{
			$own_entries_only = TRUE;
		}
				

		if (is_array($order))
		{
			if (in_array('title', array_keys($order)))
			{
				$this->db->join('channel_titles', 'exp_comments.entry_id = exp_channel_titles.entry_id', 'left');
				$title_included = TRUE;				
			}

			if (in_array('channel_title', array_keys($order)))
			{
				$this->db->join('channels', 'exp_comments.channel_id = exp_channels.channel_id ', 'left');
			}			
		}
		
		if ($title_included == FALSE && $where['keywords'] != '' && $where['search_in'] == 'entry_title')
		{
			$this->db->join('channel_titles', 'exp_comments.entry_id = exp_channel_titles.entry_id', 'left');
		}
		elseif ($title_included == FALSE && $own_entries_only == TRUE)
		{
			$this->db->join('channel_titles', 'exp_comments.entry_id = exp_channel_titles.entry_id', 'left');
		}
		
		if ($own_entries_only == TRUE)
		{
			$this->db->where('exp_channel_titles.author_id', $this->session->userdata('member_id'));			
		}		
		
		if (isset($where['site_id']))
		{
			$this->db->where_in('exp_comments.site_id', $where['site_id']);			
		}
		else
		{
			$this->db->where('exp_comments.site_id', $this->config->item('site_id'));
		}

		if ($where['keywords'] != '')
		{
			if ($where['search_in'] == 'comment')
			{
				$this->db->like('comment', $where['keywords']);
			}		
			elseif ($where['search_in'] == 'ip_address')
			{
				$this->db->like('comments.ip_address', $where['keywords']);
			}		
			elseif ($where['search_in'] == 'email')
			{
				$this->db->like('email', $where['keywords']);
			}
			elseif ($where['search_in'] == 'name')
			{
				$this->db->like('name', $where['keywords']);
			}
			elseif ($where['search_in'] == 'entry_title')
			{
				$this->db->like('title', $where['keywords']);
			}			
					
		}

		if ($where['status'] != '' && $where['status'] != 'all')
		{
			$this->db->where('exp_comments.status', $where['status']);			
		}

		if (count($entry_id) > 0)
		{
			$this->db->where_in('exp_comments.entry_id', $entry_id);			
		}		
		
		if ($where['name'] != '')
		{
			$this->db->where('name', $where['name']);			
		}
		
		if ($where['email'] != '')
		{
			$this->db->where('email', $where['email']);			
		}				 
		
		if ($where['ip_address'] != '')
		{
			$this->db->where('comments.ip_address', $where['ip_address']);			
		}
		
		if ($where['date_range'] != '')
		{
			//  Is a single number
			if (ctype_digit($where['date_range']))
			{
				$date_range = time() - ($where['date_range'] * 60 * 60 * 24);
				
				$this->db->where('comment_date >', $date_range);
			}
			elseif (strpos($where['date_range'], 'to') !== FALSE)
			{
				// Custom range
				$ranges = explode('to', $where['date_range']);
				
				$start = $this->localize->convert_human_date_to_gmt(trim($ranges[0]).' 00:00');
				$end = $this->localize->convert_human_date_to_gmt(trim($ranges[1]).' 23:59');
			
				if (ctype_digit($start) && ctype_digit($end))
				{
					$this->db->where('comment_date >=', $start);
					$this->db->where('comment_date <=', $end);
				}
			}
		}

		if ($where['channel_id'] != '' && $where['channel_id'] != 'all')
		{
			$this->db->where('comments.channel_id', $where['channel_id']);			
		}				

		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				if (in_array($key, array('ip_address', 'status')))
				{
					$key = 'exp_comments.'.$key;
				}				
				
				$this->db->order_by($key, $val);
			}
		}
		else
		{
			$this->db->order_by('comment_date', 'desc');
		}
		
		return $this->db->get('comments');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Fetch Comment Data
	 *
	 * Fetches the full data for comments
	 *
	 * @param	array
	 * @param	array
	 * @return	array
	 */
	public function fetch_comment_data($comment_ids, $order = array())
	{
		if (count($comment_ids) == 0)
		{
			return FALSE;
		}
		
		$this->db->select('comments.*,
					members.location, members.occupation, members.interests, 
					members.aol_im, members.yahoo_im, members.msn_im, 
					members.icq, members.group_id, members.member_id, 
					members.signature, members.sig_img_filename, 
					members.sig_img_width, members.sig_img_height, 
					members.avatar_filename, members.avatar_width, 
					members.avatar_height, members.photo_filename, 
					members.photo_width, members.photo_height,
					channel_titles.title, channel_titles.url_title, 
					channel_titles.author_id AS entry_author_id,
					channels.comment_text_formatting, 
					channels.comment_html_formatting, 
					channels.comment_allow_img_urls, 
					channels.comment_auto_link_urls, channels.channel_url, 
					channels.comment_url, channels.channel_title'
		)
		->join('channels',	'comments.channel_id = channels.channel_id',	'left')
		->join('channel_titles', 'comments.entry_id = channel_titles.entry_id', 'left')
		->join('members', 'members.member_id = comments.author_id', 'left')
		->where_in('comments.comment_id', $comment_ids);

		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				if (in_array($key, array('ip_address', 'status')))
				{
					$key = 'exp_comments.'.$key;
				}				
				
				$this->db->order_by($key, $val);
			}
		}
		else
		{
			$this->db->order_by('comment_date', 'desc');
		}

		$query = $this->db->get('comments');

		return $query;
	}
	
	// --------------------------------------------------------------------
	
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
						$sub_id = $subscription_map[$row->email];
						$recipients[] = array($row->email, $sub_id, $row->name);
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