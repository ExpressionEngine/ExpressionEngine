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
 * ExpressionEngine Channel Entries Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Channel_entries_model extends CI_Model {

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

	// --------------------------------------------------------------------	

	/**
	 * Fetch ping servers
	 *
	 * This needs to be moved somewhere else, as similar code is used in a few places
	 *
	 * @param 	integer
	 * @param 	integer
	 * @param 	string
	 * @param 	boolean
	 */
	public function fetch_ping_servers($entry_id = '', $show = TRUE)
	{
		$sent_pings = array();

		if ($entry_id != '')
		{
			$qry = $this->db->select('ping_id')
							->get_where('entry_ping_status', 
										array('entry_id' => (int) $entry_id)
									);
			
			if ($qry->num_rows() > 0)
			{
				foreach ($qry->result_array() as $row)
				{
					$sent_pings[$row['ping_id']] = TRUE;
				}
			}
		}

		$qry = $this->db->select('COUNT(*) as count')
						->where('site_id', $this->config->item('site_id'))
						->where('member_id', $this->session->userdata('member_id'))
						->get('ping_servers');

		$member_id = ($qry->row('count') === 0) ? 0 : $this->session->userdata('member_id');

		$qry = $this->db->select('id, server_name, is_default')
						->where('site_id', $this->config->item('site_id'))
						->where('member_id', $member_id)
						->order_by('server_order')
						->get('ping_servers');

		if ($qry->num_rows() == 0)
		{
			return FALSE;
		}

		$r = '';

		foreach($qry->result_array() as $row)
		{
			$selected = '';

			if ( ! empty($_POST))
			{
				if ($this->input->post('ping') !== FALSE && in_array($row['id'], $this->input->post('ping')))
				{
					$selected = 1; 
				}
			}
			else
			{
				if ($entry_id != '')
				{
					$selected = (isset($sent_pings[$row['id']])) ? 1 : '';
				}
				else
				{
					$selected = ($row['is_default'] == 'y') ? 1 : '';
				}
			}

			if ($entry_id != '')
			{
				$selected = '';
			}

			if ($show == TRUE)
			{
				$r .= '<label>'.form_checkbox('ping[]', $row['id'], $selected, 'class="ping_toggle"').' '.$row['server_name'].'</label>';
			}
			else
			{
				if ($entry_id != '')
				{
					$r .= form_hidden('ping[]', $row['id']);
				}
			}
		}

		if ($show == TRUE)
		{
			$r .= '<label>'.form_checkbox('toggle_pings', 'toggle_pings', FALSE, 'class="ping_toggle_all"').' '.lang('select_all').'</label>';

		}

		return $r;
	}
	
	
	
}
// END CLASS

/* End of file channel_entries_model.php */
/* Location: ./system/expressionengine/models/channel_entries_model.php */