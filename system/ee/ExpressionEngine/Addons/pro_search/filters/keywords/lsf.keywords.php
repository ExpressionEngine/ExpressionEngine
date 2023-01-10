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
 * Filter by keywords
 */
class Pro_search_filter_keywords extends Pro_search_filter
{
    /**
     * Priority for this filter
     */
    protected $priority = 10;

    /**
     * Current collections
     */
    private $_collections;

    /**
     * Items we need to keep track of
     */
    private $_colorder = array();
    private $_excerpts = array();
    private $_results = array();
    private $_score = array();
    private $_terms = array();
    private $_urls = array();

    /**
     * Bools
     */
    private $_fulltext;
    private $_fixed;

    /**
     * Allowed modes and loose ends, default first
     */
    private $_modes = array('auto', 'any', 'all', 'exact');
    private $_loose = array('no', 'left', 'right', 'both');

    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access     public
     * @return     void
     */
    public function __construct()
    {
        parent::__construct();

        // Load libs
        ee()->load->library('pro_search_words');

        // Load term classes
        if (! class_exists('Pro_search_term')) {
            require_once __DIR__ . '/terms/Pro_search_term.php';
            require_once __DIR__ . '/terms/Pro_search_term_group.php';
        }
    }

    // --------------------------------------------------------------------

