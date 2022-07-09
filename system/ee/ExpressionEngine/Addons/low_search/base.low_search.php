<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Low Search Base Class
 *
 * @package        low_search
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-search
 * @copyright      Copyright (c) 2020, Low
 */
trait Low_search_base
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
        'Low_multibyte',
        'Low_search_fields',
        'Low_search_params',
        'Low_search_settings'
    );

    /**
     * Models used
     *
     * @var        array
     * @access     protected
     */
    protected $models = array(
        'low_search_collection_model',
        'low_search_group_model',
        'low_search_index_model',
        'low_search_log_model',
        'low_search_replace_log_model',
        'low_search_shortcut_model',
        'low_search_word_model'
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

    /**
     * Return an MCP URL
     *
     * @access     protected
     * @param      string
     * @param      mixed     [array|string]
     * @param      bool
     * @return     mixed
     */
    protected function mcp_url($path = null, $extra = null, $obj = false)
    {
        // Base settings
        $segments = array('addons', 'settings', $this->package);

        // Add method to segments, of given
        if ($path) {
            $segments[] = $path;
        }

        // Create the URL
        $url = ee('CP/URL', implode('/', $segments));

        // Add the extras to it
        if (! empty($extra)) {
            // convert to array
            if (! is_array($extra)) {
                parse_str($extra, $extra);
            }

            // And add to the url
            $url->addQueryStringVariables($extra);
        }

        // Return it
        return ($obj) ? $url : $url->compile();
    }

    // --------------------------------------------------------------------
}
// End class Low_search_base
