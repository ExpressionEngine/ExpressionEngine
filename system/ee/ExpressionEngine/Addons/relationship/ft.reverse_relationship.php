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
 * Reeverse Relationship Fieldtype
 */
class Reverse_relationship_ft extends EE_Fieldtype implements ColumnInterface
{

    public $info = array(
        'name' => 'Reverse Relationships',
        'version' => '1.0.0'
    );

    public $default_settings = [
        'display_entry_id' => 'y',
        'display_status' => 'y',
        'fields' => '--'
    ];

    /**
     * A list of operators that this fieldtype supports
     *
     * @var mixed
     */
    public $supportedEvaluationRules = null;

    /**
     * Display the field on the publish page
     *
     * Show the field to the user. In this case that means either
     * showing a dropdown for single selects or our custom multiselect
     * interface.
     *
     * @param   array stored data
     * @return  string interface string
     */
    public function display_field($data)
    {
        ee()->lang->loadfile('fieldtypes');
        $field_name = $this->field_name;

        $entry_id = ($this->content_id) ?: ee()->input->get('entry_id');

        $relatedIdsQuery = ee()->db->select('parent_id')
            ->distinct()
            ->from('relationships')
            ->where('child_id', $entry_id)
            ->get();

        $relatedIds = array_map(function ($row) {
            return $row['parent_id'];
        }, $relatedIdsQuery->result_array());

        $selected = ee('Model')->get('ChannelEntry', $relatedIds)
            ->with('Channel')
            ->fields('Channel.*', 'entry_id', 'title', 'channel_id', 'author_id', 'status')
            ->all();

        if (REQ != 'CP' && REQ != 'ACTION') {
            $selected = $selected->getDictionary('entry_id', 'title');
            return form_multiselect($field_name . '[data][]', $selected, $selected, ' readonly="readonly"');
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

        $statuses = ee('Model')->get('Status')->all('true')->getDictionary('status', 'highlight');
        ee()->javascript->set_global([
            'statuses' => $statuses
        ]);

        $options = [];
        foreach ($selected as $entry) {
            $options[] = $this->_buildOption($entry);
        }

        $field_name = $field_name . '[data]';

        $select_filters = [];

        $channel_choices = [];

        return ee('View')->make('relationship:publish')->render([
            'deferred' => true,
            'disableReact' => true,
            'field_name' => $field_name,
            'choices' => $options,
            'selected' => $options,
            'multi' => true,
            'limit' => 999999,
            'no_results' => ['text' => lang('no_entries_found')],
            'no_related' => ['text' => lang('no_entries_related')],
            'filter_url' => '',
            'select_filters' => $select_filters,
            'channels' => $channel_choices,
            'channelsForNewEntries' => [],
            'in_modal' => ($this->get_setting('in_modal_context') || ee('Request')->get('modal_form') == 'y'),
            'display_entry_id' => isset($this->settings['display_entry_id']) ? (bool) $this->settings['display_entry_id'] : false,
            'display_status' => isset($this->settings['display_status']) ? (bool) $this->settings['display_status'] : false,
            'statuses' => $statuses,
            'rel_min' => isset($this->settings['rel_min']) ? (int) $this->settings['rel_min'] : 0,
            'rel_max' => isset($this->settings['rel_max']) ? (int) $this->settings['rel_max'] : '',
            'canCreateNew' => false
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
                'title' => 'rel_ft_fields',
                'desc' => 'rel_ft_fields_desc',
                'fields' => array(
                    'fields' => array(
                        'type' => 'checkbox',
                        'nested' => true,
                        'attrs' => 'data-any="y"',
                        'choices' => array(
                            '--' => array(
                                'name' => lang('any_field'),
                                'children' => ee('Model')->get('ChannelField')->filter('field_type', 'relationship')->all()->getDictionary('field_id', 'field_label')
                            )
                        ),
                        'value' => ($data['fields']) ?: '--',
                        'toggle_all' => false,
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('fields'))
                        ]
                    )
                )
            ),
            array(
                'title' => 'rel_ft_display_entry_id',
                'desc' => 'rel_ft_display_entry_id_desc',
                'fields' => array(
                    'display_entry_id' => array(
                        'type' => 'yes_no',
                        'value' => ($data['display_entry_id']) ? 'y' : 'n'
                    )
                )
            ),
            array(
                'title' => 'rel_ft_display_status',
                'desc' => 'rel_ft_display_status_desc',
                'fields' => array(
                    'display_status' => array(
                        'type' => 'yes_no',
                        'value' => ($data['display_status']) ? 'y' : 'n'
                    )
                )
            ),
        );

        if ($this->content_type() == 'grid') {
            return array('field_options' => $settings);
        }

        return array('field_options_reverse_relationship' => array(
            'label' => 'field_options',
            'group' => 'reverse_relationship',
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
        $data['display_entry_id'] = get_bool_from_string($data['display_entry_id']);
        $data['display_status'] = get_bool_from_string($data['display_status']);

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
