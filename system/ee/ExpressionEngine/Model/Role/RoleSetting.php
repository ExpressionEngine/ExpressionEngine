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
 * RoleSetting Model
 */
class RoleSetting extends Model
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'role_settings';

    protected static $_hook_id = 'role_setting';

    protected static $_typed_columns = [
        'role_id' => 'int',
        'site_id' => 'int',
        'menu_set_id' => 'int',
        'exclude_from_moderation' => 'boolString',
        'search_flood_control' => 'int',
        'prv_msg_send_limit' => 'int',
        'prv_msg_storage_limit' => 'int',
        'include_in_authorlist' => 'boolString',
        'include_in_memberlist' => 'boolString',
        'cp_homepage_channel' => 'int',
        'require_mfa' => 'boolString',
        'show_field_short_names' => 'boolString',
    ];

    protected static $_relationships = [
        'MenuSet' => array(
            'type' => 'belongsTo',
            'from_key' => 'menu_set_id'
        ),
        'Role' => array(
            'type' => 'belongsTo',
        ),
        'Site' => array(
            'type' => 'belongsTo',
        ),
    ];

    protected static $_validation_rules = [
        'role_id' => 'required',
    ];

    // protected static $_events = [];

    // Properties
    protected $id;
    protected $role_id;
    protected $site_id;
    protected $menu_set_id;
    protected $mbr_delete_notify_emails;
    protected $exclude_from_moderation;
    protected $search_flood_control;
    protected $prv_msg_send_limit;
    protected $prv_msg_storage_limit;
    protected $include_in_authorlist;
    protected $include_in_memberlist;
    protected $cp_homepage;
    protected $cp_homepage_channel;
    protected $cp_homepage_custom;
    protected $require_mfa;
    protected $show_field_names;
}

// EOF
