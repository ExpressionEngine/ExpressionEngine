<?php
namespace EllisLab\ExpressionEngine\Model\Site\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Stats Table
 *
 * @package		ExpressionEngine
 * @subpackage	Site\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class StatsGateway extends Gateway {

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
