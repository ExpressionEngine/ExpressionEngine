<?php

namespace EllisLab\Addons\Forum\Service\Spam;

use EllisLab\Addons\Forum\Service\Notifications;

/**
 * Moderate Spam for the Comment module
 */
class Moderate {

	protected $fc;

	public function __construct()
	{
		require_once PATH_ADDONS.'forum/mod.forum.php';
		require_once PATH_ADDONS.'forum/mod.forum_core.php';

		$this->fc = new \Forum_core;
	}

	public function approve($sql, $extra)
	{
		// take ze action
		ee()->db->query($sql);

		$extra = unserialize($extra);
		$postdata = $extra['postdata'];
		$redirect = $extra['redirect'];

		// was this a new topic or a reply?
		if (preg_match('/^INSERT INTO `?[a-z0-9_]+forum_topics/', $sql))
		{
			$topic = ee('Model')->get('forum:Topic', ee()->db->insert_id())->first();
			$member = $topic->Author;
		}
		else
		{
			$post = ee('Model')->get('forum:Post', ee()->db->insert_id())->first();

			if ( ! $post)
			{
				// I've made a terrible mistake
				return;
			}

			$topic = $post->Topic;
			$member = $post->Author;
		}

		if ( ! $topic)
		{
			// I've made a terrible mistake
			return;
		}

		// went live now, so let's make it so
		$topic->topic_date = ee()->localize->now;
		$topic->save();

		// Update the topic stats (count, last post info)
		if (isset($post))
		{
			$this->fc->_update_topic_stats($topic->topic_id);
		}

		// Update the forum stats
		$this->fc->_update_post_stats($topic->forum_id);
		$this->fc->_update_global_stats();

		// Update member post date
		ee()->db->where('member_id', $topic->author_id);
		ee()->db->update('members', array('last_forum_post_date' => ee()->localize->now));

		// Manage subscriptions
		if (isset($postdata['notify']) && $postdata['notify'] == 'y')
		{
			$query = ee()->db->select('COUNT(*) as count')
				->where('topic_id', $topic->topic_id)
				->where('member_id', $member->member_id)
			 	->get('forum_subscriptions');

			$row = $query->row_array();

			if ($row['count'] > 1)
			{
				ee()->db->where('topic_id', $topic->topic_id);
				ee()->db->where('member_id', $member->member_id);
				ee()->db->delete('forum_subscriptions');

				$row['count'] = 0;
			}

			if ($row['count'] == 0)
			{
				$rand = $member->member_id.ee()->functions->random('alnum', 8);

				$data = array(
					'topic_id'				=> $topic->topic_id,
					'board_id'				=> $topic->board_id,
					'member_id'				=> $member->member_id,
					'subscription_date'		=> ee()->localize->now,
					'hash'					=> $rand
				);

				ee()->db->insert('forum_subscriptions', $data);
			}
		}
		else
		{
			ee()->db->where('topic_id', $topic->topic_id);
			ee()->db->where('member_id', $member->member_id);
			ee()->db->delete('forum_subscriptions');
		}

		// send notifications
		$notify = new Notifications($topic, $redirect);
		$notify->send_admin_notifications();
		$notify->send_user_notifications();

	}

	public function reject($sql)
	{
		// nothing to do, we've not saved anything outside of the spam trap
	}

}
// END CLASS

// EOF
