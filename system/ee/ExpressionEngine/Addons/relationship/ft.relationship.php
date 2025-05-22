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
 * Relationship Fieldtype
 */
class Relationship_ft extends EE_Fieldtype implements ColumnInterface
{

    public $info = array(
        'name' => 'Relationships',
        'version' => '1.0.0'
    );

    public $has_array_data = false;

    protected $_table = 'relationships';

    protected $errors;

    protected $entityNamePlural = 'entries';

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
     * @param	field data
     * @return	bool valid
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
                return sprintf(lang('rel_ft_min_error'), (int) $this->settings['rel_min'], strtolower(lang($this->entityNamePlural)));
            }
            if (isset($this->settings['rel_max']) && $this->settings['rel_max'] !== '' && (count($set) > (int) $this->settings['rel_max'])) {
                return sprintf(lang('rel_ft_max_error'), (int) $this->settings['rel_max'], strtolower(lang($this->entityNamePlural)));
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
     * @param	field data
     * @return	column data
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
     * @param	the return value of save()
     * @return	void
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
            $all_rows_where['grid_col_id'] = $this->settings['col_id'] ?? 0;
            $all_rows_where['grid_field_id'] = $this->settings['grid_field_id'] ?? 0;
            $all_rows_where['grid_row_id'] = $this->settings['grid_row_id'] ?? 0;
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

        // -------------------------------------------
        // 'relationships_post_save' hook.
        //  - Allow developers to modify or add to the relationships array before saving
        //
        $hook = $this->_table . '_post_save';
        if (ee()->extensions->active_hook($hook) === true) {
            $ships = ee()->extensions->call($hook, $ships, $entry_id, $field_id);
        }
        //
        // -------------------------------------------

        // If child_id is empty, they are deleting a single relationship
        if (count($ships)) {
            ee()->db->insert_batch($this->_table, $ships);
        }
    }

    /**
     * Called when entries are deleted
     *
     * @access	public
     * @param	array of entry ids to delete
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
     * @access	public
     * @param	array of entry ids to delete
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
     * @param	stored data
     * @return	interface string
     */
    public function display_field($data)
    {
        ee()->lang->loadfile('fieldtypes');
        $field_name = $this->field_name;

        $entry_id = ($this->content_id) ?: ee()->input->get('entry_id');

        $order = array();
        $entries = array();
        $selected = array();

        if (is_array($data) && isset($data['data']) && ! empty($data['data'])) { // autosave
            foreach ($data['data'] as $k => $id) {
                $selected[$id] = $id;
                $order[$id] = $k + 1;
            }
        } elseif (is_int($data)) {
            $selected[$data] = $data;
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
            if (isset($this->settings['grid_field_id'])
                && ! isset($this->settings['grid_row_id'])) {
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

            // -------------------------------------------
            // 'relationships_display_field' hook.
            // - Allow developers to perform their own queries to modify which entries are retrieved
            //
            // 	There are 3 ways to use this hook:
            // 	 	1) Add to the existing Active Record call, e.g. ee()->db->where('foo', 'bar');
            // 	 	2) Call ee()->db->_reset_select(); to terminate this AR call and start a new one
            // 	 	3) Call ee()->db->_reset_select(); and modify the currently compiled SQL string
            //
            //   All 3 require a returned query result array.
            //
            $hook = $this->_table . '_display_field';
            if (ee()->extensions->active_hook($hook) === true) {
                $related = ee()->extensions->call(
                    $hook,
                    $entry_id,
                    $this->field_id,
                    ee()->db->_compile_select(false, false)
                );
            } else {
                $related = ee()->db->get()->result_array();
            }
            //
            // -------------------------------------------

            foreach ($related as $row) {
                $selected[$row['child_id']] = $row['child_id'];
                $order[$row['child_id']] = $row['order'];
            }
        }

        $settings = array(
            'channels' => $this->settings['channels'],
            'categories' => $this->settings['categories'],
            'statuses' => $this->settings['statuses'],
            'authors' => $this->settings['authors'],
            'limit' => $this->settings['limit'],
            'expired' => $this->settings['expired'],
            'future' => $this->settings['future'],
            'order_field' => $this->settings['order_field'],
            'order_dir' => $this->settings['order_dir'],
            'entry_id' => $entry_id,
            'field_id' => $this->field_id
        );

        ee()->load->library('EntryList');
        $entries = ee()->entrylist->query($settings, $selected);

        // These settings will be sent to the AJAX endpoint for filtering the
        // field, encrypt them to prevent monkey business
        $settings = json_encode($settings);
        $settings = ee('Encrypt')->encode(
            $settings,
            ee()->config->item('session_crypt_key')
        );

        // Create a cache of channel names
        if (! $channels = ee()->session->cache(__CLASS__, 'channels')) {
            $channels = ee('Model')->get('Channel')
                ->fields('channel_title', 'max_entries', 'total_records')
                ->all();

            ee()->session->set_cache(__CLASS__, 'channels', $channels);
        }

        $limit_channels = $this->settings['channels'];
        if (count($limit_channels)) {
            $channels = $channels->filter(function ($channel) use ($limit_channels) {
                return in_array($channel->getId(), $limit_channels);
            });
        }

        if (REQ != 'CP' && REQ != 'ACTION') {
            $options[''] = '--';

            foreach ($entries as $entry) {
                $options[$entry->entry_id] = $entry->title;
            }

            if ($this->settings['allow_multiple'] == 0) {
                return form_dropdown($field_name . '[data][]', $options, current($selected));
            } else {
                return form_multiselect($field_name . '[data][]', $options, $selected);
            }
        }

        ee()->javascript->set_global([
            'relationship.publishCreateUrl' => ee('CP/URL')->make('publish/create/###')->compile(),
            'relationship.publishEditUrl' => ee('CP/URL')->make('publish/edit/entry/###')->compile(),
            'relationship.lang.creatingNew' => lang('creating_new_in_rel'),
            'relationship.lang.relateEntry' => lang('relate_entry'),
            'relationship.lang.search' => lang('search'),
            'relationship.lang.channel' => lang('channel'),
            'relationship.lang.remove' => lang('remove'),
            'relationship.lang.edit' => lang('edit_entry'),
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

        $children_cache = ee()->session->cache(__CLASS__, 'children');

        if ($entry_id) {
            if (! is_array($children_cache)) {
                $children_cache = [];
            }

            if (! isset($children_cache[$entry_id])) {
                // Cache children for this entry
                $children_cache[$entry_id] = ee('Model')->get('ChannelEntry', $entry_id)
                    ->with('Children', 'Channel')
                    ->fields('Channel.channel_title', 'Children.entry_id', 'Children.title', 'Children.channel_id', 'Children.status')
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
        }

        asort($order);

        $related = array();

        $new_children_ids = array_diff(array_keys($order), $children_ids);
        $new_children = array();

        if (! empty($new_children_ids)) {
            $new_children = ee('Model')->get('ChannelEntry', $new_children_ids)
                ->with('Channel')
                ->fields('Channel.*', 'entry_id', 'title', 'channel_id', 'author_id', 'status')
                ->all()
                ->indexBy('entry_id');
        }

        foreach ($order as $key => $index) {
            if (in_array($key, $children_ids)) {
                $related[] = $children[$key];
            } elseif (isset($new_children[$key])) {
                $related[] = $new_children[$key];
            }
        }

        $multiple = (bool) $this->settings['allow_multiple'];

        $statuses = ee('Model')->get('Status')->all('true')->getDictionary('status', 'highlight');
        ee()->javascript->set_global([
            'statuses' => $statuses
        ]);

        $choices = [];
        foreach ($entries as $entry) {
            $choices[] = $this->_buildOption($entry);
        }
        $selected = [];
        foreach ($related as $child) {
            $selected[] = $this->_buildOption($child);
        }

        $field_name = $field_name . '[data]';

        // Single relationships also expects an array
        if (! $multiple) {
            $field_name .= '[]';
        }

        $select_filters = [];
        if ($channels->count() > 1) {
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

        $channel_choices = [];
        foreach ($channels as $channel) {
            $channel_choices[] = [
                'title' => $channel->channel_title,
                'id' => $channel->getId()
            ];
        }

        $channelsForNewEntriesChoices = [];
        $channelsForNewEntries = $channels->filter(function ($channel) {
            return ! $channel->maxEntriesLimitReached()
                && (ee('Permission')->isSuperAdmin() || in_array($channel->getId(), array_keys(ee()->session->userdata('assigned_channels'))));
        });
        foreach ($channelsForNewEntries as $channel) {
            $channelsForNewEntriesChoices[] = [
                'title' => $channel->channel_title,
                'id' => $channel->getId()
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
            'channels' => $channel_choices,
            'channelsForNewEntries' => $channelsForNewEntriesChoices,
            'in_modal' => ($this->get_setting('in_modal_context') || ee('Request')->get('modal_form') == 'y'),
            'display_entry_id' => isset($this->settings['display_entry_id']) ? (bool) $this->settings['display_entry_id'] : false,
            'display_status' => isset($this->settings['display_status']) ? (bool) $this->settings['display_status'] : false,
            'statuses' => $statuses,
            'rel_min' => isset($this->settings['rel_min']) ? (int) $this->settings['rel_min'] : 0,
            'rel_max' => isset($this->settings['rel_max']) ? (int) $this->settings['rel_max'] : '',
            'canCreateNew' => ee('Permission')->has('can_create_entries') && (
                empty($this->settings['channels']) ||
                !empty($channelsForNewEntriesChoices)
            )
        ]);
    }

    private function _buildOption($entry) {
        return [
            'value' => $entry->getId(),
            'label' => $entry->title,
            'instructions' => $entry->Channel->channel_title,
            'channel_id' => $entry->Channel->getId(),
            'can_edit' => ($entry->author_id == ee()->session->userdata('member_id')) ? ee('Permission')->has('can_edit_self_entries_channel_id_' . $entry->channel_id) : ee('Permission')->has('can_edit_other_entries_channel_id_' . $entry->channel_id),
            'editable' => (ee('Permission')->isSuperAdmin() || array_key_exists($entry->Channel->getId(), ee()->session->userdata('assigned_channels'))),
            'status' => $entry->status
        ];
    }

    /**
     * Show the tag on the frontend
     *
     * @param	column data
     * @param	tag parameters
     * @param	tag pair contents
     * @return	parsed output
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
     * @param	array of previously saved settings
     * @return	string
     */
    public function display_settings($data)
    {
        ee()->lang->loadfile('fieldtypes');
        ee()->load->library('Relationships_ft_cp');
        $util = ee()->relationships_ft_cp;

        ee()->cp->add_js_script(array(
            'file' => 'fields/relationship/settings',
        ));

        $form = $this->_form();
        $form->populate($data);
        $values = $form->values();

        $settings = array(
            array(
                'title' => 'rel_ft_channels',
                'desc' => 'rel_ft_channels_desc',
                'fields' => array(
                    'relationship_channels' => array(
                        'type' => 'checkbox',
                        'nested' => true,
                        'attrs' => 'data-any="y"',
                        'choices' => $util->all_channels(),
                        'value' => ($values['channels']) ?: '--',
                        'toggle_all' => false,
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('channels'))
                        ]
                    )
                )
            ),
            array(
                'title' => 'rel_ft_include',
                'desc' => 'rel_ft_include_desc',
                'fields' => array(
                    'relationship_expired' => array(
                        'type' => 'checkbox',
                        'scalar' => true,
                        'choices' => array(
                            '1' => lang('rel_ft_include_expired')
                        ),
                        'value' => $values['expired']
                    ),
                    'relationship_future' => array(
                        'type' => 'checkbox',
                        'scalar' => true,
                        'choices' => array(
                            '1' => lang('rel_ft_include_future')
                        ),
                        'value' => $values['future']
                    )
                )
            ),
            array(
                'title' => 'rel_ft_categories',
                'desc' => 'rel_ft_categories_desc',
                'fields' => array(
                    'relationship_categories' => array(
                        'type' => 'checkbox',
                        'nested' => true,
                        'attrs' => 'data-any="y"',
                        'choices' => $util->all_categories(),
                        'value' => ($values['categories']) ?: '--',
                        'toggle_all' => false,
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('categories'))
                        ]
                    )
                )
            ),
            array(
                'title' => 'rel_ft_authors',
                'desc' => 'rel_ft_authors_desc',
                'fields' => array(
                    'relationship_authors' => array(
                        'type' => 'checkbox',
                        'nested' => true,
                        'attrs' => 'data-any="y"',
                        'choices' => $util->all_authors(),
                        'value' => ($values['authors']) ?: '--',
                        'filter_url' => ee('CP/URL')->make('fields/relationship-member-filter')->compile(),
                        'toggle_all' => false,
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('authors'))
                        ]
                    )
                )
            ),
            array(
                'title' => 'rel_ft_statuses',
                'desc' => 'rel_ft_statuses_desc',
                'fields' => array(
                    'relationship_statuses' => array(
                        'type' => 'checkbox',
                        'nested' => true,
                        'attrs' => 'data-any="y"',
                        'choices' => $util->all_statuses(),
                        'value' => ($values['statuses']) ?: '--',
                        'toggle_all' => false,
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('statuses'))
                        ]
                    )
                )
            ),
            array(
                'title' => sprintf(lang('rel_ft_limit'), strtolower(lang('entries'))),
                'desc' => sprintf(lang('rel_ft_limit'), strtolower(lang('entries')), strtolower(lang('entries'))),
                'fields' => array(
                    'limit' => array(
                        'type' => 'text',
                        'value' => $values['limit']
                    )
                )
            ),
            array(
                'title' => 'rel_ft_order',
                'desc' => sprintf(lang('rel_ft_order_desc'), strtolower(lang('entries'))),
                'fields' => array(
                    'relationship_order_field' => array(
                        'type' => 'radio',
                        'choices' => array(
                            'title' => lang('rel_ft_order_title'),
                            'entry_date' => lang('rel_ft_order_date')
                        ),
                        'value' => $values['order_field']
                    ),
                    'relationship_order_dir' => array(
                        'type' => 'radio',
                        'choices' => array(
                            'asc' => lang('rel_ft_order_ascending'),
                            'desc' => lang('rel_ft_order_descending'),
                        ),
                        'value' => $values['order_dir']
                    )
                )
            ),
            array(
                'title' => 'rel_ft_allow_multi',
                'desc' => 'rel_ft_allow_multi_desc',
                'fields' => array(
                    'relationship_allow_multiple' => array(
                        'type' => 'yes_no',
                        'group_toggle' => array(
                            'y' => 'rel_min_max',
                        ),
                        'value' => ($values['allow_multiple']) ? 'y' : 'n'
                    )
                )
            ),
            array(
                'title' => sprintf(lang('rel_ft_min'), strtolower(lang('entries'))),
                'desc' => sprintf(lang('rel_ft_min_desc'), strtolower(lang('entries'))),
                'group' => 'rel_min_max',
                'fields' => array(
                    'rel_min' => array(
                        'type' => 'text',
                        'value' => isset($values['rel_min']) ? $values['rel_min'] : 0
                    )
                )
            ),
            array(
                'title' => sprintf(lang('rel_ft_max'), strtolower(lang('entries'))),
                'desc' => sprintf(lang('rel_ft_max_desc'), strtolower(lang('entries'))),
                'group' => 'rel_min_max',
                'fields' => array(
                    'rel_max' => array(
                        'type' => 'text',
                        'value' => isset($values['rel_max']) ? $values['rel_max'] : ''
                    )
                )
            ),
            array(
                'title' => 'rel_ft_display_entry_id',
                'desc' => 'rel_ft_display_entry_id_desc',
                'fields' => array(
                    'relationship_display_entry_id' => array(
                        'type' => 'yes_no',
                        'value' => ($values['display_entry_id']) ? 'y' : 'n'
                    )
                )
            ),
            array(
                'title' => 'rel_ft_display_status',
                'desc' => 'rel_ft_display_status_desc',
                'fields' => array(
                    'relationship_display_status' => array(
                        'type' => 'yes_no',
                        'value' => ($values['display_status']) ? 'y' : 'n'
                    )
                )
            ),
            array(
                'title' => 'rel_ft_deferred',
                'desc' => 'rel_ft_deferred_desc',
                'fields' => array(
                    'relationship_deferred_loading' => array(
                        'type' => 'yes_no',
                        'value' => ($values['deferred_loading']) ? 'y' : 'n'
                    )
                )
            )
        );

        if ($this->content_type() == 'grid') {
            return array('field_options' => $settings);
        }

        return array('field_options_relationship' => array(
            'label' => 'field_options',
            'group' => 'relationship',
            'settings' => $settings
        ));
    }

    /**
     * Save Settings
     *
     * Save the settings page. Populates the defaults, adds the user
     * settings and sends that off to be serialized.
     *
     * @return	array	settings
     */
    public function save_settings($data)
    {
        $form = $this->_form();
        $form->populate($data);

        $save = $form->values();

        // Boolstring conversion
        $save['allow_multiple'] = get_bool_from_string($save['allow_multiple']);
        $save['display_entry_id'] = get_bool_from_string($save['display_entry_id']);
        $save['display_status'] = get_bool_from_string($save['display_status']);
        $save['deferred_loading'] = get_bool_from_string($save['deferred_loading']);

        foreach ($save as $field => $value) {
            if (is_array($value) && count($value)) {
                if (in_array('--', $value)) {
                    $save[$field] = array();
                }
            }
        }

        return $save;
    }

    /**
     * Setup the form helper
     *
     * Assigns blank data, default data, and all the form options.
     *
     * @param	form prefix
     * @return	Object<Relationship_settings_form>
     */
    protected function _form($prefix = 'relationship')
    {
        ee()->load->library('Relationships_ft_cp');
        $util = ee()->relationships_ft_cp;

        $field_empty_values = array(
            'channels' => '--',
            'expired' => 0,
            'future' => 0,
            'categories' => '--',
            'authors' => '--',
            'statuses' => '--',
            'limit' => 100,
            'order_field' => 'title',
            'order_dir' => 'asc',
            'display_entry_id' => false,
            'display_status' => false,
            'deferred_loading' => false,
            'allow_multiple' => 'y',
            'rel_min' => 0,
            'rel_max' => ''
        );

        $field_options = array(
            'channels' => $util->all_channels(),
            'categories' => $util->all_categories(),
            'authors' => $util->all_authors(),
            'statuses' => $util->all_statuses(),
            'order_field' => $util->all_order_options(),
            'order_dir' => $util->all_order_directions()
        );

        // any default values that are not the empty ones
        $default_values = array(
            'display_entry_id' => false,
            'display_status' => false,
            'allow_multiple' => true,
            'deferred_loading' => false,
        );

        $form = $util->form($field_empty_values, $prefix);
        $form->options($field_options);
        $form->populate($default_values);

        return $form;
    }

    /**
     * Create our table on install
     *
     * @return	void
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
     * @return	void
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
     * @param	array	$data	An array of data with the structure:
     *								field_id - The id of the field to modify.
     * 								ee_action - delete or add (action we're
     *									taking)
     *
     * @return	array	The SQL definition of the modified field.
     */
    public function grid_settings_modify_column($data)
    {
        return $this->_settings_modify_column($data, true);
    }

    /**
     * Settings Modify Column
     *
     * @param	array
     *		field_id
     *		ee_action - delete OR add
     * @return	array
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
     * @param	array	$data	An array of data with the structure:
     *								field_id - The id of the field to modify.
     * 								ee_action - delete or add (action we're
     *									taking)
     * @param	boolean	$grid	Are we working with a grid field? If TRUE, we
     * 							are otherwise, it's a normal Relationship field.
     *
     * @return	array	The SQL definition of the modified field.
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
     * @param	int	$field_id	The id of the deleted field.
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
