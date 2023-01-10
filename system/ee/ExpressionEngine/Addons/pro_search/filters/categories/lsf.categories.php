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
 * Filter by grouped categories
 */
class Pro_search_filter_categories extends Pro_search_filter
{
    /**
     * Prefix
     */
    private $_pfx = 'category:';

    /**
     * Allows for category groups filtering: (1|2|3) && (4|5|6)
     *
     * @access     public
     * @return     void
     */
    public function filter($entry_ids)
    {
        // --------------------------------------
        // See if there are groups present, with correct values
        // --------------------------------------

        $groups = $this->params->get_prefixed($this->_pfx);

        // Exception: remove category_groups param; leave that up to native parser
        unset($groups['category_group']);

        // --------------------------------------
        // Bail out if there are no groups
        // --------------------------------------

        if (empty($groups)) {
            return $entry_ids;
        }

        // --------------------------------------
        // Log it
        // --------------------------------------

        $this->_log('Applying ' . __CLASS__);

        // --------------------------------------
        // Loop through groups, compose SQL
        // --------------------------------------

        foreach ($groups as $key => $val) {
            // Prep the value
            $val = $this->params->prep($key, $val);

            // Get the parameter
            list($ids, $in) = $this->params->explode($val);

            // Match all?
            $all = (bool) strpos($val, '&');

            // If value is not numeric, get IDs from category names
            if (! pro_array_is_numeric($ids)) {
                $ids = $this->_get_entry_ids($ids, $key);

                if (empty($ids)) {
                    $this->_log('Could not find matching category IDs');

                    return array();
                }
            }

            // If we already have entries and we're excluding categories,
            // subtract them from the current result set
            $subtract = (!empty($entry_ids) && !$in);

            // One query per group
            ee()->db
                ->select('entry_id')
                ->distinct()
                ->from('category_posts')
                ->{$in || $subtract ? 'where_in' : 'where_not_in'}('cat_id', $ids);

            // Limit by already existing ids
            if ($entry_ids) {
                ee()->db->where_in('entry_id', $entry_ids);
            }

            // Do the having-trick to account for *all* given entry ids
            if (($in || $subtract) && $all) {
                ee()->db
                    ->select('COUNT(*) AS num')
                    ->group_by('entry_id')
                    ->having('num', count($ids));
            }

            // Execute query
            $query = ee()->db->get();

            // And get the entry ids
            $ids = pro_flatten_results($query->result_array(), 'entry_id');

            // Subtract the found ids from existing result set
            $entry_ids = $subtract ? array_diff($entry_ids, $ids) : $ids;

            // Bail out if there aren't any matches
            if (is_array($entry_ids) && empty($entry_ids)) {
                break;
            }

            // For performance reasons, don't let EE perform the same search again
            $this->params->forget[] = $key;
        }

        return $entry_ids;
    }

    // --------------------------------------------------------------------

    /**
     * Results: remove rogue {pro_search_category:...} vars
     */
    public function results($query)
    {
        $this->_remove_rogue_vars($this->_pfx);

        return $query;
    }

    // --------------------------------------------------------------------

    /**
     * Get entry ids based on given cat_url_titles
     */
    private function _get_entry_ids($ids, $key)
    {
        $this->_log('Getting category IDs for ' . $key);

        // Start query
        ee()->db
            ->select('cat_id')
            ->from('categories')
            ->where_in('cat_url_title', $ids)
            ->where_in('site_id', $this->params->site_ids());

        // Limit by group ID? Only for category:1="foo|bar"
        if (strpos($key, ':') !== false) {
            list($pfx, $group) = explode(':', $key);

            // Only limit if group definition is numeric, so it refers to a group ID
            if (is_numeric($group)) {
                ee()->db->where('group_id', $group);
            }
        }

        // Go!
        $query = ee()->db->get();

        return pro_flatten_results($query->result_array(), 'cat_id');
    }
}
// End of file lsf.categories.php
