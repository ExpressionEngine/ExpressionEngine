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
 * Grid Fieldtype
 */
class Grid_ft extends EE_Fieldtype
{

    public $info = array(
        'name' => 'Grid',
        'version' => '1.0.0'
    );

    public $has_array_data = true;

    public $complex_data_structure = true;

    public $size = 'large';

    public $settings_form_field_name = 'grid';

    public $can_be_cloned = true;

    private $errors;

    /**
     * A list of operators that this fieldtype supports
     *
     * @var array
     */
    public $supportedEvaluationRules = null;

    public function __construct()
    {
        parent::__construct();

        ee()->lang->loadfile('fieldtypes');
        ee()->load->model('grid_model');
    }

    public function install()
    {
        ee()->grid_model->install();
    }

    public function uninstall()
    {
        ee()->grid_model->uninstall();
    }

    public function validate($data)
    {
        $this->_load_grid_lib();

        return ee()->grid_lib->validate($data);
    }

    // Actual saving takes place in post_save so we have an entry_id
    public function save($data)
    {
        if (is_null($data)) {
            $data = array();
        }

        ee()->session->set_cache(__CLASS__, $this->name(), $data);

        // we save compounded searchable data to the field data table,
        // real data gets saved to the grid's own table
        $searchable_data = null;
        if ($this->get_setting('field_search')) {
            ee()->load->helper('custom_field_helper');
            $this->_load_grid_lib();
            $searchable_data = encode_multi_field(ee()->grid_lib->getSearchableData()) ?: null;
        }

        return $searchable_data;
    }

    public function post_save($data)
    {
        // Prevent saving if save() was never called, happens in Channel Form
        // if the field is missing from the form
        if (($data = ee()->session->cache(__CLASS__, $this->name(), false)) !== false) {
            $this->_load_grid_lib();

            ee()->grid_lib->save($data);
        }
    }

    public function reindex($data)
    {
        // we save compounded searchable data to the field data table,
        // real data gets saved to the grid's own table
        $searchable_data = null;
        if ($this->get_setting('field_search')) {
            $this->_load_grid_lib();

            $rows = ee()->grid_model->get_entry(ee()->grid_lib->entry_id, ee()->grid_lib->field_id, ee()->grid_lib->content_type, ee()->grid_lib->fluid_field_data_id);

            $columns = ee()->grid_model->get_columns_for_field(ee()->grid_lib->field_id, 'channel');
            $searchable_columns = array_filter($columns, function ($column) {
                return ($column['col_search'] == 'y');
            });
            $searchable_columns = array_map(function ($element) {
                return 'col_id_' . $element['col_id'];
            }, $searchable_columns);

            $search_data = [];

            foreach ($rows as $row) {
                // We need only the column data for insertion
                $column_data = [];
                foreach ($row as $key => $value) {
                    if (in_array($key, $searchable_columns)) {
                        $column_data[$key] = $value;
                    }
                }
                $search_data[$row['row_id']] = $column_data;
            }

            ee()->load->helper('custom_field_helper');
            $searchable_data = encode_multi_field($search_data) ?: null;
        }

        return $searchable_data;
    }

    // This fieldtype has been converted, so it accepts all content types
    public function accepts_content_type($name)
    {
        return ($name != 'grid');
    }

    // When a content type is removed, we need to clean up our data
    public function unregister_content_type($name)
    {
        ee()->grid_model->delete_content_of_type($name);
    }

