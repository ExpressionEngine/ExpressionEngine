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

// include base class
if (! class_exists('Pro_variables_base')) {
    require_once(PATH_ADDONS . 'pro_variables/base.pro_variables.php');
}

/**
 * Pro Variables Module Class
 *
 * Class to be used in templates
 */
class Pro_variables
{
    // Use the base trait
    use Pro_variables_base;

    // --------------------------------------------------------------------
    //  PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Variables placeholder
     *
     * @access     private
     * @var        array
     */
    private $vars = array();

    // --------------------------------------------------------------------
    //  METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access     public
     * @return     void
     */
    public function __construct()
    {
        // Initialize base data for addon
        $this->initializeBaseData();
    }

    /**
     * Parse global template variables, alias for single use
     *
     * @access     public
     * @return     string
     * @see        parse()
     */
    public function single()
    {
        return $this->parse('');
    }

    /**
     * Parse global template variables, alias for pair use
     *
     * @access     public
     * @return     string
     * @see        parse()
     */
    public function pair()
    {
        return $this->parse(ee()->TMPL->tagdata);
    }

    /**
     * Parse global template variables, call type class if necessary
     *
     * @access     public
     * @param      string
     * @return     string
     */
    public function parse($tagdata = null)
    {
        // -------------------------------------
        //  Set tagdata
        // -------------------------------------

        if (is_null($tagdata)) {
            $tagdata = ee()->TMPL->tagdata;
        }

        // -------------------------------------
        //  Get site id and var name from var param
        // -------------------------------------

        list($var, $site_id) = $this->get_var_param();

        // -------------------------------------
        //  Store vars in $this->vars
        // -------------------------------------

        $this->get_vars($site_id);
        $this->get_types();

        if (defined('IS_PRO') && IS_PRO) {
            $local_vars = array();
            foreach ($this->vars as $t) {
                if (strpos($tagdata, "{$t['variable_name']}") !== false && $t['is_hidden'] === 'n') {
                    array_push($local_vars, $t);
                }
                //Get the early parsed variables
                elseif (!empty($t['variable_data']) && strpos($tagdata, "{$t['variable_data']}") !== false && $t['is_hidden'] === 'n') {
                    array_push($local_vars, $t);
                }
            }
            ee('pro:Prolet')->initialize('pro_variables', ['local' => $local_vars]);
        }

        // -------------------------------------
        //  Parse variables inside tagdata if no (valid) var is given
        // -------------------------------------

        if (empty($var)) {
            $this->_log('Replacing all variables inside tag pair with their data');

            // Initiate data array
            $data = array();

            // Loop through each of the vars and fill data array
            foreach ($this->vars as $key => $row) {
                // {my_var} {my_var:data} and {my_var:label}
                $data[$key] = $data[$key . ':data'] = $row['variable_data'];
                $data[$key . ':label'] = $row['variable_label'];
            }

            // Parse vars based on data array
            $it = ee()->TMPL->parse_variables_row($tagdata, $data);
        } elseif (array_key_exists($var, $this->vars)) {
            //  We have a single var. Focus on it. Get object from it.
            $row = $this->vars[$var];
            $obj = ee()->pro_variables_types->get($row);

            $this->_log('Generating output for ' . $var);

            // Call display output
            $it = $obj->replace_tag($tagdata);
            ee()->TMPL->set_data($it);
        } else {
            $this->_log("Var {$var} not found -- returning no results");
            $it = ee()->TMPL->no_results();
        }

        // Please
        return $it;
    }

    /**
     * Return the label for a given var
     *
     * Usage: {exp:pro_variables:label var="my_variable_name"}
     *
     * @access     public
     * @return     string
     */
    public function label()
    {
        // -------------------------------------
        //  Get site id and var name from var param
        // -------------------------------------

        list($var, $site_id) = $this->get_var_param();

        // -------------------------------------
        //  Store vars in $this->vars
        // -------------------------------------

        $this->get_vars($site_id);

        // -------------------------------------
        //  Return the label, if present
        // -------------------------------------

        return isset($this->vars[$var]) ? $this->vars[$var]['variable_label'] : '';
    }

