<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

/**
 * @todo Move me!  File is current StatsEntity.
 */
class StatEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'stats',
		'primary_key' => 'stat_id',
		'related_entities' => array(
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key' => 'site_id'
			),
			'member_member_id' => array(
				'entity' => 'MemberEntity',
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