    /**
     * Called when entries are deleted
     *
     * @param	array	Entry IDs to delete data for
     */
    public function delete($entry_ids)
    {
        $entries = ee()->grid_model->get_entry_rows($entry_ids, $this->id(), $this->content_type());

        // Skip params in the loop
        unset($entries['params']);

        $row_ids = array();
        foreach ($entries as $rows) {
            // Continue if entry has no rows
            if (empty($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                $row_ids[$row['entry_id']][] = $row['row_id'];
            }
        }

        $this->_load_grid_lib();

        ee()->grid_lib->delete_rows($row_ids);
    }

    public function display_field($data)
    {
        $grid = ee('CP/GridInput', array(
            'field_name' => $this->name(),
            'lang_cols' => false,
            'grid_min_rows' => isset($this->settings['grid_min_rows']) ? $this->settings['grid_min_rows'] : 0,
            'grid_max_rows' => isset($this->settings['grid_max_rows']) ? $this->settings['grid_max_rows'] : '',
            'reorder' => isset($this->settings['allow_reorder'])
                ? get_bool_from_string($this->settings['allow_reorder'])
                : true,
            'vertical_layout' => isset($this->settings['vertical_layout'])
                ? ($this->settings['vertical_layout'] == 'horizontal_layout' ? 'horizontal' : $this->settings['vertical_layout'])
                : 'n',
        ));
        $grid->loadAssets();
        $grid->setNoResultsText(
            lang('no_rows_created') . form_hidden($this->name()),
            'add_new_row'
        );

        $this->_load_grid_lib();

        $field = ee()->grid_lib->display_field($grid, $data);

        if (REQ != 'CP') {
            // channel form is not guaranteed to have this wrapper class,
            // but the js requires it
            $field = '<div class="fieldset-faux">' . $field . '</div>';
        }

        return $field;
    }

    /**
     * Replace Grid template tags
     */
    public function replace_tag($data, $params = '', $tagdata = '')
    {
        ee()->load->library('grid_parser');

        $fluid_field_data_id = (isset($this->settings['fluid_field_data_id'])) ? $this->settings['fluid_field_data_id'] : 0;

        // not in a channel scope? pre-process may not have been run.
        if ($this->content_type() != 'channel') {
            ee()->load->library('api');
            ee()->legacy_api->instantiate('channel_fields');
            ee()->grid_parser->grid_field_names[$this->id()][$fluid_field_data_id] = $this->name();
        }

        // Channel Form can throw us a model object instead of a results row
        if ($this->row instanceof \ExpressionEngine\Model\Channel\ChannelEntry) {
            $this->row = $this->row->getModChannelResultsArray();
        }

        return ee()->grid_parser->parse($this->row, $this->id(), $params, $tagdata, $this->content_type(), $fluid_field_data_id);
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
        $entry_id = $this->row['entry_id'];

        ee()->load->model('grid_model');
        $entry_data = ee()->grid_model->get_entry_rows($entry_id, $this->id(), $this->content_type(), $params);

        if ($entry_data !== false && isset($entry_data[$entry_id])) {
            return count($entry_data[$entry_id]);
        }

        return 0;
    }

    /**
     * :table modifier
     */
    public function replace_table($data, $params = array(), $tagdata = '')
    {
        ee()->load->library('table');
        ee()->load->library('grid_parser');
        ee()->load->model('grid_model');
        ee()->load->helper('array_helper');

        $fluid_field_data_id = (isset($this->settings['fluid_field_data_id'])) ? $this->settings['fluid_field_data_id'] : 0;

        // not in a channel scope? pre-process may not have been run.
        if ($fluid_field_data_id) {
            ee()->load->library('api');
            ee()->legacy_api->instantiate('channel_fields');
            ee()->grid_parser->grid_field_names[$this->id()][$fluid_field_data_id] = $this->name();
        }

        $columns = ee()->grid_model->get_columns_for_field($this->id(), $this->content_type());
        $prefix = ee()->grid_parser->grid_field_names[$this->id()][$fluid_field_data_id] . ':';

        // Parameters
        $set_classes = element('set_classes', $params, 'no');
        $set_widths = element('set_widths', $params, 'no');

        // Gather information we need from each column to build the table
        $column_headings = array();
        $column_cells = array();
        foreach ($columns as $column) {
            $column_heading = array('data' => $column['col_label']);
            $column_cell = array('data' => LD . $prefix . $column['col_name'] . RD);

            // set_classes parameter; if yes, adds column name as a class
            // to heading cells and data cells
            if ($set_classes == 'yes' || $set_classes == 'y') {
                $column_heading['class'] = $column['col_name'];
                $column_cell['class'] = $column['col_name'];
            }

            // set_widths parameter; if yes, sets column widths to those
            // defined in the field's settings
            if (($set_widths == 'yes' || $set_widths == 'y') && $column['col_width'] != 0) {
                $column_heading['width'] = $column['col_width'] . '%';
            }

            $column_headings[] = $column_heading;
            $column_cells[] = $column_cell;
        }

        // We need a marker to separate the table rows portion from the
        // rest of the table markup so that we only send the row template
        // to the Grid parser for looping; otherwise, the entire table
        // markup will loop
        $row_data_marker = '{!--GRIDTABLEROWS--}';

        $table_attributes = '';

        // Table element attributes that can be set via tag parameters
        foreach (array('border', 'cellspacing', 'cellpadding', 'class', 'id', 'width') as $attribute) {
            // Concatenate a string of them together for the table template
            if (isset($params[$attribute])) {
                $table_attributes .= ' ' . $attribute . '="' . $params[$attribute] . '"';
            }
        }

        ee()->table->set_template(array(
            'table_open' => '<table' . $table_attributes . '>',
            'tbody_open' => '<tbody>' . $row_data_marker,
            'tbody_close' => $row_data_marker . '</tbody>'
        ));

        ee()->table->set_heading($column_headings);
        ee()->table->add_row($column_cells);

        $tagdata = ee()->table->generate();

        // Match the row data section only
        if (preg_match(
            '/' . preg_quote($row_data_marker) . '(.*)' . preg_quote($row_data_marker) . '/s',
            $tagdata,
            $match
        )) {
            // Parse the loopable portion of the table
            $row_data = ee()->grid_parser->parse(
                $this->row,
                $this->id(),
                $params,
                $match[1],
                $this->content_type(),
                $fluid_field_data_id
            );

            // Replace the marker section with the parsed data
            $tagdata = str_replace($match[0], $row_data, $tagdata);
        }

        return $tagdata;
    }

    /**
     * :sum modifier
     */
    public function replace_sum($data, $params = array(), $tagdata = '')
    {
        return $this->_get_column_stats($params, 'sum');
    }

    /**
     * :average modifier
     */
    public function replace_average($data, $params = array(), $tagdata = '')
    {
        return $this->_get_column_stats($params, 'average');
    }

    /**
     * :lowest modifier
     */
    public function replace_lowest($data, $params = array(), $tagdata = '')
    {
        return $this->_get_column_stats($params, 'lowest');
    }

    /**
     * :highest modifier
     */
    public function replace_highest($data, $params = array(), $tagdata = '')
    {
        return $this->_get_column_stats($params, 'highest');
    }

    /**
     * Used in the math modifiers to return stats about numeric columns
     *
     * @param	array	Tag parameters
     * @param	string	Column metric to return
     * @param	int		Return data for tag
     */
    private function _get_column_stats($params, $metric)
    {
        $entry_id = $this->row['entry_id'];

        ee()->load->model('grid_model');
        $entry_data = ee()->grid_model->get_entry_rows($entry_id, $this->id(), $this->content_type(), $params);

        // Bail out if no entry data
        if ($entry_data === false or
            ! isset($entry_data[$entry_id]) or
            ! isset($params['column'])) {
            return '';
        }

        $columns = ee()->grid_model->get_columns_for_field($this->id(), $this->content_type());

        // Find the column that matches the passed column name
        foreach ($columns as $column) {
            if ($column['col_name'] == $params['column']) {
                break;
            }
        }

        // Gather the numbers needed to make the calculations
        $numbers = array();
        foreach ($entry_data[$entry_id] as $row) {
            if (is_numeric($row['col_id_' . $column['col_id']])) {
                $numbers[] = $row['col_id_' . $column['col_id']];
            }
        }

        if (empty($numbers)) {
            return '';
        }

        // These are our supported operations
        switch ($metric) {
            case 'sum':
                return array_sum($numbers);
            case 'average':
                return array_sum($numbers) / count($numbers);
            case 'lowest':
                return min($numbers);
            case 'highest':
                return max($numbers);
            default:
                return '';
        }
    }

    /**
     * :next_row modifier
     */
    public function replace_next_row($data, $params = '', $tagdata = '')
    {
        return $this->_parse_prev_next_row($params, $tagdata, true);
    }

    /**
     * :prev_row modifier
     */
    public function replace_prev_row($data, $params = '', $tagdata = '')
    {
        return $this->_parse_prev_next_row($params, $tagdata);
    }

    /**
     * Handles parsing of :next_row and :prev_row modifiers
     *
     * @param	array	Tag parameters
     * @param	string	Tag pair tag data
     * @param	boolean	TRUE for next row, FALSE for previous row
     * @param	string	Return data for tag
     */
    private function _parse_prev_next_row($params, $tagdata, $next = false)
    {
        if (! isset($params['row_id'])) {
            return '';
        }

        $params['offset'] = ($next) ? 1 : -1;
        $params['limit'] = 1;

        ee()->load->library('grid_parser');

        return ee()->grid_parser->parse($this->row, $this->id(), $params, $tagdata, $this->content_type());
    }

    /**
     * Gathers column data ready to be rendered as a view
     */
    public function getColumnsForSettingsView()
    {
        $field_id = (int) $this->id();

        $columns = [];

        // Validation error, repopulate
        if (isset($_POST[$this->settings_form_field_name])) {
            $columns = $_POST[$this->settings_form_field_name]['cols'];

            foreach ($columns as $field_name => &$column) {
                $column['col_id'] = $field_name;
            }
        } elseif (! empty($field_id)) {
            $columns = ee()->grid_model->get_columns_for_field($field_id, $this->content_type());
        }

        return $columns;
    }

    /**
     * Gather all view variables needed to construct a Grid settings view
     */
    protected function getSettingsVars()
    {
        $this->_load_grid_lib();

        $vars = array();

        // Gather columns for current field
        $vars['columns'] = array();

        $columns = $this->getColumnsForSettingsView();

        foreach ($columns as $column) {
            $vars['columns'][] = ee()->grid_lib->get_column_view($column, $this->errors);
        }

        // Will be our template for newly-created columns
        $vars['blank_col'] = ee()->grid_lib->get_column_view();

        // Fresh settings forms ready to be used for added columns
        $vars['settings_forms'] = array();
        $fieldtypes = ee()->grid_lib->get_grid_fieldtypes();
        foreach (array_keys($fieldtypes['fieldtypes']) as $field_name) {
            $vars['settings_forms'][$field_name] = ee()->grid_lib->get_settings_form($field_name);
        }

        $vars['grid_alert'] = '';
        if (! empty($this->error_string)) {
            $vars['grid_alert'] = ee('CP/Alert')->makeInline('grid-error')
                ->asIssue()
                ->addToBody($this->error_string)
                ->render();
        }

        return $vars;
    }

    /**
     * Load global assets needed for Grid settings
     */
    protected function loadGridSettingsAssets()
    {
        // Create a template of the banner we generally use for alerts
        // so we can manipulate it for AJAX validation
        $alert_template = ee('CP/Alert')->makeInline('grid-error')
            ->asIssue()
            ->render();

        ee()->javascript->set_global('alert.grid_error', $alert_template);

        ee()->cp->add_js_script('plugin', 'ee_url_title');
        ee()->cp->add_js_script('plugin', 'ui.touch.punch');
        ee()->cp->add_js_script('ui', 'sortable');
        ee()->cp->add_js_script('file', 'cp/grid');
    }

    public function display_settings($data)
    {
        $vars = $this->getSettingsVars();
        $vars['group'] = 'grid';

        if (empty($vars['columns'])) {
            $vars['columns'][] = $vars['blank_col'];
        }

        $settings = array(
            'field_options_grid' => array(
                'label' => 'field_options',
                'group' => 'grid',
                'settings' => array(
                    array(
                        'title' => 'grid_min_rows',
                        'desc' => 'grid_min_rows_desc',
                        'fields' => array(
                            'grid_min_rows' => array(
                                'type' => 'text',
                                'value' => isset($data['grid_min_rows']) ? $data['grid_min_rows'] : 0
                            )
                        )
                    ),
                    array(
                        'title' => 'grid_max_rows',
                        'desc' => 'grid_max_rows_desc',
                        'fields' => array(
                            'grid_max_rows' => array(
                                'type' => 'text',
                                'value' => isset($data['grid_max_rows']) ? $data['grid_max_rows'] : ''
                            )
                        )
                    ),
                    array(
                        'title' => 'grid_allow_reorder',
                        'fields' => array(
                            'allow_reorder' => array(
                                'type' => 'yes_no',
                                'value' => isset($data['allow_reorder']) ? $data['allow_reorder'] : 'y'
                            )
                        )
                    ),
                    array(
                        'title' => 'grid_vertical_layout_title',
                        'desc' => 'grid_vertical_layout_desc',
                        'fields' => array(
                            'vertical_layout' => array(
                                'type' => 'radio',
                                'choices' => array(
                                    'n' => lang('grid_auto'),
                                    'y' => lang('grid_vertical_layout'),
                                    'horizontal' => lang('grid_horizontal_layout'),
                                ),
                                'value' => isset($data['vertical_layout']) ? ($data['vertical_layout'] == 'horizontal_layout' ? 'horizontal' : $data['vertical_layout']) : 'n'
                            )
                        )
                    )
                )
            ),
            'grid_fields' => array(
                'label' => 'grid_fields',
                'group' => 'grid',
                'settings' => array($vars['grid_alert'], ee('View')->make('grid:settings')->render($vars))
            )
        );

        $this->loadGridSettingsAssets();

        ee()->javascript->output('EE.grid_settings();');
        ee()->javascript->output('FieldManager.on("fieldModalDisplay", function(modal) {
			EE.grid_settings();
		});');

        return $settings;
    }

