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
 * Core Addons
 */
class EE_Addons
{
    public $_map;						// addons sorted by addon_type (plural)
    public $_packages = array();		// contains references to _map by package name
    public $_exclusions = array();

    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct()
    {
        $this->_exclusions = array(
            'channel',
        );
    }

    /**
     * Add-on File Handler
     *
     * @access	private
     * @param	string
     * @return	void
     */
    public function get_files($type = 'modules')
    {
        $type_ident = array(
            'modules' => 'mcp',
            'extensions' => 'ext',
            'plugins' => 'pi',
            'fieldtypes' => 'ft'
        );

        if (! is_array($this->_map)) {
            ee()->load->helper('directory');

            // Initialize the _map array so if no addons of a certain type
            // are found, we can still return _map[$type] without errors

            $this->_map = array(
                'modules' => array(),
                'extensions' => array(),
                'plugins' => array(),
                'fieldtypes' => array()
            );

            foreach (array(PATH_THIRD, PATH_ADDONS) as $path) {
                if (($map = directory_map($path, 2)) !== false) {
                    $this->package_list($map, '', false, $path);
                }
            }

            if ($type != '') {
                ksort($this->_map[$type]);
            }
            ksort($this->_packages);
        }

        // And now first party addons - will override any third party packages of the same name.
        // We can be a little more efficient here and only check the directory they asked for

        static $_fp_read = array('extensions', 'modules', 'fieldtypes');

        // is_package calls this function with a blank key to skip
        // first party - we'll do that right here instead of checking
        // if the folder exists
        if (! array_key_exists($type, $type_ident)) {
            return array();
        }

        if (! in_array($type, $_fp_read)) {
            ee()->load->helper('file');

            $ext_len = strlen('.php');

            $abbr = $type_ident[$type];

            $root_path = ($abbr == 'mcp') ? PATH_ADDONS : constant('PATH_' . strtoupper($abbr));

            $list = get_filenames($root_path);

            if (is_array($list)) {
                foreach ($list as $file) {
                    if (strncasecmp($file, $abbr . '.', strlen($abbr . '.')) == 0 &&
                        substr($file, -$ext_len) == '.php' &&
                        strlen($file) > strlen($abbr . '.' . '.php')) {
                        $name = substr($file, strlen($abbr . '.'), - $ext_len);
                        $class = ($abbr == 'pi') ? ucfirst($name) : ucfirst($name) . '_' . $abbr;
                        $path = ($abbr == 'ext' or $abbr == 'acc' or $abbr == 'ft' or $abbr == 'rte') ? constant('PATH_' . strtoupper($abbr)) : $root_path . $name . '/';

                        $this->_map[$type][$name] = array(
                            'path' => $path,
                            'file' => $file,
                            'name' => ucwords(str_replace('_', ' ', $name)),
                            'class' => $class
                        );
                    }
                }
            }

            $_fp_read[] = $type;

            ksort($this->_map[$type]);
        }

        return $this->_map[$type];
    }

    /**
     * Create package array
     *
     * @access	private
     * @param	array
     * @param	string
     * @param	bool
     * @return	void
     */
    public function package_list($map, $type = '', $native = false, $path_prefix = '')
    {
        $type_ident = array(
            'modules' => 'mcp',
            'extensions' => 'ext',
            'plugins' => 'pi',
            'fieldtypes' => 'ft'
        );

        // First party is plural, third party is singular
        // so we need some inflection references

        $_plural_map = array(
            'modules' => 'module',
            'extensions' => 'extension',
            'plugins' => 'plugin',
            'fieldtypes' => 'fieldtype'
        );

        $type = ($type == '') ? '' : $type . '/';

        foreach ($map as $pkg_name => $files) {
            if (! is_array($files)) {
                $files = array($files);
            }

            foreach ($files as $file) {
                if (is_array($file)) {
                    // we're only interested in the top level files for the addon
                    continue;
                }

                foreach ($type_ident as $addon_type => $ident) {
                    // Fieldtypes can have names that do not match the $pkg_name
                    $valid = ($ident === 'ft') ? preg_match('/^' . $ident . '\.(.*?)\.php$/', $file, $match) : ($file == $ident . '.' . $pkg_name . '.php');

                    if ($valid) {
                        $name = ($ident === 'ft') ? $match[1] : $pkg_name;

                        if (in_array($name, $this->_exclusions)) {
                            continue;
                        }

                        // Plugin classes don't have a suffix
                        $class = ($ident == 'pi') ? ucfirst($name) : ucfirst($name) . '_' . $ident;
                        $path = $path_prefix . $pkg_name . '/';
                        $author = ($native) ? 'native' : 'third_party';

                        $this->_map[$addon_type][$name] = array(
                            'path' => $path,
                            'file' => $file,
                            'name' => ucwords(str_replace('_', ' ', $name)),
                            'class' => $class,
                            'package' => $pkg_name,
                            'type' => $author
                        );

                        // Add cross-reference for package lookups - singular keys
                        if ($ident === 'ft') {
                            // For fieldtypes, _packages is an array, since there can be multiple fieldtypes per package
                            if (! isset($this->_packages[$pkg_name][$_plural_map[$addon_type]])) {
                                $this->_packages[$pkg_name][$_plural_map[$addon_type]] = array();
                            }

                            $this->_packages[$pkg_name][$_plural_map[$addon_type]][$name] = & $this->_map[$addon_type][$name];
                        } else {
                            $this->_packages[$pkg_name][$_plural_map[$addon_type]] = & $this->_map[$addon_type][$pkg_name];
                        }

                        break;
                    }
                }
            }
        }
    }

