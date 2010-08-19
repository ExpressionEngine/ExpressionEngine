<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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

	function Comment_model()
	{
		parent::CI_Model();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Get Config Fields
	 *
	 * Fetches the config/preference fields, their types, and their default values
	 *
	 * @access	public
	 * @param	string
	 * @return	array
	 */
	function get_comment_ids($where, $entry_id = array(), $order = array())
	{

		/*
		comment_status
		entry_status
		author - ie name
		order
		limit
		ip
		comment_date
		email
		entry_ids
		comment_ids
		channel_id
		sort
		
		?? username
		?? category id
		
		
		*/
		
		//print_r($where); exit;
		
		if ( ! is_array($entry_id))
		{
			$entry_id = ( ! ctype_digit($entry_id)) ? array() : array($entry_id);
		}
		
		$this->db->select('comments.comment_id');		


		//  If we are sorting by the entry title or the channel name- we need to pull in more tables
		//  Ditto for search in titles
		$title_included = FALSE;

		if (is_array($order))
		{
			if (in_array('title', array_keys($order)))
			{
				$this->db->join('channel_titles', 'exp_comments.entry_id = exp_channel_titles.entry_id', 'left');
				
			}

			if (in_array('channel_title', array_keys($order)))
			{
				$this->db->join('channels', 'exp_comments.channel_id = exp_channels.channel_id ', 'left');
				$title_included = TRUE;
			}			
		}
		
		if ($title_included == FALSE && $where['keywords'] != '' && $where['search_in'] == 'entry_title')
		{
			$this->db->join('channel_titles', 'exp_comments.entry_id = exp_channel_titles.entry_id', 'left');
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
	 * @access	public
	 * @param	array
	 * @param	array
	 * @return	array
	 */

	function fetch_comment_data($comment_ids, $order = array())
	{
		
		if (count($comment_ids) == 0)
		{
			return FALSE;
		}
		
				$this->db->select('comments.*,
										members.location, members.occupation, members.interests, members.aol_im, members.yahoo_im, members.msn_im, members.icq, members.group_id, members.member_id, members.signature, members.sig_img_filename, members.sig_img_width, members.sig_img_height, members.avatar_filename, members.avatar_width, members.avatar_height, members.photo_filename, members.photo_width, members.photo_height,
										channel_titles.title, channel_titles.url_title, channel_titles.author_id AS entry_author_id,
										channels.comment_text_formatting, channels.comment_html_formatting, channels.comment_allow_img_urls, channels.comment_auto_link_urls, channels.channel_url, channels.comment_url, channels.channel_title'
				);
				
				$this->db->join('channels',			'comments.channel_id = channels.channel_id',	'left');
				$this->db->join('channel_titles',	'comments.entry_id = channel_titles.entry_id',	'left');
				$this->db->join('members',			'members.member_id = comments.author_id',		'left');
				
				$this->db->where_in('comments.comment_id', $comment_ids);



		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
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
	 * @access	public
	 * @param	array
	 * @param	array
	 * @return	array
	 */	
	
	function recount_entry_comments($entry_ids)
	{
		foreach(array_unique($entry_ids) as $entry_id)
		{
			$query = $this->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '".$this->db->escape_str($entry_id)."'");

			$comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;

			$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$this->db->escape_str($entry_id)."' AND status = 'o'");

			$this->db->query("UPDATE exp_channel_titles SET comment_total = '".($query->row('count') )."', recent_comment_date = '$comment_date' WHERE entry_id = '".$this->db->escape_str($entry_id)."'");
		}

	}
	
	
}

/* End of file comment_model.php */
/* Location: ./system/expressionengine/modules/comment/models/comment_model.php */	