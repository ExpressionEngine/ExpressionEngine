<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * CSS Loading Controller
 */
class Css extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('core');
        $this->core->bootstrap();
    }

    /**
     * _remap function
     *
     * Any CSS file in the view collection
     *
     * @access	private
     */
    public function _remap()
    {
        if (ee()->router->fetch_method() == 'cp_global_ext') {
            return $this->_cp_global_ext();
        }

        $file = 'global';
        $path = '';

        $cp_theme = $this->input->get_post('theme');

        if ($this->input->get_post('M') == 'third_party' && $package = $this->input->get_post('package')) {
            $package = strtolower($package);

            $file = $this->input->get_post('file');
            $path = PATH_THIRD . $package . '/';

            // There's a good chance we don't need ci_view_path
            // So try this first
            if (file_exists($path . 'css/' . $file . '.css')) {
                return $this->_load_css_file($path, $file);
            }

            //fallback to first-party addons
            $path = PATH_ADDONS . $package . '/';
            if (file_exists($path . 'css/' . $file . '.css')) {
                return $this->_load_css_file($path, $file);
            }
        } elseif ($this->input->get_post('M') !== false) {
            $file = $this->input->get_post('M');
        }

        $path = PATH_CP_THEME . 'css/' . $file . '.css';

        return $this->_load_css_file($path, $file);
    }

    /**
     * Load CSS File
     *
     * @access	public
     * @param	string		path to the CSS
     * @param	string		name of the CSS file, sans file extension
     * @return	void
     */
    private function _load_css_file($path, $file)
    {
        $file = ee()->security->sanitize_filename($file);

        if (! file_exists($path . 'css/' . $file . '.css')) {
            return false;
        }

        $this->output->out_type = 'cp_asset';
        $this->output->enable_profiler(false);

        $this->output->send_cache_headers(filemtime($path), 5184000, $path);

        @header('Content-type: text/css');

        $this->output->set_output(file_get_contents($path . 'css/' . $file . '.css'));

        if ($this->config->item('send_headers') == 'y') {
            @header('Content-Length: ' . strlen($this->output->final_output));
        }
    }

    /**
     * Control Panel Global Extension
     *
     * @access	public
     * @return	void
     */
    public function _cp_global_ext()
    {
        $str = '';

        /* -------------------------------------------
        /* 'cp_css_end' hook.
        /*  - Add CSS into a file call at the end of the control panel
        /*  - Added 2.1.2
        */
        $str = $this->extensions->call('cp_css_end');
        /*
        /* -------------------------------------------*/

        $this->output->out_type = 'cp_asset';
        $this->output->set_header("Content-Type: text/css");

        $this->output->set_header('Content-Length: ' . strlen($str));
        $this->output->set_output($str);
    }
}
// END CLASS

// EOF
