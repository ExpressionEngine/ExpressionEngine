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
use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Service\Member\Member;

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
        'beforeSave',
        'afterSave'
    );

    // Properties
    protected $role_id;
    protected $name;
    protected $short_name;
    protected $description;
    protected $highlight;
    protected $total_members;
    protected $is_locked;

    /**
     * Getter for highlight property
     * Will ensure default colors for built-in roles
     *
     * @return string
     */
    public function get__highlight()
    {
        $highlight = $this->getRawProperty('highlight');
        if (empty($highlight)) {
            switch ($this->role_id) {
                case Member::SUPERADMIN:
                    $highlight = '00C571'; //--ee-brand-success
                    break;
                case Member::BANNED:
                    $highlight = 'FA5252'; //--ee-brand-danger
                    break;
                case Member::GUESTS:
                    $highlight = '8F90B0'; //--ee-text-secondary
                    break;
                case Member::PENDING:
                    $highlight = 'FFB40B'; //--ee-brand-warning
                    break;
                case Member::MEMBERS:
                default:
                    $highlight = '5D63F1'; //--ee-brand-primary
                    break;
            }
        }
        return $highlight;
    }

    public function set__highlight($highlight)
    {
        $this->setRawProperty('highlight', ltrim((string) $highlight, '#'));
    }

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

    /**
     * Get total number of members that are assigned to this role (as primary or extra one)
     *
     * @param string $mode all/primary/secondary
     * @return int
     */
    public function getMembersCount($mode = 'all')
    {
        $query = ee('db')
            ->select("COUNT(DISTINCT members.member_id) AS total_members")
            ->from('members AS members');
        if (in_array($mode, ['all', 'secondary'])) {
            $query->join('members_roles', 'members_roles.member_id=members.member_id', 'left')
                ->join('members_role_groups', 'members_role_groups.member_id=members.member_id', 'left');
        }
        if (in_array($mode, ['all', 'primary'])) {
            $query->where('members.role_id', $this->getId());
        }
        if ($mode == 'all') {
            $query->or_where('members_roles.role_id', $this->getId());
        } elseif ($mode == 'secondary') {
            $query->where('members_roles.role_id', $this->getId());
        }
        if (in_array($mode, ['all', 'primary'])) {
            $roleGroupsQuery = ee('db')->select('group_id')->from('roles_role_groups')->where('role_id', $this->getId())->get();
            if ($roleGroupsQuery->num_rows() > 0) {
                foreach ($roleGroupsQuery->result_array() as $roleGroup) {
                    $query->or_where('members_role_groups.group_id', $roleGroup['group_id']);
                }
            }
        }

        return $query->get()->row('total_members');
    }

    /**
     * Get total number of members that are assigned to this role (as primary or extra one)
     *
     * @return int
     */
    public function getAllMembersData($field = 'member_id')
    {
        $cache_key = "Roles/{$this->getId()}/AllMembersData/{$field}";
        $data = $this->getFromCache($cache_key);

        if ($data === false) {
            $query = ee('db')
                ->select("members." . $field)
                ->distinct()
                ->from('members AS members')
                ->join('members_roles', 'members_roles.member_id=members.member_id', 'left')
                ->join('members_role_groups', 'members_role_groups.member_id=members.member_id', 'left')
                ->where('members.role_id', $this->getId())
                ->or_where('members_roles.role_id', $this->getId());
            foreach ($this->RoleGroups as $role_group) {
                $query->or_where('members_role_groups.group_id', $role_group->getId());
            }

            $result = $query->get();
            $data = [];
            if ($result->num_rows() > 0) {
                $data = $result->result_array();
                array_walk($data, function (&$row, $key, $field) {
                    $row = $row[$field];
                }, $field);
            }
            $this->saveToCache($cache_key, $data);
        }

        return $data;
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

    public function onBeforeSave()
    {
        $this->setProperty('total_members', $this->getMembersCount('all'));
    }

    public function onAfterSave()
    {
        ee('CP/JumpMenu')->clearAllCaches();
    }
}

// EOF
