<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Site;

use ExpressionEngine\Service\Model\Model;

/**
 * Stats Model
 */
class Stats extends Model
{
    protected static $_primary_key = 'stat_id';
    protected static $_table_name = 'stats';

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'BelongsTo'
        ),
        'RecentMember' => array(
            'type' => 'BelongsTo',
            'model' => 'Member',
            'from_key' => 'recent_member_id',
            'weak' => true
        )
    );

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

// EOF
