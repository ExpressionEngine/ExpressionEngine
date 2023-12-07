<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Library\CP\EntryManager\ColumnInterface;
use ExpressionEngine\Library\CP\Table;

/**
 * Members Fieldtype
 */
class Member_ft extends EE_Fieldtype implements ColumnInterface
{

    public $info = array(
        'name' => 'Members',
        'version' => '1.0.0'
    );

    public $has_array_data = true;

    public $complex_data_structure = true;

    private $_table = 'member_relationships';

    public $default_settings = [
        'roles' => '--',
        'limit' => '',
        'order_field' => 'screen_name',
        'order_dir' => 'asc',
        'allow_multiple' => 'y',
        'rel_min' => 0,
        'rel_max' => '',
        'display_member_id' => 'n',
        'deferred_loading' => 'n',
    ];

    private $errors;

    /**
     * A list of operators that this fieldtype supports
     *
     * @var mixed
     */
    public $supportedEvaluationRules = null;

    /**
     * Validate Field
     *
     * @todo TODO check if ids are valid according to the settings.
     *
     * @param   field data
     * @return  bool valid
     */
    public function validate($data)
    {
        $data = isset($data['data']) ? $data['data'] : array();

        $set = array();

        foreach ($data as $i => $child_id) {
            if (! $child_id) {
                continue;
            }

            $set[] = $child_id;
        }

        if ($this->settings['field_required'] == 'y') {
            if (! count($set)) {
                return lang('required');
            }
        }

        if ((bool) $this->settings['allow_multiple']) {
            ee()->lang->load('fieldtypes');
            if (isset($this->settings['rel_min']) && (count($set) < (int) $this->settings['rel_min'])) {
                return sprintf(lang('rel_ft_min_error'), (int) $this->settings['rel_min'], strtolower(lang('members')));
            }
            if (isset($this->settings['rel_max']) && $this->settings['rel_max'] !== '' && (count($set) > (int) $this->settings['rel_max'])) {
                return sprintf(lang('rel_ft_max_error'), (int) $this->settings['rel_max'], strtolower(lang('members')));
            }
        }

        return true;
    }

    /**
     * Called by FieldModel to validate the fieldtype's settings
     */
    public function validate_settings($data)
    {
        $rules = [
            'rel_min' => 'isNatural',
            'rel_max' => 'isNaturalNoZero'
        ];

        $validator = ee('Validation')->make($rules);

        $this->errors = $validator->validate($data);

        return $this->errors;
    }

    /**
     * Save Field
     *
     * In our case the actual field entry will be blank, so we'll simply
     * cache some data for the post_save method.
     *
     * @param   field data
     * @return  column data
     */
    public function save($data, $model = null)
    {
        $data = isset($data['data']) ? array_filter($data['data'], 'is_numeric') : array();

        $cache_name = $this->field_name;

        if (isset($this->settings['grid_row_name'])) {
            $cache_name .= $this->settings['grid_row_name'];
        }

        if (isset($model) && is_object($model)) {
            $name = $this->field_name;
            $model->$name = null;
        }

        ee()->session->set_cache(__CLASS__, $cache_name, array(
            'data' => $data
        ));

        return null;
    }

    /**
     * Post field save is where we do the actual works since we store
     * data in our own table based on the entry_id, which does not exist
     * before saving.
     *
     * @param   the return value of save()
     * @return  void
     */
    public function post_save($data)
    {
        $field_id = $this->field_id;
        $entry_id = $this->content_id();

        $cache_name = $this->field_name;

        if (isset($this->settings['grid_row_name'])) {
            $cache_name .= $this->settings['grid_row_name'];
        }

        $post = ee()->session->cache(__CLASS__, $cache_name);

        if ($post === false) {
            // this is a channel:form edit - save() was not called. Don't do anything.
            return;
        }

        $data = $post['data'];

        $all_rows_where = array(
            'parent_id' => $entry_id,
            'field_id' => $field_id,
            'grid_col_id' => 0,
            'grid_field_id' => 0,
            'grid_row_id' => 0,
            'fluid_field_data_id' => (isset($this->settings['fluid_field_data_id'])) ? $this->settings['fluid_field_data_id'] : 0
        );

        if (isset($this->settings['grid_field_id'])) {
            // grid takes the parent grid's field id and sticks it into "grid_field_id"
            $all_rows_where['grid_col_id'] = $this->settings['col_id'];
            $all_rows_where['grid_field_id'] = $this->settings['grid_field_id'];
            $all_rows_where['grid_row_id'] = $this->settings['grid_row_id'];
        }

        // clear old stuff
        ee()->db
            ->where($all_rows_where)
            ->delete($this->_table);

        // insert new stuff
        $ships = array();

        foreach ($data as $i => $child_id) {
            if (! $child_id) {
                continue;
            }

            // the old data array
            $new_row = $all_rows_where;
            $new_row['child_id'] = $child_id;
            $new_row['order'] = $i + 1;

            $ships[] = $new_row;
        }

        // If child_id is empty, they are deleting a single relationship
        if (count($ships)) {
            ee()->db->insert_batch($this->_table, $ships);
        }
    }

