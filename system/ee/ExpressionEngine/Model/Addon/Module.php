<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Addon;

use ExpressionEngine\Service\Model\Model;

/**
 * Module Model
 */
class Module extends Model
{
    protected static $_primary_key = 'module_id';
    protected static $_table_name = 'modules';

    protected static $_relationships = array(
        'AssignedRoles' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Role',
            'pivot' => array(
                'table' => 'module_member_roles'
            )
        ),
        'UploadDestination' => array(
            'type' => 'hasMany'
        )
    );

    protected static $_typed_columns = array(
        'has_cp_backend' => 'boolString',
        'has_publish_fields' => 'boolString',
    );

    protected static $_validation_rules = array(
        'has_cp_backend' => 'enum[y,n]',
        'has_publish_fields' => 'enum[y,n]'
    );

    protected $module_id;
    protected $module_name;
    protected $module_version;
    protected $has_cp_backend;
    protected $has_publish_fields;
}

// EOF
