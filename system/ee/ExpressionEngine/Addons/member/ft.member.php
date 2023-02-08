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

    public $has_array_data = false;

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
                return sprintf(lang('rel_ft_min_error'), (int) $this->settings['rel_min']);
            }
            if (isset($this->settings['rel_max']) && $this->settings['rel_max'] !== '' && (count($set) > (int) $this->settings['rel_max'])) {
                return sprintf(lang('rel_ft_max_error'), (int) $this->settings['rel_max']);
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
        dd($data);
        $data = isset($data['data']) ? array_filter($data['data'], 'is_numeric') : array();

        $cache_name = $this->field_name;

        if (isset($this->settings['grid_row_name'])) {
            $cache_name .= $this->settings['grid_row_name'];
        }

        if (isset($model) && is_object($model)) {
            $name = $this->field_name;
            $model->$name = '';
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
            'field_id' => $this->field_id
        );

        //$entries = ee()->entrylist->query($settings, $selected);
        $members = ee('Model')->get('Member');
        $members = $members->all();

        // These settings will be sent to the AJAX endpoint for filtering the
        // field, encrypt them to prevent monkey business
        $settings = json_encode($settings);
        $settings = ee('Encrypt')->encode(
            $settings,
            ee()->config->item('session_crypt_key')
        );

        // Create a cache of roles
        if (! $roles = ee()->session->cache(__CLASS__, 'roles')) {
            $roles = ee('Model')->get('Role')
                ->fields('name')
                ->all();

            ee()->session->set_cache(__CLASS__, 'roles', $roles);
        }

        $limit_roles = $this->settings['roles'];
        if (count($this->settings['roles'])) {
            $roles = $roles->filter(function ($role) use ($limit_roles) {
                return in_array($role->getId(), $limit_roles);
            });
        }

        if (REQ != 'CP' && REQ != 'ACTION') {
            $options[''] = '--';

            foreach ($members as $member) {
                $options[$member->member_id] = $member->name;
            }

            if ($this->settings['allow_multiple'] == 0) {
                return form_dropdown($field_name . '[data][]', $options, array_key_first($order));
            } else {
                return form_multiselect($field_name . '[data][]', $options, array_keys($order));
            }
        }

        ee()->javascript->set_global([
            'relationship.publishCreateUrl' => ee('CP/URL')->make('members/create/###')->compile(),
            'relationship.lang.creatingNew' => lang('creating_new_in_rel'),
            'relationship.lang.relateEntry' => lang('relate_member'),
            'relationship.lang.search' => lang('search'),
            'relationship.lang.channel' => lang('role'),
            'relationship.lang.remove' => lang('remove'),
            'relationship.lang.edit' => lang('edit_member'),
        ]);

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

        /*$children_cache = ee()->session->cache(__CLASS__, 'children');

        if ($entry_id) {
            if (! is_array($children_cache)) {
                $children_cache = [];
            }

            if (! isset($children_cache[$entry_id])) {
                // Cache children for this entry
                $children_cache[$entry_id] = ee('Model')->get('ChannelEntry', $entry_id)
                    ->with('Children', 'Channel')
                    ->fields('Channel.channel_title', 'Children.entry_id', 'Children.title', 'Children.channel_id')
                    ->first()
                    ->Children;

                ee()->session->set_cache(__CLASS__, 'children', $children_cache);
            }

            $children = $children_cache[$entry_id]->indexBy('entry_id');
        } else {
            $children = array();
        }

        $entries = $entries->indexBy('entry_id');
        $children_ids = array_keys($children);
        $entry_ids = array_keys($entries);

        foreach ($selected as $chosen) {
            if (! in_array($chosen, $children_ids)
                && in_array($chosen, $entry_ids)) {
                $children[$chosen] = $entries[$chosen];
            }
        }*/

        asort($order);

        /*$related = array();

        $new_children_ids = array_diff(array_keys($order), $children_ids);
        $new_children = array();

        if (! empty($new_children_ids)) {
            $new_children = ee('Model')->get('ChannelEntry', $new_children_ids)
                ->with('Channel')
                ->fields('Channel.*', 'entry_id', 'title', 'channel_id')
                ->all()
                ->indexBy('entry_id');
        }

        foreach ($order as $key => $index) {
            if (in_array($key, $children_ids)) {
                $related[] = $children[$key];
            } elseif (isset($new_children[$key])) {
                $related[] = $new_children[$key];
            }
        }*/

        $multiple = (bool) $this->settings['allow_multiple'];

        $choices = [];
        $selected = [];
        foreach ($members as $member) {
            $choices[] = $this->_buildOption($member);
            if (in_array($member->getId(), $selectedIds)) {
                $selected[] = $this->_buildOption($member);
            }
        }

        $field_name = $field_name . '[data]';

        // Single relationships also expects an array
        if (! $multiple) {
            $field_name .= '[]';
        }

        $select_filters = [];
        /*if ($channels->count() > 1) {
            $select_filters[] = [
                'name' => 'channel_id',
                'title' => lang('channel'),
                'placeholder' => lang('filter_channels'),
                'items' => $channels->getDictionary('channel_id', 'channel_title')
            ];
        }

        if ($multiple) {
            $select_filters[] = [
                'name' => 'related',
                'title' => lang('show'),
                'items' => [
                    'related' => lang('rel_ft_related_only'),
                    'unrelated' => lang('rel_ft_unrelated_only')
                ]
            ];
        }

        $channels = $channels->filter(function ($channel) {
            return ! $channel->maxEntriesLimitReached()
                && (ee('Permission')->isSuperAdmin() || in_array($channel->getId(), array_keys(ee()->session->userdata('assigned_channels'))));
        });*/

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
            'filter_url' => ee('CP/URL')->make('publish/relationship-filter', [
                'settings' => $settings
            ])->compile(),
            'limit' => $this->settings['limit'] ?: 100,
            'no_results' => ['text' => lang('no_entries_found')],
            'no_related' => ['text' => lang('no_entries_related')],
            'select_filters' => $select_filters,
            'channels' => $role_choices,
            'in_modal' => ($this->get_setting('in_modal_context') || ee('Request')->get('modal_form') == 'y'),
            'display_entry_id' => isset($this->settings['display_entry_id']) ? (bool) $this->settings['display_entry_id'] : false,
            'rel_min' =>  isset($this->settings['rel_min']) ? (int) $this->settings['rel_min'] : 0,
            'rel_max' =>  isset($this->settings['rel_max']) ? (int) $this->settings['rel_max'] : '',
        ]);
    }

    private function _buildOption($member) {
        return [
            'value' => $member->getId(),
            'label' => $member->screen_name,
            'instructions' => $member->username,
            'channel_id' => $member->role_id
        ];
    }

    /**
     * Show the tag on the frontend
     *
     * @param   column data
     * @param   tag parameters
     * @param   tag pair contents
     * @return  parsed output
     */
    public function replace_tag($data, $params = '', $tagdata = '')
    {
        if ($tagdata) {
            return $tagdata;
        }

        return $data;
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
                                'children' => ee('Model')->get('Role')->all()->getDictionary('role_id', 'name')
                            )
                        ),
                        'value' => $data['roles'],
                        'toggle_all' => false,
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('roles'))
                        ]
                    )
                )
            ),
            array(
                'title' => 'rel_ft_limit',
                'desc' => 'rel_ft_limit_desc',
                'fields' => array(
                    'limit' => array(
                        'type' => 'text',
                        'value' => $data['limit']
                    )
                )
            ),
            array(
                'title' => 'rel_ft_order',
                'desc' => 'rel_ft_order_desc',
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
                            'y' => 'rel_min_max',
                        ),
                        'value' => $data['allow_multiple']
                    )
                )
            ),
            array(
                'title' => 'rel_ft_min',
                'desc' => 'rel_ft_min_desc',
                'group' => 'rel_min_max',
                'fields' => array(
                    'rel_min' => array(
                        'type' => 'text',
                        'value' => $data['rel_min']
                    )
                )
            ),
            array(
                'title' => 'rel_ft_max',
                'group' => 'rel_min_max',
                'desc' => 'rel_ft_max_desc',
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
        if (ee()->db->table_exists($this->_table)) {
            return;
        }

        ee()->load->dbforge();

        $fields = array(
            'relationship_id' => array(
                'type' => 'int',
                'constraint' => 6,
                'unsigned' => true,
                'auto_increment' => true
            ),
            'parent_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0
            ),
            'child_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0
            ),
            'field_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0
            ),
            'order' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0
            ),
            'grid_field_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
                'null' => false
            ),
            'grid_col_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
                'null' => false
            ),
            'grid_row_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
                'null' => false
            )
        );

        ee()->dbforge->add_field($fields);

        // Worthless primary key
        ee()->dbforge->add_key('relationship_id', true);

        // Keyed table is keyed
        ee()->dbforge->add_key('parent_id');
        ee()->dbforge->add_key('child_id');
        ee()->dbforge->add_key('field_id');
        ee()->dbforge->add_key('grid_row_id');

        ee()->dbforge->create_table($this->_table);
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
                ->select('entry_id, title, channel_id, author_id, order')
                ->from($this->_table)
                ->join('channel_titles', 'channel_titles.entry_id=' . $this->_table . '.child_id', 'left')
                ->where($wheres)
                ->order_by('order')
                ->get();

            foreach ($related->result() as $row) {
                $title = ee('Format')->make('Text', $row->title)->convertToEntities();

                if ((ee('Permission')->can('edit_other_entries_channel_id_' . $row->channel_id)
                    || (ee('Permission')->can('edit_self_entries_channel_id_' . $row->channel_id) &&
                    $row->author_id == ee()->session->userdata('member_id')))) {
                    $edit_link = ee('CP/URL')->make('publish/edit/entry/' . $row->entry_id);
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
