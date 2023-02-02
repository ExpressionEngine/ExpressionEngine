<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Filter by search:title="foo"
 */
class Pro_search_filter_ranges extends Pro_search_filter
{
    /**
     * Prefixes
     */
    private $_pfxs = array(
        'range:',
        'range-from:',
        'range-to:'
    );

    /**
     * Separator character for ranges
     */
    private $_sep = '|';

    /**
     * Current ranges
     */
    private $_ranges;

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Search parameters for range:field params and return set of ids that match it
     *
     * @access      private
     * @return      void
     */
    public function filter($entry_ids)
    {
        // --------------------------------------
        // Reset ranges
        // --------------------------------------

        $this->_ranges = $params = array();

        // --------------------------------------
        // Get ranges params
        // --------------------------------------

        foreach ($this->_pfxs as $pfx) {
            $params = array_merge($params, $this->params->get_prefixed($pfx));
        }

        $params = array_filter($params, 'pro_not_empty');

        // --------------------------------------
        // Don't do anything if nothing's there
        // --------------------------------------

        if (empty($params)) {
            return $entry_ids;
        }

        // --------------------------------------
        // Log it
        // --------------------------------------

        $this->_log('Applying ' . __CLASS__);

        // --------------------------------------
        // Load this, to be on the safe side
        // --------------------------------------

        ee()->load->library('localize');

        // --------------------------------------
        // Collect ranges
        // --------------------------------------

        foreach ($params as $key => $val) {
            // remember original parameter
            $param = $key;

            // Split key into prefix and the rest of the key
            list($pfx, $key) = explode(':', $key, 2);

            // If key has a colon, it could be grid/matrix OR reverse range
            if (strpos($key, ':')) {
                list($field1, $field2) = explode(':', $key, 2);

                // Grid field?
                if (
                    ($id = $this->fields->id($field1)) && $this->fields->is_grid($field1) &&
                    ($col_id = $this->fields->grid_col_id($id, $field2))
                ) {
                    // Add range filter
                    $this->_add_range($param, $val, 'channel_grid_field_' . $id, 'col_id_' . $col_id);
                } elseif (
                    // Matrix field?
                    ($id = $this->fields->id($field1)) && $this->fields->is_matrix($field1) &&
                    ($col_id = $this->fields->matrix_col_id($id, $field2))
                ) {
                    $this->_add_range($param, $val, 'matrix_data', 'col_id_' . $col_id);
                } else {
                    // Possible reverse range
                    // Check both fields for validity
                    foreach (array($field1, $field2) as $i => $field) {
                        if ($this->fields->is_native($field)) {
                            $table = $this->fields->native_table();
                            $col = $field;
                        } elseif ($id = $this->fields->id($field)) {
                            $f = $this->fields->get($id);
                            $table = $f->getDataStorageTable();
                            $col = 'field_id_' . $id;
                        }

                        $p = ($i == 0) ? 'range-from:' : 'range-to:';

                        if (! empty($table)) {
                            $this->_add_range($p . $key, $val, $table, $col);
                        }
                    }
                }
            } elseif ($this->fields->is_native($key)) {
                // Targeting a native field here
                $this->_add_range($param, $val, $this->fields->native_table(), $key);
            } elseif ($field = $this->fields->get($key)->first()) {
                // Regular old custom fields
                $this->_add_range($param, $val, $field->getDataStorageTable(), 'field_id_' . $field->field_id);
            }
        }

        // --------------------------------------
        // No ranges, bail out
        // --------------------------------------

        if (empty($this->_ranges)) {
            $this->_log('No valid ranges found');

            return $entry_ids;
        }

        // --------------------------------------
        // Get channel IDs before starting the query
        // --------------------------------------

        $channel_ids = ee()->pro_search_collection_model->get_channel_ids();

        // --------------------------------------
        // Query each table once
        // --------------------------------------

        $nt = $this->fields->native_table();

        // Always query the native table
        ee()->db->select($nt . '.entry_id')
            ->from($nt . ' as ' . $nt);

        foreach ($this->_ranges as $table => $wheres) {
            if ($table != $nt) {
                ee()->db->join($table . ' as ' . $table, "{$table}.entry_id = {$nt}.entry_id", 'left');
            }

            foreach ($wheres as $key => $val) {
                ee()->db->where($key, $val);
            }
        }

        // Limit by given entry ids?
        if (! empty($entry_ids)) {
            ee()->db->where_in($nt . '.entry_id', $entry_ids);
        }

        // Limit by channel
        if ($channel_ids) {
            ee()->db->where_in($nt . '.channel_id', $channel_ids);
        }

        // Limit by site
        if ($site_ids = $this->params->site_ids()) {
            ee()->db->where_in($nt . '.site_id', $site_ids);
        }

        // Thunderbirds are GO!
        $query = ee()->db->get();

        // And get the entry ids
        $entry_ids = pro_flatten_results($query->result_array(), 'entry_id');
        $entry_ids = array_unique($entry_ids);

        // --------------------------------------
        // Return it dawg
        // --------------------------------------

        return $entry_ids;
    }

    // --------------------------------------------------------------------

    /**
     * Add range to class property
     */
    private function _add_range($param, $val, $table, $col)
    {
        // Get prefix from param
        list($pfx, $field) = explode(':', $param, 2);

        // Are we excluding this parameter
        $exclude = $this->params->in_param($param, 'exclude');

        if ($pfx == 'range-from') {
            $val = $this->_validate_value($val, $field);

            if (! is_null($val)) {
                $op = $exclude ? ' >' : ' >=';
                $this->_ranges[$table][$table . '.' . $col . $op] = $val;
            }
        } elseif ($pfx == 'range-to') {
            $val = $this->_validate_value($val, $field);

            if (! is_null($val)) {
                $op = $exclude ? ' <' : ' <=';
                $this->_ranges[$table][$table . '.' . $col . $op] = $val;
            }
        } else {
            // Range
            // Fallback to semi-colon for backward compatibility
            $char = (strpos($val, ';') !== false) ? ';' : $this->_sep;

            // Set from/to vals or point val based on separator
            foreach (explode($char, $val, 2) as $i => $v) {
                $v = $this->_validate_value($v, $field);

                if (! is_null($v)) {
                    $op = ($i == 0) ? ' >=' : ' <=';
                    if ($exclude) {
                        $op = rtrim($op, '=');
                    }
                    $this->_ranges[$table][$table . '.' . $col . $op] = $v;
                }
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Validate range value
     */
    private function _validate_value($val, $field)
    {
        // If value already is numeric or NULL, return that
        if (is_numeric($val) || is_null($val)) {
            return $val;
        }

        // Check field for colons
        if ($i = strpos($field, ':')) {
            $field = substr($field, 0, $i);
        }

        if ($this->fields->is_date($field) || $this->fields->is_grid($field) || $this->fields->is_matrix($field)) {
            return ee()->localize->string_to_timestamp($val);
        }

        return null;
    }

    // --------------------------------------------------------------------

    /**
     * Results: remove rogue {pro_search_range...:...} vars
     */
    public function results($query)
    {
        $this->_remove_rogue_vars($this->_pfxs);

        return $query;
    }
}
// End of file lsf.ranges.php
