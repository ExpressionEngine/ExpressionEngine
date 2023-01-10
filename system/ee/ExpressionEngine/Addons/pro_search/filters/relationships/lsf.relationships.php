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
 * Filter by relationships
 */
class Pro_search_filter_relationships extends Pro_search_filter
{
    private $_include;
    private $_exclude;

    /**
     * Search parameters for (parent|child):field params and return set of ids that match it
     *
     * @access      public
     * @return      void
     */
    public function filter($entry_ids)
    {
        // --------------------------------------
        // Check prefixed parameters needed
        // --------------------------------------

        $rels = array_filter(array_merge(
            $this->params->get_prefixed('parent:'),
            $this->params->get_prefixed('child:')
        ));

        // --------------------------------------
        // Don't do anything if nothing's there
        // --------------------------------------

        if (empty($rels)) {
            return $entry_ids;
        }

        // --------------------------------------
        // Log it
        // --------------------------------------

        $this->_log('Applying ' . __CLASS__);

        // --------------------------------------
        // Set internal include property to $entry_ids, so it can play nice with exclude property
        // --------------------------------------

        $this->_include = $entry_ids;

        // --------------------------------------
        // Loop through relationships
        // --------------------------------------

        foreach ($rels as $key => $val) {
            // Split param into [child|parent] [field_name]
            list($type, $field) = explode(':', $key, 2);

            // Initiate some variables
            $field_id = $table = $grid = $col_id = $col_type = false;
            $where = array();
            $parent = ($type == 'parent');

            // Check if field consists of multiple names
            if (strpos($field, ':') !== false) {
                // We're in a grid
                list($field, $column) = explode(':', $field, 2);

                // ...are we really?
                if ($grid = $this->fields->is_grid($field)) {
                    // Get the grid field ID and column ID
                    $field_id = $this->fields->id($field);
                    $col_id = $this->fields->grid_col_id($field_id, $column);
                    $col_type = $this->fields->grid_col_type($field_id, $column);
                }

                // No valid column ID or type? Bail out
                if (! $col_id || ! in_array($col_type, array('relationship', 'playa'))) {
                    $this->_log("{$field}:{$column} is not a valid relationship column");

                    continue;
                }
            } elseif (! ($field_id = $this->fields->id($field))) {
                // We're not in a grid, just get the field ID
                $this->_log($field . ' is not a valid relationship field');

                continue;
            }

            // Native relationship field
            if ($this->fields->is_rel($field) || ($grid && $col_type == 'relationship')) {
                $table = 'relationships';
                $select = $parent ? 'child_id' : 'parent_id';
                $target = $parent ? 'parent_id' : 'child_id';

                if ($grid) {
                    $where['grid_field_id'] = $field_id;
                    $where['grid_col_id'] = $col_id;
                    $group = 'grid_row_id';
                } else {
                    $where['field_id'] = $field_id;
                    $group = $select;
                }
            } elseif ($this->fields->is_playa($field, true) || ($grid && $col_type == 'playa')) {
                // Support for playa or tax-playa
                $table = 'playa_relationships';
                $select = $parent ? 'child_entry_id' : 'parent_entry_id';
                $target = $parent ? 'parent_entry_id' : 'child_entry_id';

                if ($grid) {
                    $where['parent_field_id'] = $field_id;
                    $where['parent_col_id'] = col_id;
                    $group = 'parent_row_id';
                } else {
                    $where['parent_field_id'] = $field_id;
                    $group = $select;
                }
            }

            // Execute query
            if ($table) {
                // Prep the value
                $val = $this->params->prep($key, $val);

                // Get the parameter
                list($ids, $in) = $this->params->explode($val);

                // Match all?
                $all = (bool) strpos($val, '&');

                // Check if $ids are numeric
                if (! pro_array_is_numeric($ids)) {
                    // Log it!
                    $this->_log("Getting entry IDs for given relationship url_titles (field {$field_id})");

                    // Translate url_titles to IDs based on field
                    $ids = $this->_get_entry_ids($ids, $field_id, $parent);

                    if (empty($ids)) {
                        $this->_log('No valid entry IDs found');

                        return array();
                    }
                }

                // Start query
                ee()->db
                    ->select($select . ' AS entry_id')
                    ->from($table)
                    ->where_in($target, $ids);

                foreach ($where as $a => $b) {
                    ee()->db->where($a, $b);
                }

                // Limit by already existing ids
                if ($this->_include) {
                    ee()->db->where_in($select, $this->_include);
                }

                // Do the having-trick to account for *all* given entry ids
                if ($in && $all) {
                    ee()->db
                        ->select('COUNT(*) AS num')
                        ->group_by($group)
                        ->having('num', count($ids));
                }

                // Execute query
                $query = ee()->db->get();

                // And get the entry ids
                $ids = pro_flatten_results($query->result_array(), 'entry_id');
                $ids = array_unique($ids);

                // We're including, already filtered down by the query
                if ($in) {
                    $this->_include = $ids;
                } else {
                    // We're excluding; how exactly follows
                    // If no inclusive nor exclusive entry IDs are found yet, set found IDs to exclude list
                    if (empty($this->_include) && empty($this->_exclude)) {
                        $this->_exclude = $ids;
                    } elseif (empty($this->_include) && is_array($this->_exclude)) {
                        // No inclusive IDs yet, but there are existing exclude IDs; add these to them
                        $this->_exclude = array_unique(array_merge($this->_exclude, $ids));
                    } elseif (is_array($this->_include) && is_array($this->_exclude)) {
                        // There are IDs to include, but we're excluding. Subtract the IDs from the inclusive List
                        // and reset the exclusive list (as those have been processed now)
                        $this->_include = array_diff($this->_include, $this->_exclude);
                        $this->_exclude = null;
                    }
                }

                // Bail out if there aren't any (inclusive) matches
                if (is_array($this->_include) && empty($this->_include)) {
                    break;
                }
            }
        }

        return $this->_include;
    }

    /**
     * Get entry ids based on given url_titles
     */
    private function _get_entry_ids($ids, $field_id, $parent)
    {
        // Price to pay for using url_titles instead of IDs:
        // At least 1 query per parameter. At least it's fairly quick.
        $query = ee()->db
            ->select('entry_id')
            ->from('channel_titles')
            ->where_in('url_title', $ids)
            ->get();

        return pro_flatten_results($query->result_array(), 'entry_id');
    }

    /**
     * Return entry IDs to exclude
     */
    public function exclude()
    {
        return $this->_exclude;
    }

    /**
     * Results: remove rogue {pro_search_parent/child:...} vars
     */
    public function results($query)
    {
        $this->_remove_rogue_vars(array('parent:', 'child:'));

        return $query;
    }
}