    /**
     * Called when entries are deleted
     *
     * @access  public
     * @param   array of entry ids to delete
     */
    public function delete($ids)
    {
        ee()->db
            ->where_in('parent_id', $ids)
            ->or_where_in('child_id', $ids)
            ->delete($this->_table);
    }

    /**
     * Called when grid entries are deleted
     *
     * @access  public
     * @param   array of entry ids to delete
     */
    public function grid_delete($ids)
    {
        ee()->db
            ->where('field_id', $this->field_id)
            ->where_in('grid_row_id', $ids)
            ->delete($this->_table);
    }

    /**
     * Display the field on the publish page
     *
     * Show the field to the user. In this case that means either
     * showing a dropdown for single selects or our custom multiselect
     * interface.
     *
     * @param   array $data stored data
     * @return  string compiled UI element
     */
    public function display_field($data)
    {
        ee()->lang->loadfile('members');
        ee()->lang->loadfile('fieldtypes');
        $field_name = $this->field_name;

        $entry_id = ($this->content_id) ?: ee()->input->get('entry_id');

        $order = array();

        if (is_array($data) && isset($data['data']) && ! empty($data['data'])) { // autosave
            foreach ($data['data'] as $k => $id) {
                $order[$id] = $k + 1;
            }
        } elseif (is_int($data)) {
            $order[$data] = $data;
        }

        // Fetch existing related entries?
        $get_related = false;
        if ($entry_id) {
            // If we have an entry_id then we are editing and likely need to
            // get related entries
            $get_related = true;

            // If this relationship belongs to a grid and doesn't have a
            // row id, then we are not editing and should not look for
            // related entries.
            if (
                isset($this->settings['grid_field_id'])
                && ! isset($this->settings['grid_row_id'])
            ) {
                $get_related = false;
            }
        }

        if ($get_related) {
            $wheres = array(
                'parent_id' => $entry_id,
                'field_id' => $this->field_id,
                'grid_col_id' => 0,
                'grid_field_id' => 0,
                'grid_row_id' => 0,
                'fluid_field_data_id' => (isset($this->settings['fluid_field_data_id'])) ? $this->settings['fluid_field_data_id'] : 0
            );

            if (isset($this->settings['grid_row_id'])) {
                $wheres['grid_col_id'] = $this->settings['col_id'];
                $wheres['grid_field_id'] = $this->settings['grid_field_id'];
                $wheres['grid_row_id'] = $this->settings['grid_row_id'];
            }

            ee()->db
                ->select('child_id, order')
                ->from($this->_table)
                ->where($wheres);

            $related = ee()->db->get()->result_array();

            foreach ($related as $row) {
                $order[$row['child_id']] = $row['order'];
            }
        }

        $selectedIds = array_keys($order);

        $settings = array(
            'roles' => $this->settings['roles'],
            'limit' => $this->settings['limit'],
            'order_field' => $this->settings['order_field'],
            'order_dir' => $this->settings['order_dir'],
            'entry_id' => $entry_id,
            'field_id' => $this->field_id,
            'selected' => $selectedIds
        );

        $members = ee('Model')->get('Member')->with('PrimaryRole');
        if (!empty($this->settings['roles'])) {
            $members->filter('PrimaryRole.role_id', 'IN', $this->settings['roles']);
        }
        if (!empty($this->settings['limit'])) {
            $limit = (int) $this->settings['limit'];
            if (!empty($selectedIds)) {
                // slightly greater limit to ensure everything is included
                $limit += count($selectedIds);
            }
            $members->limit($limit);
        }
        if (!empty($selectedIds)) {
            $members->order('FIELD( Member_members.member_id, ' . implode(', ', array_reverse($selectedIds)) . ' )', 'DESC', false);
        }
        if (!empty($this->settings['order_field'])) {
            $members->order($this->settings['order_field'], $this->settings['order_dir'] == 'asc' ? 'asc' : 'desc');
        }
        $members = $members->all();

        // These settings will be sent to the AJAX endpoint for filtering the
        // field, encrypt them to prevent monkey business
        $settings = json_encode($settings);
        $settings = ee('Encrypt')->encode(
            $settings,
            ee()->config->item('session_crypt_key')
        );

        if (REQ != 'CP' && REQ != 'ACTION') {
            $options[''] = '--';

            foreach ($members as $member) {
                $options[$member->member_id] = $member->screen_name;
            }

            if ($this->settings['allow_multiple'] == 0) {
                return form_dropdown($field_name . '[data][]', $options, array_key_first($order));
            } else {
                return form_multiselect($field_name . '[data][]', $options, array_keys($order));
            }
        }

        $roles = ee('Model')->get('Role')
            ->fields('name')
            ->all(true);

        $limit_roles = $this->settings['roles'];
        if (count($this->settings['roles'])) {
            $roles = $roles->filter(function ($role) use ($limit_roles) {
                return in_array($role->getId(), $limit_roles);
            });
        }

        ee()->cp->add_js_script([
            'plugin' => ['ui.touch.punch', 'ee_interact.event'],
            'file' => [
                'vendor/react/react.min',
                'vendor/react/react-dom.min',
                'components/relationship',
                'components/dropdown_button',
                'components/select_list'
            ],
            'ui' => 'sortable'
        ]);

        asort($order);

        $multiple = (bool) $this->settings['allow_multiple'];

        $choices = [];
        $selected = [];
        $can_edit = ee('Permission')->hasAll('can_access_members', 'can_edit_members');
        foreach ($members as $member) {
            $option = array_merge($this->_buildOption($member), ['can_edit' => $can_edit]);
            $choices[] = $option;
            if (in_array($member->getId(), $selectedIds)) {
                $selected[] = $option;
            }
        }

        $field_name = $field_name . '[data]';

        // Single relationships also expects an array
        if (! $multiple) {
            $field_name .= '[]';
        }

        $select_filters = [];

        $role_choices = [];
        foreach ($roles as $role) {
            $role_choices[] = [
                'title' => $role->name,
                'id' => $role->getId()
            ];
        }

        return ee('View')->make('relationship:publish')->render([
            'deferred' => isset($this->settings['deferred_loading']) ? $this->settings['deferred_loading'] : false,
            'field_name' => $field_name,
            'choices' => $choices,
            'selected' => $selected,
            'multi' => $multiple,
            'filter_url' => ee('CP/URL')->make('publish/member-relationship-filter', [
                'settings' => $settings
            ])->compile(),
            'limit' => $this->settings['limit'] ?: 100,
            'no_results' => ['text' => lang('no_members_found')],
            'no_related' => ['text' => lang('no_entries_related')],
            'select_filters' => $select_filters,
            'channels' => $role_choices,
            'showCreateDropdown' => false,
            'in_modal' => ($this->get_setting('in_modal_context') || ee('Request')->get('modal_form') == 'y'),
            'display_entry_id' => isset($this->settings['display_member_id']) ? (bool) $this->settings['display_member_id'] : false,
            'rel_min' =>  isset($this->settings['rel_min']) ? (int) $this->settings['rel_min'] : 0,
            'rel_max' =>  isset($this->settings['rel_max']) ? (int) $this->settings['rel_max'] : '',
            'publishCreateUrl' => ee('CP/URL')->make('members/create')->compile(),
            'publishEditUrl' => ee('CP/URL')->make('members/profile/settings&id=###')->compile(),
            'lang' => [
                'creatingNew' => lang('creating_member_in_rel'),
                'relateEntry' => lang('relate_member'),
                'search' => lang('search'),
                'channel' => lang('role'),
                'remove' => lang('remove'),
                'edit' => lang('edit_member'),
                'new_entry' => lang('new_member')
            ],
            'canCreateNew' => ee('Permission')->can('create_members'),
        ]);
    }

