<?php

namespace EllisLab\Addons\Forum;

use EllisLab\Addons\Forum\Service\Notifications;
use EllisLab\Addons\Spam\Service\SpamModerationInterface;

/**
 * Moderate Spam for the Forum module
 */
class Forum_spam implements SpamModerationInterface  {

	/**
	 * @var object Forum_core class
	 */
	protected $fc;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		require_once PATH_ADDONS.'forum/mod.forum.php';
		require_once PATH_ADDONS.'forum/mod.forum_core.php';

		$this->fc = new \Forum_core;
	}

	/**
	 * Approve Trapped Spam
	 * Posts the content to the forums and sends relevant notifications
	 *
	 * @param  string $sql SQL query to run to activate the post
	 * @param  array $extra array('postdata' => $_POST, 'redirect' => <url>)
	 * @return void
	 */
	public function approve($sql, $extra)
	{
		// take ze action
		ee()->db->query($sql);

		$postdata = $extra['postdata'];
		$redirect = $extra['redirect'];

		$post = NULL;

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
		if ($post)
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
			$subs_count = ee()->db->where('topic_id', $topic->topic_id)
				->where('member_id', $member->member_id)
			 	->count_all_results('forum_subscriptions');

			// if for some reason there are more than 1 sub for the user, let's get rid of all the old ones
			if ($subs_count > 1)
			{
				ee()->db->where('topic_id', $topic->topic_id);
				ee()->db->where('member_id', $member->member_id);
				ee()->db->delete('forum_subscriptions');
			}

			// if they don't have exactly 1 sub, add it!
			if ($subs_count != 1)
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
			// Unsubscribe on Reply? Also cleans up potential orphans
			// Relevant if this was HAMmed and they unchecked the notify box on this
			// reply, indicating that they want to stop getting notifications
			ee()->db->where('topic_id', $topic->topic_id);
			ee()->db->where('member_id', $member->member_id);
			ee()->db->delete('forum_subscriptions');
		}

		// send notifications
		$notify = new Notifications($topic, $redirect, $post);
		$notify->send_admin_notifications();
		$notify->send_user_notifications();
	}

	/**
	 * Reject Trapped Spam
	 *
	 * @param  string $sql SQL query that holds the submitted content
	 * @param  array $extra array('postdata' => $_POST, 'redirect' => <url>)
	 * @return void
	 */
	public function reject($sql, $extra)
	{
		// nothing to do, we've not saved anything outside of the spam trap
	}

}
// END CLASS

// EOF
