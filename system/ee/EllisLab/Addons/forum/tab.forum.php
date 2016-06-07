<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Forum_tab {

	/**
	 * Publish Tabs
	 *
	 * @param 	int
	 * @param 	int
	 * @return 	array
	 */
	public function display($channel_id, $entry_id = '')
	{
		$settings = array();

		// Get forum boards
		$forumsq = ee()->db->select('f.forum_id, f.forum_name, b.board_label')
			->from('forums f, forum_boards b')
			->where('f.forum_is_cat', 'n')
			->where('b.board_id = f.board_id', NULL, FALSE)
			->order_by('b.board_label asc, forum_order asc')
			->get();

		$forum_title         = '';
		$forum_body          = '';
		$forum_id            = array();
		$forum_topic_id      = '';
		$forum_topic_id_desc = '';
		$forum_id_override   = ($forumsq->num_rows() === 0)
			? lang('forums_unavailable')
			: NULL;

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
				$forum_title          = $frm_q->row('title');
				$forum_body           = $frm_q->row('body');
				$forum_id['selected'] = $frm_q->row('forum_id');
			}
		}

		$settings = array(
			'forum_title' => array(
				'field_id'             => 'forum_title',
				'field_label'          => lang('forum_title'),
				'field_required'       => 'n',
				'field_data'           => $forum_title,
				'field_show_fmt'       => 'n',
				'field_instructions'   => '',
				'field_text_direction' => 'ltr',
				'field_type'           => 'text',
				'field_maxl'           => 150
			),
			'forum_body' => array(
				'field_id'             => 'forum_body',
				'field_label'          => lang('forum_body'),
				'field_required'       => 'n',
				'field_data'           => $forum_body,
				'field_show_fmt'       => 'n',
				'field_fmt_options'    => array(),
				'field_instructions'   => '',
				'field_text_direction' => 'ltr',
				'field_type'           => 'textarea',
				'field_ta_rows'        => 8
			),
			'forum_id' => array(
				'field_id'             => 'forum_id',
				'field_label'          => lang('forum'),
				'field_required'       => 'n',
				'field_pre_populate'   => 'n',
				'field_list_items'     => (isset($forum_id['choices'])) ? $forum_id['choices'] : '',
				'field_data'           => (isset($forum_id['selected'])) ? $forum_id['selected'] : '',
				'field_text_direction' => 'ltr',
				'field_type'           => 'select',
				'field_instructions'   => '',
				'string_override'      => $forum_id_override,
			),
			'forum_topic_id' => array(
				'field_id'             => 'forum_topic_id',
				'field_label'          => lang('forum_topic_id'),
				'field_type'           => 'text',
				'field_required'       => 'n',
				'field_data'           => $forum_topic_id,
				'field_text_direction' => 'ltr',
				'field_maxl'           => '',
				'field_instructions'   => lang('forum_topic_id_exitsts')
			)
		);

		// No forums, nothing to show
		if ($forum_id_override)
		{
			$settings['forum_topic_id']['field_disabled'] = 'y';
			$settings['forum_body']['field_disabled'] = 'y';
			$settings['forum_title']['field_disabled'] = 'y';
		}

		// Edit - can't change text
		if ($entry_id)
		{
			$settings['forum_id']['field_disabled'] = 'y';
			$settings['forum_body']['field_disabled'] = 'y';

			if ($forum_title == '')
			{
				$settings['forum_title']['field_disabled'] = 'y';
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
	public function validate($entry, $values)
	{
		$allowed = $this->_allowed_forums();

		$validator = ee('Validation')->make();

		$validator->defineRule('valid_forum_title', function($key, $value, $parameters, $rule) use ($values) {
			if (empty($value) && ! empty($values['forum_body']))
			{
				$rule->stop();
				return lang('no_forum_title');
			}

			return TRUE;
		});

		$validator->defineRule('valid_forum_body', function($key, $value, $parameters, $rule) use ($values) {
			if (empty($value) && ! empty($values['forum_title']))
			{
				$rule->stop();
				return lang('no_forum_body');
			}

			return TRUE;
		});

		$validator->defineRule('valid_forum_id', function($key, $value, $parameters) use ($allowed) {
			return in_array($value, $allowed);
		});

		$validator->defineRule('valid_forum_topic_id', function($key, $value, $parameters) use ($allowed, $values) {
			$frm_q = ee()->db->select('forum_id')
				->where('topic_id', (int) $value)
				->get('forum_topics');

			ee()->lang->loadfile('forum_cp');

			if (isset($values['forum_title'], $values['forum_body'])
				&& ( ! empty($values['forum_title']) || ! empty($values['forum_body'])))
			{
				return lang('only_forum_topic_id');
			}

			if ($frm_q->num_rows() <= 0)
			{
				return lang('no_forum_topic_id');
			}

			if ( ! in_array($frm_q->row('forum_id'), $allowed))
			{
				return lang('no_forum_permissions');
			}

			return TRUE;
		});

		$validator->setRules(array(
			'forum_title'    => 'valid_forum_title|maxLength[150]',
			'forum_body'     => 'valid_forum_body',
			'forum_id'       => 'isNatural|valid_forum_id',
			'forum_topic_id' => 'whenPresent|valid_forum_topic_id'
		));

		return $validator->validate($values);
	}

	// --------------------------------------------------------------------

	/**
	 * Insert Tab Data
	 *
	 * @param 	array
	 * @return 	void
	 */
	public function save($entry, $values)
	{
		// Deleting an association
		if ($entry->forum_topic_id != $values['forum_topic_id'])
		{
			$query = ee()->db->select('forum_topic_id')
				->get_where(
					'channel_titles',
					array('entry_id' => $entry->entry_id)
				);

			$old_topic_id = $query->row('forum_topic_id');
			if ( ! empty($old_topic_id))
			{
				ee()->db->where('entry_id', (int) $entry->entry_id)
					->update(
						'channel_titles',
						array('forum_topic_id' => NULL)
					);
				return;
			}
		}

		if ( ! empty($values['forum_title'])
			&& ! empty($values['forum_body'])
			&& ! empty($values['forum_id']))
		{
			$query = ee()->db->select('board_id')
				->get_where(
					'forums',
					array('forum_id' => (int) $values['forum_id'])
				);

			if ($query->num_rows() > 0)
			{
				$title 	= $this->_convert_forum_tags($values['forum_title']);
				$body 	= str_replace(
					'{permalink}',
					 parse_config_variables($entry->Channel->comment_url).'/'.$entry->url_title.'/',
					 $values['forum_body']
				);

				$body 	= $this->_convert_forum_tags(reduce_double_slashes($body));

				$data = array(
					'title' => ee('Security/XSS')->clean($title),
					'body'  => ee('Security/XSS')->clean($body),
				);

				// Allow overwriting existing forum data
				if ( ! empty($values['forum_topic_id']))
				{
					$topic_id = $values['forum_topic_id'];
					ee()->db->where('topic_id', (int) $topic_id)
						 ->update('forum_topics', $data);
				}
				else
				{
					// If we're not overwriting, add in new forum topic parameters
					$new_forum_topic_data = array(
						'forum_id'            => $values['forum_id'],
						'board_id'            => $query->row('board_id'),
						'topic_date'          => ee()->localize->now,
						'author_id'           => $entry->author_id,
						'ip_address'          => ee()->input->ip_address(),
						'last_post_date'      => ee()->localize->now,
						'last_post_author_id' => $entry->author_id,
						'sticky'              => 'n',
						'status'              => 'o',
						'announcement'        => 'n',
						'poll'                => 'n',
						'parse_smileys'       => 'y',
						'thread_total'        => 1
					);

					$data = array_merge($data, $new_forum_topic_data);

					ee()->db->insert('forum_topics', $data);
					$topic_id = ee()->db->insert_id();

					ee()->db->insert('forum_subscriptions', array(
						'topic_id'          => $topic_id,
						'member_id'         => $entry->author_id,
						'subscription_date' => ee()->localize->now,
						'hash'              => $entry->author_id.ee()->functions->random('alpha', 8)
					));

					// Update member post total
					ee()->db->where('member_id', $entry->author_id)
						->update(
							'members',
							array('last_forum_post_date' => ee()->localize->now)
						);
				}

				ee()->db->where('entry_id', (int) $entry->entry_id)
					 ->update('channel_titles', array('forum_topic_id' => (int) $topic_id));

				// Update the forum stats
				if ( ! class_exists('Forum'))
				{
					require PATH_ADDONS.'forum/mod.forum.php';
					require PATH_ADDONS.'forum/mod.forum_core.php';
				}

				$forum_core = new Forum_Core();
				$forum_core->_update_post_stats($values['forum_id']);
			}
		}
		elseif ( ! empty($values['forum_topic_id']))
		{
			ee()->db->where('entry_id', (int) $entry->entry_id)
				->update(
					'channel_titles',
					array('forum_topic_id' => (int) $values['forum_topic_id'])
				);
		}
	}

	// -------------------------------------------------------------------------

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
