<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;

/**
 * Command to install addon
 */
class CommandAddonsInstall extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Installs an add-on';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'addons:install';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php addons:install -a <addon_name>';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'        => 'command_addons_install_option_addon',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        ee()->lang->loadfile('addons');
        $this->info('command_addons_install_begin');

        // Gather all the mcp information
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_addons_install_ask_addon", 'first', true, 'uninstalled');

        $addon = ee('pro:Addon')->get($this->data['addon']);

        $this->info(sprintf(lang('command_addons_install_in_progress'), $addon->getName()));

        $requires = $addon->getProvider()->get('requires');
        if (!empty($requires)) {
            if (isset($requires['php']) && version_compare(PHP_VERSION, $requires['php'], '<')) {
                $this->info(lang('addons_not_installed'));
                $this->fail(sprintf(lang('version_required'), 'PHP', $requires['php']));
            }
            if (isset($requires['ee']) && version_compare(APP_VER, $requires['ee'], '<')) {
                $this->info(lang('addons_not_installed'));
                $this->fail(sprintf(lang('version_required'), 'ExpressionEngine', $requires['ee']));
            }
        }

        ee()->load->library('addons/addons_installer');
        ee()->router->set_class('cp'); // some add-ons might be using this in installer
        ee()->load->library('cp');
        ee()->load->add_package_path($addon->getPath());
        ee()->lang->loadfile($addon->getPrefix(), '', false);

        try {
            $addon->installConsentRequests();
        } catch (\Exception $e) {
            $this->info(lang('addons_not_installed'));
            $this->fail(lang('existing_consent_request') . ' ' . $addon->getName() . '. ' . lang('contact_developer'));
        }

        try {
            if ($addon->hasModule() && $addon->hasInstaller()) {
                $class = $addon->getInstallerClass();
                $UPD = new $class();
                if (! $UPD->install()) {
                    $this->fail(lang('addons_not_installed'));
                }
            }
            if ($addon->hasFieldtype()) {
                $fts = $addon->getFieldtypeNames();
                foreach ($fts as $shortName => $name) {
                    ee()->addons_installer->install_fieldtype($shortName);
                }
            }
            if ($addon->hasExtension()) {
                $class = $addon->getExtensionClass();
                ee()->addons_installer->install_extension($addon->getPrefix());
            }
            if ($addon->hasPlugin()) {
                ee('Model')->make('Plugin', [
                    'plugin_name' => $addon->getName(),
                    'plugin_package' => $addon->getPrefix(),
                    'plugin_version' => $addon->getVersion(),
                    'is_typography_related' => $addon->get('plugin.typography') ? 'y' : 'n'
                ])->save();
            }

            $addon->updateDashboardWidgets();
            $addon->updateProlets();
        } catch (\Exception $e) {
            $this->info(lang('addons_not_installed'));
            $this->fail(addslashes($e->getMessage()));
        }

        ee()->load->remove_package_path($addon->getPath());

        ee()->cache->file->delete('/addons-status');
        ee('CP/JumpMenu')->clearAllCaches();

        $this->info(sprintf(lang('command_addons_install_complete'), $addon->getName()));
    }
}