    /**
     * Get information on what's installed
     *
     * @access	private
     * @param	string	$type	The type of add-on to filter by
     * @param	bool	$reset	Reset the previously saved installed values
     * @return	void
     */
    public function get_installed($type = 'modules', $reset = false)
    {
        static $_installed = array();

        if ($reset) {
            $_installed = array();
        }

        if (isset($_installed[$type])) {
            return $_installed[$type];
        }

        $_installed[$type] = array();

        ee()->load->model('addons_model');

        if ($type == 'modules') {
            $query = ee()->addons_model->get_installed_modules();

            if ($query->num_rows() > 0) {
                $files = $this->get_files('modules');

                foreach ($query->result_array() as $row) {
                    if (isset($files[$row['module_name']])) {
                        $_installed[$type][$row['module_name']] = array_merge($files[$row['module_name']], $row);
                    }
                }
            }
        } elseif ($type == 'extensions') {
            $query = ee()->addons_model->get_installed_extensions();

            if ($query->num_rows() > 0) {
                $files = $this->get_files('extensions');

                foreach ($query->result_array() as $row) {
                    $name = strtolower(substr($row['class'], 0, -4));

                    if (isset($files[$name])) {
                        $_installed[$type][$name] = array_merge($files[$name], $row);
                    }
                }
            }
        } elseif ($type == 'fieldtypes') {
            $query = ee()->db->get('fieldtypes');

            if ($query->num_rows() > 0) {
                $files = $this->get_files('fieldtypes');

                foreach ($query->result_array() as $row) {
                    $name = $row['name'];

                    if (isset($files[$name])) {
                        $_installed[$type][$name] = array_merge($files[$name], $row);
                    }
                }
            }
        }

        return $_installed[$type];
    }

    /**
     * Install a given list of first-party modules
     *
     * @param  array  $modules Array of first party module names
     * @return [array]         Array of any module install errors
     */
    public function install_modules($modules)
    {
        $module_install_errors = array();

        foreach ($modules as $module) {
            $path = SYSPATH . 'ee/ExpressionEngine/Addons/' . $module . '/';

            if (file_exists($path . 'upd.' . $module . '.php')) {
                // Add the helper/library load path and temporarily
                ee()->load->add_package_path($path, false);

                require $path . 'upd.' . $module . '.php';

                $class = ucfirst($module) . '_upd';

                $UPD = new $class();
                $UPD->install_errors = array();

                if (method_exists($UPD, 'install')) {
                    $UPD->install();
                    if (count($UPD->install_errors) > 0) {
                        // clean and combine
                        $module_install_errors[$module] = array_map(
                            'htmlentities',
                            $UPD->install_errors
                        );
                    }
                }

                // remove package path
                ee()->load->remove_package_path($path);
            }
        }

        return $module_install_errors;
    }

    /**
     * Is package
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function is_package($name)
    {
        $this->get_files('');	// blank key lets us skip first party

        return array_key_exists($name, $this->_packages);
    }

    /**
     * Package
     *
     * @access	public
     * @param	string
     * @return	mixed - FALSE if not a package, native or third_party otherwise
     */
    public function package_type($name, $type)
    {
        $this->get_files($type);	// blank key lets us skip first party

        if (! array_key_exists($name, $this->_packages)) {
            return false;
        }

        return $this->_map[$type][$name]['type'];
    }
}
// END Addons class

// EOF
