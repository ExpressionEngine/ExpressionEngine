<?php

namespace EllisLab\Addons\Forum\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Search Model for the Forum
 *
 * A model representing a search in the Forum.
 *
 * @package		ExpressionEngine
 * @subpackage	Forum Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Search extends Model {

	protected static $_primary_key = 'search_id';
	protected static $_table_name = 'forum_search';

	protected static $_typed_columns = array(
		'board_id'    => 'int',
		'search_date' => 'timestamp',
		'member_id'   => 'int',
	);

	protected static $_relationships = array(
		'Board' => array(
			'type' => 'belongsTo'
		),
		'Member' => array(
			'type'  => 'belongsto',
			'model' => 'ee:Member',
			'weak'  => TRUE,
			'inverse' => array(
				'name' => 'Search',
				'type' => 'hasMany'
			)
		),
	);

	protected static $_validation_rules = array(
		'search_date' => 'required',
		'keywords'    => 'required',
		'member_id'   => 'required',
		'ip_address'  => 'required|ipAddress',
		'topic_ids'   => 'required',
		'post_ids'    => 'required',
		'sort_order'  => 'required',
	);

	protected $search_id;
	protected $board_id;
	protected $search_date;
	protected $keywords;
	protected $member_id;
	protected $ip_address;
	protected $topic_ids;
	protected $post_ids;
	protected $sort_order;

}

// EOF
