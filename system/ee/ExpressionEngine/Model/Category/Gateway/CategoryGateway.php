<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Category\Gateway;

use ExpressionEngine\Service\Model\Gateway;

/**
 * Category Table
 */
class CategoryGateway extends Gateway
{
    protected static $_table_name = 'categories';
    protected static $_primary_key = 'cat_id';

    protected static $_related_gateways = array(
        'cat_id' => array(
            'gateway' => 'ChannelTitleGateway',
            'pivot_table' => 'category_posts',
            'pivot_key' => 'cat_id',
            'pivot_foreign_key' => 'entry_id'
        ),

        'site_id' => array(
            'gateway' => 'SiteGateway',
            'key' => 'site_id'
        ),
        'group_id' => array(
            'gateway' => 'CategoryGroupGateway',
            'key' => 'group_id'
        ),
        'parent_id' => array(
            'gateway' => 'CategoryGateway',
            'key' => 'cat_id'
        ),
    );

    // Properties
    protected $cat_id;
    protected $site_id;
    protected $group_id;
    protected $parent_id;
    protected $cat_name;
    protected $cat_url_title;
    protected $cat_description;
    protected $cat_image;
    protected $cat_order;
}

// EOF
