<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Model\Comment;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * ExpressionEngine Comment Subscription Model
 *
 * A model representing user subscriptions to the comment thread on a particular
 * entry.
 *
 * @package		ExpressionEngine
 * @subpackage	Comment Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class CommentSubscription extends Model {

	protected static $_primary_key = 'subscription_id';
	protected static $_table_name = 'comment_subscriptions';

	protected static $_relationships = array(
		'Entry' => array(
			'type' => 'belongsTo',
			'model' => 'ChannelEntry'
		),
		'Member' => array(
			'type' => 'belongsTo'
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

// EOF
