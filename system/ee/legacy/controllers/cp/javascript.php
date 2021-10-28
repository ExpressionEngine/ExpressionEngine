<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Javascript Loading Controller
 */
class Javascript extends CI_Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('core');
        $this->core->bootstrap();

        $this->lang->loadfile('jquery');
        $this->load->library('javascript_loader');
    }

    /**
     * Index function
     *
     * @access	public
     * @return	void
     */
    public function index()
    {
        // use view->script_tag() instead
        // $this->load('jquery');
    }

    /**
     * Spellcheck iFrame
     *
     * Used by the Spellcheck crappola
     *
     * @access	public
     * @return	void
     */
    public function spellcheck_iframe()
    {
        $this->output->enable_profiler(false);

        if (! class_exists('EE_Spellcheck')) {
            require APPPATH . 'libraries/Spellcheck.php';
        }

        return EE_Spellcheck::iframe();
    }

    /**
     * Spellcheck
     *
     * Used by the Spellcheck crappola
     *
     * @access	public
     * @return	void
     */
    public function spellcheck()
    {
        $this->output->enable_profiler(false);

        if (! class_exists('EE_Spellcheck')) {
            require APPPATH . 'libraries/Spellcheck.php';
        }

        return EE_Spellcheck::check();
    }

    /**
     * Load
     *
     * Sends jQuery files to the browser
     *
     * @access	public
     * @return	type
     */
    public function load($loadfile = '')
    {
        $this->output->enable_profiler(false);

        $file = '';
        $cp_theme = $this->input->get_post('theme');
        $package = $this->input->get_post('package');

        // trying to load a specific js file?
        $loadfile = $this->input->get_post('file');
        $loadfile = $this->security->sanitize_filename($loadfile, true);

        if ($loadfile == 'ext_scripts') {
            return $this->_ext_scripts();
        }

        if ($package && $loadfile) {
            $file = PATH_THIRD . $package . '/javascript/' . $loadfile . '.js';
        } elseif ($loadfile == '') {
            if (($plugin = $this->input->get_post('plugin')) !== false) {
                $plugin = ee()->security->sanitize_filename($plugin);
                $file = PATH_JAVASCRIPT . 'jquery/plugins/' . $plugin . '.js';
            } elseif (($ui = $this->input->get_post('ui')) !== false) {
                $ui = ee()->security->sanitize_filename($ui);
                $file = PATH_JAVASCRIPT . 'jquery/ui/jquery.ui.' . $ui . '.js';
            }
        } else {
            $file = PATH_JAVASCRIPT . $loadfile . '.js';
        }

        if (! $file or ! file_exists($file)) {
            if ($this->config->item('debug') >= 1) {
                $this->output->fatal_error(lang('missing_jquery_file'));
            } else {
                return false;
            }
        }

        // Can't do any of this if we're not allowed
        // to send any headers

        $this->javascript_loader->set_headers($file);

        // Grab the file, content length and serve
        // it up with the proper content type!

        $contents = file_get_contents($file);

        $this->output->set_header('Content-Length: ' . strlen($contents));
        $this->output->set_output($contents);
    }

    /**
     * Javascript from extensions
     *
     * This private method is intended for usage by the 'add_global_cp_js' hook
     *
     * @access 	private
     * @return 	void
     */
    public function _ext_scripts()
    {
        $str = '';

        /* -------------------------------------------
        /* 'cp_js_end' hook.
        /*  - Add Javascript into a file call at the end of the control panel
        /*  - Added 2.1.2
        */
        $str = $this->extensions->call('cp_js_end');
        /*
        /* -------------------------------------------*/

        $this->output->out_type = 'cp_asset';
        $this->output->set_header("Content-Type: text/javascript");
        $this->output->set_header("Cache-Control: no-cache, must-revalidate");
        $this->output->set_header('Content-Length: ' . strlen($str));
        $this->output->set_output($str);
    }

    /**
     * Javascript Combo Loader
     *
     * Combo load multiple javascript files to reduce HTTP requests
     * BASE.AMP.'C=javascript&M=combo&ui=ui,packages&file=another&plugin=plugins&package=third,party,packages'
     *
     * @access public
     * @return string
     */
    public function combo_load()
    {
        $this->javascript_loader->combo_load();
    }
}

// EOF
