<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

// --------------------------------------------------------------------

/**
 * Member Management Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Member_subscriptions extends Member {


	/** ----------------------------------
	/**  Member_settings Profile Constructor
	/** ----------------------------------*/
	function Member_subscriptions()
	{
	}

	
	
	/** ----------------------------------------
	/**  Subscriptions Edit Form
	/** ----------------------------------------*/
	
	function edit_subscriptions()
	{
		// Set some base values
		
		$channel_subscriptions		= FALSE;
		$forum_subscriptions	= FALSE;
		$result_ids				= array();
		$result_data			= array();
		$pageurl 				= $this->_member_path('edit_subscriptions');
		$perpage				= 50;
		$rownum  				= $this->cur_id;
		$page_links				= '';
		
		/** ----------------------------------------
		/**  Set update path
		/** ----------------------------------------*/
		$swap['path:update_subscriptions'] = $this->_member_path('update_subscriptions');
		
		
		/** ----------------------------------------
		/**  Fetch Channel Comment Subscriptions
		/** ----------------------------------------*/
		if ($this->EE->db->table_exists('exp_comments'))
		{
			$query = $this->EE->db->query("SELECT DISTINCT(entry_id)  FROM exp_comments WHERE email = '".$this->EE->session->userdata['email']."' AND notify = 'y' ORDER BY comment_date DESC");

			if ($query->num_rows() > 0)
			{
				$channel_subscriptions	= TRUE;

				$temp_ids = array();

				foreach ($query->result_array() as $row)
				{
					$temp_ids[] = $row['entry_id'];
				}

				// and now grab the most recent activity for each subscription for ordering later
				$query = $this->EE->db->query("SELECT entry_id, recent_comment_date FROM exp_channel_titles WHERE entry_id IN (".implode(',', $temp_ids).")");

				if ($query->num_rows() > 0)
				{
					foreach ($query->result() as $row)
					{
						$result_ids[$row->recent_comment_date.'b'] = $row->entry_id;
					}
				}
			}			
		}
		
		/** ----------------------------------------
		/**  Fetch Forum Topic Subscriptions
		/** ----------------------------------------*/
		// Since the forum module might not be installed we'll test for it first.
						
		if ($this->EE->db->table_exists('exp_forum_subscriptions'))
		{
			$query = $this->EE->db->query("SELECT topic_id FROM exp_forum_subscriptions WHERE member_id = '".$this->EE->db->escape_str($this->EE->session->userdata('member_id'))."' ORDER BY subscription_date DESC");
		
			if ($query->num_rows() > 0)
			{
				$forum_subscriptions = TRUE;
				
				$temp_ids = array();
				
				foreach ($query->result_array() as $row)
				{
					$temp_ids[] = $row['topic_id'];
				}
				
				// and now grab the most recent activity for each subscription for ordering later
				$query = $this->EE->db->query("SELECT topic_id, last_post_date FROM exp_forum_topics WHERE topic_id IN (".implode(',', $temp_ids).")");

				if ($query->num_rows() > 0)
				{
					foreach ($query->result() as $row)
					{
						$result_ids[$row->last_post_date.'f'] = $row->topic_id;
					}
				}
			}
		}
		
		
		/** ------------------------------------
		/**  No results?  Bah, how boring...
		/** ------------------------------------*/
		
		if (count($result_ids) == 0)
		{
			$swap['subscription_results'] = $this->_var_swap($this->_load_element('no_subscriptions_message'), array('lang:no_subscriptions'=> $this->EE->lang->line('no_subscriptions')));
											
			return $this->_var_swap($this->_load_element('subscriptions_form'), $swap);
		}
		
		// Sort the array for newest activity first
		// we'll end up doing this twice, one to determine what entries
		// belong on the page for pagination, then again for the data queries
		krsort($result_ids);
				
		/** ---------------------------------
		/**  Do we need pagination?
		/** ---------------------------------*/
		
		$total_rows = count($result_ids);
		
		if ($rownum != '')
			$rownum = substr($rownum, 1);

		$rownum = ($rownum == '' OR ($perpage > 1 AND $rownum == 1)) ? 0 : $rownum;
		
		if ($rownum > $total_rows)
		{
			$rownum = 0;
		}
					
		$t_current_page = floor(($rownum / $perpage) + 1);
		$total_pages	= intval(floor($total_rows / $perpage));
		
		if ($total_rows % $perpage)
			$total_pages++;
		
		if ($total_rows > $perpage)
		{
			$this->EE->load->library('pagination');
			
			$config['base_url']		= $pageurl;
			$config['prefix']		= 'R';
			$config['total_rows'] 	= $total_rows;
			$config['per_page']		= $perpage;
			$config['cur_page']		= $rownum;
			$config['query_string_segment']	  = 'rownum';
			$config['page_query_string']	= TRUE;


			$this->EE->pagination->initialize($config);
			$page_links = $this->EE->pagination->create_links();			
			
			
			$result_ids = array_slice($result_ids, $rownum, $perpage);
		}
		else
		{
			$result_ids = array_slice($result_ids, 0, $perpage);	
		}


		/** ---------------------------------
		/**  Fetch Channel Titles
		/** ---------------------------------*/
		if ($channel_subscriptions	== TRUE)
		{
			$sql = "SELECT
					exp_channel_titles.title, exp_channel_titles.url_title, exp_channel_titles.channel_id, exp_channel_titles.entry_id, exp_channel_titles.recent_comment_date,
					exp_channels.comment_url, exp_channels.channel_url	
					FROM exp_channel_titles
					LEFT JOIN exp_channels ON exp_channel_titles.channel_id = exp_channels.channel_id
					WHERE entry_id IN (";
		
			$idx = '';
		
			foreach ($result_ids as $key => $val)
			{			
				if (substr($key, strlen($key)-1) == 'b')
				{
					$idx .= $val.",";
				}
			}
		
			$idx = substr($idx, 0, -1);
			
			if ($idx != '')
			{
				$query = $this->EE->db->query($sql.$idx.') ');
	
				if ($query->num_rows() > 0)
				{
					foreach ($query->result_array() as $row)
					{																
						$result_data[$row['recent_comment_date']] = array(
												'path'	=> $this->EE->functions->remove_double_slashes($this->EE->functions->prep_query_string(($row['comment_url'] != '') ? $row['comment_url'] : $row['channel_url']).'/'.$row['url_title'].'/'),
												'title'	=> str_replace(array('<', '>', '{', '}', '\'', '"', '?'), array('&lt;', '&gt;', '&#123;', '&#125;', '&#146;', '&quot;', '&#63;'), $row['title']),
												'id'	=> 'b'.$row['entry_id'],
												'type'	=> $this->EE->lang->line('comment')
												);
					}
				}
			}
		}

		/** ---------------------------------
		/**  Fetch Forum Topics
		/** ---------------------------------*/
		if ($forum_subscriptions == TRUE)
		{
			$sql = "SELECT title, topic_id, board_forum_url, last_post_date FROM exp_forum_topics, exp_forum_boards
					WHERE exp_forum_topics.board_id = exp_forum_boards.board_id
					AND topic_id IN (";
					
			$idx = '';
		
			foreach ($result_ids as $key => $val)
			{			
				if (substr($key, strlen($key)-1) == 'f')
				{
					$idx .= $val.",";
				}
			}
		
			$idx = substr($idx, 0, -1);
			
			if ($idx != '')
			{
				$query = $this->EE->db->query($sql.$idx.') ');
	
				if ($query->num_rows() > 0)
				{
					foreach ($query->result_array() as $row)
					{																
						$result_data[$row['last_post_date']] = array(
												'path'	=> $this->EE->functions->remove_double_slashes($this->EE->functions->prep_query_string($row['board_forum_url'] ).'/viewthread/'.$row['topic_id'].'/'),
												'title'	=> str_replace(array('<', '>', '{', '}', '\'', '"', '?'), array('&lt;', '&gt;', '&#123;', '&#125;', '&#146;', '&quot;', '&#63;'), $row['title']),
												'id'	=> 'f'.$row['topic_id'],
												'type'	=> $this->EE->lang->line('mbr_forum_post')
												);
					}
				}
			}
		}
		
		// sort the data results
		krsort($result_data);	
	
		// Build the result table...

		$out = $this->_var_swap($this->_load_element('subscription_result_heading'),
								array(
										'lang:title'		=>	$this->EE->lang->line('title'),
										'lang:type'		 =>	$this->EE->lang->line('type'),
										'lang:unsubscribe'  =>	$this->EE->lang->line('unsubscribe')
									 )
							);


		$i = 0;
		foreach ($result_data as $val)
		{
			$rowtemp = $this->_load_element('subscription_result_rows');
						
			$rowtemp = str_replace('{class}',	($i++ % 2) ? 'tableCellOne' : 'tableCellTwo', $rowtemp);
			
			$rowtemp = str_replace('{path}',	$val['path'],	$rowtemp);
			$rowtemp = str_replace('{title}',	$val['title'],	$rowtemp);
			$rowtemp = str_replace('{id}',	  $val['id'],		$rowtemp);
			$rowtemp = str_replace('{type}',	$val['type'],	$rowtemp);

			$out .= $rowtemp;
		}
		
		$out .= $this->_var_swap($this->_load_element('subscription_pagination'),
								 array('pagination' => $page_links,
								 		'lang:unsubscribe' => $this->EE->lang->line('unsubscribe'),
								 		'class' => ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo'));

	
		$swap['subscription_results'] = $out;
				
		return $this->_var_swap(
									$this->_load_element('subscriptions_form'), $swap
								);
	}

	
	
	
	/** ----------------------------------------
	/**  Update Subscriptions
	/** ----------------------------------------*/
	
	function update_subscriptions()
	{
		if ( ! $this->EE->input->post('toggle'))
		{
			$this->EE->functions->redirect($this->_member_path('edit_subscriptions'));
			exit;	
		}
				
		foreach ($_POST['toggle'] as $key => $val)
		{		
			switch (substr($val, 0, 1))
			{
				case "b"	: $this->EE->db->query("UPDATE exp_comments SET notify = 'n' WHERE entry_id = '".substr($val, 1)."' AND email = '".$this->EE->db->escape_str($this->EE->session->userdata('email'))."'");
					break;
				case "f"	: $this->EE->db->query("DELETE FROM exp_forum_subscriptions WHERE topic_id = '".substr($val, 1)."' AND member_id = '{$this->EE->session->userdata['member_id']}'");
					break;
			}
		}
				
		/** -------------------------------------
		/**  Success message
		/** -------------------------------------*/
	
		return $this->_var_swap($this->_load_element('success'),
								array(
										'lang:heading'		=>	$this->EE->lang->line('subscriptions'),
										'lang:message'		=>	$this->EE->lang->line('subscriptions_removed')
									 )
								);
	}

	
}
// END CLASS

/* End of file mod.member_subscriptions.php */
/* Location: ./system/expressionengine/modules/member/mod.member_subscriptions.php */