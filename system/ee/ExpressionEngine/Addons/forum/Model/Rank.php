<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Forum\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * Rank Model for the Forum
 *
 * A model representing a rank in the Forum.
 */
class Rank extends Model
{
    protected static $_primary_key = 'rank_id';
    protected static $_table_name = 'forum_ranks';

    protected static $_typed_columns = array(
        'rank_min_posts' => 'int'
    );

    protected static $_validation_rules = array(
        'rank_title' => 'required',
        'rank_min_posts' => 'required',
        'rank_stars' => 'required',
    );

    protected $rank_id;
    protected $rank_title;
    protected $rank_min_posts;
    protected $rank_stars;
}

// EOF
