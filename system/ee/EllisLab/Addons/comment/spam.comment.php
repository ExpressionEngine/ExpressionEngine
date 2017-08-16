<?php

namespace EllisLab\Addons\Comment;

use EllisLab\Addons\Comment\Service\Notifications;
use EllisLab\Addons\Spam\Service\SpamModerationInterface;

/**
 * Moderate Spam for the Comment module
 */
class Comment_spam implements SpamModerationInterface {

	/**
	 * Approve Trapped Spam
	 * Posts the comment and sends relevant notifications
	 *
	 * @param  object $comment EllisLab\ExpressionEngine\Model\Comment
	 * @param  string $comment_path URL to the comment
	 * @return void
	 */
	public function approve($comment, $comment_path)
	{
		// is this comment still relevant?
		if ( ! $entry = $comment->Entry)
		{
			$comment->delete();
			throw new \Exception('Comment deleted, entry no longer exists');
		}

		// open it
		$comment->status = 'o';
		$comment->edit_date = ee()->localize->now;
		$comment->save();

		// send notifications
		// if column is NULL, $comment_path will be turned into an empty array by the model
		$notify = new Notifications($comment, ($comment_path) ?: '');
		$notify->sendAdminNotifications();
		$notify->sendUserNotifications();

		// @TODO we have the $comment_path so we could clear just the cache that we need to,
		// but currently the model obliterates ALL caches, and we don't have a way to inform
		// the model of where the comment lives
	}

	/**
	 * Reject Trapped Spam
	 *
	 * @param  object $comment EllisLab\ExpressionEngine\Model\Comment
	 * @param  string $comment_path URL to the comment
	 * @return void
	 */
	public function reject($comment, $comment_path)
	{
		$comment->delete();
	}
}
// END CLASS

// EOF