    /**
     * Called by FieldModel to validate the fieldtype's settings
     */
    public function validate_settings($data)
    {
        $rules = [
            'grid_min_rows' => 'isNatural',
            'grid_max_rows' => 'isNaturalNoZero',
            'fieldtype_errors' => 'ensureNoFieldtypeErrors'
        ];

        $grid_settings = ee()->input->post($this->settings_form_field_name);
        $col_labels = [];
        $col_names = [];

        if (! isset($grid_settings['cols'])) {
            return $this->errors;
        }

        // Create a flattened version of the grid settings data to pass to the
        // validator, but also assign rules to the dynamic field names
        foreach ($grid_settings['cols'] as $column_id => $column) {
            // We'll look at these later to see if there are any duplicates
            $col_labels[] = $column['col_label'];
            $col_names[] = $column['col_name'];

            foreach ($column as $field => $value) {
                $field_name = $this->settings_form_field_name . '[cols][' . $column_id . '][' . $field . ']';
                $data[$field_name] = $value;

                switch ($field) {
                    case 'col_label':
                        $rules[$field_name] = 'required|maxLength[50]|validGridColLabel';

                        break;
                    case 'col_name':
                        $rules[$field_name] = 'required|alphaDash|maxLength[32]|validGridColName';

                        break;
                    case 'col_width':
                        $rules[$field_name] = 'whenPresent|isNatural';

                        break;
                    case 'col_required':
                        $rules[$field_name] = 'enum[y,n]';

                        break;
                    case 'col_search':
                        $rules[$field_name] = 'enum[y,n]';

                        break;
                    default:
                        break;
                }
            }
        }

        $col_label_count = array_count_values($col_labels);
        $col_name_count = array_count_values($col_names);

        $validator = ee('Validation')->make($rules);

        $validator->defineRule(
            'validGridColLabel',
            function ($key, $value, $params, $rule) use ($col_label_count) {
                if ($col_label_count[$value] > 1) {
                    $rule->stop();

                    return lang('grid_duplicate_col_label');
                }

                return true;
            }
        );

        $validator->defineRule(
            'validGridColName',
            function ($key, $value, $params, $rule) use ($col_name_count) {
                ee()->load->library('grid_parser');
                if (in_array($value, ee()->grid_parser->reserved_names)) {
                    $rule->stop();

                    return lang('grid_col_name_reserved');
                }

                if ($col_name_count[$value] > 1) {
                    $rule->stop();

                    return lang('grid_duplicate_col_name');
                }

                return true;
            }
        );

        $this->_load_grid_lib();
        $fieldtype_errors = ee()->grid_lib->validate_settings($grid_settings);

        $validator->defineRule(
            'ensureNoFieldtypeErrors',
            function ($key, $value, $params, $rule) use ($fieldtype_errors) {
                if (! empty($fieldtype_errors)) {
                    $rule->stop();
                }

                return true;
            }
        );

        $this->errors = $validator->validate($data);

        // Add any failed rules from fieldtypes as a top-level fields on our
        // result object so that AJAX validation can pick it up
        foreach ($fieldtype_errors as $field_name => $error) {
            foreach ($error->getFailed() as $field => $rules) {
                $field_name = $this->settings_form_field_name . '[cols][' . $field_name . '][col_settings][' . $field . ']';
                foreach ($rules as $rule) {
                    $this->errors->addFailed($field_name, $rule);
                }
            }
        }

        return $this->errors;
    }

