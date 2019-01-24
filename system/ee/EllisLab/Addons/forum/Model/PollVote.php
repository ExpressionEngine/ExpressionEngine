<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\Forum\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * PollVote Model for the Forum
 *
 * A model representing a poll vote in the Forum.
 */
class PollVote extends Model {

	protected static $_primary_key = 'vote_id';
	protected static $_table_name = 'forum_pollvotes';

	protected static $_typed_columns = array(
		'poll_id'   => 'int',
		'topic_id'  => 'int',
		'member_id' => 'int',
		'choice_id' => 'int',
	);

	protected static $_relationships = array(
		'Member' => array(
			'type' => 'belongsTo',
			'model' => 'ee:Member',
			'inverse' => array(
				'name' => 'PollVote',
				'type' => 'hasMany',
			)
		),
		'Poll' => array(
			'type' => 'belongsTo'
		),
		'Topic' => array(
			'type' => 'belongsTo'
		),
	);

	protected static $_validation_rules = array(
		'poll_id'   => 'required',
		'topic_id'  => 'required',
		'member_id' => 'required',
		'choice_id' => 'required',
	);

	protected $vote_id;
	protected $poll_id;
	protected $topic_id;
	protected $member_id;
	protected $choice_id;

}

// EOF
