<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

/**
 *
 */
class StatsGateway extends RowDataGateway {
	protected static $_table_name = 'stats';
	protected static $_primary_key = 'stat_id';

	protected static $_related_gateways = array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key' => 'site_id'
		),
		'member_member_id' => array(
			'gateway' => 'MemberGateway',
			'key' => 'member_id'
		)
	);

	// Properties
	protected $stat_id;
	protected $site_id;
	protected $total_members;
	protected $recent_member_id;
	protected $recent_member;
	protected $total_entries;
	protected $total_forum_topics;
	protected $total_forum_posts;
	protected $total_comments;
	protected $last_entry_date;
	protected $last_forum_post_date;
	protected $last_comment_date;
	protected $last_visitor_date;
	protected $most_visitors;
	protected $most_visitor_date;
	protected $last_cache_clear;

}
