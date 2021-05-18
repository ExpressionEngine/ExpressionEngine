<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

namespace ExpressionEngine\Model\Role;

use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\Collection;

/**
 * Role Model
 */
class Role extends Model
{
    protected static $_primary_key = 'role_id';
    protected static $_table_name = 'roles';

    protected static $_hook_id = 'role';

    protected static $_typed_columns = [
        'role_id' => 'int',
        'is_locked' => 'boolString',
    ];

    protected static $_relationships = [
        'ChannelLayouts' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'ChannelLayout',
            'pivot' => array(
                'table' => 'layout_publish_member_roles',
                'key' => 'layout_id',
            )
        ),
        'Permissions' => array(
            'model' => 'Permission',
            'type' => 'hasMany'
        ),
        'RoleSettings' => array(
            'model' => 'RoleSetting',
            'type' => 'hasMany'
        ),
        'RoleGroups' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'RoleGroup',
            'pivot' => array(
                'table' => 'roles_role_groups'
            ),
            'weak' => true
        ),
        'PrimaryMembers' => array(
            'model' => 'Member',
            'type' => 'hasMany',
            'weak' => true
        ),
        'Members' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Member',
            'pivot' => array(
                'table' => 'members_roles'
            ),
            'weak' => true
        ),
        'AssignedChannels' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Channel',
            'pivot' => array(
                'table' => 'channel_member_roles'
            )
        ),
        'AssignedModules' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Module',
            'pivot' => array(
                'table' => 'module_member_roles'
            )
        ),
        'AssignedStatuses' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Status',
            'pivot' => array(
                'table' => 'statuses_roles',
                'left' => 'role_id',
                'right' => 'status_id'
            )
        ),
        'AssignedTemplates' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Template',
            'pivot' => array(
                'table' => 'templates_roles',
                'left' => 'role_id',
                'right' => 'template_id'
            )
        ),
        'AssignedTemplateGroups' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'TemplateGroup',
            'pivot' => array(
                'table' => 'template_groups_roles',
                'left' => 'role_id',
                'right' => 'template_group_id'
            )
        ),
        'AssignedUploadDestinations' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'UploadDestination',
            'pivot' => array(
                'table' => 'upload_prefs_roles',
                'left' => 'role_id',
                'right' => 'upload_id'
            )
        ),
        'EmailCache' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'EmailCache',
            'pivot' => array(
                'table' => 'email_cache_mg'
            )
        ),
    ];

    protected static $_validation_rules = [
        'name' => 'required|unique|maxLength[100]',
        'short_name' => 'required|unique|alphaDash|maxLength[50]',
    ];

    protected static $_events = array(
        'afterSave'
    );

    // Properties
    protected $role_id;
    protected $name;
    protected $short_name;
    protected $description;
    protected $is_locked;

    /**
     * Get all members that are assigned to this role (as primary or extra one)
     *
     * @return Collection
     */
    public function getAllMembers()
    {
        $members = array_replace($this->Members->indexBy('member_id'), $this->PrimaryMembers->indexBy('member_id'));


        foreach ($this->RoleGroups as $role_group) {
            foreach ($role_group->Members as $member) {
                $members[$member->member_id] = $member;
            }
        }

        return new Collection($members);
    }

    protected function saveToCache($key, $data)
    {
        if (isset(ee()->session)) {
            ee()->session->set_cache(__CLASS__, $key, $data);
        }
    }

    protected function getFromCache($key)
    {
        if (isset(ee()->session)) {
            return ee()->session->cache(__CLASS__, $key, false);
        }

        return false;
    }

    /**
     * Get permissions assigned to member role
     *
     * @return Array ['permission' => 'permission_id']
     */
    public function getPermissions()
    {
        $cache_key = "Role/{$this->role_id}/Permissions";

        $permissions = $this->getFromCache($cache_key);

        if ($permissions === false) {
            $permissions = $this->getModelFacade()->get('Permission')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('role_id', $this->getId())
                ->all()
                ->getDictionary('permission', 'permission_id');

            $this->saveToCache($cache_key, $permissions);
        }

        return $permissions;
    }

    public function can($permission)
    {
        if ($this->role_id == 1) {
            return true;
        }
        
        $permissions = $this->getPermissions();

        return array_key_exists('can_' . $permission, $permissions);
    }

    /**
     * Checks whether member role has certain permission
     *
     * @param [String] $permission
     * @return boolean
     */
    public function has($permission)
    {
        if ($this->role_id == 1) {
            return true;
        }
        
        $permissions = $this->getPermissions();

        return array_key_exists($permission, $permissions);
    }

    public function onAfterSave()
    {
        ee('CP/JumpMenu')->clearAllCaches();
    }
}

// EOF
