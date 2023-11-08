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
 * Core Add-on Installer
 */
class Addons_installer
{
    /**
     * Constructor
     */
    public function __construct()
    {
        ee()->load->library('api');
        if (!isset(ee()->addons)) {
            ee()->load->library('addons');
        }
        ee()->lang->loadfile('modules');
    }

    /**
     * Add-on Installer
     *
     * @access	public
     * @param	string
     * @return	void
     */
    public function install($addon, $type = 'module', $show_package = true)
    {
        $this->_update_addon($addon, $type, 'install', $show_package);

        return true;
    }

    /**
     * Add-on Uninstaller
     *
     * Install one or more components
     *
     * @access	public
     * @param	string
     * @return	void
     */
    public function uninstall($addon, $type = 'module', $show_package = true)
    {
        $this->_update_addon($addon, $type, 'uninstall', $show_package);

        return true;
    }

    /**
     * Module Installer
     *
     * @access	private
     * @param	string
     * @return	void
     */
    public function install_module($module)
    {
        $class = $this->_module_install_setup($module);

        $MOD = new $class();

        if ($MOD->install() !== true) {
            show_error(lang('module_can_not_be_found'));
        }
    }

    /**
     * Module Uninstaller
     *
     * @access	private
     * @param	string
     * @return	void
     */
    public function uninstall_module($module)
    {
        $class = $this->_module_install_setup($module);

        $MOD = new $class();

        if ($MOD->uninstall() !== true) {
            show_error(lang('module_can_not_be_found'));
        }
    }

    /**
     * Extension Installer
     *
     * @access	private
     * @param	string
     * @return	void
     */
    public function install_extension($extension, $enable = false)
    {
        ee()->load->model('addons_model');

        if (! ee()->addons_model->extension_installed($extension)) {
            $EXT = $this->_extension_install_setup($extension);

            if (method_exists($EXT, 'activate_extension') === true) {
                $activate = $EXT->activate_extension();
            }
        } else {
            $class = $this->_extension_install_setup($extension, false);
            ee()->addons_model->update_extension($class, array('enabled' => 'y'));
        }
    }

    /**
     * Extension Uninstaller
     *
     * @access	private
     * @param	string
     * @return	void
     */
    public function uninstall_extension($extension)
    {
        ee()->load->model('addons_model');
        $EXT = $this->_extension_install_setup($extension);

        ee()->addons_model->update_extension(ucfirst(get_class($EXT)), array('enabled' => 'n'));

        if (method_exists($EXT, 'disable_extension') === true) {
            $disable = $EXT->disable_extension();
        }
    }

    /**
     * Fieldtype Installer
     *
     * @access	private
     * @param	string
     * @return	void
     */
    public function install_fieldtype($fieldtype)
    {
        ee()->legacy_api->instantiate('channel_fields');

        if (ee()->api_channel_fields->include_handler($fieldtype)) {
            $default_settings = array();
            $FT = ee()->api_channel_fields->setup_handler($fieldtype, true);

            $default_settings = $FT->install();

            ee()->db->insert('fieldtypes', array(
                'name' => $fieldtype,
                'version' => $FT->info['version'],
                'settings' => base64_encode(serialize((array) $default_settings)),
                'has_global_settings' => method_exists($FT, 'display_global_settings') ? 'y' : 'n'
            ));

            ee()->load->library('content_types');

            foreach (ee()->content_types->all() as $content_type) {
                if ($FT->accepts_content_type($content_type)) {
                    ee()->api_channel_fields->apply('register_content_type', array($content_type));
                }
            }
        }
    }

    /**
     * Fieldtype Uninstaller
     *
     * @access	private
     * @param	string
     * @return	void
     */
    public function uninstall_fieldtype($fieldtype)
    {
        ee()->legacy_api->instantiate('channel_fields');

        if (ee()->api_channel_fields->include_handler($fieldtype)) {
            ee()->load->dbforge();

            // Uninstall
            $FT = ee()->api_channel_fields->setup_handler($fieldtype, true);
            $FT->uninstall();

            ee()->db->delete('fieldtypes', array('name' => $fieldtype));
        }
    }

