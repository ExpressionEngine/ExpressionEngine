<?php

namespace EllisLab\Addons\Comment\Service\Spam;

use EllisLab\Addons\Comment\Service\Notifications;

/**
 * Moderate Spam for the Comment module
 */
class Moderate {

	public function approve($comment)
	{
		$comment->status = 'o';
		$comment->save();

		// send notifications
		$notify = new Notifications($comment, '/foo/bar/url-title');
		$notify->send_admin_notifications();
		$notify->send_user_notifications();
	}

	public function reject($comment)
	{
		$comment->delete();
	}
}
// END CLASS

// EOF