    private function _buildOption($member)
    {
        return [
            'value' => $member->getId(),
            'label' => !empty($member->screen_name) ? $member->screen_name : $member->username,
            'instructions' => $member->PrimaryRole->name,
            'channel_id' => $member->role_id,
            'can_edit' => ee('Permission')->can('edit_members'),
            'editable' => (ee('Permission')->isSuperAdmin() || ! $member->isSuperAdmin())
        ];
    }

    /**
     * Pre-process the data before displaying.
     * @param array $data
     * @return array $data
     */
    public function pre_process($data)
    {
        if (! ee('LivePreview')->hasEntryData()) {
            $data = [];
            $wheres = array(
                'parent_id' => $this->row['entry_id'],
                'field_id' => $this->field_id,
                'grid_col_id' => 0,
                'grid_field_id' => 0,
                'grid_row_id' => 0,
                'fluid_field_data_id' => (isset($this->settings['fluid_field_data_id'])) ? $this->settings['fluid_field_data_id'] : 0
            );

            if (isset($this->settings['grid_row_id'])) {
                $wheres['grid_col_id'] = $this->settings['col_id'];
                $wheres['grid_field_id'] = $this->settings['grid_field_id'];
                $wheres['grid_row_id'] = $this->settings['grid_row_id'];
            }

            ee()->db
                ->select('child_id, order')
                ->from($this->_table)
                ->where($wheres)
                ->order_by('order', 'asc');

            $related = ee()->db->get()->result_array();

            foreach ($related as $row) {
                $data[$row['child_id']] = $row['order'];
            }
        }
        return $data;
    }