    /**
     * Module Install Setup
     *
     * Contains common code for install and uninstall routines
     *
     * @access	private
     * @param	string
     * @return	void
     */
    public function _module_install_setup($module)
    {
        if (! ee('Permission')->can('admin_addons')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if ($module == '') {
            show_error(lang('module_can_not_be_found'));
        }

        try {
            $info = ee('App')->get($module);
        } catch (\Exception $e) {
            show_error(lang('module_can_not_be_found'));
        }

        $path = $info->getPath() . '/upd.' . $module . '.php';

        if (! is_file($path)) {
            show_error(lang('module_can_not_be_found'));
        }

        $class = ucfirst($module) . '_upd';

        if (! class_exists($class)) {
            require $path;
        }

        return $class;
    }

    /**
     * Extension Install Setup
     *
     * Contains common code for install and uninstall routines
     *
     * @access	private
     * @param	string
     * @return	void
     */
    public function _extension_install_setup($extension, $instantiate = true)
    {
        if (! ee('Permission')->can('access_extensions')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if ($extension == '') {
            show_error(lang('no_extension_id'));
        }

        $class = ucfirst($extension) . '_ext';

        if (! $instantiate) {
            return $class;
        }

        if (! class_exists($class)) {
            include(ee()->addons->_packages[$extension]['extension']['path'] . 'ext.' . $extension . '.php');
        }

        return new $class();
    }

    /**
     * Universal Add-on (Un)Installer
     *
     * @access	private
     * @param	string
     * @return	void
     */
    private function _update_addon($addon, $type, $action, $show_package)
    {
        // accepts arrays
        if (is_array($type)) {
            foreach ($type as $component) {
                $this->_update_addon($addon, $component, $action, $show_package);
            }

            return;
        }

        // first party
        if (! ee()->addons->is_package($addon)) {
            return $this->{$action . '_' . $type}($addon);
        }

        ee()->load->model('addons_model');

        // third party - do entire package
        if ($show_package && count(ee()->addons->_packages[$addon]) > 1) {
            ee()->functions->redirect(BASE . AMP . 'C=addons' . AMP . 'M=package_settings' . AMP . 'package=' . $addon . AMP . 'return=' . $_GET['C']);
        } else {
            $method = $action . '_' . $type;

            if (method_exists($this, $method)) {
                // Fieldtypes provide an array of multiple fieldtypes
                if ($type === 'fieldtype') {
                    foreach (ee()->addons->_packages[$addon][$type] as $fieldtype_name => $fieldtype_settings) {
                        $installed = ee()->addons_model->fieldtype_installed($fieldtype_name);

                        //don't perform action if it's not necessary, ie it's already installed or uninstalled
                        if (($action === 'install' && ! $installed) || ($action === 'uninstall' && $installed)) {
                            ee()->load->add_package_path($fieldtype_settings['path'], false);

                            $this->$method($fieldtype_name);

                            ee()->load->remove_package_path($fieldtype_settings['path']);
                        }

                        // Remove associated Channel fields and Grid columns
                        if ($action === 'uninstall' && $installed) {
                            ee('Model')->get('ChannelField')
                                ->filter('field_type', $fieldtype_name)
                                ->delete();

                            if (ee()->addons_model->fieldtype_installed('grid')) {
                                ee()->load->model('grid_model');
                                ee()->grid_model->delete_columns_of_type($fieldtype_name);
                            }
                        }
                    }
                } else {
                    if (isset(ee()->addons->_packages[$addon][$type])) {
                        ee()->load->add_package_path(ee()->addons->_packages[$addon][$type]['path'], false);
                    }

                    $this->$method($addon);

                    if (isset(ee()->addons->_packages[$addon][$type])) {
                        ee()->load->remove_package_path(ee()->addons->_packages[$addon][$type]['path']);
                    }
                }
            }
        }
    }
}

// END Addons_installer class

// EOF
