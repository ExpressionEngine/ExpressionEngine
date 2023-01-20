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
 * Pro Search Params class, to handle parameters
 */
class Pro_search_params
{
    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Params to "forget"
     *
     * @access     public
     * @var        array
     */
    public $forget = array();

    /**
     * Parameters
     *
     * @access     private
     * @var        array
     */
    private $_params = array();

    /**
     * Given query
     *
     * @access     private
     * @var        array
     */
    private $_query;

    /**
     * Original tagparams
     *
     * @access     private
     * @var        array
     */
    private $_tagparams = array();

    /**
     * Site ids
     *
     * @access     private
     * @var        array
     */
    private $_site_ids = array();

    /**
     * Protected parameters
     *
     * @access     private
     * @var        array
     */
    private $_protected = array(
        'backspace', 'cache', 'refresh', 'disable',
        'dynamic', 'dynamic_parameters', 'dynamic_start',
        'paginate', 'paginate_base', 'paginate_field',
        'related_categories_mode', 'track_views'
    );

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Set an internal parameter, or set all if none given
     *
     * @access     public
     * @param      mixed
     * @param      mixed
     * @return     void
     */
    public function set($key = null, $val = null)
    {
        if (empty($key)) {
            $this->_set_all();
        } elseif (is_array($key)) {
            $this->_params = array_merge($this->_params, $key);
        } elseif (is_string($key) && is_null($val)) {
            $this->_params = array_merge($this->_params, pro_search_decode($key));
        } elseif (! is_null($key)) {
            $this->_params[$key] = $val;
        }

        $this->_filter();
    }

    /**
     * Delete a parameter
     *
     * @access     public
     * @param      mixed
     * @return     void
     */
    public function delete($key)
    {
        unset($this->_params[$key]);
        unset(ee()->TMPL->tagparams[$key]);
    }

    /**
     * Reset all parameters
     *
     * @access     public
     * @return     void
     */
    public function reset()
    {
        $this->_query = null;
        $this->_params = array();
        $this->_tagparams = array();
    }

    /**
     * Read all parameters from query param or GET
     *
     * @access     private
     * @return     void
     */
    private function _set_all()
    {
        // --------------------------------------
        // Reset
        // --------------------------------------

        $this->reset();

        // --------------------------------------
        // Check for given query
        // --------------------------------------

        if (ee()->pro_search_settings->get('encode_query') == 'y') {
            // Read the query parameter
            $query_param = ee()->TMPL->fetch_param('query');

            // If query is given (not FALSE or empty string), try and decode it
            // Also ignore pagination segment
            if (! empty($query_param) && ! preg_match('/^P\d+$/', $query_param)) {
                $query_val = pro_search_decode($query_param);
                $this->_query = (empty($query_val)) ? false : $query_val;
            }
        } else {
            // Or else get it from the GET vars
            foreach ($_GET as $key => $val) {
                // Skip arrays
                if (is_array($val)) {
                    continue;
                }

                // Strip slashes if < PHP 5.4
                if (version_compare(PHP_VERSION, '5.4', '<')) {
                    $val = stripslashes($val);
                }

                $this->_query[$key] = $val;
            }
        }

        // --------------------------------------
        // Combine query with default custom parameters
        // --------------------------------------

        if (is_array($this->_query)) {
            $this->_params = $this->_query;
        }

        // --------------------------------------
        // Remember current tagparams
        // --------------------------------------

        if (! empty(ee()->TMPL->tagparams)) {
            $this->_tagparams = ee()->TMPL->tagparams;

            // but not the query param
            unset($this->_tagparams['query']);
        }

        // --------------------------------------
        // And search params
        // --------------------------------------

        if (! empty(ee()->TMPL->search_fields)) {
            foreach (ee()->TMPL->search_fields as $key => $val) {
                $this->_tagparams['search:' . $key] = $val;
            }
        }

        // --------------------------------------
        // Execute any SQL in the params
        // --------------------------------------

        $this->_sql_params();
        $this->_alias_params();

        // --------------------------------------
        // Also set the site IDs
        // --------------------------------------

        $this->_set_site_ids();
    }

    /**
     * Check tagparams for SELECTs
     */
    private function _sql_params()
    {
        foreach ($this->_tagparams as $key => &$val) {
            // Param should start with SELECT
            if (! preg_match('/^(=?not |=|[<>]=?)?\s?(select .*?);(.*)$/i', $val, $match)) {
                continue;
            }

            // List out the vars
            list($val, $prefix, $sql, $no_results) = $match;

            // Execute query
            $query = ee()->db->query($sql);

            // No results? Then the value is now -1
            $val = ($res = $query->result_array())
                ? $prefix . implode('|', pro_flatten_results($res, key($query->row())))
                : $no_results;

            // Make sure it's all set
            $this->set($key, $val);
            $this->apply($key, $val);

            // Log it
            ee()->TMPL->log_item("Pro Search: SQL param {$key}=\"{$val}\"");
        }
    }

