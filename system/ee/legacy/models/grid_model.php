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
 * Grid Field Model
 */
class Grid_model extends CI_Model
{
    protected $_table = 'grid_columns';
    protected $_table_prefix = 'grid_field_';
    protected $_grid_data = array();
    protected $_columns = array();

    /**
     * Performs fieldtype install
     *
     * Beware! Changes here also need to be made in mysql_schema.
     *
     * @return	void
     */
    public function install()
    {
        $columns = array(
            'col_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true
            ),
            'field_id' => array(
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true
            ),
            'content_type' => array(
                'type' => 'varchar',
                'constraint' => 50
            ),
            'col_order' => array(
                'type' => 'int',
                'constraint' => 3,
                'unsigned' => true
            ),
            'col_type' => array(
                'type' => 'varchar',
                'constraint' => 50
            ),
            'col_label' => array(
                'type' => 'varchar',
                'constraint' => 50
            ),
            'col_name' => array(
                'type' => 'varchar',
                'constraint' => 32
            ),
            'col_instructions' => array(
                'type' => 'text'
            ),
            'col_required' => array(
                'type' => 'char',
                'constraint' => 1
            ),
            'col_search' => array(
                'type' => 'char',
                'constraint' => 1
            ),
            'col_width' => array(
                'type' => 'int',
                'constraint' => 3,
                'unsigned' => true
            ),
            'col_settings' => array(
                'type' => 'text'
            )
        );

        ee()->load->dbforge();
        ee()->dbforge->add_field($columns);
        ee()->dbforge->add_key('col_id', true);
        ee()->dbforge->add_key('field_id');
        ee()->dbforge->add_key('content_type');
        ee()->dbforge->create_table($this->_table);

