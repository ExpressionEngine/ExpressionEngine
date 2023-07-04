<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Addon;

use ExpressionEngine\Core\Provider;

/**
 * Add-on Service
 */
class Addon
{
    protected $provider;
    protected $basepath;
    protected $shortname;

    protected $_components = [];

    private static $installed_plugins;
    private static $installed_modules;
    private static $installed_extensions;
    private static $installed_fieldtypes;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        $this->shortname = $provider->getPrefix();

        $files = $this->getFilesMatching('*' . $this->getPrefix() . '.php');
        $possibleComponents = ['upd', 'mcp', 'mod', 'pi', 'ext', 'tab', 'rtefb', 'upgrade', 'spam', 'jump'];
        foreach ($possibleComponents as $type) {
            if (in_array($this->getPath() . "/{$type}." . $this->getPrefix() . '.php', $files)) {
                $this->_components[] = $type;
            }
        }
    }

    /**
     * Pass unknown calls to the provider
     */
    public function __call($fn, $args)
    {
        return call_user_func_array(array($this->provider, $fn), $args);
    }

    /**
     * Is this add-on installed?
     *
     * @return bool Is installed?
     */
    public function isInstalled()
    {
        $types = array('modules', 'fieldtypes', 'extensions');

        ee()->load->model('addons_model');

        if (is_null(self::$installed_modules)) {
            $query = ee()->addons_model->get_installed_modules();

            self::$installed_modules = array();

            foreach ($query->result() as $row) {
                self::$installed_modules[$row->module_name] = $row;
            }
        }

        if (array_key_exists($this->shortname, self::$installed_modules)) {
            return true;
        }

        if (is_null(self::$installed_extensions)) {
            $query = ee()->addons_model->get_installed_extensions();

            self::$installed_extensions = array();

            foreach ($query->result() as $row) {
                $name = strtolower(preg_replace('/^(.*?)(_(ext|mcp))?$/', '$1', $row->class));
                self::$installed_extensions[$name] = $row;
            }
        }

        if (array_key_exists($this->shortname, self::$installed_extensions)) {
            return true;
        }

        if (is_null(self::$installed_fieldtypes)) {
            $query = ee()->db->select('name')->get('fieldtypes');

            self::$installed_fieldtypes = array();

            foreach ($query->result() as $row) {
                self::$installed_fieldtypes[$row->name] = $row;
            }
        }

        $paths = $this->getFilesMatching('ft.*.php');

        foreach ($paths as $path) {
            $shortname = preg_replace('/ft.(.*?).php/', '$1', basename($path));

            if (array_key_exists($shortname, self::$installed_fieldtypes)) {
                return true;
            }
        }

        if ($this->hasPlugin()) {
            // Check for an installed plugin
            // @TODO restore the model approach once we have solved the
            // circular dependency between the Add-on service and the
            // Model/Datastore service.
            /*
            $plugin = ee('Model')->get('Plugin')
                ->filter('plugin_package', $this->shortname)
                ->first();

            if ($plugin)
            {
                return TRUE;
            }
            */

            $installed_plugins = self::$installed_plugins;

            //always return true if the table does not exist
            //e.g. when running update from EE2 to EE6
            //some older version also don't use . as separator
            if (version_compare(ee()->config->item('app_version'), '3.0.0', '<') || strpos(ee()->config->item('app_version'), '.') === false) {
                return true;
            }

            if (is_null($installed_plugins)) {
                $installed_plugins = array_map('array_pop', ee()->db
                    ->select('plugin_package')
                    ->get('plugins')
                    ->result_array());

                self::$installed_plugins = $installed_plugins;
            }

            if (in_array($this->shortname, $installed_plugins)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does this add-on have an update available?
     *
     * @return bool Does it have an update available?
     */
    public function hasUpdate()
    {
        if ($this->isInstalled()) {
            $version = $this->getInstalledVersion();

            if (!is_null($version)) {
                return version_compare($this->getVersion(), $version, '>');
            }
        }

        return false;
    }

    /**
     * Get the installed version
     *
     * @return string|NULL NULL if not installed or a version string
     */
    public function getInstalledVersion()
    {
        if (!$this->isInstalled()) {
            return null;
        }

        // Module
        if ($this->hasModule()) {
            $addon = ee('Model')->get('Module')
                ->fields('module_version')
                ->filter('module_name', $this->shortname)
                ->first();

            return $addon->module_version;
        }

        // Fieldtype
        if ($this->hasFieldtype()) {
            $addon = ee('Model')->get('Fieldtype')
                ->fields('version')
                ->filter('name', $this->shortname)
                ->first();

            return $addon->version;
        }

        // Extension
        if ($this->hasExtension()) {
            $class = ucfirst($this->shortname) . '_ext';

            $addon = ee('Model')->get('Extension')
                ->fields('version')
                ->filter('class', $class)
                ->first();

            return $addon->version;
        }

        // Plugin
        if ($this->hasPlugin()) {
            $addon = ee('Model')->get('Plugin')
                ->fields('plugin_version')
                ->filter('plugin_package', $this->shortname)
                ->first();

            return $addon->plugin_version;
        }

        return null;
    }

    /**
     * Gets the 'name' of the add-on, prefering to use the module's lang() key
     * if it is defined, otherwise using the 'name' key in the provider file.
     *
     * @return string product name
     */
    public function getName()
    {
        if ($this->hasModule()) {
            ee()->lang->loadfile($this->shortname, '', false);

            $lang_key = strtolower($this->shortname) . '_module_name';
            $name = lang($lang_key);

            if ($name != strtolower($lang_key)) {
                return $name;
            }
        }

        return $this->provider->getName();
    }

    /**
     * Get the plugin or module class
     *
     * @return string The fqcn or $class
     */
    public function getFrontendClass()
    {
        if ($this->hasModule()) {
            return $this->getModuleClass();
        }

        return $this->getPluginClass();
    }

    /**
     * Get the module class
     *
     * @return string The fqcn or $class
     */
    public function getModuleClass()
    {
        $this->requireFile('mod');

        $class = ucfirst($this->shortname);

        return $this->getFullyQualified($class);
    }

    /**
     * Get the plugin class
     *
     * @return string The fqcn or $class
     */
    public function getPluginClass()
    {
        $this->requireFile('pi');

        $class = ucfirst($this->shortname);

        return $this->getFullyQualified($class);
    }

    /**
     * Get the *_upd class
     *
     * @return string The fqcn or $class
     */
    public function getInstallerClass()
    {
        $this->requireFile('upd');

        $class = ucfirst($this->shortname) . '_upd';

        return $this->getFullyQualified($class);
    }

    /**
     * Get the *_upgrade class
     *
     * @return string The fqcn or $class
     */
    public function getUpgraderClass()
    {
        $this->requireFile('upgrade');

        $class = ucfirst($this->shortname) . '_upgrade';

        return $this->getFullyQualified($class);
    }

    /**
     * Get the *_mcp class
     *
     * @return string The fqcn or $class
     */
    public function getControlPanelClass()
    {
        $this->requireFile('mcp');

        $class = ucfirst($this->shortname) . '_mcp';

        return $this->getFullyQualified($class);
    }

    /**
     * Get the extension class
     *
     * @return string The fqcn or $class
     */
    public function getExtensionClass()
    {
        $this->requireFile('ext');

        $class = ucfirst($this->shortname) . '_ext';

        return $this->getFullyQualified($class);
    }

    /**
     * Get the spam class
     *
     * @return string The fqcn or $class
     */
    public function getSpamClass()
    {
        $this->requireFile('spam');

        $class = ucfirst($this->shortname) . '_spam';

        return $this->getFullyQualified($class);
    }

    /**
     * Get the jump class
     *
     * @return string The fqcn or $class
     */
    public function getJumpClass()
    {
        $this->requireFile('jump');

        $class = ucfirst($this->shortname) . '_jump';

        return $this->getFullyQualified($class);
    }

    /**
     * Get the RTE class
     *
     * @return string The fqcn or $class
     */
    public function getRteFilebrowserClass()
    {
        $this->requireFile('rtefb');

        $class = ucfirst($this->shortname) . '_rtefb';

        return $this->getFullyQualified($class);
    }

    public function getEvaluationRuleClass($rule)
    {
        return $this->getFullyQualified("EvaluationRules\\" . ucfirst($rule));
    }

    /**
     * Has a README.md file?
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasManual()
    {
        return file_exists($this->getPath() . '/README.md');
    }

    /**
     * Has a module or plugin?
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasFrontend()
    {
        return $this->hasModule() || $this->hasPlugin();
    }

    /**
     * Has a upd.* file??
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasInstaller()
    {
        return $this->hasFile('upd');
    }

    /**
     * Has an mcp.* file?
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasControlPanel()
    {
        return $this->hasFile('mcp');
    }

    /**
     * Has a mod.* file?
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasModule()
    {
        return $this->hasFile('mod');
    }

    /**
     * Has a pi.* file?
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasPlugin()
    {
        return $this->hasFile('pi');
    }

    /**
     * Has a rtefb.* file?
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasRteFilebrowser()
    {
        return $this->hasFile('rtefb');
    }

    /**
     * Has a jump.* file?
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasJumpMenu()
    {
        return $this->hasFile('jump');
    }

    /**
     * Has an ext.* file?
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasExtension()
    {
        return $this->hasFile('ext');
    }

    /**
     * Has an upd.* file?
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasUpgrader()
    {
        return $this->hasFile('upgrade');
    }

    /**
     * Has a ft.* file?
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasFieldtype()
    {
        $files = $this->getFilesMatching('ft.*.php');
        $this->requireFieldtypes($files);

        return !empty($files);
    }

    /**
     * Has a spam.* file?
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasSpam()
    {
        return $this->hasFile('spam');
    }


    /**
     * Has a tab.* file?
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasTab()
    {
        return $this->hasFile('tab');
    }

    /**
     * Gets an array of the jump menu items
     *
     * @return array An array of jump menu items
     */
    public function getJumps()
    {
        $class = $this->getJumpClass();
        $items = [];

        try {
            $jumpMenu = new $class();

            $items = $jumpMenu->getItems();

            foreach ($items as $key => $item) {
                // Prepend the add-on shortname to the item key to prevent command collisions.
                $newKey = $this->shortname . '_' . ucfirst($key);

                // Save the command under the new key.
                $items[$newKey] = $item;

                // Unset the old key so we don't end up with duplicates.
                unset($items[$key]);

                // Modify the command, command_title, target, and add-on flag to denote it's an add-on command.
                $items[$newKey]['addon'] = (isset($item['addon']) && $item['addon'] === false) ? false : true;
                $items[$newKey]['command'] = $this->shortname . ' ' . lang($items[$newKey]['command']);
                $items[$newKey]['command_title'] = ($items[$newKey]['addon'] === true) ? ($this->provider->getName() . ': ' . lang($items[$newKey]['command_title'])) : lang($items[$newKey]['command_title']);

                if ($items[$newKey]['addon'] === true) {
                    if ($item['dynamic'] === true) {
                        $items[$newKey]['target'] = 'addons/' . $this->shortname . '/' . ltrim($item['target'], '/');
                    } else {
                        $items[$newKey]['target'] = 'addons/settings/' . $this->shortname . '/' . ltrim($item['target'], '/');
                    }
                }
            }
        } catch (\Exception $e) {
            //if add-on does not properly implement jumps, we don't want to take resposibility, so just skip that
        }

        return $items;
    }

    /**
     * Gets an array of the filedtype classes
     *
     * @return array An array of classes
     */
    public function getFieldtypeClasses()
    {
        $files = $this->getFilesMatching('ft.*.php');

        return $this->requireFieldtypes($files);
    }

    /**
     * Get an associative array of names of each fieldtype. Maps the fieldtype's
     * shortname to it's display name. The provider file is first checked for
     * the display name in the `fieldtypes` key, falling back on the `getName()`
     * method.
     *
     * @return array An associative array of shortname to display name for each fieldtype.
     */
    public function getFieldtypeNames()
    {
        $names = array();

        $fieldtypes = $this->get('fieldtypes');

        foreach ($this->getFilesMatching('ft.*.php') as $path) {
            $ft_name = preg_replace('/ft.(.*?).php/', '$1', basename($path));
            $names[$ft_name] = (isset($fieldtypes[$ft_name]['name'])) ? $fieldtypes[$ft_name]['name'] : $this->getName();
        }

        return $names;
    }

    public function getInstalledConsentRequests()
    {
        $return = [];

        $prefix = $this->getConsentPrefix();
        $requests = $this->get('consent.requests', []);

        foreach ($requests as $name => $values) {
            $consent_name = $prefix . ':' . $name;
            if ($this->hasConsentRequestInstalled($consent_name)) {
                $return[] = $consent_name;
            }
        }

        return $return;
    }

    public function installConsentRequests()
    {
        // Preflight: if we have any consents that match there's been a problem.
        $requests = $this->getInstalledConsentRequests();
        if (!empty($requests)) {
            throw new \Exception();
        }

        $prefix = $this->getConsentPrefix();
        $requests = $this->get('consent.requests', []);

        foreach ($requests as $name => $values) {
            $consent_name = $prefix . ':' . $name;
            $this->makeConsentRequest($consent_name, $values);
        }
    }

    private function hasConsentRequestInstalled($name)
    {
        return (bool) ee('Model')->get('ConsentRequest')
            ->filter('consent_name', $name)
            ->count();
    }

    public function makeConsentRequest($name, $values)
    {
        $request = ee('Model')->make('ConsentRequest');
        $request->user_created = false; // App-generated request
        $request->consent_name = $name;
        $request->title = (isset($values['title'])) ? $values['title'] : $name;
        $request->save();

        if (isset($values['request'])) {
            $version = ee('Model')->make('ConsentRequestVersion');
            $version->request = $values['request'];
            $version->request_format = (isset($values['request_format'])) ? $values['request_format'] : 'none';
            $version->author_id = REQ != 'CLI' ? ee()->session->userdata('member_id') : 0;
            $version->create_date = ee()->localize->now;
            $request->Versions->add($version);

            $version->save();

            $request->CurrentVersion = $version;
            $request->save();
        }
    }

    public function updateConsentRequests()
    {
        $prefix = $this->getConsentPrefix();
        $requests = $this->get('consent.requests', []);

        foreach ($requests as $name => $values) {
            $consent_name = $prefix . ':' . $name;
            if (!$this->hasConsentRequestInstalled($consent_name)) {
                $this->makeConsentRequest($consent_name, $values);
            }
        }
    }

    public function removeConsentRequests()
    {
        $prefix = $this->getConsentPrefix();
        $requests = $this->get('consent.requests', []);

        $consent_names = [];

        foreach ($requests as $name => $values) {
            $consent_names[] = $prefix . ':' . $name;
        }

        if (!empty($consent_names)) {
            ee('Model')->get('ConsentRequest')
                ->filter('consent_name', 'IN', $consent_names)
                ->delete();
        }
    }

    private function getConsentPrefix()
    {
        if (strpos($this->getPath(), PATH_ADDONS) === 0) {
            return 'ee';
        } else {
            return $this->getPrefix();
        }
    }

    /**
     * Find files in this add-on matching a pattern
     *
     * @return array An array of pathnames
     */
    protected function getFilesMatching($glob)
    {
        return glob($this->getPath() . "/{$glob}") ?: array();
    }

    /**
     * Includes each filetype via PHP's `require_once` command and returns an
     * array of the classes that were included.
     *
     * @param array $files An array of file names
     * @return array An array of classes
     */
    protected function requireFieldtypes(array $files)
    {
        $classes = array();

        require_once SYSPATH . 'ee/legacy/fieldtypes/EE_Fieldtype.php';

        foreach ($files as $path) {
            require_once $path;
            $class = preg_replace('/ft.(.*?).php/', '$1', basename($path));
            $classes[] = ucfirst($class) . '_ft';
        }

        return $classes;
    }

    /**
     * Get the add-on Provider
     *
     * @return ExpressionEngine\Core\Provider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Get icon URL
     *
     * @param string default icon file name
     * @return string URL for add-on's icon, or generic one
     */
    public function getIconUrl($default = null)
    {
        $masks = [
            'icon.svg',
            'icon.png'
        ];
        foreach ($masks as $mask) {
            $icon = $this->getFilesMatching($mask);
            if (!empty($icon)) {
                break;
            }
        }

        if (!empty($icon)) {
            $action_id = ee()->db->select('action_id')
                ->where('class', 'File')
                ->where('method', 'addonIcon')
                ->get('actions');
            $url = ee()->functions->fetch_site_index() . QUERY_MARKER . 'ACT=' . $action_id->row('action_id') . AMP . 'addon=' . $this->shortname . AMP . 'file=' . $mask;
        } else {
            if (empty($default)) {
                $default = 'default-addon-icon.svg';
            }
            $url = URL_THEMES . 'asset/img/' . $default;
        }

        return $url;
    }

    /**
     * Check cached license response
     *
     * @return bool
     */
    public function checkCachedLicenseResponse()
    {
        // See if we have a cached check.
        $cached = ee()->cache->file->get('/addons-status');

        if (empty($cached)) {
            return false;
        }

        if (!ee()->cache->file->is_writable('/addons-status')) {
            $this->logLicenseError('license_error_file_not_writable');

            return false;
        }

        list($cache, $integrity) = explode('||s=', $cached);

        // Make sure the cache exists and has the proper integrity to use.
        if (empty($cache) || empty($integrity) || hash('sha256', $cache) !== $integrity) {
            $this->logLicenseError('license_error_file_broken');

            return false;
        }

        $json = ee('Encrypt')->decode($cache, ee()->config->item('session_crypt_key'));

        if (empty($json) || !$data = json_decode($json, true)) {
            $this->logLicenseError('license_error_file_broken');

            return false;
        }

        $sha = $data['sha'];
        unset($data['sha']);

        if ($sha !== hash('sha256', json_encode($data))) {
            $this->logLicenseError('license_error_file_broken');

            return false;
        }

        $addonStatus = 'na';

        if (isset($data['addons'][$this->shortname]) && isset($data['addons'][$this->shortname]['status'])) {
            $addonStatus = $data['addons'][$this->shortname]['status'];

            if ($addonStatus === 'valid' && $data['validLicense'] === false) {
                if (!empty($data['licenseStatus'])) {
                    return $data['licenseStatus'];
                } else {
                    return 'invalid';
                }
            }
        }

        return $addonStatus;
    }

    /**
     * Log license error to developer log and display alert in CP
     *
     * @param [type] $message
     * @return void
     */
    private function logLicenseError($message)
    {
        ee()->load->library('logger');
        ee()->logger->developer(lang($message), true);
        if (REQ == 'CP') {
            ee('CP/Alert')->makeBanner('license-error')
                ->asWarning()
                ->canClose()
                ->withTitle(lang('license_error'))
                ->addToBody(lang($message))
                ->now();
        }
    }

    /**
     * Get the fully qualified class name
     *
     * Checks the namespace and if that doesn't exists falls back to the
     * old name
     *
     * @param string $class The classname relative to their namespace
     * @return string The fqcn or $class
     */
    protected function getFullyQualified($class)
    {
        $ns = trim($this->provider->getNamespace(), '\\');

        $ns_class = "\\{$ns}\\{$class}";

        if (class_exists($ns_class)) {
            return $ns_class;
        }

        return $class;
    }

    /**
     * Check if the file with a given prefix exists
     *
     * @param array $prefix A prefix for the file (i.e. 'ft', 'mod', 'mcp', 'jump')
     * @return bool TRUE if it has the file, FALSE if not
     */
    protected function hasFile($prefix)
    {
        return in_array($prefix, $this->_components);
    }

    /**
     * Call require on a given file
     *
     * @param array $prefix A prefix for the file (i.e. 'ft', 'mod', 'mcp', 'jump')
     * @return void
     */
    protected function requireFile($prefix)
    {
        require_once $this->getPath() . "/{$prefix}." . $this->getPrefix() . '.php';
    }
}

// EOF
