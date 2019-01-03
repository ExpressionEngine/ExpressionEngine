<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Stats Library
 */
class EE_Stats {

	protected $_statdata	= array();

	var $stats_cache = array();

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
		$name = '';
		if (ee()->session->userdata('member_id') != 0)
		{
			$name = (ee()->session->userdata('screen_name') == '') ? ee()->session->userdata('username') : ee()->session->userdata('screen_name');
		}

		// Is user browsing anonymously?
		$anon = ( ! ee()->input->cookie('anon')) ? '' : 'y';

		//  Fetch online users

		$cutoff = ee()->localize->now - ($time_limit * 60);

		ee()->db->where('site_id', ee()->config->item('site_id'));
		ee()->db->where('date >', $cutoff);
		ee()->db->order_by('name');
		$query = ee()->db->get('online_users');

		if (ee()->config->item('dynamic_tracking_disabling') !== FALSE
			&& ee()->config->item('dynamic_tracking_disabling') != ''
			&& $query->num_rows() > ee()->config->item('dynamic_tracking_disabling'))
		{
			// disable tracking!
			ee()->config->disable_tracking();

			if ((mt_rand() % 100) < ee()->session->gc_probability)
			{
				ee()->db->where('site_id', ee()->config->item('site_id'));
				ee()->db->where('date <', $cutoff);
				ee()->db->delete('online_users');
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
				if ($row['member_id'] == ee()->session->userdata('member_id')
					&& $row['ip_address'] == ee()->input->ip_address()
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
			if (ee()->session->userdata('member_id') != 0)
			{
				$current_names[ee()->session->userdata('member_id')] = array($name, $anon);

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
						'member_id'		=> ee()->session->userdata('member_id'),
						'name'			=> $name,
						'ip_address'	=> ee()->input->ip_address(),
						'date'			=> ee()->localize->now,
						'anon'			=> $anon,
						'site_id'		=> ee()->config->item('site_id')
					);

		if ($update == FALSE)
		{
			ee()->db->query(ee()->db->insert_string('exp_online_users', $data));
		}
		else
		{
			ee()->db->where('site_id', ee()->config->item('site_id'));
			ee()->db->where('ip_address', ee()->input->ip_address());
			ee()->db->where('member_id', $data['member_id']);
			ee()->db->update('online_users', $data);
		}

		unset($data);

		ee()->db->where('site_id', ee()->config->item('site_id'));
		$query = ee()->db->get('stats');

		$row = $query->row_array();

		//  Update the stats
		if ($total_visitors > $query->row('most_visitors') )
		{
			$row['most_visitors'] 	= $total_visitors;
			$row['most_visitor_date'] 	= ee()->localize->now;

			$data = array(
					'most_visitors'		=> $total_visitors,
					'most_visitor_date'	=> ee()->localize->now,
					'last_visitor_date'	=> ee()->localize->now,
				);

			ee()->db->where('site_id', ee()->config->item('site_id'));
			ee()->db->update('stats', $data);
		}
		else
		{
			ee()->db->where('site_id', ee()->config->item('site_id'));
			ee()->db->update('stats',
									array(
											'last_visitor_date' => ee()->localize->now
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
		if ((rand() % 100) < ee()->session->gc_probability)
		{
			ee()->db->where('site_id', ee()->config->item('site_id'));
			ee()->db->where('date <', $cutoff);
			ee()->db->delete('online_users');
		}
	}

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
		ee()->db->select('channel_id');
		ee()->db->where('site_id', ee()->config->item('site_id'));
		$query = ee()->db->get('channels');

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

	/**
	 * Update Member Stats
	 *
	 * This method updates member statistics
	 *
	 * @return void
	 */
	function update_member_stats()
	{
		$query = ee()->db->select_max('member_id', 'max_id')
							  ->get('members');

		$query = ee()->db->select('screen_name, member_id')
							  ->where('member_id', $query->row('max_id'))
							  ->get('members');

		$name = $query->row('screen_name');
		$mid  = $query->row('member_id');

		$query = ee()->db->where_not_in('group_id', array('4', '2'))
							  ->select('COUNT(*) as count')
							  ->get('members');

		$data = array(
				'total_members'		=> $query->row('count'),
				'recent_member'		=> $name,
				'recent_member_id'	=> $mid
			);

		ee()->db->update('stats', $data);
	}

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

		ee()->db->select('COUNT(*) as count');

		if ($channel_ids !== FALSE)
		{
			ee()->db->where_in('channel_id', $channel_ids);
		}
		else
		{
			ee()->db->where('channel_id', (int) 0);
		}

		$now = ee()->localize->now;

		$query = ee()->db->where('entry_date <', $now)
							  ->where('(expiration_date = 0 OR expiration_date > '.$now.')')
							  ->where('status !=', 'closed')
							  ->get('channel_titles');

		$total = $query->row('count');

		ee()->db->select('MAX(entry_date) as max_date');

		if ($channel_ids !== FALSE)
		{
			ee()->db->where_in('channel_id', $channel_ids);
		}
		else
		{
			ee()->db->where('channel_id', (int) 0);
		}

		$query = ee()->db->where('entry_date <', $now)
							  ->where('(expiration_date = 0 OR expiration_date > '.$now.')')
							  ->where('status !=', 'closed')
							  ->get('channel_titles');

		$date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;

		$d = array(
				'total_entries'		=> $total,
				'last_entry_date'	=> $date
			);

		ee()->db->where('site_id', ee()->config->item('site_id'));
		ee()->db->update('stats', $d);

		// Update exp_channel table
		if ($channel_id != '')
		{
			ee()->db->select('site_id');
			$query = ee()->db->get_where('channels', array('channel_id' => $channel_id));

			$site_id = $query->row('site_id') ;

			$query = ee()->db->select('COUNT(*) as count, MAX(entry_date) as max_date')
								  ->where('channel_id', (int) $channel_id)
								  ->where('entry_date <', $now)
								  ->where('(expiration_date = 0 OR expiration_date > '.$now.')')
								  ->where('status !=', 'closed')
								  ->get('channel_titles');

			$date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;

			$total = $query->row('count');

			$d = array(
					'total_entries'		=> $total,
					'last_entry_date'	=> $date
				);

			ee()->db->where('site_id', $site_id)
						 ->where('channel_id', $channel_id)
						 ->update('channels', $d);
		}
	}

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
		if ( ! ee()->db->table_exists('comments'))
		{
			return FALSE;
		}

		// Update an site's table comment stats
		if ($global === TRUE)
		{
			$channel_ids = $this->_fetch_channel_ids();

			ee()->db->select('COUNT(comment_id) as count');
			ee()->db->where('status', 'o');

			if ($channel_ids !== FALSE)
			{
				ee()->db->where_in('channel_id', $channel_ids);
			}
			else
			{
				ee()->db->where('channel_id', (int) 0);
			}

			$query = ee()->db->get('comments');
			//
			// $query = ee()->db->query("SELECT COUNT(comment_id) AS count FROM exp_comments WHERE status = 'o' AND ".$channel_ids);

			$total = $query->row('count') ;

			if ($newtime == '')
			{
				ee()->db->select('MAX(comment_date) AS max_date');
				ee()->db->where('status', 'o');

				if ($channel_ids !== FALSE)
				{
					ee()->db->where_in('channel_id', $channel_ids);
				}
				else
				{
					ee()->db->where('channel_id', (int) 0);
				}

				$query = ee()->db->get('comments');

				$date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
			}
			else
			{
				$query = ee()->db->select('last_comment_date')
										->where('site_id', ee()->config->item('site_id'))
										->get('stats');

				$date = ($newtime > $query->row('last_comment_date') ) ? $newtime : $query->row('last_comment_date') ;
			}

			$data = array(
				'total_comments'	=> $total,
				'last_comment_date'	=> $date
			);

			ee()->db->where('site_id', ee()->config->item('site_id'));
			ee()->db->update('stats', $data);
		}

		// Update exp_channel table
		if ($channel_id != '')
		{
			$this->update_channels_comment_stats($channel_id, $newtime);
		}
	}

	/**
	 * Update Channel's Comment Stats
	 *
	 * Updates exp_channels with recalculated comment totals and date
	 *
	 * @param 	integer		channel id
	 * @param 	string		empty string or last comment date override
	 * @access	public
	 * @return	void
	 */
	function update_channels_comment_stats($channel_id, $newtime)
	{
		// Is the comments module installed?
		if ( ! ee()->db->table_exists('comments'))
		{
			$data = array(
					'total_comments'	=> 0,
					'last_comment_date'	=> 0
			);

			ee()->db->where('channel_id', $channel_id)
						->update('channels', $data);

			return;
		}

		$query = ee()->db->where('status', 'o')
					->where('channel_id', $channel_id)
					->select('COUNT(comment_id) AS count')
		 			->get('comments');

		$total = $query->row('count') ;

		if ($newtime == '')
		{
			$query = ee()->db->where('status', 'o')
									->where('channel_id', $channel_id)
									->select_max('comment_date', 'max_date')
			 						->get('comments');

			$date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
		}
		else
		{
			$query = ee()->db->select('last_comment_date, site_id')
									->where('channel_id', $channel_id)
			 						->get('channels');

			$date = ($newtime > $query->row('last_comment_date') ) ? $newtime : $query->row('last_comment_date') ;
		}

		$data = array(
					'total_comments'	=> $total,
					'last_comment_date'	=> $date
			);

		ee()->db->where('channel_id', $channel_id)
						->update('channels', $data);
	}



	/**
	 * Update Channel Title Stats
	 *
	 * Updates exp_channel_titles with recalculated comment totals and date
	 * for each specified entry_id
	 *
	 * @param 	array		array of entry ids you want recalculated
	 * @access	public
	 * @return	void
	 */
	function update_channel_title_comment_stats($entry_ids)
	{
		foreach($entry_ids as $entry_id)
		{
			$comment_date = 0;

			ee()->db->where('entry_id', $entry_id);
			ee()->db->where('status', 'o');
			$comment_total = ee()->db->count_all_results('comments');


			if ($comment_total > 0)
			{
				$query = ee()->db->select_max('comment_date')
					->where('entry_id', $entry_id)
					->where('status', 'o')
					->get('comments');

				$comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('comment_date') )) ? 0 : $query->row('comment_date') ;
			}

			ee()->db->set('comment_total', $comment_total)
							->set('recent_comment_date', $comment_date)
							->where('entry_id', $entry_id)
							->update('channel_titles');
		}
	}

	/**
	 * Update Author's Comment Stats
	 *
	 * Updates exp_members with recalculated comment totals and date
	 *
	 * @param 	array		array of member ids you want recalculated
	 * @access	public
	 * @return	void
	 */
	function update_authors_comment_stats($author_ids)
	{
		foreach($author_ids as $author_id)
		{
			// Note- query would not work with GROUP BY
			$res = ee()->db->select('COUNT(comment_id) AS comment_total, MAX(comment_date) AS comment_date', FALSE)
					->where('author_id', $author_id)
					->get('comments');

			$resrow = $res->row_array();

			$comment_total = $resrow['comment_total'] ;
			$comment_date  = ( ! empty($resrow['comment_date'])) ? $resrow['comment_date'] : 0;

			ee()->db->set('total_comments', $comment_total)
							->set('last_comment_date', $comment_date)
							->where('member_id', $author_id)
							->update('members');
		}
	}

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
		if ( ! empty($this->_statdata))
		{
			return;
		}


		$time_limit = 15; // Number of minutes to track users

		// Fetch current user's name
		$name = '';

		if (ee()->session->userdata('member_id') != 0)
		{
			$name = (ee()->session->userdata('screen_name') == '') ?
				ee()->session->userdata('username') : ee()->session->userdata('screen_name');
		}

		// Is user browsing anonymously?
		$anon = ( ! ee()->input->cookie('anon')) ? '' : 'y';

		// Fetch online users
		$cutoff = ee()->localize->now - ($time_limit * 60);

		ee()->db->where('site_id', ee()->config->item('site_id'));
		ee()->db->where('date >', $cutoff);
		ee()->db->order_by('name');
		$query = ee()->db->get('online_users');

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
				if ($row['member_id'] == ee()->session->userdata('member_id')
					&& $row['ip_address'] == ee()->input->ip_address()
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
			if (ee()->session->userdata('member_id') != 0)
			{
				$current_names[ee()->session->userdata('member_id')] = array($name, $anon);

				$total_logged++;
			}
			else
			{
				$total_guests++;
			}

			$total_visitors = $query->num_rows() + 1;
		}

		$query = ee()->db->get_where('stats', array('site_id' => ee()->config->item('site_id')));

		$this->_statdata = array(
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
	}

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

}
// END CLASS

// EOF