    /**
     * Fetch and return all options from var settings
     *
     * @access     public
     * @return     string
     */
    public function options()
    {
        // -------------------------------------
        //  Get site id and var name from var param
        // -------------------------------------

        list($var, $site_id) = $this->get_var_param();

        // -------------------------------------
        //  Store vars in $this->vars
        // -------------------------------------

        $this->get_vars($site_id);
        $this->get_types();

        // -------------------------------------
        //  Get parameter
        // -------------------------------------

        if (! $var || ! isset($this->vars[$var])) {
            $this->_log('No valid var-parameter found, returning no results');

            return ee()->TMPL->no_results();
        }

        // -------------------------------------
        //  Focus on given var, get object from it
        // -------------------------------------

        $row = $this->vars[$var];
        $obj = ee()->pro_variables_types->get($row);

        // -------------------------------------
        //  Get the options from its settings
        // -------------------------------------

        $options = $obj->settings('options', false);

        // -------------------------------------
        //  If given var is a fieldtype or no options exist, then don't bother
        // -------------------------------------

        if ($options === false) {
            $this->_log("Variable {$var} doesn't support the Options tag");

            return ee()->TMPL->no_results();
        }

        // -------------------------------------
        //  No options? Bail out
        // -------------------------------------

        if (! ($options = PVUI::choices($options))) {
            $this->_log('No options found, returning no results');

            return ee()->TMPL->no_results();
        }

        // -------------------------------------
        //  Get active items
        // -------------------------------------

        $active = ($sep = $obj->settings('separator'))
            ? PVUI::explode($sep, $row['variable_data'])
            : array($row['variable_data']);

        // -------------------------------------
        //  Initiate vars
        // -------------------------------------

        $data = array();
        $total = count($options);
        $i = 0;

        // loop through options, populate variables array
        foreach ($options as $key => $val) {
            $data[] = array(
                $var . ':data'          => $key,
                $var . ':label'         => $row['variable_label'],
                $var . ':data_label'    => $val,
                $var . ':count'         => ++$i,
                $var . ':total_results' => $total,

                'active'   => (in_array($key, $active) ? 'y' : ''),
                'checked'  => (in_array($key, $active) ? ' checked="checked"' : ''),
                'selected' => (in_array($key, $active) ? ' selected="selected"' : '')
            );
        }

        // Parse template
        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $data);
    }

    // --------------------------------------------------------------------
    //  ACT METHODS
    // --------------------------------------------------------------------

    /**
     * Sync vars by ACT
     *
     * @access     public
     * @return     void
     */
    public function sync()
    {
        // Only ACTions are allowed
        if (REQ != 'ACTION') {
            return;
        }

        // Get the settings
        $key1 = $this->license_key;
        $key2 = ee('Request')->get('key');

        // Nope
        if ($key1 !== $key2) {
            show_error('Action not allowed');
        }

        // Or else, sync it
        // ee()->pro_variables_sync->files();

        // Clear cache too?
        if (ee('Request')->get('clear_cache') == 'yes') {
            ee()->functions->clear_caching('all', '', true);
        }
    }

    // --------------------------------------------------------------------
    //  PRIVATE METHODS
    // --------------------------------------------------------------------

    /**
     * Get the site id and cleaned var from a var="" parameter value
     *
     * @access     private
     * @return     array
     */
    private function get_var_param()
    {
        // -------------------------------------
        //  Get the var parameter value
        // -------------------------------------

        $var = ee()->TMPL->fetch_param('var', false);

        // -------------------------------------
        //  Default site id to current site id
        // -------------------------------------

        $site_id = $this->site_id;

        // -------------------------------------
        //  Get site id based on site_name:var_name value
        // -------------------------------------

        if (! empty($var) && ($pos = strpos($var, ':')) !== false) {
            // Get the part before the :
            $prefix = substr($var, 0, $pos);

            // If MSM is enabled and prefix is a valid site name
            if (ee()->config->item('multiple_sites_enabled') == 'y' && in_array($prefix, ee()->TMPL->sites)) {
                // Strip prefix from var name
                $var = substr($var, $pos + 1);

                // Get the correct site ID
                $site_id = array_search($prefix, ee()->TMPL->sites);

                // And make note of it in the log
                $this->_log("Found var {$var} in site {$prefix}");
            }
        }

        // -------------------------------------
        //  Return the site id and cleaned var name
        // ------------------------------------

        return array($var, $site_id);
    }

    /**
     * Get variables for given site from cache or DB
     *
     * @access     private
     * @param      int
     * @return     array
     */
    private function get_vars($site_id = false)
    {
        // -------------------------------------
        //  Reset
        // -------------------------------------

        $this->vars = array();

        // -------------------------------------
        //  If no site id is given, use current
        // -------------------------------------

        if ($site_id == false) {
            $site_id = $this->site_id;
        }

        // -------------------------------------
        //  Get cached vars
        // -------------------------------------

        $var_cache = pro_get_cache($this->package, 'vars') ?: [];

        if (isset($var_cache[$site_id])) {
            $this->_log('Getting variables from Session Cache');

            $this->vars = $var_cache[$site_id];
        } else {
            $this->_log('Getting variables from Database');

            // Get vars for the site
            $this->vars = ee()->pro_variables_variable_model->get_by_site($site_id);
            $this->vars = pro_associate_results($this->vars, 'variable_name');

            // Register to cache
            $var_cache[$site_id] = $this->vars;

            pro_set_cache($this->package, 'vars', $var_cache);
        }

        return $this->vars;
    }

    /**
     * Get variables types from cache or settings
     *
     * @access     private
     * @return     array
     */
    private function get_types()
    {
        static $types;

        if (! $types) {
            $types = ee()->pro_variables_types->load_enabled();
        }

        return $types;
    }

    // --------------------------------------------------------------------

    /**
     * Log template item
     */
    private function _log($msg)
    {
        ee()->TMPL->log_item('Pro Variables: ' . $msg);
    }
}
// End class

/* End of file mod.pro_variables.php */
