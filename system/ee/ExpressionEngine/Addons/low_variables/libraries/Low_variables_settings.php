<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Low Variables Settings class
 *
 * @package        low_variables
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-variables
 * @copyright      Copyright (c) 2009-2020, Low
 */
class Low_variables_settings
{
    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Settings
     *
     * @access     private
     * @var        array
     */
    private $_settings = array();

    /**
     * Default settings
     *
     * @var        array
     * @access     private
     */
    private $_default_settings = array(
        'can_manage'       => array(1),
        'clear_cache'      => 'n',
        'register_globals' => 'n',
        'save_as_files'    => 'n',
        'file_path'        => '',
        'one_way_sync'     => 'n',
        'enabled_types'    => array('low_textarea')
    );

    /**
     * Custom config settings
     *
     * @var        array
     * @access     private
     */
    private $_cfg = array(
        'save_as_files',
        'file_path',
        'one_way_sync'
    );

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Set the settings
     */
    public function set($settings)
    {
        $this->_settings = array_merge($this->_default_settings, (array) $settings);

        $this->_config_overrides();

        return $this->_settings;
    }

    // --------------------------------------------------------------------

    /**
     * Magic getter
     */
    public function __get($str)
    {
        return $this->get($str);
    }

    // --------------------------------------------------------------------

    /**
     * Get setting
     */
    public function get($key = null)
    {
        if (empty($this->_settings)) {
            // Not set yet? Get from DB and add to cache
            $ext = ee('Model')
                ->get('Extension')
                ->filter('class', 'Low_variables_ext')
                ->first();

            $this->_settings = $ext ? $ext->settings : array();

            $this->_config_overrides();
        }

        // Always fallback to default settings
        $this->_settings = array_merge($this->_default_settings, $this->_settings);

        return is_null($key)
            ? $this->_settings
            : (isset($this->_settings[$key]) ? $this->_settings[$key] : null);
    }

    // --------------------------------------------------------------------

    /**
     * Apply Config overrides to $this->settings
     *
     * @access     protected
     * @return     void
     */
    private function _config_overrides()
    {
        // Check custom config values
        foreach ($this->_cfg as $key) {
            // Check the config for the value
            $val = ee()->config->item('low_variables_' . $key);

            // If not FALSE, override the settings
            if ($val !== false) {
                $this->_settings[$key] = $val;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Check if given setting is present in the config file
     *
     * @access     public
     * @return     bool
     */
    public function is_config($item)
    {
        return (in_array($item, $this->_cfg) && (ee()->config->item('low_variables_' . $item) !== false));
    }

    // --------------------------------------------------------------------

    /**
     * Is current user a variable manager?
     *
     * @access     public
     * @return     bool
     */
    public function can_manage()
    {
        // Member Group ID
        $group_id = ee()->session->userdata('group_id');

        return ($group_id == 1 || in_array($group_id, $this->get('can_manage')));
    }

    // --------------------------------------------------------------------

    /**
     * Return default settings
     *
     * @access     public
     * @return     bool
     */
    public function default_settings()
    {
        return $this->_default_settings;
    }
}
// End of file Low_variables_settings.php