    /**
     * Replace template tags
     */
    public function replace_tag($data, $params = '', $tagdata = '')
    {
        $vars = [
            'entries' => []
        ];
        foreach ($data as $member_id => $order) {
            $memberQuery = ee('Model')->get('Member', $member_id)->with('PrimaryRole')->first();
            if (!empty($memberQuery)) {
                $memberData = array_merge(
                    $memberQuery->toArray(),
                    [
                        'primary_role_id' => $memberQuery->PrimaryRole->getId(),
                        'primary_role_name' => $memberQuery->PrimaryRole->name,
                        'primary_role_description' => $memberQuery->PrimaryRole->description,
                        'primary_role_short_name' => $memberQuery->PrimaryRole->short_name
                    ],
                    $memberQuery->PrimaryRole->toArray()
                );
                unset($memberData['password']);
                unset($memberData['unique_id']);
                unset($memberData['crypt_key']);
                unset($memberData['authcode']);
                unset($memberData['salt']);
                unset($memberData['backup_mfa_code']);
                unset($memberData['enable_mfa']);
                $vars['entries'][] = array_merge(
                    [
                        'site_id' => $this->row['site_id'],
                        'entry_id' => $this->row['entry_id'],
                        'entry_date' => $this->row['entry_date'],
                        'edit_date' => $this->row['edit_date'],
                        'recent_comment_date' => $this->row['recent_comment_date'],
                        'expiration_date' => $this->row['expiration_date'],
                        'comment_expiration_date' => $this->row['comment_expiration_date'],
                        'allow_comments' => $this->row['allow_comments'],
                        'channel_title' => $this->row['channel_title'],
                        'channel_name' => $this->row['channel_name'],
                        'entry_site_id' => $this->row['entry_site_id'],
                        'channel_url' => $this->row['channel_url'],
                        'comment_url' => $this->row['comment_url']
                    ],
                    $memberData
                );
            }
        }

        if ($this->content_type() == 'grid') {
            ee()->load->library('grid_parser');
            $fluid_field_data_id = (isset($this->settings['fluid_field_data_id'])) ? $this->settings['fluid_field_data_id'] : 0;
            $prefix = ee()->grid_parser->grid_field_names[$this->settings['grid_field_id']][$fluid_field_data_id] . ':' . $this->settings['col_name'] . ':';
        } else {
            $prefix = $this->field_name . ':';
        }

        if (! class_exists('Channel')) {
            require PATH_ADDONS . 'channel/mod.channel.php';
        }
        $channel = new Channel();
        $channel->fetch_custom_member_fields();

        // Load the parser
        ee()->load->library('channel_entries_parser');
        $parser = ee()->channel_entries_parser->create($tagdata, $prefix);

        $tagdata = $parser->parse($channel, $vars);

        if (isset($params['backspace']) && !empty($params['backspace'])) {
            $tagdata = substr($tagdata, 0, - (int) $params['backspace']);
        }

        return $tagdata;
    }

    /**
     * :length modifier
     */
    public function replace_length($data, $params = array(), $tagdata = false)
    {
        return $this->replace_total_rows($data, $params, $tagdata);
    }

    /**
     * :total_rows modifier
     */
    public function replace_total_rows($data, $params = '', $tagdata = '')
    {
        return count($data);
    }

    /**
     * :member_ids modifier
     */
    public function replace_member_ids($data, $params = '', $tagdata = '')
    {
        $delim = isset($params['delimiter']) ? $params['delimiter'] : '|';

        return implode($delim, array_keys($data));
    }

