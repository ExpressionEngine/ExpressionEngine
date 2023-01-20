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
 * Pro Search Index class
 */
class Pro_search_index
{
    /**
     * Keep track of found fields
     */
    private $_fields = array();

    // --------------------------------------------------------------------

    /**
     * Build index for given entry or entries
     *
     * @access     public
     * @param      mixed     int or array of ints
     * @return     bool
     */
    public function build_by_entry($entry_ids, $build = null)
    {
        // --------------------------------------
        // Force array
        // --------------------------------------

        if (! is_array($entry_ids)) {
            $entry_ids = array($entry_ids);
        }

        // --------------------------------------
        // Clean up
        // --------------------------------------

        $entry_ids = array_filter($entry_ids);

        // --------------------------------------
        // Bail out if nothing given
        // --------------------------------------

        if (empty($entry_ids)) {
            return false;
        }

        // --------------------------------------
        // Get collections for these entries
        // --------------------------------------

        $query = ee()->db->select('t.entry_id, c.collection_id')
            ->from('channel_titles t')
            ->join('pro_search_collections c', 't.channel_id = c.channel_id')
            ->where_in('t.entry_id', $entry_ids)
            ->get();

        // --------------------------------------
        // No collections? Bail.
        // --------------------------------------

        if (! $query->num_rows()) {
            return false;
        }

        // --------------------------------------
        // Collect in array
        // --------------------------------------

        $rows = array();

        foreach ($query->result() as $row) {
            $rows[$row->collection_id][] = $row->entry_id;
        }

        // --------------------------------------
        // Call build_collection for each found
        // --------------------------------------

        foreach ($rows as $collection_id => $entry_ids) {
            $this->build_by_collection($collection_id, $entry_ids);
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Build given collection in batches
     *
     * @access     public
     * @param      int
     * @param      int
     * @return     mixed     bool or int
     */
    public function build_batch($collection_id, $start = 0, $build = null)
    {
        // --------------------------------------
        // None given or invalid? Bail out
        // --------------------------------------

        if (! ($col = ee()->pro_search_collection_model->get_by_id($collection_id))) {
            return false;
        }

        // Focus on the one
        $col = $col[$collection_id];

        // --------------------------------------
        // Get total number entry IDs for collection's channel
        // --------------------------------------

        $total = ee()->db->from('channel_titles')
            ->where('channel_id', $col['channel_id'])
            ->count_all_results();

        $batch = ee()->pro_search_settings->get('batch_size');

        // --------------------------------------
        // Get batch entry IDs for this collection
        // --------------------------------------

        $query = ee()->db->select('entry_id')
            ->from('channel_titles')
            ->where('channel_id', $col['channel_id'])
            ->order_by('entry_id')
            ->limit($batch, $start)
            ->get();

        $entry_ids = pro_flatten_results($query->result_array(), 'entry_id');

        // --------------------------------------
        // Call build by collection, with given batch
        // --------------------------------------

        $ok = $this->build_by_collection($collection_id, $entry_ids, $build);

        // --------------------------------------
        // Return new start position or TRUE if finished
        // --------------------------------------

        if ($ok) {
            // New start position
            $start = $start + $batch;

            // Or TRUE if finished
            if ($start >= $total) {
                $start = true;
            }
        } else {
            $start = false;
        }

        return $start;
    }

    // --------------------------------------------------------------------

    /**
     * Build index by given collection ID, optionally limited by given IDs
     *
     * @access     public
     * @param      int
     * @param      array
     * @return     bool
     */
    public function build_by_collection($collection_id, $entry_ids = array(), $build = null)
    {
        // --------------------------------------
        // Get collection details
        // --------------------------------------

        if (! ($cols = ee()->pro_search_collection_model->get_by_id($collection_id))) {
            return false;
        }

        // Focus on the one
        $col = $cols[$collection_id];

        // --------------------------------------
        // Select what from entries?
        // --------------------------------------

        $fields = array_keys($col['settings']);
        $select = $field_ids = $cat_fields = array();
        $select[] = 'entry_id';
        $select[] = 'channel_id';

        foreach ($fields as $id) {
            // Title field
            if ($id == '0') {
                $select[] = 'title';
            } elseif (is_numeric($id)) {
                // Regular fields are numeric
                $select[] = 'field_id_' . $id;
                if ($id) {
                    $field_ids[] = $id;
                }
            } else {
                // Non-numeric fields are category fields
                // Split in group and field ID and add to cats
                list($group, $id) = explode(':', $id);
                if (is_numeric($id)) {
                    $id = 'field_id_' . $id;
                }
                if (! in_array($id, $cat_fields)) {
                    $cat_fields[] = $id;
                }
            }
        }

        // --------------------------------------
        // Get entries for this collection
        // --------------------------------------

        if (ee()->extensions->active_hook('pro_search_get_index_entries') === true) {
            $entries = ee()->extensions->call('pro_search_get_index_entries', $col, $entry_ids);
        } else {
            $builder = ee('Model')
                ->get('ChannelEntry')
                ->filter('channel_id', $col['channel_id']);

            // Only these fields
            foreach ($select as $field) {
                $builder->fields($field);
            }

            // Optionally limit by given entry IDs
            if (is_array($entry_ids) && ! empty($entry_ids)) {
                $builder->filter('entry_id', 'IN', $entry_ids);
            }

            $entries = [];

            // Compose the entries
            foreach ($builder->all() as $row) {
                $entry = [];
                foreach ($select as $key) {
                    $val = $row->$key;
                    if ($key == 'title') {
                        $key = 'field_id_0';
                    }
                    $entry[$key] = $val;
                }
                $entries[$row->entry_id] = $entry;
            }

            // Get categories for the found entries
            foreach ($this->get_entry_categories(array_keys($entries), $cat_fields) as $key => $val) {
                $entries[$key] += $val;
            }
        }

        // --------------------------------------
        // Load the fields
        // --------------------------------------

        $this->_load_fields($field_ids);

        // --------------------------------------
        // Load words lib
        // --------------------------------------

        ee()->load->library('Pro_search_words');

        // --------------------------------------
        // Build index for each entry
        // --------------------------------------

        // Seen words for cache
        static $seen = array();
        $index = $lexicon = array();

        // batch-insert 100 at a time
        $batch = 100;

        foreach ($entries as $entry) {
            // Make sure all fields have their content (check fieldtypes)
            $entry = $this->_prep_entry($col, $entry);

            // --------------------------------------
            // Optionally build lexicon
            // --------------------------------------

            if ($col['language'] && $build != 'index') {
                // Get the words for the lexicon
                $words = explode(' ', implode(' ', $entry));
                $words = array_filter($words, array(ee()->pro_search_words, 'is_valid'));
                $words = array_unique($words);

                // Diff 'em from the words we've already encountered or ignoring
                $words = array_diff($words, $seen);
                $words = array_diff($words, ee()->pro_search_settings->ignore_words());

                // And remember what we've seen
                $seen = array_merge($seen, $words);

                // Build lexicon
                foreach ($words as $word) {
                    // Get clean word
                    $clean = ee()->pro_search_words->remove_diacritics($word);

                    // Get sound of word
                    $sound = soundex($word);

                    // Compose row
                    $data = array(
                        'site_id'  => $col['site_id'],
                        'word'     => $word,
                        'language' => $col['language'],
                        'length'   => ee()->pro_multibyte->strlen($word),
                        'sound'    => ($sound == '0000' ? null : $sound),
                        'clean'    => ($word == $clean ? null : $clean)
                    );

                    // --------------------------------------
                    // 'pro_search_update_lexicon' hook
                    // - Add additional attributes to the lexicon
                    // --------------------------------------

                    if (ee()->extensions->active_hook('pro_search_update_lexicon') === true) {
                        $ext_data = ee()->extensions->call('pro_search_update_lexicon', $data);

                        if (is_array($ext_data) && ! empty($ext_data)) {
                            $data = array_merge($data, $ext_data);
                        }
                    }

                    // Add row
                    $lexicon[] = $data;
                }

                if (count($lexicon) >= $batch) {
                    ee()->pro_search_word_model->insert_ignore_batch($lexicon);
                    $lexicon = array();
                }
            }

            // --------------------------------------
            // Optionally build index
            // --------------------------------------

            if ($build != 'lexicon') {
                // --------------------------------------
                // Apply weight to the entry
                // --------------------------------------

                $text = $this->_get_weighted_text($col, $entry);
                $text = ee()->pro_search_words->remove_diacritics($text);

                // Compose data to insert
                $data = array(
                    'collection_id' => $col['collection_id'],
                    'entry_id'      => $entry['entry_id'],
                    'site_id'       => $col['site_id'],
                    'index_text'    => $text,
                    'index_date'    => ee()->localize->now
                );

                // --------------------------------------
                // 'pro_search_update_index' hook
                // - Add additional attributes to the index
                // --------------------------------------

                if (ee()->extensions->active_hook('pro_search_update_index') === true) {
                    $ext_data = ee()->extensions->call('pro_search_update_index', $data, $entry);

                    if (is_array($ext_data) && ! empty($ext_data)) {
                        $data = array_merge($data, $ext_data);
                    }
                }

                // --------------------------------------
                // Add data to rows for batch replace
                // --------------------------------------

                $index[] = $data;

                // --------------------------------------
                // If we're at a batch size, insert 'em
                // --------------------------------------

                if (count($index) == $batch) {
                    ee()->pro_search_index_model->replace_batch($index);

                    // and reset the rows
                    $index = array();
                }
            }
        }

        // --------------------------------------
        // Insert left-overs
        // --------------------------------------

        if ($lexicon) {
            ee()->pro_search_word_model->insert_ignore_batch($lexicon);
        }

        if ($index) {
            ee()->pro_search_index_model->replace_batch($index);
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Get categories for entries
     *
     * @access     public
     * @param      array
     * @param      array
     * @return     array
     */
    public function get_entry_categories($entry_ids, $fields)
    {
        // --------------------------------------
        // Prep output
        // --------------------------------------

        $cats = array();

        if (empty($entry_ids) || empty($fields)) {
            return $cats;
        }

        // Get category IDs for given entries
        $query = ee()->db->select('entry_id, cat_id')
            ->from('category_posts')
            ->where_in('entry_id', $entry_ids)
            ->get();

        $map = $query->result_array();
        $cat_ids = array_unique(pro_flatten_results($map, 'cat_id'));

        // Bail out if there aren't any categories assigned here
        if (empty($cat_ids)) {
            return $cats;
        }

        // --------------------------------------
        // Get all categories plus data for given entries
        // --------------------------------------

        $all = ee('Model')->get('Category')
            ->filter('cat_id', 'IN', $cat_ids)
            ->all()->indexBy('cat_id');

        // --------------------------------------
        // Done with the query; loop through results
        // --------------------------------------

        foreach ($map as $row) {
            $entry_id = $row['entry_id'];
            $cat_id = $row['cat_id'];

            $cat = isset($all[$cat_id]) ? $all[$cat_id]->toArray() : null;

            if (empty($cat)) {
                continue;
            }

            // Loop through each of the fields and populate cats array
            foreach ($fields as $key) {
                // Skip non-valid fields or empty ones
                if (empty($cat[$key])) {
                    continue;
                }

                // Get the value
                $val = $cat[$key];

                // Set field to ID if applicable
                if (preg_match('/^field_id_(\d+)$/', $key, $match)) {
                    $key = $match[1];
                }

                // Use that as the key in the array to return
                $cats[$entry_id]["{$cat['group_id']}:{$key}"][$cat_id] = $val;
            }
        }

        return $cats;
    }

    // --------------------------------------------------------------------

    /**
     * Load fieldtypes for given field IDs -- populates $this->_fields
     *
     * @access     private
     * @param      array
     * @return     void
     */
    private function _load_fields($field_ids)
    {
        // --------------------------------------
        // Load addon/fieldtype files
        // --------------------------------------

        ee()->load->library('addons');

        // Include EE Fieldtype class
        if (! class_exists('EE_Fieldtype')) {
            include_once(APPPATH . 'fieldtypes/EE_Fieldtype.php');
        }

        // --------------------------------------
        // Initiate fieldtypes var
        // --------------------------------------

        static $fieldtypes;

        // Set fieldtypes
        if ($fieldtypes === null) {
            $fieldtypes = ee()->addons->get_installed('fieldtypes');
        }

        // --------------------------------------
        // Check for ids we haven't dealt with yet
        // --------------------------------------

        $not_encountered = array_diff($field_ids, array_keys($this->_fields));

        if (empty($not_encountered)) {
            return;
        }

        // --------------------------------------
        // Get the details for not encountered fields
        // --------------------------------------

        $query = ee()->db->select()
            ->from('channel_fields')
            ->where_in('field_id', $not_encountered)
            ->get();

        foreach ($query->result() as $field) {
            // Shortcut to fieldtype
            $ftype = $fieldtypes[$field->field_type];

            // Include the file if it doesn't yet exist
            if (! class_exists($ftype['class']) && file_exists($ftype['path'] . $ftype['file'])) {
                require $ftype['path'] . $ftype['file'];
            }

            // Only initiate the fieldtypes that have the necessary method
            if (method_exists($ftype['class'], 'third_party_search_index')) {
                // Initiate this fieldtype
                $obj = new $ftype['class']();

                // Add settings to object
                if ($settings = @unserialize(base64_decode($field->field_settings))) {
                    $settings = array_merge((array) $field, $settings);
                }

                // Set this instance's settings
                $obj->settings = $settings;
            } else {
                $obj = true;
            }

            // Record the field
            $this->_fields[$field->field_id] = $obj;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Make sure all the fields in the entry have their content
     *
     * @access     private
     * @param      array     collection
     * @param      array     entry
     * @return     string
     */
    private function _prep_entry($col, $entry)
    {
        // --------------------------------------
        // Loop through the entry's keys and fire the third_party method, if present
        // --------------------------------------

        foreach (array_keys($entry) as $field_name) {
            // Skip entry_id
            if ($field_name == 'entry_id') {
                continue;
            }

            // --------------------------------------
            // Determine proper field id
            // --------------------------------------

            $field_id = (preg_match('/^field_id_(\d+)$/', $field_name, $match))
                ? $match[1]
                : false;

            // --------------------------------------
            // Fire third party thingie for this field?
            // --------------------------------------

            if ($field_id && array_key_exists($field_id, $this->_fields) && is_object($this->_fields[$field_id])) {
                // Extra settings per entry
                $settings = array(
                    'entry_id'      => $entry['entry_id'],
                    'collection_id' => $col['collection_id']
                );

                // Merge the extra settings
                $this->_fields[$field_id]->settings = array_merge(
                    $this->_fields[$field_id]->settings,
                    $settings
                );

                // If fieldtype exists, it will have the correct method, so call that
                $entry[$field_name] = $this->_fields[$field_id]->third_party_search_index($entry[$field_name]);
            }

            // --------------------------------------
            // Get the value for this field and force arry
            // --------------------------------------

            $val = (array) $entry[$field_name];

            // Clean up the values
            $val = array_map(array(ee()->pro_search_words, 'clean'), $val);

            // And turn back into a string
            $val = implode(' | ', $val);

            // Set it to the entry's value
            $entry[$field_name] = trim($val);
        }

        // --------------------------------------
        // Filter out empty values
        // --------------------------------------

        $entry = array_filter($entry);

        // --------------------------------------
        // Return the prep'ed entry again
        // --------------------------------------

        return $entry;
    }

    // --------------------------------------------------------------------

    /**
     * Get index text based in given entry
     *
     * @access     private
     * @param      array     collection
     * @param      array     entry
     * @return     string
     */
    private function _get_weighted_text($col, $entry)
    {
        // --------------------------------------
        // Init text array which will contain the index
        // and weight separator
        // --------------------------------------

        $text = array();
        $sep = ' | ';

        // --------------------------------------
        // Loop through settings and add weight to field by repeating string
        // --------------------------------------

        foreach ($entry as $key => $val) {
            // Skip entry_id
            if ($key == 'entry_id' || empty($val)) {
                continue;
            }

            // --------------------------------------
            // Determine proper settings ID
            // --------------------------------------

            $key = (preg_match('/^field_id_(\d+)$/', $key, $match))
                ? $match[1]
                : $key;

            // --------------------------------------
            // Get weight
            // --------------------------------------

            $weight = array_key_exists($key, $col['settings'])
                ? $col['settings'][$key]
                : false;

            // Skip if not there
            if (! $weight) {
                continue;
            }

            // --------------------------------------
            // Apply weight and add to text
            // --------------------------------------

            $text[] = trim($sep . str_repeat($val . $sep, $weight));
        }

        // --------------------------------------
        // Return text with each field on its own line
        // --------------------------------------

        return implode(NL, $text);
    }
}
// End of file Pro_search_index.php
