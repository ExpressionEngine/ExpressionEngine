<?php

namespace EllisLab\Addons\Channel;
use EllisLab\Addons\Spam\Service\SpamModerationInterface;

/**
 * Moderate Spam for the Channel Form
 */
class Channel_spam implements SpamModerationInterface  {

	/**
	 * Approve Trapped Spam
	 *
	 * @param  object $entry EllisLab\ExpressionEngine\Model\ChannelEntry
	 * @param  array $post_data The original $_POST data
	 * @return void
	 */
	public function approve($entry, $post_data)
	{
		// save it
		$entry->set($post_data);
		$entry->edit_date = ee()->localize->now;
		$entry->save();

		// ChannelEntry model handles all post-save actions: notifications, cache clearing, stats updates, etc.
	}

	/**
	 * Reject Trapped Spam
	 *
	 * @param  object $entry EllisLab\ExpressionEngine\Model\ChannelEntry
	 * @param  array $post_data The original $_POST data
	 * @return void
	 */
	public function reject($entry, $post_data)
	{
		// Nothing was saved outside of the spam trap, so we don't need to do anything
		return;
	}
}
// END CLASS

// EOF