    public function save_settings($data)
    {
        if (! $this->get_setting('field_search')
            && (isset($data['field_search']) && $data['field_search'] == 'y')) {
            ee('CP/Alert')->makeInline('search-reindex')
                ->asImportant()
                ->withTitle(lang('search_reindex_tip'))
                ->addToBody(sprintf(lang('search_reindex_tip_desc'), ee('CP/URL')->make('utilities/reindex')->compile()))
                ->defer();

            ee()->config->update_site_prefs(['search_reindex_needed' => ee()->localize->now], 0);
        }

        // Make sure grid_min_rows is at least zero
        return array(
            'grid_min_rows' => empty($data['grid_min_rows']) ? 0 : $data['grid_min_rows'],
            'grid_max_rows' => empty($data['grid_max_rows']) ? '' : $data['grid_max_rows'],
            'allow_reorder' => empty($data['allow_reorder']) ? 'y' : $data['allow_reorder'],
            'vertical_layout' => empty($data['vertical_layout']) ? 'n' : $data['vertical_layout'],
        );
    }

    public function post_save_settings($data)
    {
        if (! isset($_POST[$this->settings_form_field_name])) {
            return;
        }

        // Need to get the field ID of the possibly newly-created field, so
        // we'll actually re-save the field settings in the Grid library
        $data['field_id'] = $this->id();
        $data['grid'] = ee()->input->post($this->settings_form_field_name);

        $this->_load_grid_lib();
        ee()->grid_lib->apply_settings($data);
    }

