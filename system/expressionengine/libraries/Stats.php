<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Stats Library
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Libraries
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class EE_Stats {

	protected $_statdata	= array();
	
	var $stats_cache = array();
	
	var $cache_off  = FALSE;

	// --------------------------------------------------------------------	
	
	/**
	 * Class Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();

		if ($this->EE->db->cache_on === TRUE)
		{
			$this->EE->db->cache_off();
			$this->cache_off = TRUE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Update statistics
	 *
	 * Update site statistics
	 *
	 * @return void
	 */
	function update_stats()
	{		
		$time_limit = 15; // Number of minutes to track users

		//  Fetch current user's name
		if ($this->EE->session->userdata('member_id') != 0)
		{
			$name = ($this->EE->session->userdata('screen_name') == '') ? $this->EE->session->userdata('username') : $this->EE->session->userdata('screen_name');
		}
		else
		{
			$name = '';
		}

		// Is user browsing anonymously?
		$anon = ( ! $this->EE->input->cookie('anon')) ? '' : 'y';

		//  Fetch online users

		$cutoff = $this->EE->localize->now - ($time_limit * 60);

		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->where('date >', $cutoff);
		$this->EE->db->order_by('name');
		$query = $this->EE->db->get('online_users');

		if ($this->EE->config->item('dynamic_tracking_disabling') !== FALSE 
			&& $this->EE->config->item('dynamic_tracking_disabling') != '' 
			&& $query->num_rows() > $this->EE->config->item('dynamic_tracking_disabling'))
		{
			// disable tracking!
			$this->EE->config->disable_tracking();

			if ((mt_rand() % 100) < $this->EE->session->gc_probability) 
			{
				$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
				$this->EE->db->where('date <', $cutoff);
				$this->EE->db->delete('online_users');
			}

			return;
		}
		
		//  Assign users to a multi-dimensional array
		$total_logged	= 0;
		$total_guests	= 0;
		$total_anon		= 0;
		$update 		= FALSE;
		$current_names	= array();		

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				if ($row['member_id'] == $this->EE->session->userdata('member_id')  
					&& $row['ip_address'] == $this->EE->input->ip_address() 
					&& $row['name'] == $name)
				{
					$update = TRUE;
					$anon = $row['anon'];
				}
			
				if ($row['member_id'] != 0)
				{
					$current_names[$row['member_id']] = array($row['name'], $row['anon']);
	
					if ($row['anon'] != '')
					{		
						$total_anon++;
					}
					else
					{	
						$total_logged++;
					}
				}
				else
				{
					$total_guests++;
				}
			}
		}
		else
		{
			$total_guests++;
		}

		//  Set the "update" pref, which we'll use later
		if ($update === TRUE)
		{
			$total_visitors = $query->num_rows;
		}
		else
		{
			if ($this->EE->session->userdata('member_id') != 0)
			{
				$current_names[$this->EE->session->userdata('member_id')] = array($name, $anon);
			
				$total_logged++;
			}
			else
			{
				$total_guests++;
			}
			
			$total_visitors = $query->num_rows() + 1;
		}

		//  Update online_users table
		$data = array(
						'member_id'		=> $this->EE->session->userdata('member_id'),
						'name'			=> $name,
						'ip_address'	=> $this->EE->input->ip_address(),
						'date'			=> $this->EE->localize->now,
						'anon'			=> $anon,
						'site_id'		=> $this->EE->config->item('site_id')
					);

		if ($update == FALSE)
		{
			$this->EE->db->query($this->EE->db->insert_string('exp_online_users', $data));
		}
		else
		{
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$this->EE->db->where('ip_address', $this->EE->input->ip_address());
			$this->EE->db->where('member_id', $data['member_id']);
			$this->EE->db->update('online_users', $data);
		}
		
		unset($data);

		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$query = $this->EE->db->get('stats');

		$row = $query->row_array();

		//  Update the stats
		if ($total_visitors > $query->row('most_visitors') )			
		{
			$row['most_visitors'] 	= $total_visitors;
			$row['most_visitor_date'] 	= $this->EE->localize->now;
			
			$data = array(
					'most_visitors'		=> $total_visitors,
					'most_visitor_date'	=> $this->EE->localize->now,
					'last_visitor_date'	=> $this->EE->localize->now,		
				);
			
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$this->EE->db->update('stats', $data);
		}
		else
		{
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$this->EE->db->update('stats', 
									array(
											'last_visitor_date' => $this->EE->localize->now
									)
								);
		}

		//  Assign the stats
		$this->_statdata = array(
								'recent_member'				=> $row['recent_member'] ,
								'recent_member_id'			=> $row['recent_member_id'] ,
								'total_members'				=> $row['total_members'] ,
								'total_entries'				=> $row['total_entries'] ,
								'total_forum_topics'		=> $row['total_forum_topics'] ,
								'total_forum_posts'			=> $row['total_forum_posts']  + $row['total_forum_topics'] ,
								'total_forum_replies'		=> $row['total_forum_posts'] ,
								'total_comments'			=> $row['total_comments'] ,
								'most_visitors'				=> $row['most_visitors'] ,
								'last_entry_date'			=> $row['last_entry_date'] ,
								'last_forum_post_date'		=> $row['last_forum_post_date'] ,
								'last_comment_date'			=> $row['last_comment_date'] ,
								'last_cache_clear'			=> $row['last_cache_clear'] ,
								'last_visitor_date'			=> $row['last_visitor_date'] ,
								'most_visitor_date'			=> $row['most_visitor_date'] ,
								'total_logged_in'			=> $total_logged,
								'total_guests'				=> $total_guests,
								'total_anon'				=> $total_anon,
								'current_names'				=> $current_names
							);
		unset($query);

		srand(time());
		if ((rand() % 100) < $this->EE->session->gc_probability) 
		{
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$this->EE->db->where('date <', $cutoff);
			$this->EE->db->delete('online_users');
		}	
		
		if ($this->cache_off)
		{
			$this->EE->db->cache_on();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Channel Ids
	 *
	 * This private method fetches channel id numbers for other queries 
	 * in this class.  
	 *
	 * @return 	mixed	FALSE if no channels, else array of channel ids
	 */
	protected function _fetch_channel_ids()
	{	
		$this->EE->db->select('channel_id');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$query = $this->EE->db->get('channels');
		
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
	
		$channel_ids = array();
		
		foreach ($query->result_array() as $row)
		{
			$channel_ids[] = $row['channel_id'];
		}
	
		return $channel_ids;
	}

	// --------------------------------------------------------------------

	/**
	 * Update Member Stats
	 *
	 * This method updates member statistics
	 *
	 * @return void
	 */
	function update_member_stats()
	{
		$this->EE->db->select_max('member_id', 'max_id');
		$query = $this->EE->db->get('members');
		
		$this->EE->db->select('screen_name, member_id');
		$this->EE->db->where('member_id', $query->row('max_id'));
		$query = $this->EE->db->get('members');

		$name	= $query->row('screen_name') ;
		$mid	= $query->row('member_id') ;
		
		$this->EE->db->where_not_in('group_id', array('4', '2'));
		$this->EE->db->select('COUNT(*) as count');
		$query = $this->EE->db->get('members');

		$data = array(
				'total_members'		=> $query->row('count'),
				'recent_member'		=> $name,
				'recent_member_id'	=> $mid
			
			);
		
		$this->EE->db->update('stats', $data);

		if ($this->cache_off)
		{
			$this->EE->db->cache_on();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Update Channel Stats
	 *
	 * this method updates channel statistics.  total entries, etc.
	 *
	 * @param 	integer		channel id
	 * @return 	void
	 */
	function update_channel_stats($channel_id = '')
	{
		// Update
		$channel_ids = $this->_fetch_channel_ids();
		
		$this->EE->db->select('COUNT(*) as count');
		
		if ($channel_ids !== FALSE)
		{
			$this->EE->db->where_in('channel_id', $channel_ids);
		}
		else
		{
			$this->EE->db->where('channel_id', (int) 0);			
		}
		
		$this->EE->db->where('entry_date <', $this->EE->localize->now);
		$this->EE->db->where('(expiration_date = 0 OR expiration_date > '.$this->EE->localize->now.')');
		$this->EE->db->where('status !=', 'closed');
		
		$query = $this->EE->db->get('channel_titles');
		
		$total = $query->row('count');
		
		
		$this->EE->db->select('MAX(entry_date) as max_date');
		
		if ($channel_ids !== FALSE)
		{
			$this->EE->db->where_in('channel_id', $channel_ids);
		}
		else
		{
			$this->EE->db->where('channel_id', (int) 0);			
		}

		$this->EE->db->where('entry_date <', $this->EE->localize->now);
		$this->EE->db->where('(expiration_date = 0 OR expiration_date > '.$this->EE->localize->now.')');
		$this->EE->db->where('status !=', 'closed');

		$query = $this->EE->db->get('channel_titles');
		
		$date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;

		$d = array(
				'total_entries'		=> $total,
				'last_entry_date'	=> $date
			);

		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->update('stats', $d);
								
		// Update exp_channel table
		if ($channel_id != '')
		{
			$this->EE->db->select('site_id');
			$query = $this->EE->db->get_where('channels', array('channel_id' => $channel_id));
			
			$site_id = $query->row('site_id') ;

			$this->EE->db->select('COUNT(*) as count, MAX(entry_date) as max_date');
			$this->EE->db->where('channel_id', (int) $channel_id);
			$this->EE->db->where('entry_date <', $this->EE->localize->now);
			$this->EE->db->where('(expiration_date = 0 OR expiration_date > '.$this->EE->localize->now.')');
			$this->EE->db->where('status !=', 'closed');
			$query = $this->EE->db->get('channel_titles');	
			
			$total = $query->row('count');

			$this->EE->db->select('');
			$this->EE->db->where('channel_id', (int) $channel_id);
			$this->EE->db->where('entry_date <', $this->EE->localize->now);
			$this->EE->db->where('(expiration_date = 0 OR expiration_date > '.$this->EE->localize->now.')');
			$this->EE->db->where('status !=', 'closed');
			$query = $this->EE->db->get('channel_titles');			
			
			$date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
			
			$d = array(
					'total_entries'		=> $total,
					'last_entry_date'	=> $date
				);
			
			$this->EE->db->where('site_id', $site_id);
			$this->EE->db->where('channel_id', $channel_id);
			$this->EE->db->update('channels', $d);
		}

		if ($this->cache_off)
		{
			$this->EE->db->cache_on();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Update Comment Stats
	 *
	 * This method updates comment statistics
	 *
	 * @param 	integer		channel id number
	 * @param	integer
	 * @param	boolean
	 * @return 	mixed
	 */
	function update_comment_stats($channel_id = '', $newtime = '', $global=TRUE)
	{
		// Is the comments module installed?  Bail out if not.
		if ( ! $this->EE->db->table_exists('comments'))
		{
			return FALSE;
		}
		
		// Update
		if ($global === TRUE)
		{
			$channel_ids = $this->_fetch_channel_ids();

			$this->EE->db->select('COUNT(comment_id) as count');
			$this->EE->db->where('status', 'o');

			if ($channel_ids !== FALSE)
			{
				$this->EE->db->where_in('channel_id', $channel_ids);
			}
			else
			{
				$this->EE->db->where('channel_id', (int) 0);
			}
			
			$query = $this->EE->db->get('comments');
			// 
			// $query = $this->EE->db->query("SELECT COUNT(comment_id) AS count FROM exp_comments WHERE status = 'o' AND ".$channel_ids);
		
			$total = $query->row('count') ;
		
			if ($newtime == '')
			{
				$this->EE->db->select('MAX(comment_date) AS max_date');
				$this->EE->db->where('status', 'o');
				
				if ($channel_ids !== FALSE)
				{
					$this->EE->db->where_in('channel_id', $channel_ids);
				}
				else
				{
					$this->EE->db->where('channel_id', (int) 0);
				}
				
				$query = $this->EE->db->get('comments');
			
				$date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
			}
			else
			{
				$this->EE->db->select('last_comment_date');
				$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
				$query = $this->EE->db->get('stats');

				$date = ($newtime > $query->row('last_comment_date') ) ? $newtime : $query->row('last_comment_date') ;
			}
		
			$data = array(
				'total_comments'	=> $total,
				'last_comment_date'	=> $date
			);
		
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$this->EE->db->update('stats', $data);
		}
		
		// Update exp_channel table

		if ($channel_id != '')
		{
			$this->EE->db->where('status', 'o');
			$this->EE->db->where('channel_id', $channel_id);
			$this->EE->db->select('COUNT(comment_id) AS count');
			$query = $this->EE->db->get('comments');
			
			$total = $query->row('count') ;
			
			if ($newtime == '')
			{
				$this->EE->db->where('status', 'o');
				$this->EE->db->where('channel_id', $channel_id);
				$this->EE->db->select_max('comment_date', 'max_date');
				$query = $this->EE->db->get('comments');
			
				$date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
			}
			else
			{
				$this->EE->db->select('last_comment_date, site_id');
				$this->EE->db->where('channel_id', $channel_id);
				$query = $this->EE->db->get('channels');

				$date = ($newtime > $query->row('last_comment_date') ) ? $newtime : $query->row('last_comment_date') ;
			}
			
			$data = array(
					'total_comments'	=> $total,
					'last_comment_date'	=> $date
				);

			$this->EE->db->where('channel_id', $channel_id);
			$this->EE->db->update('channels', $data);			
		}

		if ($this->cache_off)
		{
			$this->EE->db->cache_on();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Load Stats
	 *
	 * This method is used when stats are read-only
	 *
	 * @access	public
	 * @return	void
	 */
	function load_stats()
	{
		$time_limit = 15; // Number of minutes to track users

		// Fetch current user's name
		if ($this->EE->session->userdata('member_id') != 0)
		{
			$name = ($this->EE->session->userdata('screen_name') == '') ? $this->EE->session->userdata('username') : $this->EE->session->userdata('screen_name');
		}
		else
		{
			$name = '';
		}

		// Is user browsing anonymously?
		$anon = ( ! $this->EE->input->cookie('anon')) ? '' : 'y';

		// Fetch online users
		$cutoff = $this->EE->localize->now - ($time_limit * 60);

		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->where('date >', $cutoff);
		$this->EE->db->order_by('name');
		$query = $this->EE->db->get('online_users');

		// Assign users to a multi-dimensional array
		$total_logged	= 0;
		$total_guests	= 0;
		$total_anon		= 0;
		$update 		= FALSE;
		$current_names	= array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				if ($row['member_id'] == $this->EE->session->userdata('member_id')
					&& $row['ip_address'] == $this->EE->input->ip_address() 
					&& $row['name'] == $name)
				{
					$update = TRUE;
					$anon = $row['anon'];
				}

				if ($row['member_id'] != 0)
				{
					$current_names[$row['member_id']] = array($row['name'], $row['anon']);

					if ($row['anon'] != '')
					{
						$total_anon++;
					}
					else
					{
						$total_logged++;
					}
				}
				else
				{
					$total_guests++;
				}
			}
		}
		else
		{
			$total_guests++;
		}

		// This user already counted or no?
		if ($update == TRUE)
		{
			$total_visitors = $query->num_rows();
		}
		else
		{
			if ($this->EE->session->userdata('member_id') != 0)
			{
				$current_names[$this->EE->session->userdata('member_id')] = array($name, $anon);
			
				$total_logged++;
			}
			else
			{
				$total_guests++;
			}
			
			$total_visitors = $query->num_rows() + 1;
		}
		
		$query = $this->EE->db->get_where('stats', array('site_id' => $this->EE->config->item('site_id')));

		$this->EE->stats->_statdata = array(
					'recent_member'				=> $query->row('recent_member'),
					'recent_member_id'			=> $query->row('recent_member_id'),
					'total_members'				=> $query->row('total_members'),
					'total_entries'				=> $query->row('total_entries'),
					'total_forum_topics'		=> $query->row('total_forum_topics'),
					'total_forum_posts'			=> $query->row('total_forum_posts') + $query->row('total_forum_topics'),
					'total_forum_replies'		=> $query->row('total_forum_posts'),
					'total_comments'			=> $query->row('total_comments'),
					'most_visitors'				=> $query->row('most_visitors'),
					'last_entry_date'			=> $query->row('last_entry_date'),
					'last_forum_post_date'		=> $query->row('last_forum_post_date'),
					'last_comment_date'			=> $query->row('last_comment_date'),
					'last_cache_clear'			=> $query->row('last_cache_clear'),
					'last_visitor_date'			=> $query->row('last_visitor_date'),
					'most_visitor_date'			=> $query->row('most_visitor_date'),
					'total_logged_in'			=> $total_logged,
					'total_guests'				=> $total_guests,
					'total_anon'				=> $total_anon,
					'current_names'				=> $current_names
				);
		unset($query);

		if ($this->cache_off)
		{
			$this->EE->db->cache_on();
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get Statdata
	 *
	 * This method will retrieve items or the entire statdata class property.
	 *
	 * @param 	string		which piece of the array to get (optional)
	 * @return 	mixed		FALSE on failure, string or array on success
	 */
	function statdata($which = NULL)
	{
		// I want it all!
		if ( ! $which)
		{
			return $this->_statdata;
		}
		
		if (isset($this->_statdata[$which]))
		{
			return $this->_statdata[$which];
		}

		return FALSE;		
	}

	// --------------------------------------------------------------------
	
	/**
	 * Set statdata
	 *
	 * A setter method to change the statdata class prop array outside this
	 * class.  Is really only used in the mod.stats.php file.
	 *
	 * @param 	string	key to change
	 * @param	mixecd	value
	 */
	function set_statdata($key, $val)
	{
		$this->_statdata[$key] = $val;
	}

	// --------------------------------------------------------------------	
	
}
// END CLASS

/* End of file mcp.stats.php */
/* Location: ./system/expressionengine/modules/stats/mcp.stats.php */