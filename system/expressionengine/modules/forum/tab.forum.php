<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
		$forumsq = ee()->db->select('f.forum_id, f.forum_name, b.board_label')
			->from('forums f, forum_boards b')
			->where('f.forum_is_cat', 'n')
			->where('b.board_id = f.board_id', NULL, FALSE)
			->order_by('b.board_label asc, forum_order asc')
			->get();

		$forum_title 			= '';
		$forum_body 			= '';
		$forum_id				= array();
		$forum_topic_id			= '';
		$forum_topic_id_desc	= '';
		$forum_id_override		= ($forumsq->num_rows() === 0) ? lang('forums_unavailable') : NULL;

		// Get allowed forum boards
		$allowed = $this->_allowed_forums();

		foreach ($forumsq->result() as $row)
		{
			if ( ! in_array($row->forum_id, $allowed))
			{
				continue;
			}

			$forum_id['choices'][$row->forum_id] = $row->board_label . ': ' . $row->forum_name;
		}

		$query = ee()->db->select('forum_topic_id')
			->get_where('channel_titles', array('entry_id' => (int) $entry_id));

		if ($query->num_rows() > 0)
		{
			$forum_topic_id = $query->row('forum_topic_id');


			$frm_q = ee()->db->select('forum_id, title, body')
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
			)
		);

		// No forums, nothing to show
		if ($forum_id_override)
		{
			$settings['forum_body']['field_type'] = 'hidden';
			$settings['forum_title']['field_type'] = 'hidden';
			$settings['forum_topic_id']['field_type'] = 'hidden';

	//		$settings = array('forum_id' => $settings['forum_id']);
		}


		// Edit - can't change text
		if ($entry_id)
		{
			$settings['forum_id']['field_type'] = 'hidden';
			$settings['forum_body']['field_type'] = 'hidden';

			if ( $forum_title == '')
			{
				$settings['forum_title']['field_type'] = 'hidden';
			}
		}


		foreach ($settings as $k => $v)
		{
			ee()->api_channel_fields->set_settings($k, $v);
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
        ee()->lang->loadfile('forum_cp');

		$errors = FALSE;
		$edit = FALSE;

		// Get allowed forum boards
		$allowed = $this->_allowed_forums();

        $params = $params[0];
		$forum_title = (isset($params['forum_title'])) ? $params['forum_title'] : '';
		$forum_body = (isset($params['forum_body'])) ? $params['forum_body'] : '';

		if ($forum_body == '' && $forum_title != '')
		{
			$errors = array(lang('empty_body_field') => 'forum_body');
		}
		elseif ($forum_body != '' && $forum_title == '')
		{
			$errors = array(lang('empty_title_field') => 'forum_body');
		}

		// Check for permission to post to the specified forum
		if ((isset($params['forum_title'], $params['forum_body'],
					  $params['forum_id'])
			&& $params['forum_title'] !== '' && $params['forum_body'] !== ''))
		{
			if ( ! in_array($params['forum_id'], $allowed))
			{
				$errors = array(lang('invalid_forum_id') => 'forum_id');
			}
		}
		elseif( ! empty($params['forum_topic_id']))
		{
			$frm_q = ee()->db->select('forum_id')
				->where('topic_id', (int) $params['forum_topic_id'])
				->get('forum_topics');

			if ($frm_q->num_rows() > 0)
			{
				if ( ! in_array($frm_q->row('forum_id'), $allowed))
				{
					$errors = array(lang('invalid_topic_id') => 'forum_topic_id');
				}
			}
			else
			{
				$errors = array(lang('invalid_topic_id') => 'forum_topic_id');
			}
		}


		return $errors;
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
		$c_prefs = ee()->api_channel_entries->c_prefs;

		$edit = ($params['data']['entry_id'] != 0) ? TRUE : FALSE;

		// Are they deleting an association?
		if ($edit && empty($params['mod_data']['forum_topic_id']))
		{
			$query = ee()->db->select('forum_topic_id')
				->get_where(
					'channel_titles',
					array('entry_id' => (int) $params['entry_id'])
				);

			$old_topic_id = $query->row('forum_topic_id');

			if ( ! empty($old_topic_id))
			{
				ee()->db->where('entry_id', (int) $params['entry_id'])
					->update('channel_titles', array('forum_topic_id' => NULL));

				// Bail out, nothing else changes
				return;
			}
		}

		if ((isset($params['mod_data']['forum_title'], $params['mod_data']['forum_body'],
					  $params['mod_data']['forum_id'])
			&& $params['mod_data']['forum_title'] !== '' && $params['mod_data']['forum_body'] !== ''))
		{
			$query = ee()->db->select('board_id')
				->get_where(
					'forums',
					array('forum_id' => (int) $params['mod_data']['forum_id'])
				);

			if ($query->num_rows() > 0)
			{
				$title 	= $this->_convert_forum_tags($params['mod_data']['forum_title']);
				$body 	= str_replace(
					'{permalink}',
					 $c_prefs['comment_url'].'/'.$params['meta']['url_title'].'/',
					 $params['mod_data']['forum_body']
				);

				$body 	= $this->_convert_forum_tags(reduce_double_slashes($body));

				$data = array(
					'title'					=> ee()->security->xss_clean($title),
					'body'					=> ee()->security->xss_clean($body),
				);

				// This allows them to overwrite existing forum data- 1.x did not allow this

				if ( ! empty($params['mod_data']['forum_topic_id']))
				{
					$topic_id = $params['mod_data']['forum_topic_id'];
					ee()->db->where('topic_id', (int) $topic_id)
						 ->update('forum_topics', $data);
				}
				else
				{
					// If we're not overwriting, add in new forum topic parameters
					$new_forum_topic_data = array(
						'forum_id'				=> $params['mod_data']['forum_id'],
						'board_id'				=> $query->row('board_id'),
						'topic_date'			=> ee()->localize->now,
						'author_id'         	=> $params['meta']['author_id'],
						'ip_address'			=> ee()->input->ip_address(),
						'last_post_date'		=> ee()->localize->now,
						'last_post_author_id'	=> $params['meta']['author_id'],
						'sticky'				=> 'n',
						'status'				=> 'o',
						'announcement'			=> 'n',
						'poll'					=> 'n',
						'parse_smileys'			=> 'y',
						'thread_total'			=> 1
					);

					$data = array_merge($data, $new_forum_topic_data);

					ee()->db->insert('forum_topics', $data);
					$topic_id = ee()->db->insert_id();

					ee()->db->insert('forum_subscriptions', array(
						'topic_id'			=> $topic_id,
						'member_id'			=> $params['meta']['author_id'],
						'subscription_date'	=> ee()->localize->now,
						'hash'				=> $params['meta']['author_id'].ee()->functions->random('alpha', 8)
					));

					// Update member post total
					ee()->db->where('member_id', $params['meta']['author_id'])
						->update(
							'members',
							array('last_forum_post_date' => ee()->localize->now)
						);
				}

				ee()->db->where('entry_id', (int) $params['entry_id'])
					 ->update('channel_titles', array('forum_topic_id' => (int) $topic_id));

				// Update the forum stats
				if ( ! class_exists('Forum'))
				{
					require PATH_MOD.'forum/mod.forum.php';
					require PATH_MOD.'forum/mod.forum_core.php';
				}

				Forum_Core::_update_post_stats($params['mod_data']['forum_id']);
			}
		}
		elseif ( ! empty($params['mod_data']['forum_topic_id']))
		{
			$topic_id = $params['mod_data']['forum_topic_id'];

			ee()->db->where('entry_id', (int) $params['entry_id'])
				->update('channel_titles', array('forum_topic_id' => (int) $topic_id));
		}
	}

	function _allowed_forums()
	{
		$allowed = array();

		$group_id = ee()->session->userdata('group_id');
		$member_id = ee()->session->userdata('member_id');

		// Get Admins
		$admins = array();

		if ($group_id != 1)
		{
			$adminq = ee()->db->get('forum_administrators');

			foreach ($adminq->result() as $row)
			{
				$admins[$row->board_id] = array('member_id' => $row->admin_member_id, 'group_id' =>  $row->admin_group_id);
			}
		}

		// Get forums
		$forums = ee()->db->select('f.forum_id, f.forum_permissions, f.board_id')
					   			->from('forums f')
								->where('f.forum_is_cat', 'n')
								->get();

		foreach ($forums->result() as $row)
		{
			$perms = unserialize(stripslashes($row->forum_permissions));

			if ( ! isset($perms['can_post_topics']) OR strpos($perms['can_post_topics'], '|'.$group_id.'|') === FALSE)
			{
				if ($group_id != 1)
				{
					if ( ! isset($admins[$row->board_id]))
					{
						continue;
					}
					elseif ($admins[$row->board_id]['member_id'] != $member_id &&
					 	$admins[$row->board_id]['group_id'] != $group_id)
					{
						continue;
					}
				}
			}

			$allowed[] = $row->forum_id;
		}

		return $allowed;
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