    /**
     * Display the settings page
     *
     * This basically just constructs a table of stuff. Pretty simple.
     *
     * @param   array of previously saved settings
     * @return  string
     */
    public function display_settings($data)
    {
        ee()->lang->loadfile('members');
        ee()->lang->loadfile('fieldtypes');

        ee()->cp->add_js_script(array(
            'file' => 'fields/relationship/settings',
        ));

        $data = array_merge($this->default_settings, $data);

        $settings = array(
            array(
                'title' => 'rel_ft_roles',
                'desc' => 'rel_ft_roles_desc',
                'fields' => array(
                    'roles' => array(
                        'type' => 'checkbox',
                        'nested' => true,
                        'attrs' => 'data-any="y"',
                        'choices' => array(
                            '--' => array(
                                'name' => lang('any_role'),
                                'children' => ee('Model')->get('Role')->all(true)->getDictionary('role_id', 'name')
                            )
                        ),
                        'value' => ($data['roles']) ?: '--',
                        'toggle_all' => false,
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('roles'))
                        ]
                    )
                )
            ),
            array(
                'title' => sprintf(lang('rel_ft_limit'), strtolower(lang('members'))),
                'desc' => sprintf(lang('rel_ft_limit_desc'), strtolower(lang('members')), strtolower(lang('members'))),
                'fields' => array(
                    'limit' => array(
                        'type' => 'text',
                        'value' => $data['limit']
                    )
                )
            ),
            array(
                'title' => 'rel_ft_order',
                'desc' => sprintf(lang('rel_ft_order_desc'), strtolower(lang('members'))),
                'fields' => array(
                    'order_field' => array(
                        'type' => 'radio',
                        'choices' => array(
                            'screen_name' => lang('screen_name'),
                            'join_date' => lang('join_date')
                        ),
                        'value' => $data['order_field']
                    ),
                    'order_dir' => array(
                        'type' => 'radio',
                        'choices' => array(
                            'asc' => lang('rel_ft_order_ascending'),
                            'desc' => lang('rel_ft_order_descending'),
                        ),
                        'value' => $data['order_dir']
                    )
                )
            ),
            array(
                'title' => 'rel_ft_allow_multi',
                'desc' => 'rel_ft_allow_multi_desc',
                'fields' => array(
                    'allow_multiple' => array(
                        'type' => 'yes_no',
                        'group_toggle' => array(
                            'y' => 'member_rel_min_max',
                        ),
                        'value' => $data['allow_multiple']
                    )
                )
            ),
            array(
                'title' => sprintf(lang('rel_ft_min'), strtolower(lang('members'))),
                'desc' => sprintf(lang('rel_ft_min_desc'), strtolower(lang('members'))),
                'group' => 'member_rel_min_max',
                'fields' => array(
                    'rel_min' => array(
                        'type' => 'text',
                        'value' => $data['rel_min']
                    )
                )
            ),
            array(
                'title' => sprintf(lang('rel_ft_max'), strtolower(lang('members'))),
                'desc' => sprintf(lang('rel_ft_max_desc'), strtolower(lang('members'))),
                'group' => 'member_rel_min_max',
                'fields' => array(
                    'rel_max' => array(
                        'type' => 'text',
                        'value' => $data['rel_max']
                    )
                )
            ),
            array(
                'title' => 'rel_ft_display_member_id',
                'desc' => 'rel_ft_display_member_id_desc',
                'fields' => array(
                    'display_member_id' => array(
                        'type' => 'yes_no',
                        'value' => $data['display_member_id']
                    )
                )
            ),
            array(
                'title' => 'rel_ft_deferred',
                'desc' => 'rel_ft_deferred_desc',
                'fields' => array(
                    'deferred_loading' => array(
                        'type' => 'yes_no',
                        'value' => $data['deferred_loading']
                    )
                )
            )
        );

        if ($this->content_type() == 'grid') {
            return array('field_options' => $settings);
        }

        return array('field_options_member' => array(
            'label' => 'field_options',
            'group' => 'member',
            'settings' => $settings
        ));
    }

    /**
     * Save Settings
     *
     * Save the settings page. Populates the defaults, adds the user
     * settings and sends that off to be serialized.
     *
     * @return  array   settings
     */
    public function save_settings($data)
    {
        // Boolstring conversion
        $data['allow_multiple'] = get_bool_from_string($data['allow_multiple']);
        $data['display_member_id'] = get_bool_from_string($data['display_member_id']);
        $data['deferred_loading'] = get_bool_from_string($data['deferred_loading']);

        foreach ($data as $field => $value) {
            if (is_array($value) && count($value)) {
                if (in_array('--', $value)) {
                    $data[$field] = array();
                }
            }
        }

        $all = array_merge($this->default_settings, $data);

        return array_intersect_key($all, $this->default_settings);
    }

    /**
     * Create our table on install
     *
     * @return  void
     */
    public function install()
    {
        return true;
    }

    /**
     * Drop the table
     *
     * @return  void
     */
    public function uninstall()
    {
        ee()->load->dbforge();
        ee()->dbforge->drop_table($this->_table);
    }

    /**
     * Make sure that we only accept data for grid and channels.
     *
     * Long term this should support all content types, but currently that
     * is not the case.
     *
     * @param string  The name of the content type
     * @return bool    Allows content type?
     */
    public function accepts_content_type($name)
    {
        return ($name == 'channel' || $name == 'grid' || $name == 'fluid_field');
    }

    /**
     * Modify column settings for a Relationship field in a grid.
     *
     * @return  array   The SQL definition of the modified field.
     */
    public function grid_settings_modify_column($data)
    {
        return $this->_settings_modify_column($data, true);
    }

    /**
     * Settings Modify Column
     */
    public function settings_modify_column($data)
    {
        return $this->_settings_modify_column($data);
    }

    /**
     * Modify column settings for a Relationship field
     *
     * Handles cases both in and out of Grids, since they're mostly the
     * same except for a minor tweak (col_id_ vs field_id_).  If you need
     * to add something to both add it here.  Make sure it's valid for
     * both and you account for the change in field name.
     *
     * @param   array   $data   An array of data with the structure:
     *                  field_id - The id of the field to modify.
     *                  ee_action - delete or add (action we'retaking)
     * @param   boolean $grid   Are we working with a grid field? If TRUE, we
     *                  are otherwise, it's a normal Relationship field.
     *
     * @return  array   The SQL definition of the modified field.
     */
    protected function _settings_modify_column($data, $grid = false)
    {
        if ($data['ee_action'] == 'delete') {
            $this->_clear_defunct_relationships(
                ($grid) ? $data['col_id'] : $data['field_id'],
                $grid
            );
        }

        // pretty much a dummy field. Here just for consistency's sake
        // and in case we decide to store something in it.
        $field_name = ($grid ? 'col_id_' . $data['col_id'] : 'field_id_' . $data['field_id']);

        $fields[$field_name] = array(
            'type' => 'VARCHAR',
            'constraint' => 8
        );

        return $fields;
    }

    /**
     * Delete the relationship rows belonging to a field that has been deleted.
     * This code is called from multiple places.
     *
     * @param   int $field_id   The id of the deleted field.
     *
     * @return void
     */
    protected function _clear_defunct_relationships($field_id, $grid = false)
    {
        // remove relationships
        if ($grid) {
            ee()->db
                ->where('grid_col_id', $field_id)
                ->delete($this->_table);
        } else {
            ee()->db
                ->where('field_id', $field_id)
                ->delete($this->_table);
        }
    }

    /**
     * Update the fieldtype
     *
     * @param string $version The version being updated to
     * @return boolean TRUE if successful, FALSE otherwise
     */
    public function update($version)
    {
        return true;
    }

    public function getTableColumnConfig()
    {
        return [
            'encode' => false,
            'type' => Table::COL_INFO
        ];
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        $links = [];

        if ($entry && $field_id) {
            $wheres = array(
                'parent_id' => $entry->getId(),
                'field_id' => $field_id,
                'grid_col_id' => 0,
                'grid_field_id' => 0,
                'grid_row_id' => 0,
                'fluid_field_data_id' => 0
            );
            $related = ee()->db
                ->select('member_id, screen_name, username, order')
                ->from($this->_table)
                ->join('members', 'members.member_id=' . $this->_table . '.child_id', 'left')
                ->where($wheres)
                ->order_by('order')
                ->get();

            foreach ($related->result() as $row) {
                $title = !empty($row->screen_name) ? $row->screen_name : $row->username;
                if (ee('Permission')->can('edit_members')) {
                    $edit_link = ee('CP/URL')->make('members/profile/settings&id=' . $row->member_id);
                    $title = '<a href="' . $edit_link . '">' . $title . '</a>';
                }
                $links[] = $title;
            }
        }

        return implode('<br />', $links);
    }
}

// END Relationship_ft class

// EOF