    /**
     * Allows for keywords="" parameter, along with its associated params
     *
     * @access     public
     * @param      mixed     NULL or Array
     * @return     mixed     NULL or Array
     */
    public function filter($entry_ids)
    {
        // --------------------------------------
        // Log it
        // --------------------------------------

        $this->_log('Applying ' . __CLASS__);

        // --------------------------------------
        // Set parameters to default
        // --------------------------------------

        $this->_collections = ee()->pro_search_collection_model->get_by_params();
        $this->_fixed = false;
        $this->_fulltext = true;
        $this->_results = array();
        $this->_score = array();

        // --------------------------------------
        // Collection params given, but not valid
        // --------------------------------------

        if (is_array($this->_collections) && empty($this->_collections)) {
            $this->_log('No valid collections found');

            return array();
        }

        // --------------------------------------
        // Set words language
        // --------------------------------------

        if ($lang = $this->params->get('keywords:lang')) {
            ee()->pro_search_words->set_language($lang);
        }

        // --------------------------------------
        // Check keywords and set the search terms
        // --------------------------------------

        $this->_set_terms();

        // --------------------------------------
        // Only perform actual search if keywords are given
        // --------------------------------------

        if (empty($this->_terms)) {
            $this->_log('No keyword search');
            $this->_no_keywords();

            return $entry_ids;
        }

        // --------------------------------------
        // Optionally stem the keywords
        // --------------------------------------

        if ($this->params->get('keywords:stem') == 'yes') {
            $this->_log('Extending keywords with stems');
            $this->_extend_terms('_get_stems');
        }

        // --------------------------------------
        // Optionally inflect the keywords
        // --------------------------------------

        if ($this->params->get('keywords:inflect') == 'yes') {
            $this->_log('Extending keywords with inflections');
            $this->_extend_terms('_get_inflections');
        }

        // --------------------------------------
        // Optionally get phonetically similar keywords
        // Undocumented, as I'm not happy with it:
        // it slows down the search significantly. For now,
        // just offer suggestions based on soundex instead.
        // --------------------------------------

        if ($this->params->get('keywords:sound') == 'yes') {
            $this->_log('Extending keywords with phonetics');
            $this->_extend_terms('_get_sounds');
        }

        // --------------------------------------
        // Get the 'dirty' variations for the keywords
        // One extra query for the sake of diacritic-insensitive word-highlighting
        // --------------------------------------

        if ($lang && ee()->pro_search_settings->get('excerpt_hilite')) {
            $this->_log('Extending keywords with diacritics');
            $this->_extend_terms('_get_dirty');
        }

        // --------------------------------------
        // Set the min score
        // --------------------------------------

        if (
            ($score = $this->params->get('keywords:score', $this->params->get('min_score'))) &&
            preg_match('/^([<>]?=?)?([\d\.]+)$/', $score, $match)
        ) {
            // Get the matches
            list(, $a, $b) = $match;

            // Change the operator if necessary
            if ($a == '=' || $a == '') {
                $a = '>=';
            }

            // Set the score
            $this->_score = array($a, $b);
        }

        // --------------------------------------
        // Get and log the keywords
        // --------------------------------------

        $keywords = $this->_keywords(true);
        $this->_log('Keywords: ' . $keywords);

        // --------------------------------------
        // Select what?
        // --------------------------------------

        // Always this
        $t = ee()->pro_search_index_model->table();
        $select = array("{$t}.entry_id", "{$t}.collection_id");

        // --------------------------------------
        // Field Match?
        // --------------------------------------

        $join = false;

        if (
            ($match = $this->params->get('keywords:match')) &&
            ($field = $this->fields->name($match)) &&
            ($rawKeywords = addslashes($this->params->get('keywords')))
        ) {
            $join = $this->fields->table($match);
            $select[] = "IF({$field} = '{$rawKeywords}', 1, 0) AS `match`";
        }

        // This depends on FT
        $select[] = $this->_fulltext
            ? ("MATCH({$t}.index_text) AGAINST('{$keywords}') AS `score`")
            : ($keywords ? "{$t}.index_text" : '0 AS `score`');

        // --------------------------------------
        // Begin composing query
        // --------------------------------------

        ee()->db->select($select, false)->from($t);

        if ($join) {
            ee()->db->join($join . ' as ' . $join, "{$t}.entry_id = {$join}.entry_id", 'left');
        }

        // --------------------------------------
        // Filters used by both searches
        // --------------------------------------

        // Limit query by collection
        if ($this->_collections) {
            ee()->db->where_in('collection_id', pro_flatten_results($this->_collections, 'collection_id'));
        }

        // Limit query by site
        if ($site_ids = $this->params->site_ids()) {
            ee()->db->where_in("{$t}.site_id", array_values($site_ids));
        }

        // If entry ids were given, limit to those
        if ($entry_ids) {
            ee()->db->where_in("{$t}.entry_id", $entry_ids);
        }

        // Add where clause
        ee()->db->where($this->_sql_where_keywords(), null, false);

        // Limit by min_score
        if ($this->_fulltext && $this->_score) {
            list($oper, $score) = $this->_score;
            ee()->db->having("score {$oper}", $score);
        }

        // --------------------------------------
        // Extra search stuff
        // --------------------------------------

        if ($add_to_query = $this->params->get_prefixed('keywords-query:', true)) {
            foreach ($add_to_query as $field => $val) {
                if (ee()->db->field_exists($field, ee()->pro_search_index_model->table())) {
                    list($items, $in) = $this->params->explode($val);
                    ee()->db->{($in ? 'where_in' : 'where_not_in')}($field, $val);
                } else {
                    $this->_log("Field {$field} does not exist in " . ee()->pro_search_index_model->table());
                }
            }
        }

        // --------------------------------------
        // Perform the search
        // --------------------------------------

        $this->_log('Starting search ' . ($this->_fulltext ? '(fulltext)' : '(fallback)'));
        $query = ee()->db->get();

        // --------------------------------------
        // If the search had no results, return no results bit
        // --------------------------------------

        if ($query->num_rows == 0) {
            $this->_log('Searched but found nothing');

            return array();
        }

        // --------------------------------------
        // If we do have results, continue
        // --------------------------------------

        $this->_results = ($this->_fulltext || empty($keywords))
            ? (pro_associate_results($query->result_array(), 'entry_id'))
            : $this->_get_fallback_results($query);

        // Bail out if no entry falls above the min_score threshold
        if (empty($this->_results)) {
            $this->_log('No valid results after scoring');

            return array();
        }

        // --------------------------------------
        // Modify scores for each collection
        // --------------------------------------

        // Make sure internal collections are set
        if (empty($this->_collections)) {
            $this->_set_collections_by_results();
        }

        if (
            ($modifiers = array_unique(pro_flatten_results($this->_collections, 'modifier'))) &&
            ! (count($modifiers) == 1 && $modifiers[0] == 1.0)
        ) {
            $this->_log('Applying collection modifier to search results');

            foreach ($this->_results as &$row) {
                if ($mod = (float) $this->_collections[$row['collection_id']]['modifier']) {
                    $row['score'] = $row['score'] * $mod;
                }
            }
        }

        // -------------------------------------
        // 'pro_search_modify_score' hook.
        //  - Modify scoring for keyword searches
        // -------------------------------------

        if (ee()->extensions->active_hook('pro_search_modify_score') === true) {
            $this->_results = ee()->extensions->call('pro_search_modify_score', $this->_results);

            if (empty($this->_results) || ee()->extensions->end_script === true) {
                return array();
            }
        }

        // --------------------------------------
        // Orderby what?
        // --------------------------------------

        // Default by score
        $orderby = $this->params->get('orderby', 'pro_search_score');

        // Order by collection
        $prefix = 'pro_search_collection:';

        if ($orderby == 'pro_search_score') {
            // Just order by score
            $this->_log('Ordering results by score');
            uasort($this->_results, array($this, '_by_score'));
            $this->_fixed = true;
        } elseif (substr($orderby, 0, strlen($prefix)) == $prefix) {
            $this->_log('Ordering results by collection order');
            // An array to map collection names to IDs
            $map = pro_flatten_results($this->_collections, 'collection_id', 'collection_name');

            // Set the _colorder to the given order
            foreach (explode(',', substr($orderby, strlen($prefix))) as $col) {
                if (! array_key_exists($col, $map)) {
                    continue;
                }
                $this->_colorder[] = $map[$col];
            }

            // And sort by collection first, score second
            uasort($this->_results, array($this, '_by_colorder'));
            $this->_fixed = true;
        }

        // --------------------------------------
        // Add results to cache, so extension can look this up
        // --------------------------------------

        $this->_log('Returning entry IDs');

        return array_keys($this->_results);
    }

    // --------------------------------------------------------------------

