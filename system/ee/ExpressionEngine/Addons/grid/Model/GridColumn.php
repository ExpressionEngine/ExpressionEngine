<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Grid\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine Grid Column Model
 */
class GridColumn extends Model
{
    protected static $_primary_key = 'col_id';
    protected static $_table_name = 'grid_columns';

    protected static $_typed_columns = array(
        'field_id' => 'int',
        'col_settings' => 'json',
        'col_order' => 'int'
    );

    protected static $_relationships = array(
        'ChannelField' => array(
            'type' => 'belongsTo',
            'model' => 'ee:ChannelField',
            'from_key' => 'field_id',
            'to_key' => 'field_id',
            'weak' => true
        )
    );

    protected $col_id;
    protected $field_id;
    protected $content_type;
    protected $col_order;
    protected $col_type;
    protected $col_label;
    protected $col_name;
    protected $col_instructions;
    protected $col_required;
    protected $col_search;
    protected $col_width;
    protected $col_settings;

}

// EOF