<?php

namespace EllisLab\ExpressionEngine\Module\Comment\Model\Gateway;

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
 * ExpressionEngine Comment Subscription Gateway
 *
 * A gateway allowing persistance of subscriptions to comments on entries.
 *
 * @package		ExpressionEngine
 * @subpackage	Comment Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CommentSubscriptionGateway extends Gateway {
	protected static $_primary_key = 'subsciption_id';
	protected static $_table_name = 'comment_subscriptions';

	protected static $_related_gateways = array(
		'entry_id' => array(
			'gateway' => 'ChannelTitleGateway',
			'key' => 'entry_id'
		),
		'member_id' => array(
			'gateway' => 'MemberGateway',
			'key' => 'member_id'
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
