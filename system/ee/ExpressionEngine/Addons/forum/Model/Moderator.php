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
 * Moderator Model for the Forum
 *
 * A model representing a moderator in the Forum.
 */
class Moderator extends Model
{
    protected static $_primary_key = 'mod_id';
    protected static $_table_name = 'forum_moderators';

    protected static $_typed_columns = array(
        'board_id' => 'int',
        'mod_forum_id' => 'int',
        'mod_member_id' => 'int',
        'mod_group_id' => 'int',
        'mod_can_edit' => 'boolString',
        'mod_can_move' => 'boolString',
        'mod_can_delete' => 'boolString',
        'mod_can_split' => 'boolString',
        'mod_can_merge' => 'boolString',
        'mod_can_change_status' => 'boolString',
        'mod_can_announce' => 'boolString',
        'mod_can_view_ip' => 'boolString',
    );

    protected static $_relationships = array(
        'Board' => array(
            'type' => 'belongsTo'
        ),
        'Forum' => array(
            'type' => 'belongsTo',
            'from_key' => 'mod_forum_id',
            'to_key' => 'forum_id'
        ),
        'Member' => array(
            'type' => 'belongsTo',
            'model' => 'ee:Member',
            'from_key' => 'mod_member_id',
            'to_key' => 'member_id',
            'inverse' => array(
                'name' => 'Moderator',
                'type' => 'hasMany'
            )
        ),
        'Role' => array(
            'type' => 'belongsTo',
            'model' => 'ee:Role',
            'from_key' => 'mod_group_id',
            'to_key' => 'role_id',
            'inverse' => array(
                'name' => 'Moderator',
                'type' => 'hasMany'
            )
        ),
    );

    protected static $_validation_rules = array(
        'mod_forum_id' => 'required',
        'mod_member_name' => 'required',
        'mod_can_edit' => 'enum[y,n]',
        'mod_can_move' => 'enum[y,n]',
        'mod_can_delete' => 'enum[y,n]',
        'mod_can_split' => 'enum[y,n]',
        'mod_can_merge' => 'enum[y,n]',
        'mod_can_change_status' => 'enum[y,n]',
        'mod_can_announce' => 'enum[y,n]',
        'mod_can_view_ip' => 'enum[y,n]',
    );

    protected $mod_id;
    protected $board_id;
    protected $mod_forum_id;
    protected $mod_member_id;
    protected $mod_member_name;
    protected $mod_group_id;
    protected $mod_can_edit;
    protected $mod_can_move;
    protected $mod_can_delete;
    protected $mod_can_split;
    protected $mod_can_merge;
    protected $mod_can_change_status;
    protected $mod_can_announce;
    protected $mod_can_view_ip;

    public function getModeratorName()
    {
        $name = $this->mod_member_name;

        if ($this->mod_group_id) {
            $name = $this->Role->name;
        }

        return $name;
    }

    public function getType()
    {
        $type = "";

        if ($this->mod_group_id) {
            $type = lang('group');
        } elseif ($this->mod_member_id) {
            $type = lang('individual');
        }

        return $type;
    }
}

// EOF
