<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2009, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: mcp.stats.php
-----------------------------------------------------
 Purpose: Statistical tracking module - backend
=====================================================
*/
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


class Stats_mcp {

	var $statdata	= array();
	
	function Stats_mcp()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}


	/**
	  *  Update statistics
	  */
	function update_stats()
	{
		$time_limit = 15; // Number of minutes to track users

		//  Fetch current user's name

		if ($this->EE->session->userdata('member_id') != 0)
		{
			$name = ($this->EE->session->userdata['screen_name'] == '') ? $this->EE->session->userdata['username'] : $this->EE->session->userdata['screen_name'];
		}
		else
		{
			$name = '';
		}

		// Is user browsing anonymously?
		$anon = ( ! $this->EE->input->cookie('anon')) ? '' : 'y';

		//  Fetch online users

		$cutoff = $this->EE->localize->now - ($time_limit * 60);

		$query = $this->EE->db->query("SELECT * FROM exp_online_users WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' AND date > $cutoff ORDER BY name");

		if ($this->EE->config->item('dynamic_tracking_disabling') !== FALSE && $this->EE->config->item('dynamic_tracking_disabling') != '' && $query->num_rows() > $this->EE->config->item('dynamic_tracking_disabling'))
		{
			// disable tracking!
			$this->EE->config->disable_tracking();

			if ((mt_rand() % 100) < $this->EE->session->gc_probability) 
			{
				$this->EE->db->query("DELETE FROM exp_online_users WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' AND date < $cutoff");
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
				if ($row['member_id'] == $this->EE->session->userdata('member_id')  AND $row['ip_address'] == $this->EE->input->ip_address() AND $row['name'] == $name)
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

		if ($update == TRUE)
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
			$this->EE->db->query($this->EE->db->update_string('exp_online_users', $data, array('site_id' => $this->EE->config->item('site_id'), "ip_address" => $this->EE->input->ip_address(), "member_id" => $data['member_id'])));
		}
		
		unset($data);

		$query = $this->EE->db->query("SELECT * FROM exp_stats WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'");
		
		$row = $query->row_array();

		//  Update the stats

		if ($total_visitors > $query->row('most_visitors') )			
		{
			 $row['most_visitors'] 	= $total_visitors;
			 $row['most_visitor_date'] 	= $this->EE->localize->now;
		
			$sql = "UPDATE exp_stats SET most_visitors = '{$total_visitors}', most_visitor_date = '{$this->EE->localize->now}', last_visitor_date = '{$this->EE->localize->now}' WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'";
		}
		else
		{
			$sql = "UPDATE exp_stats SET last_visitor_date = '{$this->EE->localize->now}' WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'";
		}
		
		$this->EE->db->query($sql);

		//  Assign the stats

		$this->statdata = array(
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
			$this->EE->db->query("DELETE FROM exp_online_users WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' AND date < $cutoff");			 
		}	
	}

	// --------------------------------------------------------------------

	/**
	  *  Fetch Channel ID numbers for query
	  */
	function fetch_channel_ids()
	{
		$sql = '';
	
		$query = $this->EE->db->query("SELECT channel_id FROM exp_channels WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'");
		
		if ($query->num_rows() == 0)
		{
			return " channel_id = '0'";
		}
		
		$sql .= " channel_id IN (";
			
		foreach ($query->result_array() as $row)
		{
			$sql .= $row['channel_id'].",";
		}
		
		$sql = substr($sql, 0, -1).") ";
	
		return $sql;
	}

	// --------------------------------------------------------------------

	/**
	  *  Update Member Stats
	  */
	function update_member_stats()
	{
		$query = $this->EE->db->query("SELECT MAX(member_id) AS max_id FROM exp_members");
		
		$query = $this->EE->db->query("SELECT screen_name, member_id FROM exp_members WHERE member_id = '".$query->row('max_id') ."'");
		$name	= $query->row('screen_name') ;
		$mid	= $query->row('member_id') ;
		
		$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_members WHERE group_id NOT IN ('4','2')");
		
		$sql = "UPDATE exp_stats SET total_members = '".$query->row('count') ."', recent_member = '".$this->EE->db->escape_str($name)."', recent_member_id = '{$mid}'";
				
		$this->EE->db->query($sql);
	}

	// --------------------------------------------------------------------

	/**
	  *  Update Channel Stats
	  */
	function update_channel_stats($channel_id = '')
	{
		// Update
		$channel_ids = $this->fetch_channel_ids();
		
		$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_channel_titles WHERE ".$channel_ids." AND entry_date < ".$this->EE->localize->now." AND (expiration_date = 0 OR expiration_date > ".$this->EE->localize->now.") AND status != 'closed'");
		
		$total = $query->row('count') ;
		
		$query = $this->EE->db->query("SELECT MAX(entry_date) as max_date FROM exp_channel_titles WHERE ".$channel_ids." AND entry_date < ".$this->EE->localize->now." AND (expiration_date = 0 OR expiration_date > ".$this->EE->localize->now.") AND status != 'closed'");
		
		$date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
								
		$this->EE->db->query("UPDATE exp_stats SET total_entries = '$total', last_entry_date = '$date' WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'");

		// Update exp_channel table
		
		if ($channel_id != '')
		{
			$query = $this->EE->db->query("SELECT site_id FROM exp_channels WHERE channel_id = '$channel_id'");
			
			$site_id = $query->row('site_id') ;
			
			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_channel_titles WHERE channel_id = '$channel_id' AND entry_date < ".$this->EE->localize->now." AND (expiration_date = 0 OR expiration_date > ".$this->EE->localize->now.") AND status != 'closed'");
			
			$total = $query->row('count') ;
			
			$query = $this->EE->db->query("SELECT MAX(entry_date) AS max_date FROM exp_channel_titles WHERE channel_id = '$channel_id' AND entry_date < ".$this->EE->localize->now." AND (expiration_date = 0 OR expiration_date > ".$this->EE->localize->now.") AND status != 'closed'");
			
			$date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
								
			$this->EE->db->query("UPDATE exp_channels SET total_entries = '$total', last_entry_date = '$date' WHERE site_id = '".$this->EE->db->escape_str($site_id)."' AND channel_id = '$channel_id'");
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Update Comment Stats
	  */
	function update_comment_stats($channel_id = '', $newtime = '', $global=TRUE)
	{
		// Update
		if ($global === TRUE)
		{
			$channel_ids = $this->fetch_channel_ids();

			$query = $this->EE->db->query("SELECT COUNT(comment_id) AS count FROM exp_comments WHERE status = 'o' AND ".$channel_ids);
		
			$total = $query->row('count') ;
		
			if ($newtime == '')
			{
				$query = $this->EE->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND ".$channel_ids."");
			
				$date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
			}
			else
			{
				$query = $this->EE->db->query("SELECT last_comment_date FROM exp_stats WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'");
				
				$date = ($newtime > $query->row('last_comment_date') ) ? $newtime : $query->row('last_comment_date') ;
			}
		
			$this->EE->db->query("UPDATE exp_stats SET total_comments = '$total', last_comment_date = '$date' WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'");
		}
		
		// Update exp_channel table

		if ($channel_id != '')
		{
			$query = $this->EE->db->query("SELECT COUNT(comment_id) AS count FROM exp_comments WHERE status = 'o' AND channel_id = '$channel_id'");
			
			$total = $query->row('count') ;
			
			if ($newtime == '')
			{
				$query = $this->EE->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND channel_id = '$channel_id'");
			
				$date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
			}
			else
			{
				$query = $this->EE->db->query("SELECT last_comment_date, site_id FROM exp_channels WHERE channel_id = '$channel_id'");
			
				$date = ($newtime > $query->row('last_comment_date') ) ? $newtime : $query->row('last_comment_date') ;
			}
								
			$this->EE->db->query("UPDATE exp_channels SET total_comments = '$total', last_comment_date = '$date' WHERE channel_id = '$channel_id'");
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
		
		/** --------------------------------
		/**  Fetch current user's name
		/** --------------------------------*/

		if ($this->EE->session->userdata('member_id') != 0)
		{
			$name = ($this->EE->session->userdata['screen_name'] == '') ? $this->EE->session->userdata['username'] : $this->EE->session->userdata['screen_name'];
		}
		else
		{
			$name = '';
		}

		// Is user browsing anonymously?

		$anon = ( ! $this->EE->input->cookie('anon')) ? '' : 'y';


		/** --------------------------------
		/**  Fetch online users
		/** --------------------------------*/

		$cutoff = $this->EE->localize->now - ($time_limit * 60);

		$query = $this->EE->db->query("SELECT * FROM exp_online_users WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' AND date > $cutoff ORDER BY name");


		/** -------------------------------------------
		/**  Assign users to a multi-dimensional array
		/** -------------------------------------------*/

		$total_logged	= 0;
		$total_guests	= 0;
		$total_anon		= 0;
		$update 		= FALSE;
		$current_names	= array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				if ($row['member_id'] == $this->EE->session->userdata('member_id')  AND $row['ip_address'] == $this->EE->input->ip_address() AND $row['name'] == $name)
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
		
		/** -------------------------------------------
		/**  This user already counted or no?
		/** -------------------------------------------*/
		
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

		$this->EE->stats->statdata = array(
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
	
	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file mcp.stats.php */
/* Location: ./system/expressionengine/modules/stats/mcp.stats.php */