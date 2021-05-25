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
 * Administrator Model for the Forum
 *
 * A model representing an administrator in the Forum.
 */
class Administrator extends Model
{
    protected static $_primary_key = 'admin_id';
    protected static $_table_name = 'forum_administrators';

    protected static $_typed_columns = array(
        'board_id' => 'int',
        'admin_group_id' => 'int',
        'admin_member_id' => 'int',
    );

    protected static $_relationships = array(
        'Board' => array(
            'type' => 'belongsTo'
        ),
        'Member' => array(
            'type' => 'belongsTo',
            'model' => 'ee:Member',
            'from_key' => 'admin_member_id',
            'to_key' => 'member_id',
            'inverse' => array(
                'name' => 'Administrator',
                'type' => 'hasMany'
            )
        ),
        'Role' => array(
            'type' => 'belongsTo',
            'model' => 'ee:Role',
            'from_key' => 'admin_group_id',
            'to_key' => 'role_id',
            'inverse' => array(
                'name' => 'Administrator',
                'type' => 'hasMany'
            )
        ),
    );

    protected static $_validation_rules = array(
        'board_id' => 'required',
        'admin_group_id' => 'required',
        'admin_member_id' => 'required',
    );

    protected $admin_id;
    protected $board_id;
    protected $admin_group_id;
    protected $admin_member_id;

    public function getAdminName()
    {
        $name = "";

        if ($this->admin_group_id) {
            $name = $this->Role->name;
        } elseif ($this->admin_member_id) {
            $name = $this->Member->getMemberName();
        }

        return $name;
    }

    public function getType()
    {
        $type = "";

        if ($this->admin_group_id) {
            $type = lang('group');
        } elseif ($this->admin_member_id) {
            $type = lang('individual');
        }

        return $type;
    }
}

// EOF
