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
 * ExpressionEngine Discussion Forum Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Forum_tab {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------	
	
	/**
	 * Publish Tabs
	 *
	 * @param 	int
	 * @param 	int
	 * @return 	array
	 */
	public function publish_tabs($channel_id, $entry_id = '')
	{
		$settings = array();

		// Get forum boards
		$forumsq = $this->EE->db->select('f.forum_id, f.forum_name, b.board_label')
					   			->from('forums f, forum_boards b')
								->where('f.forum_is_cat', 'n')
								->where('b.board_id = f.board_id', NULL, FALSE)
								->order_by('b.board_label asc, forum_order asc')
								->get();
		
		$forum_title 			= '';
		$forum_body 			= '';
		$forum_topic_id_desc	= '';
		$forum_id				= array();
		$forum_topic_id			= '';
		$forum_id_override		= ($forumsq->num_rows() === 0) ? lang('forums_unavailable') : NULL;
		
		foreach ($forumsq->result() as $row)
		{
			$forum_id['choices'][$row->forum_id] = $row->board_label . ': ' . $row->forum_name;
		}
		
		$query = $this->EE->db->select('forum_topic_id')
							  ->get_where('channel_titles', array('entry_id' => (int) $entry_id));
		
		if ($query->num_rows() > 0)
		{
			$forum_topic_id = $query->row('forum_topic_id');
			
			
			$frm_q = $this->EE->db->select('forum_id, title, body')
								  ->where('topic_id', (int) $forum_topic_id)
								  ->get('forum_topics');
			
			if ($frm_q->num_rows() > 0)
			{
				$forum_title 			= $frm_q->row('title');
				$forum_body  			= $frm_q->row('body');
				$forum_id['selected'] 	= $frm_q->row('forum_id');				
			}			
		}
		
		$settings = array(
			'forum_title'		=> array(
				'field_id'				=> 'forum_title',
				'field_label'			=> lang('forum_title'),
				'field_required'		=> 'n',
				'field_data'			=> $forum_title,
				'field_show_fmt'		=> 'n',
				'field_instructions'	=> '',
				'field_text_direction'	=> 'ltr',
				'field_type'			=> 'text',
				'field_maxl'			=> 150
			),
			'forum_body'		=> array(
				'field_id'				=> 'forum_body',
				'field_label'			=> lang('forum_body'),
				'field_required'		=> 'n',
				'field_data'			=> $forum_body,
				'field_show_fmt'		=> 'y',
				'field_fmt_options'		=> array(),
				'field_instructions'	=> '',
				'field_text_direction'	=> 'ltr',
				'field_type'			=> 'textarea',
				'field_ta_rows'			=> 8
			),
			'forum_id'			=> array(
				'field_id'				=> 'forum_id',
				'field_label'			=> lang('forum'),
				'field_required'		=> 'n',
				'field_pre_populate'	=> 'n',
				'field_list_items'		=> (isset($forum_id['choices'])) ? $forum_id['choices'] : '',
				'field_data'			=> (isset($forum_id['selected'])) ? $forum_id['selected'] : '',
	 			'field_text_direction'	=> 'ltr',
				'field_type'			=> 'select',
				'field_instructions'	=> '',
				'string_override'		=> $forum_id_override,
			),
			'forum_topic_id'	=> array(
				'field_id'				=> 'forum_topic_id',
				'field_label'			=> lang('forum_topic_id'),
				'field_type'			=> 'text',
				'field_required'		=> 'n',
				'field_data'			=> $forum_topic_id,
				'field_text_direction'	=> 'ltr',
				'field_maxl'			=> '',
				'field_instructions'	=> lang('forum_topic_id_exitsts')
			),
		);

		foreach ($settings as $k => $v)
		{
			$this->EE->api_channel_fields->set_settings($k, $v);
		}
		
		return $settings;
	}

	// --------------------------------------------------------------------	
	
	/**
	 * Validate Publish
	 *
	 * @param 	array
	 * @return 	mixed
	 */
	public function validate_publish($params)
	{
        return FALSE;
	}
	
	// --------------------------------------------------------------------	
	
	/**
	 * Insert Tab Data
	 *
	 * @param 	array
	 * @return 	void
	 */
	public function publish_data_db($params)
	{
		$c_prefs = $this->EE->api_channel_entries->c_prefs;

		if ((isset($params['mod_data']['forum_title'], $params['mod_data']['forum_body'],
					  $params['mod_data']['forum_id'])
			&& $params['mod_data']['forum_title'] !== '' && $params['mod_data']['forum_body'] !== ''))
		{
			$query = $this->EE->db->select('board_id')
								->get_where('forums', 
											array('forum_id' => (int) $params['mod_data']['forum_id']));

			if ($query->num_rows() > 0)
			{
				$this->EE->load->library('security');
				
				$title 	= $this->_convert_forum_tags($params['mod_data']['forum_title']);
				$body 	= str_replace('{permalink}',
									 $c_prefs['comment_url'].'/'.$params['meta']['url_title'].'/',
									 $params['mod_data']['forum_body']);
				
				$body 	= $this->_convert_forum_tags($this->EE->functions->remove_double_slashes($body));
				
				$data = array(
					'forum_id'				=> $params['mod_data']['forum_id'],
					'board_id'				=> $query->row('board_id'),
					'topic_date'			=> $this->EE->localize->now,
					'title'					=> $this->EE->security->xss_clean($title),
					'body'					=> $this->EE->security->xss_clean($body),
	          		'author_id'         	=> $params['meta']['author_id'],
					'ip_address'			=> $this->EE->input->ip_address(),
					'last_post_date'		=> $this->EE->localize->now,
					'last_post_author_id'	=> $params['meta']['author_id'],
					'sticky'				=> 'n',
					'status'				=> 'o',
					'announcement'			=> 'n',
					'poll'					=> 'n',
					'parse_smileys'			=> 'y',
					'thread_total'			=> 1
				);
				
				if (isset($params['mod_data']['forum_topic_id']))
				{
					$topic_id = $params['mod_data']['forum_topic_id'];
					$this->EE->db->where('topic_id', (int) $topic_id)
								 ->update('forum_topics', $data);
					
				}
				else
				{
					$this->EE->db->insert('forum_topics', $data);
					$topic_id = $this->EE->db->insert_id();

					$this->EE->db->insert('forum_subscriptions', array(
						'topic_id'			=> $topic_id,
						'member_id'			=> $params['meta']['author_id'],
						'subscription_date'	=> $this->EE->localize->now,
						'hash'				=> $params['meta']['author_id'].$this->EE->functions->random('alpha', 8)
					));

					// Update member post total
					$this->EE->db->where('member_id', $params['meta']['author_id'])
								 ->update('members', 
											array('last_forum_post_date' => $this->EE->localize->now));
				}

				$this->EE->db->where('entry_id', (int) $params['entry_id'])
							 ->update('channel_titles', array('forum_topic_id' => (int) $topic_id));

				// Update the forum stats
				if ( ! class_exists('Forum'))
				{
					require PATH_MOD.'forum/mod.forum'.EXT;
					require PATH_MOD.'forum/mod.forum_core'.EXT;
				}
				
				Forum_Core::_update_post_stats($params['mod_data']['forum_id']);
			}
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Convert forum special characters
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	function _convert_forum_tags($str)
	{
		$str = str_replace('{include:', '&#123;include:', $str);
		$str = str_replace('{path:', '&#123;path:', $str);
		$str = str_replace('{lang:', '&#123;lang:', $str);

		return $str;
	}

}