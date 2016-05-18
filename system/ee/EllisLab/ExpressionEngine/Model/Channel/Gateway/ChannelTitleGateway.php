<?php

namespace EllisLab\ExpressionEngine\Model\Channel\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class ChannelTitleGateway extends Gateway {

	protected static $_table_name = 'channel_titles';
	protected static $_primary_key = 'entry_id';

	// Properties
	public $entry_id;
	public $site_id;
	public $channel_id;
	public $author_id;
	public $forum_topic_id;
	public $ip_address;
	public $title;
	public $url_title;
	public $status;
	public $versioning_enabled;
	public $view_count_one;
	public $view_count_two;
	public $view_count_three;
	public $view_count_four;
	public $allow_comments;
	public $sticky;
	public $entry_date;
	public $year;
	public $month;
	public $day;
	public $expiration_date;
	public $comment_expiration_date;
	public $edit_date;
	public $recent_comment_date;
	public $comment_total;

}

// EOF
