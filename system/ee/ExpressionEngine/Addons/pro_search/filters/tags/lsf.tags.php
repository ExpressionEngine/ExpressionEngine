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
 * Filter by tag / tagger
 */
class Pro_search_filter_tags extends Pro_search_filter
{
    /**
     * Allows for tag filtering: (1|2|3) && (4|5|6)
     *
     * @access     public
     * @return     void
     */
    public function filter($entry_ids)
    {
        // -------------------------------------------
        // Make sure addons-library is loaded
        // -------------------------------------------

        ee()->load->library('addons');

        // -------------------------------------------
        // Solspace Tag or DevDemon Tagger?
        // -------------------------------------------

        if (ee()->addons->is_package('tag')) {
            $tables = array(
                'tag_tags',
                'tag_entries'
            );
        } elseif (ee()->addons->is_package('tagger')) {
            $tables = array(
                'tagger',
                'tagger_links'
            );
        }

        // --------------------------------------
        // See if there are tag params present
        // --------------------------------------

        $tag_names = array_filter($this->params->get_prefixed('tag_name'), 'pro_not_empty');
        $tag_ids = array_filter($this->params->get_prefixed('tag_id'), 'pro_not_empty');

        // --------------------------------------
        // Bail out if there are no tags
        // --------------------------------------

        if (empty($tables) || (empty($tag_names) && empty($tag_ids))) {
            return $entry_ids;
        }

        // --------------------------------------
        // Log it
        // --------------------------------------

        $this->_log('Applying ' . __CLASS__);

        // -------------------------------------------
        // Check tag names and convert to tag IDs
        // -------------------------------------------

        if ($tag_names) {
            $unique_tags = array();

            foreach ($tag_names as $key => $val) {
                // Get the tags
                list($tags, $in) = $this->params->explode($val);

                $unique_tags = array_merge($unique_tags, $tags);
            }

            // Remove duplicates and convert
            $unique_tags = array_unique($unique_tags);
            $unique_tags = array_map(array($this, '_convert_tag'), $unique_tags);

            // Get IDs for unique tags
            $query = ee()->db->select('tag_id, tag_name')
                ->from($tables[0])
                ->where_in('site_id', $this->params->site_ids())
                ->where_in('tag_name', $unique_tags)
                ->get();

            // clean up
            unset($unique_tags);

            // Get tag map: [tag name] => tag_id
            $tag_map = pro_flatten_results($query->result_array(), 'tag_id', 'tag_name');

            // Now, loop through original tags thing and convert to tag IDs
            foreach ($tag_names as $key => $val) {
                // Initiate tag ids
                $ids = array();

                // Read parameter value
                list($tags, $in) = $this->params->explode($val);

                // Loop through tags and map them to IDs
                foreach ($tags as $tag) {
                    $tag = $this->_convert_tag($tag);

                    // Convert non-existing tags to -1 to allow for combining valid and invalid tags
                    $ids[] = isset($tag_map[$tag]) ? $tag_map[$tag] : '-1';
                }

                if ($ids) {
                    // Check separator and implode back to parameter
                    $sep = (strpos($val, '&') === false) ? '|' : '&';
                    $str = implode($sep, $ids);

                    // Add negator back
                    if (! $in) {
                        $str = 'not ' . $ids;
                    }

                    // Add final parameter string to IDs
                    $tag_ids[$key] = $str;
                }
            }
        }

        // --------------------------------------
        // Get channel IDs before starting the query
        // --------------------------------------

        $channel_ids = ee()->pro_search_collection_model->get_channel_ids();

        // --------------------------------------
        // Loop through groups, compose SQL
        // --------------------------------------

        foreach ($tag_ids as $key => $val) {
            // Prep the value
            $val = $this->params->prep($key, $val);

            // Get the parameter
            list($ids, $in) = $this->params->explode($val);

            // Match all?
            $all = (bool) strpos($val, '&');

            // One query per group
            ee()->db->distinct()
                ->select('entry_id')
                ->from($tables[1])
                ->where_in('site_id', $this->params->site_ids())
                ->{$in ? 'where_in' : 'where_not_in'}('tag_id', $ids);

            // Limit by already existing ids
            if ($entry_ids) {
                ee()->db->where_in('entry_id', $entry_ids);
            }

            // Limit by channel ID
            if ($channel_ids) {
                ee()->db->where_in('channel_id', $channel_ids);
            }

            // Do the having-trick to account for *all* given entry ids
            if ($in && $all) {
                ee()->db->select('COUNT(*) AS num')
                    ->group_by('entry_id')
                    ->having('num', count($ids));
            }

            // Execute query
            $query = ee()->db->get();

            // And get the entry ids
            $entry_ids = pro_flatten_results($query->result_array(), 'entry_id');

            // Bail out if there aren't any matches
            if (is_array($entry_ids) && empty($entry_ids)) {
                break;
            }
        }

        return $entry_ids;
    }

    // --------------------------------------------------------------------

    /**
     * Results: remove rogue {pro_search_tag_id...} and {pro_search_tag_name...} vars
     */
    public function results($query)
    {
        $this->_remove_rogue_vars('tag_id');
        $this->_remove_rogue_vars('tag_name');

        return $query;
    }

    /**
     * Convert websave tag
     */
    private function _convert_tag($str)
    {
        return strtolower(str_replace($this->params->get('websafe_separator', '+'), ' ', $str));
    }
}
// End of file lsf.tags.php
