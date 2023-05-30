<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

namespace ExpressionEngine\Model\Role;

use ExpressionEngine\Service\Model\Model;

/**
 * RoleGroup Model
 */
class RoleGroup extends Model
{
    protected static $_primary_key = 'group_id';
    protected static $_table_name = 'role_groups';

    protected static $_hook_id = 'role_group';

    protected static $_typed_columns = [
        'group_id' => 'int',
    ];

    protected static $_relationships = [
        'Roles' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Role',
            'pivot' => array(
                'table' => 'roles_role_groups'
            ),
            'weak' => true
        ),
        'Members' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Member',
            'pivot' => array(
                'table' => 'members_role_groups'
            ),
            'weak' => true
        ),
    ];

    protected static $_validation_rules = [
        'name' => 'required',
    ];

    // protected static $_events = [];

    // Properties
    protected $group_id;
    protected $name;
}

// EOF
