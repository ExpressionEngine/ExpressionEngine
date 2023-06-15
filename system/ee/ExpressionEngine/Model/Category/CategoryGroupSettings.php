<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Category;

use ExpressionEngine\Service\Model\Model;

/**
 * Category Group Settings Model
 */
class CategoryGroupSettings extends Model
{
    protected static $_primary_key = 'category_group_settings_id';
    protected static $_table_name = 'category_group_settings';

    protected static $_hook_id = 'category_group_settings';

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo'
        ),
        'Channel' => array(
            'type' => 'belongsTo'
        ),
        'CategoryGroup' => array(
            'type' => 'belongsTo'
        ),
    );

    protected static $_typed_columns = array(
        'cat_required' => 'boolString',
        'cat_allow_multiple' => 'boolString'
    );

    protected static $_validation_rules = array(
        'group_id' => 'required',
        'channel_id' => 'required'
    );

    // Properties
    protected $category_group_settings_id;
    protected $site_id;
    protected $group_id;
    protected $channel_id;
    protected $cat_required;
    protected $cat_allow_multiple;
}

// EOF
