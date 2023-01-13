<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Comment;

use ExpressionEngine\Service\Model\Model;

/**
 * Comment Subscription Model
 *
 * A model representing user subscriptions to the comment thread on a particular
 * entry.
 */
class CommentSubscription extends Model
{
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
