<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Search;

use ExpressionEngine\Service\Model\Model;

/**
 * Search Log Model
 */
class SearchLog extends Model
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'search_log';

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'BelongsTo'
        ),
        'Member' => array(
            'type' => 'BelongsTo'
        )
    );

    protected $id;
    protected $site_id;
    protected $member_id;
    protected $screen_name;
    protected $ip_address;
    protected $search_date;
    protected $search_type;
    protected $search_terms;
}

// EOF
