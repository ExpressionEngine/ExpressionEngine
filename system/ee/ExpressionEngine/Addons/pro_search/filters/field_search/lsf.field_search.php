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
class Pro_search_filter_field_search extends Pro_search_filter
{
    /**
     * Prefix
     */
    private $_pfx = 'search:';

    /**
     * Channel IDs
     */
    private $_channel_ids = array();

    // --------------------------------------------------------------------

    /**
     * Allows for search:title="foo|bar" parameter
     *
     * @access     private
     * @return     void
     */
    public function filter($entry_ids)
    {
        // --------------------------------------
        // Check if search:title is there
        // --------------------------------------

        $params = $this->params->get_prefixed($this->_pfx, true);
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
        // Set channel IDs
        // --------------------------------------

        $this->_channel_ids = ee()->pro_search_collection_model->get_channel_ids();

        $native_table = $this->fields->native_table();

        // --------------------------------------
        // Loop through search filters and prep queries accordingly
        // --------------------------------------

        $queries = array();

        foreach ($params as $key => $val) {
            // Make sure value is prepped correctly with exact/exclude/require_all values
            $val = $this->params->prep($this->_pfx . $key, $val);

            // Search channel_titles fields
            if ($this->fields->is_native($key)) {
                // (URL) Title search
                $queries[$native_table][] = $this->fields->sql($native_table . '.' . $key, $val);
            } elseif (strpos($key, ':')) {
                // Search grid or matrix cols
                list($field_name, $col_name) = explode(':', $key, 2);

                // Skip invalid fields
                if (! ($field_id = $this->fields->id($field_name))) {
                    continue;
                }

                $table = false;

                // Make sure it's an omelette!
                if (
                    $this->fields->is_grid($field_name) &&
                    ($col_id = $this->fields->grid_col_id($field_id, $col_name))
                ) {
                    $table = 'channel_grid_field_' . $field_id;
                    $field = $table . '.col_id_' . $col_id;
                } elseif (
                    $this->fields->is_matrix($field_name) &&
                    ($col_id = $this->fields->matrix_col_id($field_id, $col_name))
                ) {
                    $table = 'matrix_data';
                    $field = $table . '.col_id_' . $col_id;
                }

                if ($table) {
                    $queries[$table][] = $this->fields->sql($field, $val);
                }
            } elseif ($field_ids = $this->fields->ids($key)) {
                // Search custom channel fields
                $wheres = array();

                // One for each MSM site
                foreach ($field_ids as $site_id => $field_id) {
                    $field = $this->fields->get($field_id);
                    $table = $field->getDataStorageTable();

                    // Get where-clause
                    $where = $this->fields->sql($table . '.field_id_' . $field_id, $val);

                    // Enable Smart Field Searches?
                    $channel_ids = ($this->params->get('smart_field_search') == 'yes')
                        ? $this->_get_channel_ids_by_field($field)
                        : array();

                    // If so, add CASE to this statement
                    if (! empty($channel_ids)) {
                        $where = sprintf(
                            "(CASE WHEN {$native_table}.channel_id IN (%s) THEN %s ELSE {$native_table}.site_id = '%s' END)",
                            implode(', ', $channel_ids),
                            $where,
                            $site_id
                        );
                    }

                    $wheres[] = $where;
                }

                // And add the where clause to the queries
                $queries[$table][] = count($wheres) > 1
                    ? '(' . implode(' OR ', $wheres) . ')'
                    : current($wheres);
            }

            // For performance reasons, don't let EE perform the same search again
            $this->params->forget[] = $this->_pfx . $key;
        }

        // --------------------------------------
        // Where now contains a list of clauses
        // --------------------------------------

        if (empty($queries)) {
            return $entry_ids;
        }

        // --------------------------------------
        // Query the lot!
        // --------------------------------------

        ee()->db
            ->select($native_table . '.entry_id')
            ->from($native_table . ' as ' . $native_table);

        foreach ($queries as $table => $wheres) {
            // Join another table if necessary
            if ($table != $native_table) {
                ee()->db->join($table . ' as ' . $table, "{$table}.entry_id = {$native_table}.entry_id");
            }

            // Add wheres
            foreach ($wheres as $sql) {
                ee()->db->where($sql);
            }
        }

        // Limit by given entry ids?
        if (! empty($entry_ids)) {
            ee()->db->where_in($native_table . '.entry_id', $entry_ids);
        }

        // Limit to this lot
        // Limit by channel
        if ($this->_channel_ids) {
            ee()->db->where_in($native_table . '.channel_id', $this->_channel_ids);
        }

        // Limit by site
        if ($site_ids = $this->params->site_ids()) {
            ee()->db->where_in($native_table . '.site_id', $site_ids);
        }

        // Execute!
        $query = ee()->db->get();

        // Get entry IDs
        $entry_ids = pro_flatten_results($query->result_array(), 'entry_id');
        $entry_ids = array_unique($entry_ids);

        return $entry_ids;
    }

    // --------------------------------------------------------------------

    /**
     * Get channel IDs based on field ID
     */
    private function _get_channel_ids_by_field($field)
    {
        return $field->getAllChannels()->pluck('channel_id');
    }

    // --------------------------------------------------------------------

    /**
     * Results: remove rogue {pro_search_search:...} vars
     */
    public function results($query)
    {
        $this->_remove_rogue_vars($this->_pfx);

        return $query;
    }
}
// End of file lsf.field_search.php
