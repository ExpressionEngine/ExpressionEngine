<?php

namespace EllisLab\ExpressionEngine\Model\Comment;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Comment Subscription Model
 *
 * A model representing user subscriptions to the comment thread on a particle
 * entry.
 *
 * @package		ExpressionEngine
 * @subpackage	Comment Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CommentSubscription extends Model {

	protected static $_primary_key = 'subscription_id';
	protected static $_table_name = 'comment_subscriptions';

	protected static $_relationships = array(
		'Entry' => array(
			'type' => 'many_to_one',
			'model' => 'ChannelEntry'
		),
		'Member' => array(
			'type' => 'many_to_one'
		)
	);

	protected $subscription_id;
	protected $entry_id;
	protected $member_id;
	protected $email;
	protected $subscription_date;
	protected $notification_sent;
	protected $hash;
}
