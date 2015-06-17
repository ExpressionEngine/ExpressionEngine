<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class ChannelTitleGateway extends Gateway {
	// Structural definition stuff
	protected static $_table_name 		= 'channel_titles';
	protected static $_primary_key 		= 'entry_id';
	protected static $_related_gateways = array(
		'entry_id' => array(
			'Categories'=>array(
				'gateway' => 'CategoryGateway',
				'key'	 => 'cat_id',
				'pivot_table' => 'category_posts',
				'pivot_key' => 'entry_id',
				'pivot_foreign_key' => 'cat_id'
			)
		),
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key'	 => 'site_id'
		),
		'channel_id' => array(
			'gateway' => 'ChannelGateway',
			'key'    => 'channel_id'
		),
		'author_id' => array(
			'gateway' => 'MemberGateway',
			'key'	=> 'member_id'
		)
	);

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