    /**
     * Comparison function to order results by score
     *
     * @access     private
     * @param      array     result row
     * @param      array     result row
     * @return     int
     */
    private function _by_score($a, $b)
    {
        // Field match
        if (isset($a['match']) && isset($b['match'])) {
            if ($a['match'] > $b['match']) {
                return -1;
            }
            if ($a['match'] < $b['match']) {
                return 1;
            }
        }
        // No field match, or equal field match
        if ($a['score'] == $b['score']) {
            return 0;
        }

        return ($a['score'] > $b['score']) ? -1 : 1;
    }

    /**
     * Comparison function to order results by given collection, then score
     *
     * @access     private
     * @param      array     result row
     * @param      array     result row
     * @return     int
     */
    private function _by_colorder($a, $b)
    {
        $x = array_search($a['collection_id'], $this->_colorder);
        $y = array_search($b['collection_id'], $this->_colorder);
        if ($x === false) {
            $x = count($this->_colorder);
        }
        if ($y === false) {
            $y = count($this->_colorder);
        }

        if ($x === $y) {
            return $this->_by_score($a, $b);
        } else {
            return ($x < $y) ? -1 : 1;
        }
    }

    /**
     * Fixed order?
     */
    public function fixed_order()
    {
        return $this->_fixed;
    }

    // --------------------------------------------------------------------