    /**
     * Account for aliased parameters:
     *
     * alias:keywords="q"
     * alias:range-from:grid_field:grid_col="min"
     * alias:range-to:grid_field:grid_col="max"
     */
    private function _alias_params()
    {
        $pfx = 'alias:';

        $aliases = pro_array_get_prefixed($this->_tagparams, $pfx, true);

        foreach ($aliases as $key => $alias) {
            // Get the aliased value
            $val = $this->get($alias);

            // If it is empty, delete the target
            if (pro_not_empty($val)) {
                // Set aliased key based on alias
                $this->set($key, $val);
            } else {
                $this->delete($key);
            }

            // Then delete both the alias and alias:key params
            $this->delete($alias);
            $this->delete($pfx . $key);
        }
    }

    /**
     * Completely overwrite current params with given array
     */
    public function overwrite($array, $query = false)
    {
        $this->_params = $array;
        if ($query) {
            $this->_query = $array;
        }
        $this->_filter();
    }

    /**
     * Combine tagparams and other params
     */
    public function combine()
    {
        $this->_params = array_merge($this->_params, $this->_tagparams);
        $this->_set_site_ids();
        $this->_filter();
    }

    /**
     * Is given query valid?
     */
    public function valid_query()
    {
        return ! ($this->_query === false or $this->_query === array());
    }

    /**
     * Is there a given query, ie. not NULL
     */
    public function query_given()
    {
        return ! is_null($this->_query);
    }