    public function settings_modify_column($data)
    {
        if (isset($data['ee_action']) && $data['ee_action'] == 'delete') {
            $columns = ee()->grid_model->get_columns_for_field($data['field_id'], $this->content_type(), false);

            $col_types = array();
            foreach ($columns as $column) {
                $col_types[$column['col_id']] = $column['col_type'];
            }

            // Give fieldtypes a chance to clean up when its parent Grid
            // field is deleted
            if (! empty($col_types)) {
                ee()->grid_model->delete_columns(
                    array_keys($col_types),
                    $col_types,
                    $data['field_id'],
                    $this->content_type()
                );
            }

            ee()->grid_model->delete_field($data['field_id'], $this->content_type());
        }

        return array();
    }

    /**
     * Loads Grid library and assigns relevant field information to it
     */
    private function _load_grid_lib()
    {
        ee()->load->library('grid_lib');

        // Attempt to get an entry ID first
        $entry_id = (isset($this->settings['entry_id']))
            ? $this->settings['entry_id'] : ee()->input->get_post('entry_id');

        ee()->grid_lib->entry_id = ($this->content_id() == null) ? $entry_id : $this->content_id();
        ee()->grid_lib->field_id = $this->id();
        ee()->grid_lib->field_name = $this->name();
        ee()->grid_lib->field_short_name = isset($this->settings['field_short_name']) ? $this->settings['field_short_name'] : null;
        ee()->grid_lib->field_required = $this->settings['field_required'] ?? 'n';
        ee()->grid_lib->content_type = $this->content_type();
        ee()->grid_lib->fluid_field_data_id = (isset($this->settings['fluid_field_data_id'])) ? $this->settings['fluid_field_data_id'] : 0;
        ee()->grid_lib->in_modal_context = $this->get_setting('in_modal_context');
        ee()->grid_lib->settings_form_field_name = $this->settings_form_field_name;
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
}

// EOF
