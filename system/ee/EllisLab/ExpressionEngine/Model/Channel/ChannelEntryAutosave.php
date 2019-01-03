<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Channel;

use EllisLab\ExpressionEngine\Service\Model\Model as Model;

/**
 * Channel Entry Autosave Model
 */
class ChannelEntryAutosave extends Model {

	protected static $_primary_key = 'entry_id';
	protected static $_table_name = 'channel_entries_autosave';

	protected static $_typed_columns = array(
		'entry_data' => 'json'
	);

	protected static $_relationships = array(
		'ChannelEntry' => array(
			'type' => 'belongsTo',
			'from_key' => 'original_entry_id'
		),
		'Channel' => array(
			'type' => 'belongsTo',
			'key' => 'channel_id'
		),
		'Author'	=> array(
			'type' => 'belongsTo',
			'model' => 'Member',
			'from_key' 	=> 'author_id',
			'weak' => TRUE
		),
	);

	// Properties
	protected $entry_id;
	protected $original_entry_id;
	protected $site_id;
	protected $channel_id;
	protected $author_id;
	protected $forum_topic_id;
	protected $ip_address;
	protected $title;
	protected $url_title;
	protected $status;
	protected $versioning_enabled;
	protected $view_count_one;
	protected $view_count_two;
	protected $view_count_three;
	protected $view_count_four;
	protected $allow_comments;
	protected $sticky;
	protected $entry_date;
	protected $year;
	protected $month;
	protected $day;
	protected $expiration_date;
	protected $comment_expiration_date;
	protected $edit_date;
	protected $recent_comment_date;
	protected $comment_total;
	protected $entry_data;
}

// EOF
