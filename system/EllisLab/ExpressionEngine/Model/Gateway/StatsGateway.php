<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

/**
 * @todo Move me!  File is current StatsGateway.
 */
class StatGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'stats',
		'primary_key' => 'stat_id',
		'related_gateways' => array(
			'site_id' => array(
				'gateway' => 'SiteGateway',
				'key' => 'site_id'
			),
			'member_member_id' => array(
				'gateway' => 'MemberGateway',
				'key' => 'member_id'
			)
		)
	);


	// Properties
	public $stat_id;
	public $site_id;
	public $total_members;
	public $recent_member_id;
	public $recent_member;
	public $total_entries;
	public $total_forum_topics;
	public $total_forum_posts;
	public $total_comments;
	public $last_entry_date;
	public $last_forum_post_date;
	public $last_comment_date;
	public $last_visitor_date;
	public $most_visitors;
	public $most_visitor_date;
	public $last_cache_clear;

}	
