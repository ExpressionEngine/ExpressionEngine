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

use ExpressionEngine\Model\Content\VariableColumnGateway;

/**
 * Category Field Data Table
 */
class CategoryFieldDataGateway extends VariableColumnGateway
{
    protected static $_table_name = 'category_field_data';
    protected static $_primary_key = 'cat_id';
    protected static $_gateway_model = 'CategoryField'; // model that defines elements fetched by this gateway

    protected static $_related_gateways = array(
        'cat_id' => array(
            'gateway' => 'CategoryGateway',
            'key' => 'cat_id'
        ),
        'site_id' => array(
            'gateway' => 'SiteGateway',
            'key' => 'site_id'
        ),
        'group_id' => array(
            'gateway' => 'CategoryGroupGateway',
            'key' => 'group_id'
        ),
    );

    // Properties
    protected $cat_id;
    protected $site_id;
    protected $group_id;
}

// EOF
