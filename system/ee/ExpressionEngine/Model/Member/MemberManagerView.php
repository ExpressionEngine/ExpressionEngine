<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Member;

use ExpressionEngine\Service\Model\Model;

/**
 *
 */
class MemberManagerView extends Model
{
    protected static $_primary_key = 'view_id';
    protected static $_table_name = 'member_manager_views';

    protected static $_typed_columns = [
        'view_id' => 'int',
        'member_id' => 'int',
        'role_id' => 'int',
        'name' => 'string',
        'columns' => 'serialized'
    ];

    protected static $_relationships = [
        'Members' => array(
            'type' => 'belongsTo',
            'model' => 'Member'
        ),
        'Roles' => array(
            'type' => 'belongsTo',
            'model' => 'Role'
        ),
    ];

    protected static $_validation_rules = [
        'member_id' => 'required'
    ];

    protected $view_id;
    protected $member_id;
    protected $role_id;
    protected $name;
    protected $columns;

    public function getColumns()
    {
        if (!is_array($this->columns)) {
            return json_decode($this->columns);
        }

        return $this->columns;
    }
}

// EOF
