<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Relationship Fieldtype Settings Helper Class
 */
class Relationships_ft_cp
{
    private $all_authors;
    private $all_channels;
    private $all_categories;
    private $all_statuses;

    /**
     * Create a settings form object
     *
     * @param	array
     *		field_name => empty value
     * @param	form prefix
     * @return	Object<Relationship_settings_form>
     */
    public function form($data, $prefix = '')
    {
        return new Relationship_settings_form($data, $prefix);
    }

    /**
     * Grab all channels, across sites if appropriate.
     *
     * @return	array
     *		channel_id => site_label - channel_title
     */
    public function all_channels()
    {
        if (isset($this->all_channels)) {
            return $this->all_channels;
        }

        $from_all_sites = (ee()->config->item('multiple_sites_enabled') == 'y');

        $channels = ee('Model')->get('Channel')
            ->with('Site')
            ->order('channel_title', 'asc');

        if (! $from_all_sites) {
            $channels->filter('site_id', 1);
        }

        if ($from_all_sites) {
            $channel_choices = $channels->all()->map(function ($channel) {
                return [
                    'value' => $channel->getId(),
                    'label' => $channel->channel_title,
                    'instructions' => $channel->Site->site_label
                ];
            });
        } else {
            $channel_choices = $channels->all()->getDictionary('channel_id', 'channel_title');
        }

        $this->all_channels = array(
            '--' => array(
                'name' => lang('any_channel'),
                'children' => $channel_choices
            )
        );

        return $this->all_channels;
    }

    /**
     * Grab all categories, across sites if appropriate.
     *
     * @return	Object <TreeIterator>
     */
    public function all_categories()
    {
        if (isset($this->all_categories)) {
            return $this->all_categories;
        }

        $from_all_sites = (ee()->config->item('multiple_sites_enabled') == 'y');

        $categories = ee('Model')->get('Category as C0')
            ->with(array('Children as C1' => array('Children as C2' => 'Children as C3')))
            ->fields('C0.cat_id', 'C0.cat_name')
            ->fields('C1.cat_id', 'C1.cat_name')
            ->fields('C2.cat_id', 'C2.cat_name')
            ->fields('C3.cat_id', 'C3.cat_name')
            ->filter('parent_id', 0)
            ->order('group_id', 'asc')
            ->order('parent_id', 'asc')
            ->order('cat_name', 'asc');

        if (! $from_all_sites) {
            $categories->filter('site_id', 1);
        }

        $this->all_categories = array(
            '--' => array(
                'name' => lang('any_category'),
                'children' => $this->buildNestedCategoryArray($categories->all())
            )
        );

        return $this->all_categories;
    }

    private function buildNestedCategoryArray($categories)
    {
        $choices = array();

        foreach ($categories as $category) {
            if (count($category->Children)) {
                $choices[$category->cat_id] = array(
                    'name' => $category->cat_name,
                    'children' => $this->buildNestedCategoryArray($category->Children)
                );
            } else {
                $choices[$category->cat_id] = $category->cat_name;
            }
        }

        return $choices;
    }

    /**
     * Grab all possible authors (individuals and member groups)
     *
     * @param	string $search	Optional string to search members by
     * @return	array
     *		id => name (id can either be g_# or m_# for group or member ids)
     */
    public function all_authors($search = null)
    {
        if (isset($this->all_authors)) {
            return $this->all_authors;
        }

        $from_all_sites = (ee()->config->item('multiple_sites_enabled') == 'y');

        $prefix = ee()->db->dbprefix;

        // First the author groups
        $role_settings = ee('Model')->get('RoleSetting')
            ->with('Role')
            ->filter('include_in_authorlist', 'y')
            ->order('Role.name', 'asc');

        if (! $from_all_sites) {
            $role_settings->filter('site_id', '1');
        }

        $role_settings = $role_settings->all();
        $roles = $role_settings->Role;
        $role_ids = $roles->pluck('role_id');

        $member_ids = [];
        foreach ($roles as $role) {
            $member_ids = array_merge($role->getAllMembersData('member_id'), $member_ids);
        }

        // Then all authors who are in those groups or who have author access
        $members = ee('Model')->get('Member')
            ->with('PrimaryRole', 'Roles', 'RoleGroups')
            ->filter('in_authorlist', 'y')
            ->order('screen_name', 'asc')
            ->order('username', 'asc')
            ->limit(100);

        if (! empty($member_ids)) {
            $members->orFilter('member_id', 'IN', $member_ids);
        }

        if ($search) {
            $members->search(
                ['screen_name', 'username', 'email', 'member_id'],
                $search
            );
        }

        $role_to_member = array_fill_keys($role_ids, array());

        foreach ($members->all() as $m) {
            foreach ($m->getAllRoles(false) as $role) {
                if (isset($role_to_member[$role->role_id])) {
                    $role_to_member[$role->role_id][] = $m;
                }
            }
        }

        $authors = array();

        // Reoder by groups with subitems for authors
        foreach ($roles as $role) {
            $authors['g_' . $role->role_id] = array(
                'name' => $role->name,
                'children' => array()
            );

            foreach ($role_to_member[$role->role_id] as $m) {
                $authors['g_' . $role->role_id]['children']['m_' . $m->member_id] = $m->getMemberName();
            }
        }

        $authors = array_filter($authors, function ($group) {
            return ! empty($group['children']);
        });

        ee()->lang->loadfile('fieldtypes');

        $this->all_authors = array(
            '--' => array(
                'name' => lang('any_author'),
                'children' => $authors
            )
        );

        return $this->all_authors;
    }

