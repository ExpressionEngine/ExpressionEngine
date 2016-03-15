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
 * ExpressionEngine PollVote Model for the Forum
 *
 * A model representing a poll vote in the Forum.
 *
 * @package		ExpressionEngine
 * @subpackage	Forum Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
			'weak'     => TRUE,
			'inverse' => array(
				'name' => 'PollVote',
				'type' => 'hasMany',
			)
		),
		'Poll' => array(
			'type' => 'belongsTo',
			'weak' => TRUE
		),
		'Topic' => array(
			'type' => 'belongsTo',
			'weak' => TRUE
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
