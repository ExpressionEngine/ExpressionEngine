<?php

namespace EllisLab\Addons\Comment\Service\Spam;

/**
 * Moderate Spam for the Comment module
 */
class Moderate {

	public function approve($comment)
	{
		$comment->status = 'o';
		$comment->save();
	}

	public function reject($comment)
	{
		$comment->delete();
	}
}
// END CLASS

// EOF
