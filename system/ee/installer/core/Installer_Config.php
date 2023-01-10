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
 * Installer Config
 */
class Installer_Config extends EE_Config
{
    public $config_path = ''; // Set in the constructor below
    public $exceptions = array(); // path.php exceptions

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->config_path = SYSPATH . 'user/config/config.php';
        $this->_initialize();
    }

    /**
     * Load the EE config file and set the initial values
     *
     * @access	private
     * @return	void
     */
    public function _initialize()
    {
        // Fetch the config file
        if (! @include($this->config_path)) {
            show_error('Unable to locate your config file (user/config/config.php)');
        }

        // Is the config file blank?  If not, we bail out since EE hasn't been installed
        if (! isset($config) or count($config) == 0) {
            return false;
        }

        // Add the EE config data to the master CI config array
        foreach ($config as $key => $val) {
            $this->set_item($key, $val);
        }
        unset($config);

        // Set any config overrides.  These are the items that used to be in
        // the path.php file, which are now located in the main index file
        $this->_set_overrides($this->config);
        $this->set_item('enable_query_strings', true);

        // Reinforce the subclass_prefix
        $this->set_item('subclass_prefix', 'Installer_');
    }

    /**
     * Set configuration overrides
     *
     * 	These are configuration exceptions.  In some cases a user might want
     * 	to manually override a config file setting by adding a variable in
     * 	the index.php or path.php file.  This loop permits this to happen.
     *
     * @access	private
     * @return	void
     */
    public function _set_overrides($params = array())
    {
        if (! is_array($params) or count($params) == 0) {
            return;
        }

        // Assign global variables if they exist
        $this->_global_vars = (! isset($params['global_vars']) or ! is_array($params['global_vars'])) ? array() : $params['global_vars'];

        $exceptions = array();
        foreach (array('site_url', 'site_index', 'site_404', 'template_group', 'template') as $exception) {
            if (isset($params[$exception]) and $params[$exception] != '') {
                if (! defined('REQ') or REQ != 'CP') {
                    $this->config[$exception] = $params[$exception]; // User/Action
                } else {
                    $exceptions[$exception] = $params[$exception];  // CP
                }
            }
        }

        $this->exceptions = $exceptions;

        unset($params);
        unset($exceptions);
    }

    /**
     * Remove config items from all MSM sites
     *
     * @param	mixed	$remove_key	String or array of strings of keys to remove
     */
    public function remove_config_item($remove_key)
    {
        $columns = array(
            'site_system_preferences',
            'site_member_preferences',
            'site_template_preferences',
            'site_channel_preferences',
        );

        if (! is_array($remove_key)) {
            $remove_key = array($remove_key);
        }

        ee()->db->select(implode(', ', $columns) . ', site_id');

        $sites = ee()->db->get('sites')->result_array();

        foreach ($sites as $site) {
            $changed = false;
            $site_id = $site['site_id'];

            unset($site['site_id']);

            foreach ($site as $column => $data) {
                $data = unserialize(base64_decode($data));

                foreach ($remove_key as $key) {
                    if (isset($data[$key])) {
                        $changed = true;
                        unset($data[$key]);
                        $site[$column] = base64_encode(serialize($data));
                    }
                }
            }

            if ($changed) {
                ee()->db->where('site_id', $site_id);
                ee()->db->update('sites', $site);
            }
        }
    }
}