        ee()->db->insert('content_types', array('name' => 'grid'));
    }

    /**
     * Performs fieldtype uninstall
     *
     * @return	void
     */
    public function uninstall()
    {
        // Get field IDs to drop corresponding field table
        $grid_fields = ee()->db->select('field_id')
            ->distinct()
            ->get($this->_table)
            ->result_array();

        // Drop grid_field_n tables
        foreach ($grid_fields as $row) {
            $this->delete_field($row['field_id'], $row['content_type']);
        }

        // Drop grid_columns table
        ee()->load->dbforge();
        ee()->dbforge->drop_table($this->_table);

        ee()->db->delete('content_types', array('name' => 'grid'));
    }

    /**
     * Creates data table for a new Grid field
     *
     * @param	int		Field ID of field to create a data table for
     * @return	boolean	Whether or not a table was created
     */
    public function create_field($field_id, $content_type)
    {
        $table_name = $this->_data_table($content_type, $field_id);

        if (! ee()->db->table_exists($table_name)) {
            ee()->load->dbforge();

            // Every field table needs these two rows, we'll start here and
            // add field columns as necessary
            $db_columns = array(
                'row_id' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'auto_increment' => true
                ),
                'entry_id' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true
                ),
                'row_order' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true
                ),
                'fluid_field_data_id' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0
                ),
            );

            ee()->dbforge->add_field($db_columns);
            ee()->dbforge->add_key('row_id', true);
            ee()->dbforge->add_key('entry_id');
            ee()->dbforge->create_table($table_name);

            return true;
        }

        return false;
    }

    /**
     * Performs cleanup on our end if a Grid field is deleted from a channel:
     * drops field's table, removes column settings from grid_columns table
     *
     * @param	int		Field ID of field to delete
     * @return	void
     */
    public function delete_field($field_id, $content_type)
    {
        $table_name = $this->_data_table($content_type, $field_id);

        if (ee()->db->table_exists($table_name)) {
            ee()->load->dbforge();
            ee()->dbforge->drop_table($table_name);
        }

        ee()->db->delete($this->_table, array('field_id' => $field_id));
    }

    /**
     * Performs cleanup on our end if a grid field's parent content type is deleted.
     * Removes all associated tables and drops all entry rows.
     *
     * @param	string  Name of the content type that was removed
     * @return	void
     */
    public function delete_content_of_type($content_type)
    {
        $tables = ee()->db->list_tables($content_type . $this->_table_prefix);

        ee()->load->dbforge();

        foreach ($tables as $table_name) {
            ee()->dbforge->drop_table($table_name);
        }

        ee()->db->delete($this->_table, array('content_type' => $content_type));
    }

    /**
     * Adds a new column to the columns table or updates an existing one; also
     * manages columns in the field's respective data table
     *
     * @param	array	Column data
     * @param	int		Column ID to update, or FALSE if new column
     * @return	int		Column ID
     */
    public function save_col_settings($column, $col_id = false, $content_type = 'channel')
    {
        // Existing column
        if ($col_id) {
            // Make any column modifications necessary
            ee()->api_channel_fields->edit_datatype(
                $col_id,
                $column['col_type'],
                is_array($column['col_settings']) ? $column['col_settings'] : json_decode($column['col_settings'], true),
                $this->_get_ft_api_settings($column['field_id'], $content_type)
            );

            ee('db')->where('col_id', $col_id)
                ->update($this->_table, $column);
        }
        // New column
        else {
            $db = ee('db');
            $db->insert($this->_table, $column);
            $col_id = $db->insert_id();

            // Add the fieldtype's columns to our data table
            ee()->api_channel_fields->setup_handler($column['col_type']);
            ee()->api_channel_fields->set_datatype(
                $col_id,
                is_array($column['col_settings']) ? $column['col_settings'] : json_decode($column['col_settings'], true),
                array(),
                true,
                false,
                $this->_get_ft_api_settings($column['field_id'], $content_type)
            );
        }

        return $col_id;
    }

    /**
     * Deletes columns from grid settings and drops columns from their
     * respective field tables
     *
     * @param	array	Column IDs to delete
     * @param	array	Column types
     * @param	int		Field ID
     */
    public function delete_columns($column_ids, $column_types, $field_id, $content_type)
    {
        if (! is_array($column_ids)) {
            $column_ids = array($column_ids);
        }

        ee()->db->where_in('col_id', $column_ids);
        ee()->db->delete($this->_table);

        foreach ($column_ids as $col_id) {
            // Delete columns from data table
            ee()->api_channel_fields->setup_handler($column_types[$col_id]);
            ee()->api_channel_fields->delete_datatype(
                $col_id,
                array(),
                $this->_get_ft_api_settings($field_id, $content_type)
            );
        }
    }

    /**
     * Typically used when a fieldtype is uninstalled, removes all columns of
     * a certain fieldtype across all Grid fields and content types
     *
     * @param	string 	$field_type	Fieldtype short name
     */
    public function delete_columns_of_type($field_type)
    {
        $grid_cols = ee()->db->where('col_type', $field_type)
            ->get('grid_columns')
            ->result_array();

        $cols_to_fieldtypes = array();
        $fields_to_columns = array();
        $fields_to_contenttypes = array();
        foreach ($grid_cols as $column) {
            $cols_to_fieldtypes[$column['col_id']] = $column['col_type'];
            $fields_to_columns[$column['field_id']][] = $column['col_id'];
            $fields_to_contenttypes[$column['field_id']] = $column['content_type'];
        }

        foreach ($fields_to_columns as $field_id => $col_ids) {
            $this->delete_columns(
                $col_ids,
                $cols_to_fieldtypes,
                $field_id,
                $fields_to_contenttypes[$field_id]
            );
        }
    }

    /**
     * Returns the row data for a single entry ID and field ID
     *
     * @param	int 	Entry ID
     * @param	int		Field ID to get row data for
     * @param	string	Content type to get data for
     * @return	array	Row data
     */
    public function get_entry($entry_id, $field_id, $content_type, $fluid_field_data_id = 0)
    {
        $table = $this->_data_table($content_type, $field_id);
        ee()->db->where('entry_id', $entry_id);
        ee()->db->where('fluid_field_data_id', $fluid_field_data_id);

        return ee()->db->get($table)->result_array();
    }

    /**
     * Returns entry row data for a given entry ID and field ID, caches data
     * it has already queried for
     *
     * @param	array	Entry IDs to get row data for
     * @param	int		Field ID to get row data for
     * @param	string	Name of content type
     * @param	array	Options for the query, often filled by tag parameters
     * @param	boolean	Whether or not to get fresh data on this call instead of from the _grid_data cache
     * @return	array	Row data
     */
    public function get_entry_rows($entry_ids, $field_id, $content_type, $options = array(), $reset_cache = false, $fluid_field_data_id = 0)
    {
        if (! is_array($entry_ids)) {
            $entry_ids = array($entry_ids);
        }

        // Validate the passed parameters and create a unique marker for these
        // specific parameters so we know not to query for them again
        $options = $this->_validate_params($options, $field_id, $content_type);
        $marker = $this->_get_tag_marker($options);

        if (isset($this->_grid_data[$content_type][$field_id][$marker]['fluid_field_data_id'])
            && $this->_grid_data[$content_type][$field_id][$marker]['fluid_field_data_id'] != $fluid_field_data_id) {
            $reset_cache = true;
        }

        foreach ($entry_ids as $key => $entry_id) {
            // If we already have data for this particular tag configuation
            // and entry ID, we don't need to get it again
            if ($reset_cache === false && isset($this->_grid_data[$content_type][$field_id][$marker][$entry_id])) {
                unset($entry_ids[$key]);
            }
        }

        $this->_grid_data[$content_type][$field_id][$marker]['params'] = $options;
        $this->_grid_data[$content_type][$field_id][$marker]['fluid_field_data_id'] = $fluid_field_data_id;

        if (! empty($entry_ids)) {
            // Insert a blank array for each entry ID in case the query returns
            // no results, we don't want the cache check to fail and we keep
            // querying for data that doesn't exist
            foreach ($entry_ids as $entry_id) {
                $this->_grid_data[$content_type][$field_id][$marker][$entry_id] = array();
            }

            // fixed_order parameter
            if (isset($options['fixed_order']) && ! empty($options['fixed_order'])) {
                ee()->functions->ar_andor_string($options['fixed_order'], 'row_id');
                ee()->db->order_by(
                    'FIELD(row_id, ' . implode(', ', explode('|', $options['fixed_order'])) . ')',
                    element('sort', $options, 'asc'),
                    false
                );
            }

            // search:field parameter
            if (isset($options['search']) && ! empty($options['search'])) {
                $this->_field_search($options['search'], $field_id, $content_type);
            }

            ee()->load->helper('array_helper');

            $orderby = element('orderby', $options);
            if ($orderby == 'random' || empty($orderby)) {
                $orderby = 'row_order';
            }

            ee()->db->where_in('entry_id', $entry_ids);
            ee()->db->where('fluid_field_data_id', $fluid_field_data_id);
            ee()->db->order_by($orderby, element('sort', $options, 'asc'));

            // -------------------------------------------
            // 'grid_query' hook.
            // - Allows developers to modify and run the query for Grid data
            //
            if (ee()->extensions->active_hook('grid_query') === true) {
                $rows = ee()->extensions->call(
                    'grid_query',
                    $entry_ids,
                    $field_id,
                    $content_type,
                    $this->_data_table($content_type, $field_id),
                    ee()->db->_compile_select(false, false)
                );
            } else {
                $rows = ee()->db->get(
                    $this->_data_table($content_type, $field_id)
                )->result_array();
            }
            //
            // -------------------------------------------

            // Add these rows to the cache
            foreach ($rows as $row) {
                $this->_grid_data[$content_type][$field_id][$marker][$row['entry_id']][$row['row_id']] = $row;
            }
        }

        $entry_data = isset($this->_grid_data[$content_type][$field_id][$marker]) ? $this->_grid_data[$content_type][$field_id][$marker] : false;

        // override With Preview Data
        if (ee('LivePreview')->hasEntryData()) {
            $data = ee('LivePreview')->getEntryData();
            $entry_id = $data['entry_id'];
            $fluid_field = 0;

            if ($fluid_field_data_id && ! is_int($fluid_field_data_id)) {
                list($fluid_field, $sub_field_id) = explode(',', $fluid_field_data_id);
                $data = reset($data[$fluid_field]['fields'][$sub_field_id]);
            }

            if (array_key_exists($entry_id, $entry_data)
                && isset($data['field_id_' . $field_id])
                && is_array($data['field_id_' . $field_id])
                && array_key_exists('rows', $data['field_id_' . $field_id])) {
                $override = [];
                $i = 0;
                foreach ($data['field_id_' . $field_id]['rows'] as $row_id => $row_data) {
                    $override[$i] = [
                        'row_id' => crc32($row_id),
                        'orig_row_id' => $row_id,
                        'entry_id' => $entry_id,
                        'row_order' => $i,
                        'fluid_field_data_id' => $fluid_field_data_id
                    ] + $row_data;
                    // search:field parameter
                    if (isset($options['search']) && ! empty($options['search'])) {
                        $conditions = $this->_field_search($options['search'], $field_id, $content_type, false);
                        if (!empty($conditions)) {
                            $valid = false;
                            foreach ($conditions as $sub_condition) {
                                $valid = $this->previewDataPassesCondition($sub_condition, $override[$i]);
                                if ($valid) {
                                    break;
                                }
                            }
                            if (!$valid) {
                                unset($override[$i]);
                            }
                        }
                    }
                    $i++;
                }

                if (isset($options['orderby']) || isset($options['sort'])) {
                    $orderby = element('orderby', $options);
                    $sort = element('sort', $options, 'asc');
                    if ($orderby == 'random' || empty($orderby)) {
                        $orderby = 'row_order';
                    }
                    if ($orderby == 'row_id') {
                        $orderby = 'orig_row_id';
                    }
                    usort($override, function ($a, $b) use ($orderby, $sort) {
                        if ($sort == 'asc') {
                            return ($a[$orderby] > $b[$orderby]) ? 1 : -1;
                        } else {
                            return ($a[$orderby] < $b[$orderby]) ? 1 : -1;
                        }
                    });
                }

                $entry_data[$entry_id] = $override;
            }
        }

        return $entry_data;
    }

    private function previewDataPassesCondition($condition, $data)
    {
        $condition = str_replace("  ", " ", trim(trim($condition, ') '), ' ('));
        list($column, $comparison, $value) = explode(' ', trim($condition));
        if (strpos($column, '.') !== false) {
            list($table, $key) = explode('.', $column);
        } else {
            $key = $column;
        }

        $datum = $data[$key];
        $value = trim($value, "'");

        $passes = false;

        switch ($comparison) {
            case 'LIKE':
                $value = trim(trim($value, '"'), '%');
                if (is_array($datum)) {
                    foreach ($datum as $piece) {
                        $passes = stripos($piece, $value)!==false;
                        if ($passes) {
                            break 2;
                        }
                    }
                } else {
                    $passes = stripos($datum, $value)!==false;
                }

                break;

            case '=':
                if (is_array($datum)) {
                    $passes = in_array($value, $datum);
                } else {
                    $passes = ($datum == $value);
                }

                break;

            case '!=':
                if (is_array($datum)) {
                    $passes = ! in_array($value, $datum);
                } else {
                    $passes = ($datum != $value);
                }

                break;

            case '>':
                $passes = ($datum > $value);

                break;

            case '<':
                $passes = ($datum < $value);

                break;

            case '>=':
                $passes = ($datum >= $value);

                break;

            case '<=':
                $passes = ($datum <= $value);

                break;

            case 'IN':
                $value = trim($value, '()');
                $value = explode(',', str_replace("'", '', $value));

                if (is_array($datum)) {
                    $passes = array_intersect($datum, $value);
                    $passes = ! empty($passes);
                } else {
                    $passes = in_array($datum, $value);
                }

                break;
        }

        return $passes;
    }

    /**
     * Assigns some default parameters and makes sure parameters can be
     * safely used in an SQL query or otherwise used to help parsing
     *
     * @param	array	Array of parameters from ee('Variables/Parser')->parseTagParameters()
     * @param	int		Field ID of field being parsed so we can make sure
     *					the orderby parameter is ordering via a real column
     * @return	array	Array of validated and default parameters to use for parsing
     */
    protected function _validate_params($params, $field_id, $content_type)
    {
        ee()->load->helper('array_helper');

        if (is_string($params)) {
            $params = ee('Variables/Parser')->parseTagParameters($params);
        }

        // dynamic_parameters
        if (($dynamic_params = element('dynamic_parameters', $params)) != false) {
            foreach (explode('|', $dynamic_params) as $param) {
                // Add its value to the params array if exists in POST
                if (($value = ee()->input->post($param)) !== false) {
                    $params[$param] = $value;
                }
            }
        }

        // Gather params and defaults
        $sort = element('sort', $params);
        $orderby = element('orderby', $params);
        $limit = element('limit', $params, 100);
        $offset = element('offset', $params, 0);
        $backspace = element('backspace', $params, 0);
        $row_id = element('row_id', $params, 0);
        $fixed_order = element('fixed_order', $params, 0);

        // Validate sort parameter, only 'asc' and 'desc' allowed, default to 'asc'
        if (! in_array($sort, array('asc', 'desc'))) {
            $sort = 'asc';
        }

        $columns = $this->get_columns_for_field($field_id, $content_type);

        $sortable_columns = array();
        foreach ($columns as $col) {
            $sortable_columns[$col['col_name']] = $col['col_id'];
        }

        // orderby parameter can only order by the columns available to it,
        // default to 'row_id'
        if ($orderby != 'random') {
            if (! in_array($orderby, array_keys($sortable_columns))) {
                $orderby = 'row_order';
            }
            // Convert the column name to its matching table column name to hand
            // off to the query for proper sorting
            else {
                $orderby = 'col_id_' . $sortable_columns[$orderby];
            }
        }

        // Gather search:field_name parameters
        $search = array();
        if ($params !== false) {
            foreach ($params as $key => $val) {
                if (strncmp($key, 'search:', 7) == 0) {
                    $search[substr($key, 7)] = $val;
                }
            }
        }

        return compact(
            'sort',
            'orderby',
            'limit',
            'offset',
            'search',
            'backspace',
            'row_id',
            'fixed_order'
        );
    }

    /**
     * Creates a unique marker for this tag configuration based on its
     * parameters so we can match up the field data later in parse();
     * if there are no parameters, we'll just use 'data'
     *
     * @param	array	Unvalidated params
     * @return	string	Marker
     */
    private function _get_tag_marker($params)
    {
        ee()->load->helper('array_helper');

        // These are the only parameters that affect the DB query so we'll
        // only check against these; we could put some of these other
        // parameters in the code later on  so that even more tags could
        // use the same data set
        $db_params = array(
            'fixed_order' => element('fixed_order', $params),
            'search' => element('search', $params),
            'orderby' => element('orderby', $params),
            'sort' => element('sort', $params),
        );

        return md5(json_encode($db_params));
    }

    /**
     * Constructs query for search params and adds it to the current
     * Active Record call
     *
     * @param	array	Array of field names mapped to search terms
     * @param	int		Field ID to get column data for
     */
    protected function _field_search($search_terms, $field_id, $content_type = 'channel', $set_sql_query = true)
    {
        if (empty($search_terms)) {
            return;
        }

        ee()->load->model('channel_model');

        $columns = $this->get_columns_for_field($field_id, $content_type);

        // We'll need to map column names to field IDs so we know which column
        // to search
        foreach ($columns as $col) {
            $column_ids[$col['col_name']] = $col['col_id'];
        }

        $conditions = [];
        foreach ($search_terms as $col_name => $terms) {
            $terms = trim($terms);

            // Empty search param or invalid field name? Bail out
            if (empty($search_terms) ||
                $search_terms === '=' ||
                ! isset($column_ids[$col_name])) {
                continue;
            }

            // We'll search on this column name
            $field_name = 'col_id_' . $column_ids[$col_name];

            $search_sql = ee()->channel_model->field_search_sql($terms, $field_name);
            $conditions[] = $search_sql;

            if ($set_sql_query) {
                ee()->db->where('(' . $search_sql . ')');
            }
        }
        return $conditions;
    }

    /**
     * Public getter for $_grid_data property
     *
     * @return	array
     */
    public function get_grid_data()
    {
        return $this->_grid_data;
    }

    /**
     * Gets array of all columns and settings for a given field ID
     *
     * @param	int		Field ID to get columns for
     * @param	string	Content type
     * @param	boolean	When FALSE, skip the cache and get a fresh set of columns
     * @return	array	Settings from grid_columns table
     */
    public function get_columns_for_field($field_ids, $content_type, $cache = true)
    {
        $multi_column = is_array($field_ids);

        if ($multi_column && $cache) {
            $cached = array();

            // Only get the colums for the field IDs we don't already have
            foreach ($field_ids as $key => $field_id) {
                if (isset($this->_columns[$content_type][$field_id]) && $cache) {
                    $cached[$field_id] = $this->_columns[$content_type][$field_id];
                    unset($field_ids[$key]);
                }
            }

            // If there are no field IDs to query, great!
            if (empty($field_ids)) {
                return $cached;
            }
        } else {
            // Return fron cache if exists and allowed
            if (isset($this->_columns[$content_type][$field_ids]) && $cache) {
                return $this->_columns[$content_type][$field_ids];
            }

            $field_ids = array($field_ids);
        }

        $columns = ee('db')->where_in('field_id', $field_ids)
            ->where('content_type', $content_type)
            ->order_by('col_order')
            ->get($this->_table)
            ->result_array();

        foreach ($columns as &$column) {
            $column['col_settings'] = is_array($column['col_settings']) ? $column['col_settings'] : json_decode($column['col_settings'], true);
            $this->_columns[$content_type][$column['field_id']][$column['col_id']] = $column;
        }

        foreach ($field_ids as $field_id) {
            if (! isset($this->_columns[$content_type][$field_id])) {
                $this->_columns[$content_type][$field_id] = array();
            }
        }

        return ($multi_column) ? $this->_columns[$content_type] : $this->_columns[$content_type][$field_id];
    }

    /**
     * Returns settings we need to pass along to the channel fields API when
     * working with managing the data columns for our fieldtypes
     *
     * @param	int		Current field ID
     * @return	array
     */
    protected function _get_ft_api_settings($field_id, $content_type = 'channel')
    {
        return array(
            'id_field' => 'col_id',
            'type_field' => 'col_type',
            'col_settings_method' => 'grid_settings_modify_column',
            'col_prefix' => 'col',
            'fields_table' => $this->_table,
            'data_table' => $this->_data_table($content_type, $field_id),
        );
    }

    /**
     * Saves an data for a given Grid field using an array generated by the
     * Grid libary's data processing method
     *
     * @param	array	Field data array
     * @param	int	Field ID of field we're saving
     * @param	int	Entry ID to assign the row to
     * @return	array	IDs of rows to be deleted
     */
    public function save_field_data($data, $field_id, $content_type, $entry_id, $fluid_field_data_id = null)
    {
        // Keep track of which rows are updated and which are new, and the
        // order they are received
        $updated_rows = array();
        $new_rows = array();
        $order = 0;

        // Log existing row IDs so we can delete all others related to this
        // field and entry
        $row_ids = array(0);

        foreach ($data as $row_id => $columns) {
            // Each row gets its order updated
            $columns['row_order'] = $order;

            if (! is_null($fluid_field_data_id)) {
                $columns['fluid_field_data_id'] = $fluid_field_data_id;
            }

            // New rows
            if (strpos($row_id, 'new_row_') !== false) {
                $columns['entry_id'] = $entry_id;
                $new_rows[] = $columns;
            }
            // Existing rows
            elseif (strpos($row_id, 'row_id_') !== false) {
                if (defined('CLONING_MODE') && CLONING_MODE === true) {
                    $columns['entry_id'] = $entry_id;
                    $new_rows[] = $columns;
                } else {
                    $columns['row_id'] = str_replace('row_id_', '', $row_id);
                    $row_ids[] = $columns['row_id'];
                    $updated_rows[] = $columns;
                }
            }

            $order++;
        }

        $table_name = $this->_data_table($content_type, $field_id);

        // If there are other existing rows for this entry that weren't in
        // the data array, they are to be deleted
        $deleted_rows = ee()->db->select('row_id')
            ->where('entry_id', $entry_id)
            ->where_not_in('row_id', $row_ids);

        if (! is_null($fluid_field_data_id)) {
            $deleted_rows->where('fluid_field_data_id', $fluid_field_data_id);
        }

        $deleted_rows = $deleted_rows->get($table_name)
            ->result_array();

        // Put rows into an array for easy passing and returning for the hook
        $data = array(
            'new_rows' => $new_rows,
            'updated_rows' => $updated_rows,
            'deleted_rows' => $deleted_rows
        );

        // -------------------------------------------
        // 'grid_save' hook.
        //  - Allow developers to modify or add to the Grid data array before saving
        //
        if (ee()->extensions->active_hook('grid_save') === true) {
            $data = ee()->extensions->call(
                'grid_save',
                $entry_id,
                $field_id,
                $content_type,
                $table_name,
                $data
            );
        }
        //
        // -------------------------------------------

        // Batch update and insert rows to save queries
        if (! empty($data['updated_rows'])) {
            ee()->db->update_batch($table_name, $data['updated_rows'], 'row_id');
        }

        if (! empty($data['new_rows'])) {
            ee()->db->insert_batch($table_name, $data['new_rows']);
        }

        // Return deleted row IDs
        return $data['deleted_rows'];
    }

    /**
     * Deletes Grid data for given row IDs
     *
     * @param	array	Row IDs to delete data for
     */
    public function delete_rows($row_ids, $field_id, $content_type)
    {
        if (! empty($row_ids)) {
            ee()->db->where_in('row_id', $row_ids)
                ->delete($this->_data_table($content_type, $field_id));
        }
    }

    /**
     * Create the data table name given the content type and field id.
     *
     * @param string	Content type (typically 'channel')
     * @param string	Field id
     * @return string   Table name of format <content_type>_grid_field_<id>
     */
    protected function _data_table($content_type, $field_id)
    {
        return $content_type . '_' . $this->_table_prefix . $field_id;
    }

    /**
     * When revision has been loaded and then trying to save it,
     * some keys might correspond to the rows that no longer exist
     * Here we make then submitted as new rows instead
     *
     * @param array $rows
     * @param integer $field_id
     * @param integer $entry_id
     * @param integer $fluid_field_data_id
     * @param string $content_type
     * @return array
     */
    public function remap_revision_rows($rows, $field_id, $entry_id, $fluid_field_data_id = 0, $content_type = 'channel')
    {
        // get IDs of rows that already exist
        $existingRows = ee('db')
            ->select('row_id')
            ->from($this->_data_table($content_type, $field_id))
            ->where('entry_id', $entry_id)
            ->where('fluid_field_data_id', $fluid_field_data_id)
            ->get();
        $existingRowKeys = array();
        if ($existingRows->num_rows() > 0) {
            $existingRowKeys = array_map(function ($row) {
                return 'row_id_' . $row['row_id'];
            }, $existingRows->result_array());
        }
        $total_rows = count($rows);
        $values = array_values($rows);
        $keys = array_keys($rows);
        $values = array_values($rows);
        foreach ($keys as $index => $key) {
            if (! in_array($key, $existingRowKeys)) {
                $keys[$index] = 'new_row_' . ($total_rows + (int) str_replace('row_id_', '', $key));
            }
        }
        $rows = array_combine($keys, $values);
        return $rows;
    }

    /**
     * Update grid field(s) search values
     *
     * @param array $field_ids Array of field_ids
     * @return void
     */
    public function update_grid_search(array $field_ids)
    {
        // Get the fields, and filter for grid as a safety measure. If this was somehow
        // called with the wrong field IDs it could clobber those fields' contents
        $fields = ee('Model')->get('ChannelField', $field_ids)
            ->fields('field_id', 'field_search', 'legacy_field_data')
            ->filter('field_type', 'grid')
            ->all();

        if (empty($fields)) {
            return;
        }

        $search_data = [];
        $unsearchable = [];

        foreach ($fields as $field) {
            $data_col = 'field_id_' . $field->field_id;
            $table = $field->getDataStorageTable();

            if (! $field->field_search) {
                $unsearchable[$table][$data_col] = null;

                continue;
            }

            $columns = $this->get_columns_for_field($field->field_id, 'channel');
            $searchable_columns = array_filter($columns, function ($column) {
                return ($column['col_search'] == 'y');
            });
            $searchable_columns = array_map(function ($element) {
                return 'col_id_' . $element['col_id'];
            }, $searchable_columns);

            $rows = ee()->db->select('row_id, entry_id')
                ->select($searchable_columns)
                ->where('fluid_field_data_id', 0)
                ->get($this->_data_table('channel', $field->field_id))
                ->result_array();

            // No rows? Move on.
            if (empty($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                // We need only the column data for insertion
                $column_data = [];
                foreach ($row as $key => $value) {
                    if (strncmp($key, 'col_id_', 7) === 0) {
                        $column_data[$key] = $value;
                    }
                }

                if (! isset($search_data[$table][$row['entry_id']])) {
                    $search_data[$table][$row['entry_id']] = [];
                    $search_data[$table][$row['entry_id']][$data_col] = [];
                }

                // merge with existing data for this field
                $search_data[$table][$row['entry_id']][$data_col] = array_merge(
                    $search_data[$table][$row['entry_id']][$data_col],
                    array_values($column_data)
                );
            }
        }

        // empty out unsearchable Grids
        foreach ($unsearchable as $table => $columns) {
            ee()->db->update($table, $columns);
        }

        if (empty($search_data)) {
            return;
        }

        // repopulate the searchable Grids
        $entry_data = [];
        ee()->load->helper('custom_field_helper');

        foreach ($search_data as $table => $entries) {
            $entry_data = [];
            foreach ($entries as $entry_id => $fields) {
                $fields = array_map('encode_multi_field', $fields);

                $fields['entry_id'] = $entry_id;
                $entry_data[] = $fields;
            }

            ee()->db->update_batch($table, $entry_data, 'entry_id');
        }
    }

    /**
     * Search and replace a single Grid field's contents
     *
     * @param string $content_type Content type (typically 'channel')
     * @param int $field_id Grid field id
     * @param string $search String to search for in the Grid's rows
     * @param string $replace Replacement string
     * @return int Number of affected rows
     */
    public function search_and_replace($content_type, $field_id, $search, $replace)
    {
        $table = $this->_data_table($content_type, $field_id);
        $columns = $this->get_columns_for_field($field_id, 'channel');

        if (empty($columns)) {
            return 0;
        }

        $sql = "UPDATE `exp_{$table}` SET ";

        foreach ($columns as $column) {
            $column_name = 'col_id_' . $column['col_id'];
            $sql .= "`{$column_name}` = REPLACE(`{$column_name}`, '{$search}', '{$replace}'),";
        }

        $sql = rtrim($sql, ',');

        ee()->db->query($sql);

        return ee()->db->affected_rows();
    }
}

// EOF
