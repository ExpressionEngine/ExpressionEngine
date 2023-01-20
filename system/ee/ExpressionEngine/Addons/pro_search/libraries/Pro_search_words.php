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
 * Pro Search Words class, for handling words based on language
 */
class Pro_search_words
{
    /**
     * Language to use
     *
     * @var        string
     * @access     private
     */
    private $_lang;

    /**
     * Inflection rules
     *
     * @var        array
     * @access     private
     */
    private $_rules;

    /**
     * Stemmer class
     *
     * @var        object
     * @access     private
     */
    private $stemmer;

    /**
     * Base file path
     *
     * @var        string
     * @access     private
     */
    private $_path;

    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access     public
     * @param      string
     * @return     void
     */
    public function __construct($lang = null)
    {
        // Set path
        $this->_path = PATH_ADDONS . 'pro_search/i18n/';

        // Optionally set language
        if ($lang) {
            $this->set_language($lang);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Set language to work with
     *
     * @access     public
     * @param      string
     * @return     void
     */
    public function set_language($lang = 'en')
    {
        $this->_set_inflection_rules($lang);
        $this->_load_stemmer($lang);
        $this->_lang = $lang;
    }

    /**
     * Sets the inflection rules based on given language
     *
     * @access     private
     * @param      string
     * @return     void
     */
    private function _set_inflection_rules($lang)
    {
        // --------------------------------------
        // Compose filename
        // --------------------------------------

        $file = $this->_path . $lang . '/inflection_rules.php';

        // --------------------------------------
        // Check custom inflection rules in config file
        // --------------------------------------

        if (
            ($rules = ee()->config->item('pro_search_inflection_rules')) &&
            isset($rules[$lang])
        ) {
            $this->_rules = $rules[$lang];
        } elseif (file_exists($file)) {
            // Check our own location
            $this->_rules = include $file;
        } else {
            // Set rules to NULL if unknown
            $this->_rules = null;
        }
    }

    /**
     * Load the Stemmer class
     *
     * @access     private
     * @param      string
     * @return     void
     */
    private function _load_stemmer($lang)
    {
        // --------------------------------------
        // Local cache to see if we checked things
        // --------------------------------------

        static $classes = array();

        // --------------------------------------
        // Defaults
        // --------------------------------------

        $file = $this->_path . $lang . '/stemmer.php';
        $class = "Pro_search_{$lang}_stemmer";
        $method = 'stem';

        // --------------------------------------
        // Does the class exist already?
        // --------------------------------------

        if (isset($classes[$lang]) && $classes[$lang] !== false) {
            $this->stemmer = $classes[$lang];

            return;
        }

        // --------------------------------------
        // Check config file
        // --------------------------------------

        if (
            ($s = ee()->config->item('pro_search_stemmers')) &&
            isset($s[$lang]) && is_array($s[$lang]) && count($s[$lang]) == 3
        ) {
            list($file, $class, $method) = $s[$lang];
        }

        // --------------------------------------
        // Does the file exist?
        // --------------------------------------

        $ok = false; // Initiate cache value

        if (file_exists($file)) {
            include $file;

            if (class_exists($class) && is_callable(array($class, $method))) {
                $this->stemmer = new $class();
                $this->stemmer->pro_search_stem_method = $method;

                // We have an OK class!
                $ok = true;
            }
        }

        // --------------------------------------
        // Save to cache
        // --------------------------------------

        $classes[$lang] = $ok ? $this->stemmer : false;
    }

    // --------------------------------------------------------------------

    /**
     * Return the stem of a word
     */
    public function stem($word)
    {
        $stem = null;

        if ($this->stemmer) {
            $method = $this->stemmer->pro_search_stem_method;
            $stem = $this->stemmer->$method($word);
        }

        return $stem;
    }

    // --------------------------------------------------------------------

    /**
     * Return a plural string
     */
    public function plural($word)
    {
        return $this->_inflect($word, 'plural');
    }

    /**
     * Return a singular string
     */
    public function singular($word)
    {
        return $this->_inflect($word, 'singular');
    }

    /**
     * Is term countable?
     *
     * @access     public
     * @param      string
     * @return     bool
     */
    public function is_countable($word)
    {
        return ! in_array(ee()->pro_multibyte->strtolower($word), $this->_get_rules('uncountable'));
    }

    /**
     * Inflect a given word to the given type
     *
     * @access     public
     * @param      string
     * @param      string
     * @return     string
     */
    public function inflect($word, $type)
    {
        // If we have no rules, bail out
        if (empty($this->_rules) || ! in_array($type, array('singular', 'plural'))) {
            return null;
        }

        // Term should be countable
        if (! $this->is_countable($word)) {
            return $word;
        }

        // Get irregular rules
        $rules = $this->_get_rules('irregular');

        // Swap if singular
        if ($type == 'singular') {
            $rules = array_flip($rules);
        }

        // Check for irregular singular forms
        foreach ($rules as $pattern => $result) {
            $pattern = "/{$pattern}\$/iu";

            if (preg_match($pattern, $word)) {
                return preg_replace($pattern, $result, $word);
            }
        }

        // Get singular or plural rules rules
        foreach ($this->_get_rules($type) as $pattern => $result) {
            if (preg_match($pattern, $word)) {
                return preg_replace($pattern, $result, $word);
            }
        }

        // Fallback
        return $word;
    }

    /**
     * Get inflection rules
     *
     * @access     private
     * @param      string
     * @return     array
     */
    private function _get_rules($name)
    {
        return isset($this->_rules[$name])
            ? $this->_rules[$name]
            : array();
    }

    // --------------------------------------------------------------------

    /**
     * Is this word a valid word for the lexicon
     *
     * @access     public
     * @param      string
     * @return     bool
     */
    public function is_valid($str)
    {
        // No digits and at least 3 characters long
        return ! (ee()->pro_multibyte->strlen(trim($str)) < 3 || preg_match('/\d/', $str));
    }

    /**
     * Clean a string for lexicon use:
     * No tags, entities, non-word chacacters, or double/trailing spaces
     * Optionally remove ignore words
     *
     * @access     public
     * @param      string
     * @param      bool
     * @return     string
     */
    public function clean($str, $ignore = false)
    {
        static $words;

        if (is_null($words)) {
            $words = ee()->pro_search_settings->ignore_words();
            $words = array_map('preg_quote', $words);
        }

        $str = preg_replace('/<br\s?\/?>/iu', ' ', $str);
        $str = strip_tags($str);
        $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
        $str = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $str);
        $str = ee()->pro_multibyte->strtolower($str);

        if ($ignore === true && $words) {
            $str = preg_replace('/\b(' . implode('|', $words) . ')\b/iu', '', $str);
        }

        $str = preg_replace('/\s{2,}/', ' ', $str);
        $str = trim($str);

        return $str;
    }

    /**
     * Remove diacritics
     *
     * @access     public
     * @param      string
     * @return     string
     */
    public function remove_diacritics($str)
    {
        static $chars;

        // --------------------------------------
        // Get translation array from native foreign_chars.php file
        // --------------------------------------

        if (is_null($chars)) {
            $chars = array();

            if ($foreign_characters = $this->get_foreign_chars()) {
                foreach ($foreign_characters as $k => $v) {
                    $chars[pro_chr($k)] = $v;
                }
            }
        }

        // --------------------------------------
        // Remove diacritics from the given string
        // --------------------------------------

        if ($chars) {
            $str = strtr($str, $chars);
        }

        return $str;
    }

    /**
     * Get foreign characters
     */
    private function get_foreign_chars()
    {
        return ee()->config->loadFile('foreign_chars');
    }

    /**
     * Get array of dirty words from given words
     *
     * @access     public
     * @param      array
     * @return     array
     */
    public function get_dirty($words, $site = null)
    {
        return ee()->pro_search_word_model->get_dirty($words, $this->_lang, $site);
    }

    /**
     * Get array of similar sounding words from given words
     *
     * @access     public
     * @param      array
     * @return     array
     */
    public function get_sounds($words, $site = null)
    {
        return ee()->pro_search_word_model->get_sounds($words, $this->_lang, $site);
    }
}
// End of file Pro_search_words.php
