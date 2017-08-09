<?php

namespace EllisLab\Addons\Comment\Service\Spam;

use EllisLab\Addons\Comment\Service\Notifications;

/**
 * Moderate Spam for the Comment module
 */
class Moderate {

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
		// open it
		$comment->status = 'o';
		$comment->edit_date = ee()->localize->now;
		$comment->save();

		// update stats
		// comment total and recent comment date
		ee()->db->set('recent_comment_date', $comment->comment_date);
		ee()->db->where('entry_id', $comment->entry_id);
		ee()->db->update('channel_titles');

		// member's comment stats
		if (ee()->session->userdata('member_id') != 0)
		{
			ee()->db->select('total_comments');
			ee()->db->where('member_id', ee()->session->userdata('member_id'));
			$query = ee()->db->get('members');

			ee()->db->set('total_comments', $query->row('total_comments') + 1);
			ee()->db->set('last_comment_date', $comment->comment_date);
			ee()->db->where('member_id', ee()->session->userdata('member_id'));
			ee()->db->update('members');
		}

		// site comment stats
		ee()->stats->update_comment_stats($comment->channel_id, $comment->comment_date);

		// send notifications
		$notify = new Notifications($comment, $comment_path);
		$notify->sendAdminNotifications();
		$notify->sendUserNotifications();

		// clear caches
		ee()->functions->clear_caching('all', ee()->functions->fetch_site_index().$comment_path);

		// clear out the entry_id version if the url_title is in the URI, and vice versa
		if (preg_match("#\/".preg_quote($comment->Entry->url_title)."\/#", $comment_path, $matches))
		{
			ee()->functions->clear_caching('all', ee()->functions->fetch_site_index().preg_replace("#".preg_quote($matches['0'])."#", "/{$comment->entry_id}/", $comment_path));
		}
		else
		{
			ee()->functions->clear_caching('all', ee()->functions->fetch_site_index().preg_replace("#{$comment->entry_id}#", $comment->Entry->url_title, $comment_path));
		}
	}

	/**
	 * Reject Trapped Spam
	 *
	 * @param  object $comment EllisLab\ExpressionEngine\Model\Comment
	 * @return void
	 */
	public function reject($comment)
	{
		$comment->delete();
	}
}
// END CLASS

// EOF