    /**
     * Filter params to remove array values
     */
    private function _filter()
    {
        foreach ($this->_params as $key => $val) {
            if (is_array($val)) {
                unset($this->_params[$key]);
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Set defaults
     */
    public function set_defaults()
    {
        $pfx = 'default:';

        if ($defaults = $this->get_prefixed($pfx, true)) {
            foreach ($defaults as $key => $val) {
                // Set the default only if not already there
                if (empty($this->_params[$key])) {
                    $this->_params[$key] = $val;
                }

                // And forget it again
                $this->delete($pfx . $key);
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Return site ids
     */
    public function site_ids()
    {
        if (empty($this->_site_ids)) {
            $this->_set_site_ids();
        }

        return $this->_site_ids;
    }

    /**
     * Get site ids by parameter
     *
     * @access      private
     * @return      void
     */
    private function _set_site_ids()
    {
        // Reset
        $this->_site_ids = array();

        if (! empty($this->_tagparams['site'])) {
            $this->_site_ids = ee()->TMPL->site_ids;
        } elseif (empty($this->_params['site'])) {
            // No site param? limit to current site only
            $this->_site_ids[] = ee()->config->item('site_id');
        } elseif (! empty($this->_params['site'])) {
            // Read sites from parameter
            list($sites, $in) = $this->explode($this->_params['site']);

            // Set template site parameter
            ee()->TMPL->tagparams['site'] = $this->_params['site'];

            // Make sure all sites are fetched
            ee()->TMPL->_fetch_site_ids();

            // Shortcut to all sites
            $all_sites = ee()->TMPL->sites;

            // Numeric?
            $check = pro_array_is_numeric($sites) ? 'key' : 'val';

            // Loop through all sites and add some of them
            foreach ($all_sites as $key => $val) {
                if ($in === in_array($$check, $sites)) {
                    $this->_site_ids[$val] = $key;
                }
            }

            // And set to global TMPL
            ee()->TMPL->site_ids = $this->_site_ids;
        }

        // add 0 to site ids
        $this->_site_ids[] = 0;
    }

    // --------------------------------------------------------------------

    /**
     * Get parameter from $this->_params with fallback
     *
     * @access     public
     * @param      string
     * @param      mixed
     * @return     mixed
     */
    public function get($key = null, $fallback = null)
    {
        if (is_null($key)) {
            return $this->_params;
        } else {
            return (array_key_exists($key, $this->_params) &&
                    pro_not_empty($this->_params[$key]))
                ? $this->_params[$key]
                : $fallback;
        }
    }

    /**
     * Get prefixed parameters
     *
     * @access     public
     * @param      string
     * @return     array
     */
    public function get_prefixed($prefix = '', $strip = false)
    {
        return pro_array_get_prefixed($this->_params, $prefix, $strip);
    }

    /**
     * Get vars
     */
    public function get_vars($prefix = '')
    {
        $this->_filter();
        $vars = array();

        foreach ($this->_params as $key => $val) {
            // force string
            $val = (string) $val;
            $vars[$prefix . $key . ':raw'] = $val;
            $vars[$prefix . $key] = pro_format($val);
        }

        return $vars;
    }

    /**
     * Magic getter
     */
    public function __get($key)
    {
        $key = '_' . $key;

        return isset($this->$key) ? $this->$key : null;
    }

    // --------------------------------------------------------------------

    /**
     * Apply parameter key and value to TMPL tagparams
     *
     * @access     public
     * @param      mixed
     * @param      string
     * @return     void
     */
    public function apply($key = null, $val = null)
    {
        // What are we applying to the TMPL tagparams?
        if (empty($key)) {
            $vars = $this->_params;
        } elseif (is_array($key)) {
            $vars = $key;
        } else {
            $vars = array($key => $val);
        }

        // Loop through vars and set the tagparam
        foreach ($vars as $k => $v) {
            // If forget, set to NULL, else prep it
            $v = in_array($k, $this->forget)
               ? null
               : $this->prep($k, $v);

            // Set it
            $this->_set_tagparam($k, $v);
        }
    }

    /**
     * Set TMPL tagparam
     *
     * @access     private
     * @param      string
     * @param      string
     * @return     void
     */
    private function _set_tagparam($key, $val)
    {
        // If it's a protected param, don't do anything
        if (in_array($key, $this->_protected)) {
            return;
        }

        // Check for search fields and add parameter to either tagparams or search_fields
        if (strpos($key, 'search:') === 0) {
            $key = substr($key, 7);
            $array = 'search_fields';
        } else {
            $array = 'tagparams';
        }

        // Set or unset the value, depending on if it's NULL or not
        if (is_null($val)) {
            unset(ee()->TMPL->{$array}[$key]);
        } else {
            ee()->TMPL->{$array}[$key] = $val;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Explode parameter, previously pro_explode_param() helper function
     *
     * @access     public
     * @param      string    String like 'not 1|2|3' or '40|15|34|234'
     * @return     array     [0] = array of ids, [1] = boolean whether to include or exclude
     */
    public function explode($str)
    {
        // Initiate $in var to TRUE
        $in = true;

        // Check if parameter is "not bla|bla"
        if (ee()->pro_multibyte->strpos($str, 'not ') === 0) {
            // Change $in var accordingly
            $in = false;

            // Strip 'not ' from string
            $str = ee()->pro_multibyte->substr($str, 4);
        }

        // Return two values in an array
        return array(preg_split('/(&?&(?![\da-z]{2,6};|#\d{2,4};|#x[\da-f]{2,4};)|\|)/iu', $str), $in);
    }

    /**
     * Implode parameter, previously pro_implode_param() helper function
     */
    public function implode($array = array(), $in = true, $sep = '|')
    {
        // Initiate string
        $str = '';

        // Implode array
        if (! empty($array)) {
            $str = implode($sep, $array);

            // Prepend 'not '
            if ($in === false) {
                $str = 'not ' . $str;
            }
        }

        // Return string
        return $str;
    }

    /**
     * Merge two parameter values
     */
    public function merge($haystack, $needles, $as_param = false)
    {
        // Prep the haystack
        if (! is_array($haystack)) {
            // Explode the param, forget about the 'not '
            list($haystack, ) = $this->explode($haystack);
        }

        // Prep the needles
        if (! is_array($needles)) {
            list($needles, $in) = $this->explode($needles);
        } else {
            $in = true;
        }

        // Choose function to merge
        $method = $in ? 'array_intersect' : 'array_diff';

        // Do the merge thing
        $merged = $method($haystack, $needles);

        // Change back to parameter syntax if necessary
        if ($as_param) {
            $merged = implode('|', $merged);
        }

        return $merged;
    }

    // --------------------------------------------------------------------

    /**
     * Check if a value is present in a parameter
     */
    public function in_param($val, $param)
    {
        $it = false;

        if ($param = $this->get($param)) {
            list($fields, $in) = $this->explode($param);

            $it = in_array($val, $fields);
        }

        return $it;
    }

    // --------------------------------------------------------------------

    /**
     * Prep param value
     */
    public function prep($key, $val)
    {
        // --------------------------------------
        // Account for contains_words: \W
        // --------------------------------------

        if ($this->in_param($key, 'contains_words')) {
            list($items, $in) = $this->explode($val);

            foreach ($items as &$item) {
                if (substr($item, -2) != '\W') {
                    $item .= '\W';
                }
            }

            $val = $this->implode($items, $in);
        }

        // --------------------------------------
        // Account for require_all: & / &&
        // --------------------------------------

        if ($this->in_param($key, 'require_all')) {
            $amp = (substr($key, 0, 7) == 'search:') ? '&&' : '&';
            $val = str_replace('|', $amp, $val);
        }

        // --------------------------------------
        // Stuff to add to the beginning of the value
        // --------------------------------------

        $prepend = array(
            'gt'          => '>',
            'gte'         => '>=',
            'lt'          => '<',
            'lte'         => '<=',
            'exclude'     => 'not ',
            'exact'       => '=',
            'starts_with' => '^',
        );

        foreach ($prepend as $param => $str) {
            if ($this->in_param($key, $param) && substr($val, 0, strlen($str)) != $str) {
                $val = $str . $val;
            }
        }

        // --------------------------------------
        // Stuff to add to the end of the value
        // --------------------------------------

        $append = array(
            'ends_with' => '$'
        );

        foreach ($append as $param => $str) {
            if ($this->in_param($key, $param) && substr($val, -strlen($str)) != $str) {
                $val = $val . $str;
            }
        }

        // --------------------------------------
        // All done
        // --------------------------------------

        return $val;
    }
}
// End of file Pro_search_params.php
