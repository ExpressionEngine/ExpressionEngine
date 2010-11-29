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

	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------	
	
	public function publish_tabs($channel_id, $entry_id = '')
	{
		$settings = array();
		
		$hide_forum_fields = FALSE;
	
		if ($this->EE->config->item('forum_is_installed') == 'n')
		{
			return $settings;
		}
	
		$forum_title			= '';
		$forum_body				= '';
		$forum_topic_id_descp	= '';
		$forum_id				= '';
		$forum_topic_id			= ( ! isset($entry_data['forum_topic_id'])) ? '' : $entry_data['forum_topic_id'];	
	
		$entry_id = ($entry_id != '') ? $entry_id : 0;
	
		if ($entry_id !== 0)
		{
			$qry = $this->EE->db->select('f.forum_id, f.forum_name, b.board_label')
								->from('forums f, forum_boards b')
								->where('f.forum_is_cat', 'n')
								->where('b.board_id = f.board_id', NULL, FALSE)
								->order_by('b.board_label asc, forum_order asc')
								->get();
	
			if ($qry->num_rows() === 0)
			{
				$forum_id = lang('forums_unavailable');
			}
			else
			{
				if ($forum_topic_id != '')
				{
					$qr2 = $this->EE->db->select('forum_topic_id')
										->get_where('channel_titles', array('entry_id'	=> (int) $entry_id));
					
					if ($qr2->num_rows() !== 0)
					{
						$forum_topic_id = $qr2->row('forum_topic_id');
					}
				}
				
				foreach ($qry->result() as $row)
				{
					$forums[$row->forum_id] = $row->board_label . ': ' . $row->forum_name;
				}
	
				$forum_id		= array('selected'	=> $this->input->get_post('forum_id'),
										'choices'	=> $forums);
				$forum_title 	= ( ! isset($entry_data['forum_title'])) ? '' : $entry_data['forum_title'];
				$forum_body 	= ( ! isset($entry_data['forum_body']))	 ? '' : $entry_data['forum_body'];
				$forum_topic_id	= ( ! isset($entry_data['forum_topic_id'])) ? '' : $entry_data['forum_topic_id'];
				$forum_topic_id_desc = lang('forum_topic_id_exists');
			}			
		}
		else
		{
			$hide_forum_fields = TRUE;
			
			if ( ! isset($forum_topic_id))
			{
				$qry = $this->EE->db->select('forum_topic_id')
									->get_where('channel_titles', array('entry_id' => (int) $entry_id));
				
				if ($qry->num_rows() !== 0)
				{
					$forum_topic_id = $qry->row('forum_topic_id');
				}				
			}
			
			$forum_topic_id_desc	= lang('forum_topic_id_info');
	
			if ($forum_topic_id !== 0)
			{
				$fq2 = $this->EE->db->select('title')
									->get_where('forum_topics',
										array('topic_id' => (int) $forum_topic_id));
				
				$forum_title = ($fq2->num_rows() === 0) ? '' : $fq2->row('title');
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
				'field_instructions'	=> ''
			),
			'forum_topic_id'	=> array(
				'field_id'				=> 'forum_topic_id',
				'field_label'			=> lang('forum_topic_id'),
				'field_type'			=> 'text',
				'field_required'		=> 'n',
				'field_data'			=> ( ! isset($entry_data['forum_topic_id'])) ? '' : $entry_data['forum_topic_id'],
				'field_text_direction'	=> 'ltr',
				'field_maxl'			=> '',
				'field_instructions'	=> ''
			),
		);
		
		foreach ($settings as $k => $v)
		{
			$this->EE->api_channel_fields->set_settings($k, $v);
		}
		
		return $settings;
	}
}