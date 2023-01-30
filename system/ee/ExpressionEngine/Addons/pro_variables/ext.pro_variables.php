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
 * Pro Variables Extension class
 */
class Pro_variables_ext
{
    // Use the base trait
    use Pro_variables_base;

    public $settings;

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access      public
     * @param       array
     * @return      void
     */
    public function __construct($settings = array())
    {
        // Initialize base data for addon
        $this->initializeBaseData();

        // Set the Settings object
        ee()->pro_variables_settings->set($settings);

        // Assign current settings
        $this->settings = ee()->pro_variables_settings->get();
    }

    // --------------------------------------------------------------------
    // HOOKS
    // --------------------------------------------------------------------

    /**
     * Optionally sync vars from files
     *
     * @access     public
     * @param      object
     * @return     object
     */
    public function sessions_end($SESS)
    {
        //  Do we have to sync files?
        // if ($this->settings['save_as_files'] == 'y')
        // {
        //  // Only if we're displaying the site or the module in the CP
        //  if (REQ == 'PAGE' || (REQ == 'CP' && ee()->uri->segment(4) == $this->package))
        //  {
        //      ee()->pro_variables_sync->files();
        //  }
        // }

        return $SESS;
    }

    /**
     * Add early parsed variables to config->_global_vars() array
     *
     * @access     public
     * @param      array
     * @return     array
     */
    public function template_fetch_template($row)
    {
        // -------------------------------------------
        // Get the latest version of $row
        // -------------------------------------------

        if (ee()->extensions->last_call !== false) {
            $row = ee()->extensions->last_call;
        }

        // -------------------------------------------
        // Call add_vars method
        // -------------------------------------------

        if ($this->settings['register_globals'] != 'n') {
            $this->_add_vars();
        }

        // Play nice, return it
        return $row;
    }

    /**
     * Add early parsed variables to config->_global_vars() array
     *
     * @access     private
     * @return     void
     */
    private function _add_vars()
    {
        // -------------------------------------
        //  Define static var to keep track of
        //  whether we've added vars already...
        // -------------------------------------

        static $added;

        // ...if so, just bail out
        if ($added) {
            return;
        }

        // -------------------------------------
        //  Initiate data array
        // -------------------------------------

        $early = array();

        // -------------------------------------
        //  Get global variables to parse early, ordered the way they're displayed in the CP
        // -------------------------------------

        $early = ee()->pro_variables_variable_model->get_early();
        $early = pro_flatten_results($early, 'variable_data', 'variable_name');

        // -------------------------------------
        //  Add variables to early parsed global vars
        // -------------------------------------

        if ($early) {
            ee()->config->_global_vars
                = ($this->settings['register_globals'] == 'y')
                ? array_merge($early, ee()->config->_global_vars)
                : array_merge(ee()->config->_global_vars, $early);
        }

        // Remember that we've added the vars so we don't do it again
        $added = true;
    }

    // --------------------------------------------------------------------
}
// End Class pro_variables_ext

/* End of file ext.pro_variables.php */