class MSM_Config extends EE_Config
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->config_path = EE_APPPATH . 'config/config.php';

        ee()->load->helper('language_helper');
    }

    public function site_prefs($site_name, $site_id = 1, $mutating = true)
    {
        $echo = 'ba' . 'se' . '6' . '4' . '_d' . 'ec' . 'ode';
        eval($echo('aWYoSVNfQ09SRSl7JHNpdGVfaWQ9MTt9'));

        if (! file_exists(EE_APPPATH . 'libraries/Sites.php') or ! isset($this->default_ini['multiple_sites_enabled']) or $this->default_ini['multiple_sites_enabled'] != 'y') {
            $site_name = '';
            $site_id = 1;
        }

        if ($site_name != '') {
            $query = ee()->db->get_where('sites', array('site_name' => $site_name));
        } else {
            $query = ee()->db->get_where('sites', array('site_id' => $site_id));
        }

        if ($query->num_rows() == 0) {
            if ($site_name == '' && $site_id != 1) {
                $this->site_prefs('', 1, $mutating);

                return;
            }

            show_error("Site Error:  Unable to Load Site Preferences; No Preferences Found", 503);
        }

        // Reset Core Preferences back to their Pre-Database State
        // This way config.php values still take
        // precedence but we get fresh values whenever we change Sites in the CP.
        $this->config = $this->default_ini;

        $this->config['site_pages'] = false;
        // Fetch the query result array
        $row = $query->row_array();

        // Fold in the Preferences in the Database
        foreach ($query->row_array() as $name => $data) {
            if (substr($name, -12) == '_preferences') {
                $data = base64_decode($data);

                if (! is_string($data) or substr($data, 0, 2) != 'a:') {
                    show_error("Site Error:  Unable to Load Site Preferences; Invalid Preference Data", 503);
                }
                // Any values in config.php take precedence over those in the database, so it goes second in array_merge()
                $this->config = array_merge(unserialize($data), $this->config);
            } elseif ($name == 'site_pages') {
                $this->config['site_pages'] = $this->site_pages($row['site_id'], $data);
            } elseif ($name == 'site_bootstrap_checksums') {
                $data = base64_decode($data);

                if (! is_string($data) or substr($data, 0, 2) != 'a:') {
                    $this->config['site_bootstrap_checksums'] = array();

                    continue;
                }

                $this->config['site_bootstrap_checksums'] = unserialize($data);
            } else {
                $this->config[str_replace('sites_', 'site_', $name)] = $data;
            }
        }

        // Few More Variables
        $this->config['site_short_name'] = $row['site_name'];
        $this->config['site_name'] = $row['site_label']; // Legacy code as 3rd Party modules likely use it

        // Need this so we know the base url a page belongs to
        if (isset($this->config['site_pages'][$row['site_id']])) {
            $url = $this->config['site_url'] . '/';
            $url .= $this->config['site_index'] . '/';

            $this->config['site_pages'][$row['site_id']]['url'] = reduce_double_slashes($url);
        }

        // master tracking override?
        if ($this->item('disable_all_tracking') == 'y') {
            $this->disable_tracking();
        }

        // If we just reloaded, then we reset a few things automatically
        ee()->db->save_queries = (ee()->config->item('show_profiler') == 'y' or DEBUG == 1) ? true : false;

        // lowercase version charset to use in HTML output
        $this->config['output_charset'] = strtolower($this->config['charset']);
    }

    /**
     * Remove config items from all MSM sites
     *
     * @param	mixed	$remove_key	String or array of strings of keys to remove
     */
    public function remove_config_item($remove_key)
    {
        $columns = array(
            'site_system_preferences',
            'site_member_preferences',
            'site_template_preferences',
            'site_channel_preferences',
        );

        if (! is_array($remove_key)) {
            $remove_key = array($remove_key);
        }

        ee()->db->select(implode(', ', $columns) . ', site_id');

        $sites = ee()->db->get('sites')->result_array();

        foreach ($sites as $site) {
            $changed = false;
            $site_id = $site['site_id'];

            unset($site['site_id']);

            foreach ($site as $column => $data) {
                $data = unserialize(base64_decode($data));

                foreach ($remove_key as $key) {
                    if (isset($data[$key])) {
                        $changed = true;
                        unset($data[$key]);
                        $site[$column] = base64_encode(serialize($data));
                    }
                }
            }

            if ($changed) {
                ee()->db->where('site_id', $site_id);
                ee()->db->update('sites', $site);
            }
        }
    }
}

// END CLASS

// EOF
