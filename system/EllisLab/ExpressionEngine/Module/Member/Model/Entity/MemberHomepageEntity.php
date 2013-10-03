<?php
namespace EllisLab\ExpressionEngine\Module\Member\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

/**
 * CP homepage layout
 * Each member can have their own control panel layout.
 * We store their preferences here.
 */
class MemberHomepageEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'member_homepage',
		'primary_id' => 'member_id'
	);

	// Properties
	public $member_id;
	public $recent_entries;
	public $recent_entries_order;
	public $recent_comments;
	public $recent_comments_order;
	public $recent_members;
	public $recent_members_order;
	public $site_statistics;
	public $site_statistics_order;
	public $member_search_form;
	public $member_search_form_order;
	public $notepad;
	public $notepad_order;
	public $bulletin_board;
	public $bulletin_board_order;
	public $pmachine_news_feed;
	public $pmachine_news_feed_order;
}
