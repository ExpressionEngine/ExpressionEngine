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

// include super model
if (! class_exists('Pro_search_model')) {
    require_once(PATH_ADDONS . 'pro_search/model.pro_search.php');
}

/**
 * Pro Search Word Model class
 */
class Pro_search_word_model extends Pro_search_model
{
    /**
     * Limit suggestions to words that match the first letter,
     * override using config setting
     */
    private $_first_letter_suggestions = false;

    /**
     * The Levenshtein method to use, override using config setting,
     * but only if MySQL Levenshtein function is installed, by you!
     */
    private $_levenshtein_method = 'php';

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access      public
     * @return      void
     */
    public function __construct()
    {
        // Call parent constructor
        parent::__construct();

        // Initialize this model
        $this->initialize(
            'pro_search_words',
            array(
                'site_id'  => 'int(4) unsigned NOT NULL',
                'language' => 'varchar(5) NOT NULL',
                'word'     => 'varchar(50) COLLATE utf8_bin NOT NULL'
            ),
            array(
                'length'   => 'int(4) unsigned NOT NULL',
                'sound'    => 'char(4)',
                'clean'    => 'varchar(50)'
            )
        );

        // --------------------------------------
        // First Letter Suggestions config option
        // --------------------------------------

        $this->_first_letter_suggestions = (bool) ee()->config->item('pro_search_first_letter_suggestions');

        // --------------------------------------
        // Optionally override the Levenshtein method via config file
        // --------------------------------------

        $method = ee()->config->item('pro_search_levenshtein_method');

        if (in_array($method, array('php', 'sql'))) {
            $this->_levenshtein_method = $method;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Installs given table
     *
     * @access      public
     * @return      void
     */
    public function install()
    {
        // Call parent install
        parent::install();

        // Add indexes to table
        foreach (array('length', 'sound', 'clean') as $key) {
            ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`{$key}`)");
        }
    }

    // --------------------------------------------------------------------

    /**
     * Insert ignore a bunch of words into the database
     *
     * @access     public
     * @param      array
     * @return     void
     */
    public function insert_ignore($data)
    {
        // --------------------------------------
        // Get insert sql
        // --------------------------------------

        $sql = ee()->db->insert_string($this->table(), $data);

        // --------------------------------------
        // Change insert to replace to update existing entry
        // --------------------------------------

        return ee()->db->query(preg_replace('/^INSERT/', 'INSERT IGNORE', $sql));
    }

    // --------------------------------------------------------------------

    /**
     * Batch-insert ignore a bunch of words into the database
     *
     * @access     public
     * @param      array
     * @return     void
     */
    public function insert_ignore_batch($data)
    {
        $this->_batch('INSERT IGNORE', $data);
    }

    /**
     * Batch-replace a bunch of words into the database
     *
     * @access     public
     * @param      array
     * @return     void
     */
    public function replace_batch($data)
    {
        $this->_batch('REPLACE', $data);
    }

    /**
     * Batch-insert ignore or replace a bunch of words into the database
     *
     * @access     private
     * @param      string
     * @param      array
     * @return     void
     */
    private function _batch($type, $data)
    {
        // --------------------------------------
        // Get table attributes
        // --------------------------------------

        $attrs = array_keys(current($data));
        $fields = implode(', ', $attrs);
        $values = '';

        // --------------------------------------
        // Collect values
        // --------------------------------------

        foreach ($data as $row) {
            $values .= "\n(";

            foreach ($row as $val) {
                $values .= is_null($val)
                    ? 'NULL,'
                    : "'" . ee()->db->escape_str($val) . "',";
            }

            $values = rtrim($values, ',') . '),';
        }

        // --------------------------------------
        // Define SQL
        // --------------------------------------

        $sql = "{$type} INTO `{$this->table()}` ({$fields}) VALUES" . rtrim($values, ',');

        ee()->db->query($sql);
    }

    // --------------------------------------------------------------------

    /**
     * Get unknown words for given words
     *
     * @access     public
     * @param      array
     * @param      mixed
     * @param      mixed
     * @return     array
     */
    public function get_unknown($words, $lang, $site)
    {
        // Bail out if none given
        if (empty($words)) {
            return array();
        }

        // Make sure they're arrays
        if (! is_array($lang)) {
            $lang = array($lang);
        }
        if (! is_array($site)) {
            $site = array($site);
        }

        $query = ee()->db->select('word')
            ->from($this->table())
            ->where_in('word', $words)
            ->where_in('language', $lang)
            ->where_in('site_id', $site)
            ->get();

        // Get known words
        $known = pro_flatten_results($query->result_array(), 'word');

        // And diff them
        return array_diff($words, $known);
    }

    /**
     * Get array of dirty words from given words
     *
     * @access     public
     * @param      array
     * @return     array
     */
    public function get_dirty($words, $lang, $site)
    {
        // Filter the words
        $words = array_filter($words, array(ee()->pro_search_words, 'is_valid'));

        // Bail out if none given
        if (empty($words)) {
            return array();
        }

        // Default to this site id
        if (! $site) {
            $site = array($this->site_id);
        }

        // Make sure they're arrays
        if (! is_array($lang)) {
            $lang = array($lang);
        }
        if (! is_array($site)) {
            $site = array($site);
        }

        $query = ee()->db->select('word, clean')
            ->from($this->table())
            ->where_in('clean', $words)
            ->where_in('language', $lang)
            ->where_in('site_id', $site)
            ->get();

        return $query->result_array();
    }

    /**
     * Get array of phonetically similar words from given words
     *
     * @access     public
     * @param      array
     * @return     array
     */
    public function get_sounds($words, $lang, $site, $distance = 1)
    {
        // Filter the words
        $words = array_filter($words, array(ee()->pro_search_words, 'is_valid'));

        // Bail out if none given
        if (empty($words)) {
            return array();
        }

        // Default to this site id
        if (! $site) {
            $site = array($this->site_id);
        }

        // Make sure they're arrays
        if (! is_array($lang)) {
            $lang = array($lang);
        }
        if (! is_array($site)) {
            $site = array($site);
        }

        // Convert to sounds
        $where = array();
        $tmpl = "(`sound` = '%s' AND length BETWEEN %u AND %u)";

        foreach ($words as $word) {
            $length = ee()->pro_multibyte->strlen($word);
            $where[] = sprintf($tmpl, soundex($word), $length - $distance, $length + $distance);
        }

        $query = ee()->db->select('word, sound')
            ->from($this->table())
            ->where_in('language', $lang)
            ->where_in('site_id', $site)
            ->where('(' . implode(' OR ', $where) . ')')
            ->get();

        return $query->result_array();
    }

    // --------------------------------------------------------------------

    /**
     * Get lang count for this site
     *
     * @access     public
     * @return     array
     */
    public function get_lang_count()
    {
        $query = ee()->db->select('language, count(*) as num')
            ->from($this->table())
            ->where('site_id', $this->site_id)
            ->group_by('language')
            ->order_by('num desc')
            ->order_by('language asc')
            ->get();

        // Return the flatness
        return pro_flatten_results($query->result_array(), 'num', 'language');
    }

    // --------------------------------------------------------------------

    /**
     * Find words
     *
     * @access     public
     * @param      string
     * @param      string
     * @return     array
     */
    public function find($word, $lang)
    {
        $oper = (strpos($word, '%') === false) ? '=' : 'LIKE';

        $where = "(`word` {$oper} '{$word}' OR `clean` {$oper} '{$word}')";

        $query = ee()->db->select('language, word')
            ->from($this->table())
            ->where('site_id', $this->site_id)
            ->where('language', $lang)
            ->where($where)
            ->order_by('word')
            ->get();

        // Return the words
        return $query->result_array();
    }

    // --------------------------------------------------------------------

    /**
     * Delete words
     *
     * @access     public
     * @param      string
     * @param      string
     * @return     array
     */
    public function delete($word, $lang = false)
    {
        ee()->db->where('site_id', $this->site_id)
            ->where('language', $lang)
            ->where('word', $word)
            ->delete($this->table());
    }

    // --------------------------------------------------------------------

    /**
     * Get suggestions for given words, languages, sites, etc.
     *
     * @access     public
     * @param      array
     * @param      mixed
     * @param      mixed
     * @param      int
     * @param      int
     * @return     array
     */
    public function get_suggestions($words, $lang, $site = null, $distance = 2, $limit = 5)
    {
        // Default to this site
        if (empty($site)) {
            $site = $this->site_id;
        }

        // Make sure they're arrays
        if (! is_array($words)) {
            $words = array($words);
        }
        if (! is_array($lang)) {
            $lang = array($lang);
        }
        if (! is_array($site)) {
            $site = array($site);
        }

        // Initiate suggestions
        $data = array();

        if ($this->_levenshtein_method == 'php') {
            $data = $this->_php_suggestions($words, $lang, $site, $distance, $limit);
        }

        if ($this->_levenshtein_method == 'sql') {
            $data = $this->_sql_suggestions($words, $lang, $site, $distance, $limit);
        }

        return $data;
    }

    /**
     * Get suggestions via PHP Levenshtein function
     * Tested with 100k+ words in table. Takes up quite some memory,
     * but much quicker than using the SQL version with the same dataset.
     */
    private function _php_suggestions($words, $lang, $site, $distance, $limit)
    {
        // Local cache, NOT static,
        // as it needs to be forgotten when the method is done
        $cache = array();
        $data = array();

        // loop through the words
        foreach ($words as $word) {
            // Get word length
            $length = ee()->pro_multibyte->strlen($word);

            // Get first letter
            $first = $this->_first_letter_suggestions
                ? ee()->pro_multibyte->substr($word, 0, 1)
                : '0';

            // Compose cache key
            $key = $length . ':' . $first;

            // If there isn't cache yet, set it
            if (! isset($cache[$key])) {
                // Min and max word lengths to query
                $min = (int) ($length - 1);
                $max = (int) ($length + 1);

                // Get it?
                ee()->db->select('word')
                    ->from($this->table())
                    ->where_in('language', $lang)
                    ->where_in('site_id', $site)
                    ->where("`length` BETWEEN {$min} AND {$max}");

                if ($this->_first_letter_suggestions) {
                    ee()->db->like('clean', $first, 'after');
                }

                $query = ee()->db->get();

                // Flatten into an array
                $cache[$key] = pro_flatten_results($query->result_array(), 'word');
            }

            // Apply levenshtein and add to the data
            foreach ($cache[$key] as $suggestion) {
                $cost = levenshtein($word, $suggestion);

                if ($cost && $cost <= $distance) {
                    $data[$suggestion] = $cost;
                }
            }
        }

        // Sort data
        asort($data);

        // Slice data
        $data = array_slice(array_keys($data), 0, $limit);

        return $data;
    }

    /**
     * Get suggestions via SQL Levenshtein function
     * Tested with 100k+ words in table. VERY slow.
     * Leave as option: YMMV
     * Install LEVENSHTEIN function yourself, tho.
     */
    private function _sql_suggestions($words, $lang, $site, $distance, $limit)
    {
        // Return data
        $data = array();

        // Loop through words and get suggestions
        foreach ($words as $word) {
            // Get length
            $length = ee()->pro_multibyte->strlen($word);

            // And wiggle room
            $min = (int) ($length - 1);
            $max = (int) ($length + 1);

            // Query the DB
            ee()->db->select(array('word', "LEVENSHTEIN('{$word}', word) AS distance"))
                ->from($this->table())
                ->where('word !=', $word)
                ->where_in('language', $lang)
                ->where_in('site_id', $site)
                ->where("`length` BETWEEN {$min} AND {$max}")
                ->having('distance <=', $distance)
                ->order_by('distance')
                ->limit($limit);

            if ($this->_first_letter_suggestions) {
                ee()->db->like('clean', ee()->pro_multibyte->substr($word, 0, 1), 'after');
            }

            $query = ee()->db->get();

            // Suggestions
            $suggestions = pro_flatten_results($query->result_array(), 'word');

            $data = array_merge($data, $suggestions);
            if (count($data) >= $limit) {
                break;
            }
        }

        return $data;
    }

    // --------------------------------------------------------------------
}
// End class

/* End of file Pro_search_word_model.php */
