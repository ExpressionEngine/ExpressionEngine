<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\EntryManager;

use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Library\CP\EntryManager\Columns\EntryId;
use ExpressionEngine\Library\CP\EntryManager;

/**
 *
 */
class View extends Model
{
    protected static $_primary_key = 'view_id';
    protected static $_table_name = 'entry_manager_views';

    protected static $_typed_columns = [
        'view_id' => 'int',
        'member_id' => 'int',
        'channel_id' => 'int',
        'name' => 'string',
        'columns' => 'json'
    ];

    protected static $_relationships = [
        'Members' => array(
            'type' => 'belongsTo',
            'model' => 'Member'
        ),
        'Channels' => array(
            'type' => 'belongsTo',
            'model' => 'Channel'
        ),
    ];

    protected static $_validation_rules = [
        'member_id' => 'required',
        'columns' => 'validateColumns'
    ];

    protected $view_id;
    protected $member_id;
    protected $channel_id;
    protected $name;
    protected $columns;

    public function getColumns()
    {
        if (!is_array($this->columns) && !is_null($this->columns)) {
            return json_decode($this->columns);
        }

        return $this->columns;
    }

    public function validateColumns($name, $value, $params, $object)
    {
        $columns = json_decode($this->columns);
        print_r($columns);
        exit;
    }
}

// EOF
