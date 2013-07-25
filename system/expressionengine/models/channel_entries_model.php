<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Entries Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Channel_entries_model extends CI_Model {


	// --------------------------------------------------------------------
	
	/**
	 *
	 */
	public function get_entry_data(array $entries)
	{
		$sql = 'SELECT t.entry_id, t.channel_id, t.forum_topic_id, t.author_id, t.ip_address, t.title, t.url_title, t.status, t.view_count_one, t.view_count_two, t.view_count_three, t.view_count_four, t.allow_comments, t.comment_expiration_date, t.sticky, t.entry_date, t.year, t.month, t.day, t.edit_date, t.expiration_date, t.recent_comment_date, t.comment_total, t.site_id as entry_site_id,
				  w.channel_title, w.channel_name, w.channel_url, w.comment_url, w.comment_moderate, w.channel_html_formatting, w.channel_allow_img_urls, w.channel_auto_link_urls, w.comment_system_enabled, 
				  m.group_id, m.username, m.email, m.url, m.screen_name, m.location, m.occupation, m.interests, m.aol_im, m.yahoo_im, m.msn_im, m.icq, m.signature, m.sig_img_filename, m.sig_img_width, m.sig_img_height, m.avatar_filename, m.avatar_width, m.avatar_height, m.photo_filename, m.photo_width, m.photo_height, m.group_id, m.member_id, m.bday_d, m.bday_m, m.bday_y, m.bio,
				  md.*,
				  wd.*
				FROM exp_channel_titles		AS t
				LEFT JOIN exp_channels 		AS w  ON t.channel_id = w.channel_id
				LEFT JOIN exp_channel_data	AS wd ON t.entry_id = wd.entry_id
				LEFT JOIN exp_members		AS m  ON m.member_id = t.author_id
				LEFT JOIN exp_member_data	AS md ON md.member_id = m.member_id ';

		$sql .= 'WHERE t.entry_id IN ('.implode(',', $entries).')';

		$query_result = ee()->db->query($sql);
		$entries = $query_result->result_array();
		$query_result->free_result();
		return $entries;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Entries
	 *
	 * Gets all entry ids for a channel.  Other fields and where can be specified optionally
	 *
	 * @access	public
	 * @param	int
	 * @param	mixed	// single field, or array of fields
	 * @param	array	// associative array of where
	 * @return	object
	 */
	function get_entries($channel_id, $additional_fields = array(), $additional_where = array())
	{
		if ( ! is_array($additional_fields))
		{
			$additional_fields = array($additional_fields);
		}

		if (count($additional_fields) > 0)
		{
			$this->db->select(implode(',', $additional_fields));
		}

		// default just fecth entry id's
		$this->db->select('entry_id');
		$this->db->from('channel_titles');
		
		// which channel id's?
		if (is_array($channel_id))
		{
			$this->db->where_in('channel_id', $channel_id);
		}
		else
		{
			$this->db->where('channel_id', $channel_id);
		}

		// add additional WHERE clauses
		foreach ($additional_where as $field => $value)
		{
			if (is_array($value))
			{
				$this->db->where_in($field, $value);
			}
			else
			{
				$this->db->where($field, $value);
			}
		}

		return $this->db->get();
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch the channel data for one entry
	 *
	 * @access	public
	 * @return	mixed
	 */
	function get_entry($entry_id, $channel_id = '', $autosave_entry_id = FALSE)
	{
		if ($channel_id != '')
		{
			if ($autosave_entry_id)
			{
				$this->db->from('channel_entries_autosave AS t');
				$this->db->where('t.entry_id', $autosave_entry_id);
			}
			else
			{
				$this->db->select('t.*, d.*');
				$this->db->from('channel_titles AS t, channel_data AS d');
				$this->db->where('t.entry_id', $entry_id);
				$this->db->where('t.entry_id = d.entry_id', NULL, FALSE);
			}

			$this->db->where('t.channel_id', $channel_id);
		}
		else
		{
			if ($autosave_entry_id)
			{
				$from = 'channel_entries_autosave';
				$entry_id_selection = $autosave_entry_id;
			}
			else
			{
				$from = 'channel_titles';
				$entry_id_selection = $entry_id;
			}

			$this->db->from($from);
			$this->db->select('channel_id, entry_id, author_id');
			$this->db->where('entry_id', $entry_id_selection);
		}


		return $this->db->get();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Get most recent entries
	 *
	 * Gets all recently posted entries
	 *
	 * @access	public
	 * @param	int
	 * @return	object
	 */
	function get_recent_entries($limit = '10')
	{
		$allowed_channels = $this->functions->fetch_assigned_channels();
		
		if (count($allowed_channels) == 0)
		{
			return FALSE;
		}

		$this->db->select('
						channel_titles.channel_id, 
						channel_titles.author_id,
						channel_titles.entry_id,         
						channel_titles.title, 
						channel_titles.comment_total'
						);
		$this->db->from('channel_titles, channels');
		$this->db->where('channels.channel_id = '.$this->db->dbprefix('channel_titles.channel_id'));
		$this->db->where('channel_titles.site_id', $this->config->item('site_id'));
		
		if ( ! $this->cp->allowed_group('can_view_other_entries') AND
			 ! $this->cp->allowed_group('can_edit_other_entries') AND
			 ! $this->cp->allowed_group('can_delete_all_entries'))
		{
			$this->db->where('channel_titles.author_id', $this->session->userdata('member_id'));
		}
		
		$allowed_channels = $this->functions->fetch_assigned_channels();
		
		$this->db->where_in('channel_titles.channel_id', $allowed_channels);
			
		$this->db->limit($limit);
		$this->db->order_by('entry_date', 'DESC');
		return $this->db->get();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get recent commented entries
	 *
	 * Gets all entries with recently posted comments
	 *
	 * @access	public
	 * @param	int
	 * @return	object
	 */
	function get_recent_commented($limit = '10')
	{
		$this->db->select('
						channel_titles.channel_id, 
						channel_titles.author_id,
						channel_titles.entry_id,         
						channel_titles.title, 
						channel_titles.recent_comment_date'
						);
		$this->db->from('channel_titles, channels');
		$this->db->where('channels.channel_id = '.$this->db->dbprefix('channel_titles.channel_id'));
		$this->db->where('channel_titles.site_id', $this->config->item('site_id'));
		
		if ( ! $this->cp->allowed_group('can_view_other_comments') AND
			 ! $this->cp->allowed_group('can_moderate_comments') AND
			 ! $this->cp->allowed_group('can_delete_all_comments') AND
			 ! $this->cp->allowed_group('can_edit_all_comments'))
		{
			$this->db->where('channel_titles.author_id', $this->session->userdata('member_id'));
		}
		
		$allowed_channels = $this->functions->fetch_assigned_channels();
		
		if (count($allowed_channels) > 0)
		{
			$this->db->where_in('channel_titles.channel_id', $allowed_channels);
			$this->db->where("recent_comment_date != ''");
			
			$this->db->limit($limit);
			$this->db->order_by("recent_comment_date", "desc"); 
			return $this->db->get();
		}
		
		return FALSE;
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Prune Revisions
	 *
	 * Removes all revisions of an entry except for the $max latest
	 *
	 * @access	public
	 * @param	int
	 * @return	int
	 */
	function prune_revisions($entry_id, $max)
	{
		$this->db->where('entry_id', $entry_id);
		$count = $this->db->count_all_results('entry_versioning');
		
		if ($count > $max)
		{
			$this->db->select('version_id');
			$this->db->where('entry_id', $entry_id);
			$this->db->order_by('version_id', 'DESC');
			$this->db->limit($max);
			
			$query = $this->db->get('entry_versioning');
			
			$ids = array();
			foreach ($query->result_array() as $row)
			{
				$ids[] = $row['version_id'];
			}
			
			$this->db->where('entry_id', $entry_id);
			$this->db->where_not_in('version_id', $ids);
			$this->db->delete('entry_versioning');
			unset($ids);
		}
	}
}
// END CLASS

/* End of file channel_entries_model.php */
/* Location: ./system/expressionengine/models/channel_entries_model.php */