    /**
     * Grab all statuses
     *
     * @return	array
     *		id => name
     */
    public function all_statuses()
    {
        if (isset($this->all_statuses)) {
            return $this->all_statuses;
        }

        $statuses = ee('Model')->get('Status')
            ->order('status_id', 'asc');

        $status_options = array();

        foreach ($statuses->all() as $status) {
            $status_name = ($status->status == 'closed' or $status->status == 'open') ? lang($status->status) : $status->status;
            $status_options[$status->status] = $status_name;
        }

        $this->all_statuses = array(
            '--' => array(
                'name' => lang('any_status'),
                'children' => $status_options
            )
        );

        return $this->all_statuses;
    }

    /**
     * Returns our possible ordering columns
     *
     * @return	array [column => human name]
     */
    public function all_order_options()
    {
        return array(
            'title' => lang('rel_ft_order_title'),
            'entry_date' => lang('rel_ft_order_date')
        );
    }

    /**
     * Returns our possible ordering directions
     *
     * @return	array [dir => human name]
     */
    public function all_order_directions()
    {
        return array(
            'asc' => lang('rel_ft_order_asc'),
            'desc' => lang('rel_ft_order_desc'),
        );
    }

    /**
     * Default multiselect data (-- Any --)
     *
     * @return	array
     */
    protected function _form_any()
    {
        return array('--' => '-- Any --');
    }
}

/**
 * Settings Form Class
 *
 * Handles form population, default values, field prefixes etc. Passes
 * everything on to the form helper for actual creation.
 */
class Relationship_settings_form
{
    protected $_prefix = '';
    protected $_fields = array();
    protected $_options = array();
    protected $_selected = array();

    public function __construct(array $defaults, $prefix)
    {
        $this->_fields = $defaults;
        $this->_prefix = $prefix ? $prefix . '_' : '';
    }

    /**
     * Get the current form values for all fields
     *
     * @return	array [form_name => value]
     */
    public function values()
    {
        return $this->_selected;
    }

    /**
     * Populate the form with values
     *
     * @param	array
     *		field_name => value
     * @return	self
     */
    public function populate($data)
    {
        $rename = preg_grep('/^' . $this->_prefix . '.*/i', array_keys($data));

        foreach ($rename as $key) {
            $new_key = substr($key, strlen($this->_prefix));
            $data[$new_key] = $data[$key];
            unset($data[$key]);
        }

        $data = array_intersect_key($data, $this->_fields);
        $this->_selected = array_merge($this->_selected, $this->_fields, $data);

        // Bug 19321: Old relationship fields use "date" instead of "entry_date"
        if (isset($data['order_field']) && $data['order_field'] == 'date') {
            $data['order_field'] = 'entry_date';
        }

        // array_merge_recursive($this->_fields, $data) without
        // php's super weird recursive merging on on arrays

        foreach ($data as $k => $v) {
            if (is_array($this->_fields[$k])) {
                $this->_selected[$k] = array_merge($this->_fields[$k], $v);
            }
        }

        return $this;
    }

    /**
     * Set possible options for dropdowns and multiselects
     *
     * @param	array
     *		field_name => options
     * @return	self
     */
    public function options($data)
    {
        $this->_options = array_intersect_key($data, $this->_fields);

        return $this;
    }

    /**
     * Pass calls to form names on to the form helper for html generation
     *
     * @param	function name
     * @param	[form name, extras]
     * @return	string of form element
     */
    public function __call($fn, $args)
    {
        $args[1] = isset($args[1]) ? $args[1] : '';
        list($name, $extras) = $args;

        $prefix = $this->_prefix;

        // dropdowns
        if (in_array($fn, array('dropdown', 'multiselect'))) {
            $full_name = $prefix . $name;

            if ($fn == 'multiselect') {
                $full_name .= '[]';
            }

            if (! count($this->_selected[$name])) {
                $this->_selected[$name] = array('--');
            }

            $params = array(
                $full_name,
                $this->_options[$name],
                set_value($prefix . $name, $this->_selected[$name]),
                $extras
            );
        } elseif (in_array($fn, array('checkbox', 'radio'))) {
            $params = array(
                $prefix . $name,
                1,
                set_value($prefix . $name, $this->_selected[$name]),
                $extras
            );
        } else {
            $params = array(
                $prefix . $name,
                set_value($prefix . $name, $this->_selected[$name]),
                $extras
            );
        }

        return call_user_func_array('form_' . $fn, $params);
    }
}

// EOF
