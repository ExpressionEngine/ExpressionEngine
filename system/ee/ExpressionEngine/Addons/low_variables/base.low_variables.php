<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Low Variables Base Trait
 *
 * @package        low_variables
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-variables
 * @copyright      Copyright (c) 2015, Low
 */
trait Low_variables_base
{
    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Add-on version
     *
     * @var        string
     * @access     public
     */
    public $version;

    // --------------------------------------------------------------------

    /**
     * Package name
     *
     * @var        string
     * @access     protected
     */
    protected $package;

    /**
     * This add-on's info based on setup file
     *
     * @access      private
     * @var         object
     */
    protected $info;

    /**
     * Main class shortcut
     *
     * @var        string
     * @access     protected
     */
    protected $class_name;

    /**
     * Site id shortcut
     *
     * @var        int
     * @access     protected
     */
    protected $site_id;

    /**
     * Ignore Site shortcut
     *
     * @var        int
     * @access     protected
     */
    protected $ignore_site;

    /**
     * Libraries used
     *
     * @var        array
     * @access     protected
     */
    protected $libraries = array(
        'Low_variables_settings',
        'Low_variables_types',
        'Low_variables_sync',
        'Low_variables_ui',
    );

    /**
     * Models used
     *
     * @var        array
     * @access     protected
     */
    protected $models = array(
        'low_variables_group_model',
        'low_variables_variable_model'
    );

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Initialize Base data
     *
     * @access     public
     * @return     void
     */
    public function initializeBaseData()
    {
        // Set the package name
        $this->package = basename(__DIR__);

        // -------------------------------------
        //  Set info and version
        // -------------------------------------

        $this->info = ee('App')->get($this->package);
        $this->version = $this->info->getVersion();

        // -------------------------------------
        //  Load helper, libraries and models
        // -------------------------------------

        ee()->load->helper($this->package);
        ee()->load->library($this->libraries);
        ee()->load->model($this->models);

        // -------------------------------------
        //  Class name shortcut
        // -------------------------------------

        $this->class_name = ucfirst($this->package);

        // -------------------------------------
        //  Get site shortcut
        // -------------------------------------

        $this->site_id = (int) ee()->config->item('site_id');
    }

    // --------------------------------------------------------------------
}
// End class Low_variables_base