    /**
     * No Keywords means:
     * - get excerpt ID from channel prefs, as no collection is relevant
     * - get {auto_path} URL for search results per channel
     * - set channel="" param based on Collections given if it isn't set
     * - remove reference to keywords and collections from orderby param
     *
     * @access     private
     * @return     void
     */
    private function _no_keywords()
    {
        // --------------------------------------
        // Query Channels table
        // --------------------------------------

        ee()->db->select('channel_id, channel_name, search_excerpt')
            ->select("IF(search_results_url='', channel_url, search_results_url) AS url", false)
            ->from('channels')
            ->where_in('site_id', $this->params->site_ids());

        // Filter by given collections
        if ($this->_collections) {
            $channel_ids = array_unique(pro_flatten_results($this->_collections, 'channel_id'));
            ee()->db->where_in('channel_id', $channel_ids);
        }

        // Also filter by channel param
        if ($channel_param = $this->params->get('channel')) {
            list($channel, $in) = $this->params->explode($channel_param);
            ee()->db->{$in ? 'where_in' : 'where_not_in'}('channel_name', $channel);
        }

        // Get the data
        $channels = ee()->db->get()->result_array();

        // --------------------------------------
        // Populate both internal excerpts and URLs
        // --------------------------------------

        foreach ($channels as $row) {
            $this->_excerpts[$row['channel_id']] = $row['search_excerpt'];
            $this->_urls[$row['channel_id']] = $row['url'];
        }

        // --------------------------------------
        // Set channel="" parameter if not set
        // --------------------------------------

        if ($this->_collections && $channels && ! $channel_param) {
            $channels = array_unique(pro_flatten_results($channels, 'channel_name'));
            $this->params->set('channel', implode('|', $channels));
        }

        // --------------------------------------
        // Remove reference to keywords and collections from orderby param
        // --------------------------------------

        $orderby = (string) $this->params->get('orderby');

        if (preg_match('/^(pro_search_(score|collection))/i', $orderby, $match)) {
            $this->params->set('orderby', preg_replace("/^{$match[1]}\|?/", '', $orderby));

            if ($sort = $this->params->get('sort')) {
                $this->params->set('sort', preg_replace('/^(asc|desc)\|?/i', '', $sort));
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Prep all {auto_path} URLs like the native Search module does
     *
     * @access     private
     * @return     void
     */
    private function _prep_urls()
    {
        foreach ($this->_urls as &$url) {
            $url = ee()->functions->prep_query_string($url);
            $url = rtrim($url, '/') . '/';
            $url = str_replace('{base_url}', ee()->config->item('base_url'), $url);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Populate internal Terms array
     *
     * @access     private
     * @return     void
     */
    private function _set_terms()
    {
        // --------------------------------------
        // Reset terms
        // --------------------------------------

        $this->_terms = array();

        // --------------------------------------
        // Check validity of search mode
        // --------------------------------------

        $mode = $this->params->get(
            'keywords:mode',
            $this->params->get('search_mode')
        );

        if (! in_array($mode, $this->_modes)) {
            $mode = $this->_modes[0];
        }

        // --------------------------------------
        // Check validity of loose ends
        // --------------------------------------

        $loose = $this->params->get(
            'keywords:loose',
            $this->params->get('loose_ends')
        );

        if (! in_array($loose, $this->_loose)) {
            $loose = $this->_loose[0];
        }

        // --------------------------------------
        // Ignore loose ends when mode="auto"
        // --------------------------------------

        if ($mode == 'auto') {
            $loose = false;
        }

        // --------------------------------------
        // Get raw keywords from parameters
        // --------------------------------------

        $words = (string) $this->params->get('keywords');

        // --------------------------------------
        // Replace pipes with spaces as if it were separate words
        // --------------------------------------

        $words = str_replace('|', ' ', $words);
        $words = preg_replace('/\s{2,}/', ' ', $words);
        $words = trim($words);

        // --------------------------------------
        // Alter keywords based on mode
        // --------------------------------------

        switch ($mode) {
            case 'exact':
                $words = '"' . str_replace('"', '', $words) . '"';

                break;

            case 'any':
                // Thanks http://stackoverflow.com/a/1191598/1769664
                $words = (strpos($words, '"') === false)
                    ? str_replace(' ', ' OR ', $words)
                    : preg_replace('/\s(?=(?:(?:[^"]*+"){2})*+[^"]*+\z)/iu', ' OR ', $words);

                break;
        }

        // --------------------------------------
        // Use tokens to get to each term,
        // including quoted ones
        // --------------------------------------

        for ($word = strtok($words, ' '); $word !== false; $word = strtok(' ')) {
            // Create new Term Object
            $term = new Pro_search_term();

            // Is term part of an OR group
            $group = false;

            // Check if search term is part of an OR group
            if ($word == 'OR') {
                // Grab the next one or bail
                if (($word = strtok(' ')) === false) {
                    break;
                }

                if ($total = count($this->_terms)) {
                    // Set the previous token to a term_group, if it's not already
                    $prev = $this->_terms[$total - 1];

                    if (! ($prev instanceof Pro_search_term_group)) {
                        $prev = new Pro_search_term_group(array($prev));
                        $this->_terms[$total - 1] = $prev;
                    }

                    $group = true;
                }
            }

            // Negation?
            if ($term->exclude = (substr($word, 0, 1) == '-')) {
                $word = ltrim($word, '-');
            }

            // Loose ends on the left
            if ($term->loose_left = (substr($word, 0, 1) == '*' || $loose == 'left' || $loose == 'both')) {
                $word = ltrim($word, '*');
                $this->_fulltext = false;
            }

            // Loose ends on the right
            if ($term->loose_right = (substr($word, -1) == '*' || $loose == 'right' || $loose == 'both')) {
                $word = rtrim($word, '*');
            }

            // Check for quoted terms
            if (substr($word, 0, 1) == '"') {
                // 1-word quote or not?
                $word = (substr($word, -1) == '"')
                     ? substr($word, 1, -1)
                     : substr($word, 1) . ' ' . strtok('"');

                // Set exact marker to TRUE
                $term->exact = true;
            }

            // Record the raw term
            $term->raw = $word;

            // Record the cleaned up term
            $word = ee()->pro_search_words->clean($word, !$term->exact);
            $word = ee()->pro_search_words->remove_diacritics($word);

            $term->clean = $word;

            // This is an original word
            $term->original = true;

            // Skip the rest if nothing's left
            if (! $term->clean) {
                continue;
            }

            // Add term object to previous group or general terms
            $group ? ($prev->terms[] = $term) : ($this->_terms[] = $term);

            // Check if keywords are fulltext-worthy
            if ($this->_fulltext) {
                $this->_fulltext = $term->is_fulltext();
            }
        }

        // --------------------------------------
        // Final ckeck to see if we're dealing with fulltext or not:
        // Empty keywords is sign that there's only negated keywords,
        // which cannot use fulltext...
        // --------------------------------------

        if ($this->_fulltext && ! $this->_keywords()) {
            $this->_fulltext = false;
        }
    }

    /**
     * Extend search terms based on given method
     *
     * @access     private
     * @param      string
     * @return     void
     */
    private function _extend_terms($method)
    {
        // --------------------------------------
        // Initiate the new terms
        // --------------------------------------

        $new_terms = array();

        // --------------------------------------
        // Loop through existing terms
        // --------------------------------------

        foreach ($this->_terms as $obj) {
            // If it's a term group...
            if ($obj instanceof Pro_search_term_group) {
                // ...loop through each term...
                foreach ($obj->terms as $term) {
                    // ...call the method, which should return an array of new term objects...
                    if ($terms = $this->$method($term)) {
                        // ...which we add to the group.
                        $obj->add($terms);
                    }
                }
            } else {
                // If it's a non-grouped term...
                // ...call the method, which should return an array of new term objects...
                if ($terms = $this->$method($obj)) {
                    // ...to which we add the original term to the top...
                    $terms = array_merge(array($obj), $terms);

                    // ...and then add it to a new term group.
                    $obj = new Pro_search_term_group($terms);
                }
            }

            // Finally, we add the (extended) object to the new terms
            $new_terms[] = $obj;
        }

        // --------------------------------------
        // Replace the terms
        // --------------------------------------

        $this->_terms = $new_terms;
    }

    /**
     * For given term, get array of alternative terms based on the stem
     *
     * @access     private
     * @param      object
     * @return     array
     */
    private function _get_stems(Pro_search_term $term)
    {
        // --------------------------------------
        // Initiate terms
        // --------------------------------------

        $terms = array();

        // --------------------------------------
        // Skip exact matches or substring matches
        // --------------------------------------

        if (! $term->is_stemmable()) {
            return $terms;
        }

        // --------------------------------------
        // Loop through types
        // --------------------------------------

        // Get the stem from the raw term
        $raw = ee()->pro_search_words->stem($term->raw);
        $clean = ee()->pro_search_words->clean($raw, true);
        $clean = ee()->pro_search_words->remove_diacritics($clean);

        // If it's too small, bail out
        if (ee()->pro_multibyte->strlen($clean) <= 1) {
            return $terms;
        }

        // Otherwise, create a new term object from it
        $stem = new Pro_search_term();

        // Set the term
        $stem->raw = $raw;
        $stem->clean = $clean;
        $stem->loose_right = true;
        $stem->stem = true;

        // Add it to the terms to return
        $terms[] = $stem;

        // Keep checking if keywords are fulltext-worthy
        if ($this->_fulltext) {
            $this->_fulltext = $stem->is_fulltext();
        }

        // --------------------------------------
        // And return the terms
        // --------------------------------------

        return $terms;
    }

    /**
     * For given term, get array of alternative terms based on inflection
     *
     * @access     private
     * @param      object
     * @return     array
     */
    private function _get_inflections(Pro_search_term $term)
    {
        // --------------------------------------
        // Initiate terms
        // --------------------------------------

        $terms = array();

        // --------------------------------------
        // Skip exact matches or substring matches
        // --------------------------------------

        if (! $term->is_inflectable()) {
            return $terms;
        }

        // --------------------------------------
        // Loop through types
        // --------------------------------------

        foreach (array('singular', 'plural') as $type) {
            // Get the inflected term from the raw term
            $raw = ee()->pro_search_words->inflect($term->raw, $type);
            $clean = ee()->pro_search_words->clean($raw, true);
            $clean = ee()->pro_search_words->remove_diacritics($clean);

            // If it's too small, bail out
            if (ee()->pro_multibyte->strlen($clean) <= 1) {
                continue;
            }

            // Otherwise, create a new term object from it
            $inflect = new Pro_search_term();

            // Set the term
            $inflect->raw = $raw;
            $inflect->clean = $clean;

            // Add it to the terms to return
            $terms[] = $inflect;

            // Keep checking if keywords are fulltext-worthy
            if ($this->_fulltext) {
                $this->_fulltext = $inflect->is_fulltext();
            }
        }

        // --------------------------------------
        // And return the terms
        // --------------------------------------

        return $terms;
    }

    /**
     * For given term, get array of alternative terms based on soundex
     *
     * @access     private
     * @param      object
     * @return     array
     */
    private function _get_sounds(Pro_search_term $term)
    {
        // --------------------------------------
        // We need some cache
        // --------------------------------------

        static $sounds;

        // --------------------------------------
        // Get sounds for all
        // --------------------------------------

        if (is_null($sounds)) {
            $sounds = array();

            foreach (ee()->pro_search_words->get_sounds($this->_keywords()) as $row) {
                $sounds[$row['sound']][] = $row['word'];
            }
        }

        // --------------------------------------
        // Given sound
        // --------------------------------------

        $soundex = soundex($term->raw);

        // --------------------------------------
        // If we don't have a dirty variant, bail out
        // --------------------------------------

        if (! array_key_exists($soundex, $sounds)) {
            return array();
        }

        // --------------------------------------
        // Initiate new terms
        // --------------------------------------

        $terms = array();

        // --------------------------------------
        // Loop through the dirty words
        // --------------------------------------

        foreach ($sounds[$soundex] as $raw) {
            $clean = ee()->pro_search_words->clean($raw, true);
            $clean = ee()->pro_search_words->remove_diacritics($clean);

            // If it's too small, bail out
            if (ee()->pro_multibyte->strlen($clean) <= 1) {
                continue;
            }

            // Create new term
            $sound = new Pro_search_term();

            // Overwrite the raw term
            $sound->raw = $raw;
            $sound->clean = $clean;

            // Add it to the terms to return
            $terms[] = $sound;

            // Keep checking if keywords are fulltext-worthy
            if ($this->_fulltext) {
                $this->_fulltext = $sound->is_fulltext();
            }
        }

        // --------------------------------------
        // And return the terms
        // --------------------------------------

        return $terms;
    }

    /**
     * For given term, get array of alternative terms based on dirtiness
     *
     * @access     private
     * @param      object
     * @return     array
     */
    private function _get_dirty(Pro_search_term $term)
    {
        // --------------------------------------
        // We need some cache
        // --------------------------------------

        static $mess;

        // --------------------------------------
        // Get dirty words for all
        // --------------------------------------

        if (is_null($mess)) {
            $mess = array();

            foreach (ee()->pro_search_words->get_dirty($this->_keywords()) as $row) {
                $mess[$row['clean']][] = $row['word'];
            }
        }

        // --------------------------------------
        // If we don't have a dirty variant, bail out
        // --------------------------------------

        if (! array_key_exists($term->clean, $mess)) {
            return array();
        }

        // --------------------------------------
        // Initiate new terms
        // --------------------------------------

        $terms = array();

        // --------------------------------------
        // Loop through the dirty words
        // --------------------------------------

        foreach ($mess[$term->clean] as $word) {
            // Clone the term, so we keep the properties
            $dirty = clone $term;

            // Overwrite the raw term
            $dirty->raw = $word;

            // It's not an original
            $dirty->original = false;

            // Add it to the terms to return
            $terms[] = $dirty;

            // Keep checking if keywords are fulltext-worthy
            if ($this->_fulltext) {
                $this->_fulltext = $dirty->is_fulltext();
            }
        }

        // --------------------------------------
        // And return the terms
        // --------------------------------------

        return $terms;
    }

    // --------------------------------------------------------------------

    /**
     * Get fallback results and calculate score
     *
     * @access     private
     * @param      object
     * @return     array
     */
    private function _get_fallback_results($query)
    {
        $this->_log('Calculating relevance score');

        // Calculate scores ourselves
        $results = array();
        $kcount = count($this->_keywords());

        // Get min score and stuff
        list($x, $threshold) = $this->_score ? $this->_score : array(null, null);

        // --------------------------------------
        // Loop thru results, calculate score
        // based on total words / word count
        // --------------------------------------

        foreach ($query->result() as $row) {
            // Calculate score
            $score = 0;

            // Check occurrence of each word in index_text
            // Added score is number of occurrences / total words / number of keywords * 100
            if ($found = preg_match_all($this->_get_pattern(), $row->index_text, $m)) {
                // Removes weight
                $text = preg_replace('/^\|\s(.+?)\s\|.*$/miu', '$1', $row->index_text);
                $text = str_replace(NL, ' ', $text);

                // Safe word count
                $wcount = count(explode(' ', $text));

                // Add score
                $score = $found / $wcount / $kcount * 100;
            }

            // Skip entries that fall below the threshold
            if (
                ($x == '<' && $threshold < $score) ||
                ($x == '<=' && $threshold <= $score) ||
                ($x == '>' && $threshold > $score) ||
                ($x == '>=' && $threshold >= $score)
            ) {
                continue;
            }

            // Add row to results only if the entry doesn't exist yet
            // or if existing score is lower than this one
            if (
                ! array_key_exists($row->entry_id, $results) ||
                $results[$row->entry_id]['score'] < $score
            ) {
                $results[$row->entry_id] = array(
                    'entry_id'      => $row->entry_id,
                    'collection_id' => $row->collection_id,
                    'match'         => isset($row->match) ? $row->match : null,
                    'score'         => $score
                );
            }
        }

        return $results;
    }

    // --------------------------------------------------------------------

    /**
     * Get where clausule for keywords based on terms
     *
     * @access     private
     * @return     string
     */
    private function _sql_where_keywords()
    {
        $where = array();
        $method = $this->_fulltext ? 'get_fulltext_sql' : 'get_fallback_sql';

        foreach ($this->_terms as $obj) {
            $where[] = $obj->$method();
        }

        $str = $this->_fulltext
            ? sprintf("MATCH(index_text) AGAINST('%s' IN BOOLEAN MODE)", implode(' ', $where))
            : implode("\nAND ", $where);

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Set internal collections by results
     *
     * @access     private
     * @return     void
     */
    private function _set_collections_by_results()
    {
        $col_ids = pro_flatten_results($this->_results, 'collection_id');
        $col_ids = array_unique($col_ids);
        $this->_collections = ee()->pro_search_collection_model->get_by_id($col_ids);
    }

    // --------------------------------------------------------------------

    /**
     * Modify a row for a search result for this filter
     *
     * @access     public
     * @param      array
     * @return     array
     */
    public function results($rows)
    {
        // -------------------------------------------
        // Shortcut to prefix
        // -------------------------------------------

        $pfx = ee()->pro_search_settings->prefix;

        // -------------------------------------------
        // Prep collection info
        // -------------------------------------------

        if (! $this->_collections && $this->_results) {
            $this->_set_collections_by_results();
        }

        // -------------------------------------------
        // Get auto_path info if not present, and only if we need to
        // -------------------------------------------

        if (empty($this->_urls) && array_key_exists('auto_path', ee()->TMPL->var_single)) {
            $channel_ids = array_unique(pro_flatten_results($rows, 'channel_id'));

            $query = ee()->db->select('channel_id')
                ->select("IF(search_results_url='', channel_url, search_results_url) AS url", false)
                ->from('channels')
                ->where_in('channel_id', $channel_ids)
                ->get();

            $this->_urls = pro_flatten_results($query->result_array(), 'url', 'channel_id');
        }

        $this->_prep_urls();

        // -------------------------------------------
        // Loop through results and do yer thing
        // -------------------------------------------

        foreach ($rows as &$row) {
            // Add {auto_path}
            $row['auto_path'] = (array_key_exists($row['channel_id'], $this->_urls)
                ? $this->_urls[$row['channel_id']]
                : '/') . $row['url_title'];

            // Get score for this entry
            $row[$pfx . 'score'] = $this->_get_score($row['entry_id']);

            // Add collection info to row
            foreach ($this->_get_collection_info($row['entry_id']) as $key => $val) {
                $row[$pfx . $key] = $val;
            }
        }

        // -------------------------------------------
        // No excerpt var in tagdata? No need to proceed.
        // -------------------------------------------

        if (strpos(ee()->TMPL->tagdata, $pfx . 'excerpt') === false) {
            return $rows;
        }

        // -------------------------------------------
        // Get all entry ids we're working with
        // -------------------------------------------

        $entry_ids = pro_flatten_results($rows, 'entry_id');

        // -------------------------------------------
        // Loop through results and add the excerpt
        // -------------------------------------------

        // Remember last format
        $last_fmt = null;

        foreach ($rows as &$row) {
            // Get excerpt ID, the field ID to use as excerpt; 0 for title
            $eid = $this->_get_excerpt_id($row);

            // Skip if no valid excerpt ID is found
            if ($eid === false) {
                continue;
            }

            // Get string and format for excerpt
            $str = ($eid == '0' || ! isset($row['field_id_' . $eid])) ? $row['title'] : $row['field_id_' . $eid];
            $fmt = ($eid == '0' || ! isset($row['field_fmt_' . $eid])) ? 'xhtml' : $row['field_fmt_' . $eid];

            // -------------------------------------------
            // 'pro_search_excerpt' hook
            // - change the excerpt for an entry
            // -------------------------------------------

            if (ee()->extensions->active_hook('pro_search_excerpt') === true) {
                $str = ee()->extensions->call('pro_search_excerpt', $entry_ids, $row, $eid);

                // Check return value
                if (is_array($str) && count($str) == 2) {
                    // Set excerpt string to first item in array
                    list($str, $skip) = $str;

                    // If second item in return value, skip native creation of excerpt
                    if ($skip === true) {
                        $row[$pfx . 'excerpt'] = $str;

                        continue;
                    }
                }
            }

            // Apply the same formatting to the keywords for better matching
            if ($last_fmt != $fmt) {
                $this->_format_terms($fmt);
                $this->_get_pattern(true);
                $last_fmt = $fmt;
            }

            // Get the formatted excerpt string
            $str = $this->_apply_typography($str, $fmt);

            // Overwrite empty excerpt with formatted one
            $row[$pfx . 'excerpt'] = $this->_create_excerpt($str);

            // Highlight keywords if we have 'em
            if ($this->_keywords()) {
                $row[$pfx . 'excerpt'] = $this->_highlight($row[$pfx . 'excerpt']);

                if (ee()->pro_search_settings->get('title_hilite') == 'y') {
                    // Remove entities for better matching
                    $row['title'] = html_entity_decode($row['title'], ENT_QUOTES, 'UTF-8');
                    $row['title'] = htmlspecialchars($row['title']);
                    $row['title'] = $this->_highlight($row['title']);
                }
            }
        }

        return $rows;
    }

    /**
     * Get score for entry ID
     *
     * @access      private
     * @param       entry_id
     * @return      mixed
     */
    private function _get_score($entry_id)
    {
        return isset($this->_results[$entry_id])
            ? number_format(round($this->_results[$entry_id]['score'], 2), 2)
            : 0;
    }

    /**
     * Get collection info for this search result
     *
     * @access      private
     * @param       int
     * @return      array
     */
    private function _get_collection_info($entry_id)
    {
        // Prefix
        $pfx = 'collection_';

        // Init info
        $cols = array(
            $pfx . 'id'       => '',
            $pfx . 'name'     => '',
            $pfx . 'label'    => '',
            $pfx . 'language' => '',
            'excerpt'       => ''
        );

        if (isset($this->_results[$entry_id])) {
            $col_id = $this->_results[$entry_id][$pfx . 'id'];

            if (isset($this->_collections[$col_id])) {
                $row = $this->_collections[$col_id];

                foreach ($row as $key => $val) {
                    // Prefix language with collection_
                    if ($key == 'language') {
                        $key = $pfx . $key;
                    }

                    if (array_key_exists($key, $cols)) {
                        $cols[$key] = $val;
                    }
                }
            }
        }

        return $cols;
    }

    /**
     * Get excerpt ID, the field ID to use as excerpt; 0 for title
     *
     * @access      private
     * @param       array
     * @return      int
     */
    private function _get_excerpt_id($row)
    {
        $id = false;

        if (is_numeric($row['pro_search_excerpt'])) {
            $id = $row['pro_search_excerpt'];
        } elseif (isset($this->_excerpts[$row['channel_id']])) {
            $id = $this->_excerpts[$row['channel_id']];
        }

        return $id;
    }

    /**
     * Create smartly truncated excerpt from string
     *
     * @access      private
     * @param       string
     * @return      string
     */
    private function _create_excerpt($str)
    {
        // If no excerpt length, bail
        if (! ($length = (int) ee()->pro_search_settings->get('excerpt_length'))) {
            return $str;
        }

        // Multibyte safe checks
        $str_length = ee()->pro_multibyte->strlen($str);
        $word_count = ee()->pro_multibyte->substr_count($str, ' ');
        $word_count++;

        // Bail out if string is shorter than the amount of words given
        if ($length >= $str_length || $length >= $word_count) {
            return $str;
        }

        if ($this->_keywords()) {
            // Prep our marker to get the actual position of the first occurrence of the keywords
            $marker = '[[__KEYWORD__]]';

            // Replace the keywords with the markers...
            $tmp = preg_replace($this->_get_pattern(), $marker, $str);

            // ...so we can accurately get the position of the first keyword
            $pos = ee()->pro_multibyte->strpos($tmp, $marker);

            // Overwrite the tmp var where we split the string: at the first found keyword
            list($left, $right) = array(
                ee()->pro_multibyte->substr($str, 0, $pos),
                ee()->pro_multibyte->substr($str, $pos, ee()->pro_multibyte->strlen($str))
            );

            // Left and right words
            $left_words = explode(' ', $left);
            $right_words = explode(' ', $right);

            // If we have a split, check the left part
            // Amount of words to put on the left
            $left_count = round($length / 10);

            // Account for little bits on the right
            if (count($right_words) + $left_count < $length) {
                $left_count *= 2;
            }

            // If there are more words on the left than allowed...
            if (count($left_words) > $left_count) {
                // ...slice off excess words...
                $left_words = array_slice($left_words, -$left_count);

                // ...add horizontal ellipsis to the now first word and...
                $left = '&#8230;' . implode(' ', $left_words);
            }

            // Now bring the whole excerpt together again...
            $str = $left . $right;
        }

        // ...and let EE's word limiter do the rest
        $str = ee()->functions->word_limiter($str, $length);

        return $str;
    }

    /**
     * Highlight keywords in given string
     *
     * @access      private
     * @param       string
     * @return      string
     */
    private function _highlight($str)
    {
        if ($tag = ee()->pro_search_settings->get('excerpt_hilite')) {
            // Case insensitive replace
            $str = preg_replace($this->_get_pattern(), "<{$tag}>$1</{$tag}>", $str);
        }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Format the given terms
     *
     * @access      private
     * @param       bool
     * @return      mixed
     */
    private function _format_terms($fmt)
    {
        // Add all given terms to the array
        foreach ($this->_terms as &$obj) {
            if ($obj instanceof Pro_search_term_group) {
                foreach ($obj->terms as &$term) {
                    $term->format = $this->_apply_typography($term->raw, $fmt);
                }
            } else {
                $obj->format = $this->_apply_typography($obj->raw, $fmt);
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Apply typography to string for excerpt and hiliting
     */
    private function _apply_typography($str, $fmt = 'none')
    {
        // Load typo lib
        ee()->load->library('typography');

        // Strip tags first
        $str = strip_tags($str);

        // Typography options
        $options = array(
            'text_format'   => $fmt,
            'html_format'   => 'safe',
            'auto_links'    => 'n',
            'allow_img_url' => 'n'
        );

        // Format text
        $str = ee()->typography->parse_type($str, $options);

        // Strip again and trim it
        $str = trim(strip_tags($str));

        // Remove non-breaking spaces
        $str = str_replace('&nbsp;', ' ', $str);

        // Decode entities
        $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');

        // Make sure this stuff is encoded
        $str = htmlspecialchars($str);

        // Clean white space
        $str = preg_replace('/\s+/', ' ', $str);

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Get keywords based on current terms
     *
     * @access      private
     * @param       bool
     * @return      mixed
     */
    private function _keywords($as_string = false)
    {
        // Init keywords
        $keywords = array();

        // Add all given terms to the array
        foreach ($this->_terms as $obj) {
            if ($obj instanceof Pro_search_term_group) {
                foreach ($obj->terms as $term) {
                    // Skip excluded terms
                    if ($term->exclude) {
                        continue;
                    }
                    $keywords[] = $term->clean;
                }
            } else {
                // Skip excluded terms
                if ($obj->exclude) {
                    continue;
                }
                $keywords[] = $obj->clean;
            }
        }

        // Strip out duplicates
        $keywords = array_unique($keywords);

        // Return as string or array
        return $as_string ? implode(' ', $keywords) : $keywords;
    }

    // --------------------------------------------------------------------

    /**
     * Get regex pattern for current search terms
     *
     * @access      private
     * @return      string
     */
    private function _get_pattern($reset = false)
    {
        // Local cache
        static $pattern;

        // Reset?
        if ($reset) {
            $pattern = null;
        }

        // Return it if set
        if ($pattern) {
            return $pattern;
        }

        // Init full words and partial words
        $full = $part = array();

        // Add all given terms to the array
        foreach ($this->_terms as $obj) {
            if ($obj instanceof Pro_search_term_group) {
                foreach ($obj->terms as $term) {
                    // Skip excluded terms
                    if ($term->exclude) {
                        continue;
                    }
                    ($term->loose_left || $term->loose_right)
                        ? $part[] = $term->get_pattern()
                        : $full[] = $term->get_pattern();
                }
            } else {
                // Skip excluded terms
                if ($obj->exclude) {
                    continue;
                }
                ($obj->loose_left || $obj->loose_right)
                    ? $part[] = $obj->get_pattern()
                    : $full[] = $obj->get_pattern();
            }
        }

        // We want full words to be first in the pattern
        $pattern = implode('|', array_unique(array_merge($full, $part)));
        $pattern = "/({$pattern})/ui";

        return $pattern;
    }
}
// End of class Pro_search_filter_keywords
// End of file lsf.keywords.php
