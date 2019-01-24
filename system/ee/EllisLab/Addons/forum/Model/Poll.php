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
 * Poll Model for the Forum
 *
 * A model representing a poll in the Forum.
 */
class Poll extends Model {

	protected static $_primary_key = 'poll_id';
	protected static $_table_name = 'forum_polls';

	protected static $_typed_columns = array(
		'topic_id'      => 'int',
		'author_id'     => 'int',
		'poll_date'     => 'timestamp',
		'total_votes'   => 'int',
	);

	protected static $_relationships = array(
		'Author' => array(
			'type'     => 'belongsTo',
			'model'    => 'ee:Member',
			'from_key' => 'author_id',
			'inverse' => array(
				'name' => 'Poll',
				'type' => 'hasMany'
			)
		),
		'PollVotes' => array(
			'type'  => 'hasMany',
			'model' => 'PollVote'
		),
		'Topic' => array(
			'type' => 'belongsTo',
		),
	);

	protected static $_validation_rules = array(
		'topic_id'      => 'required',
		'poll_question' => 'required',
		'poll_answers'  => 'required',
		'poll_date'     => 'required',
	);

	protected $poll_id;
	protected $topic_id;
	protected $author_id;
	protected $poll_question;
	protected $poll_answers;
	protected $poll_date;
	protected $total_votes;

}

// EOF
