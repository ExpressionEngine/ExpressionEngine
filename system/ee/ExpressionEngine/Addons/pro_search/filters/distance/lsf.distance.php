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
 * Filter by distance:foo="bar"
 */
class Pro_search_filter_distance extends Pro_search_filter
{
    // Usage:
    // distance:from="lat|long"
    // distance:to="lat_field|long_field"
    // distance:radius="10"
    // distance:unit="km"

    /**
     * Results cached
     */
    private $_results;

    /**
     * Search parameters for distance: params
     *
     * @access      private
     * @return      void
     */
    public function filter($entry_ids)
    {
        // --------------------------------------
        // Reset
        // --------------------------------------

        $this->_results = array();

        // --------------------------------------
        // Get distance params
        // --------------------------------------

        $params = $this->params->get_prefixed('distance:', true);
        $params = array_filter($params, 'pro_not_empty');

        // --------------------------------------
        // Check required params
        // --------------------------------------

        foreach (array('from', 'to') as $key) {
            if (empty($params[$key])) {
                return $entry_ids;
            }
        }

        // --------------------------------------
        // Log it
        // --------------------------------------

        $this->_log('Applying ' . __CLASS__);

        // --------------------------------------
        // Get channel IDs before starting the query
        // --------------------------------------

        $channel_ids = ee()->pro_search_collection_model->get_channel_ids();

        // --------------------------------------
        // Get from coords
        // --------------------------------------

        list($lat, $long) = preg_split('/[|,]/', $params['from'], 2, PREG_SPLIT_NO_EMPTY);

        // --------------------------------------
        // Unit
        // --------------------------------------

        if (empty($params['unit'])) {
            $params['unit'] = 'km';
        }

        // Radius of the earth
        switch ($params['unit']) {
            case 'mi':
                $R = 3959;

                break;

            case 'yd':
                $R = 6967410;

                break;

            case 'm':
                $R = 6371000;

                break;

            default: // km
                $R = 6371;
        }

        // --------------------------------------
        // Get reference to fields from params
        // --------------------------------------

        $tables = array();
        $fields = preg_split('/[|,]/', $params['to'], 2, PREG_SPLIT_NO_EMPTY);
        $single = (count($fields) == 1);

        if ($single) {
            // Make sure we have a valid field
            if (! ($field = $this->fields->name($fields[0]))) {
                $this->_log($fields[0] . ' field not found');

                return $entry_ids;
            }

            $table = $this->fields->table($fields[0]);
            $field = $table . '.' . $field;
            $tables[] = $table;

            // Lat/long fields based on a single field
            $lat_field = "CONVERT(SUBSTRING({$field}, 1, LOCATE(',', {$field}) - 1), DECIMAL(12,8))";
            $long_field = "CONVERT(SUBSTRING({$field}, LOCATE(',', {$field}) + 1), DECIMAL(12,8))";
        } else {
            // Get both field IDs
            $lat_field = $this->fields->name($fields[0]);
            $long_field = $this->fields->name($fields[1]);

            // Validate
            if (! ($lat_field && $long_field)) {
                $this->_log('Lat/Long field combo invalid');

                return $entry_ids;
            }

            $lat_table = $this->fields->table($fields[0]);
            $long_table = $this->fields->table($fields[1]);

            $tables[] = $lat_table;
            $tables[] = $long_table;

            $lat_field = $lat_table . '.' . $lat_field;
            $long_field = $long_table . '.' . $long_field;
        }

        // --------------------------------------
        // The distance SQL statement: Haversine formula
        // --------------------------------------

        $haversine
            = '(%d * acos(cos(radians(%2$f)) * cos(radians(%4$s)) * cos(radians(%5$s) - radians(%3$f))'
            . ' + sin(radians(%2$f)) * sin(radians(%4$s)))) AS distance';

        $native_table = $this->fields->native_table();

        // Get entry IDs and their distances
        ee()->db
            ->select(array($native_table . '.entry_id', sprintf($haversine, $R, $lat, $long, $lat_field, $long_field)), false)
            ->from($native_table . ' as ' . $native_table)
            ->order_by('distance', 'asc');

        // Join another tables as necessary
        foreach (array_unique($tables) as $table) {
            ee()->db->join($table . ' as ' . $table, "{$table}.entry_id = {$native_table}.entry_id", 'left');
        }

        // --------------------------------------
        // Optimization
        // --------------------------------------

        if ($single) {
            ee()->db->where($field . ' !=', '');
        } else {
            ee()->db->where("({$lat_field} OR {$long_field})");
        }

        // --------------------------------------
        // Limit by site ids?
        // --------------------------------------

        if ($site_ids = $this->params->site_ids()) {
            ee()->db->where_in($native_table . '.site_id', $site_ids);
        }

        // --------------------------------------
        // Limit by channel ids?
        // --------------------------------------

        if ($channel_ids) {
            ee()->db->where_in($native_table . '.channel_id', $channel_ids);
        }

        // --------------------------------------
        // Limit by entry ids?
        // --------------------------------------

        if ($entry_ids) {
            ee()->db->where_in($native_table . '.entry_id', $entry_ids);
        }

        // --------------------------------------
        // Limit by radius?
        // --------------------------------------

        if (! empty($params['radius'])) {
            ee()->db->having('distance <=', $params['radius']);
        }

        // --------------------------------------
        // Execute!
        // --------------------------------------

        $query = ee()->db->get();

        $this->_results = pro_flatten_results($query->result_array(), 'distance', 'entry_id');

        return array_keys($this->_results);
    }

    // --------------------------------------------------------------------

    /**
     * Fixed order?
     */
    public function fixed_order()
    {
        $fixed = false;

        // --------------------------------------
        // Is there a custom sort order?
        // If so, set the entry_id param instead of the fixed_order
        // --------------------------------------

        if ($this->_results) {
            $orderby = $this->params->get('orderby', 'pro_search_distance');

            if (substr($orderby, 0, 19) == 'pro_search_distance') {
                $fixed = true;
            }
        }

        return $fixed;
    }

    // --------------------------------------------------------------------

    /**
     * Modify rows for a search result for this filter
     */
    public function results($rows)
    {
        if ($this->_results) {
            // Populate rows with the distance value
            $pfx = ee()->pro_search_settings->prefix;

            foreach ($rows as &$row) {
                $row[$pfx . 'distance'] = isset($this->_results[$row['entry_id']])
                    ? round($this->_results[$row['entry_id']])
                    : '';
            }
        } else {
            // Remove any distance-specific vars
            $this->_remove_rogue_vars('distance');
        }

        return $rows;
    }

    // --------------------------------------------------------------------
}
