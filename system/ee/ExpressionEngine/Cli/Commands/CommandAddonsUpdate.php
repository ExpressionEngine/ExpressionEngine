<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;

/**
 * Command to uninstall addon
 */
class CommandAddonsUpdate extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Updates an add-on';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'addons:update';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php addons:update -a <addon_name>';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'  => 'command_addons_update_option_addon',
        'all,l'     => 'command_addons_update_option_all',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        ee()->lang->loadfile('addons');
        $this->info('command_addons_update_begin');

        ee()->load->library('session');
        ee()->load->library('addons/addons_installer');
        // some add-ons might be using this in installer
        ee()->router->set_class('cp');
        ee()->load->library('cp');

        if ($this->option('--all')) {
            $addons = array_keys($this->getAddonList('update'));

            // Update all addons with available updates
            foreach ($addons as $addon) {
                $this->updateAddon($addon);
            }

            $this->info('command_addons_update_all_complete');
        } else {
            // Get the single addon to update
            $addon = $this->getOptionOrAskAddon('--addon', "command_addons_update_ask_addon", 'first', true, 'update');

            $this->updateAddon($addon);
        }

        ee()->cache->file->delete('/addons-status');
        ee('CP/JumpMenu')->clearAllCaches();
    }

    /**
     * Update the installed components of an add-on.
     *
     * @param string $addon
     * @return void
     */
    private function updateAddon($addon)
    {
        $addon = ee('pro:Addon')->get($addon);

        $this->info(sprintf(lang('command_addons_update_in_progress'), $addon->getName()));

        ee()->load->add_package_path($addon->getPath());
        ee()->lang->loadfile($addon->getPrefix(), '', false);

        try {
            $version = $addon->getVersion();
            $addon->updateConsentRequests();

            if ($addon->hasModule() && $addon->hasInstaller()) {
                $module = ee('Model')->get('Module')->filter('module_name', ucfirst($addon->getPrefix()))->first();
                $class = $addon->getInstallerClass();
                $UPD = new $class();
                if ($UPD->update($module->module_version) !== false) {
                    $module->module_version = $version;

                    $module->save();
                }
            }
            if ($addon->hasFieldtype()) {
                $fts = $addon->getFieldtypeNames();
                ee()->load->library('api');
                ee()->legacy_api->instantiate('channel_fields');
                foreach ($fts as $shortName => $name) {
                    ee()->api_channel_fields->include_handler($shortName);
                    $FT = ee()->api_channel_fields->setup_handler($shortName, true);

                    $fieldtype = ee('Model')->get('Fieldtype')
                        ->filter('name', $shortName)
                        ->first();

                    $update_ft = false;
                    if (!method_exists($FT, 'update')) {
                        $update_ft = true;
                    } else {
                        if ($FT->update($fieldtype->version) !== false) {
                            if (ee()->api_channel_fields->apply('update', array($fieldtype->version)) !== false) {
                                $update_ft = true;
                            }
                        }
                    }

                    if ($update_ft) {
                        $model = ee('Model')->get('Fieldtype')
                            ->filter('name', $shortName)
                            ->first();

                        if (!empty($model)) {
                            $model->version = $version;
                            $model->save();
                        }
                    }
                }
            }
            if ($addon->hasExtension()) {
                $this->updateExtension($addon, $version);
            }
            if ($addon->hasPlugin()) {
                $model = ee('Model')->get('Plugin')->filter('plugin_package', $addon->getPrefix())->first();
                if (! $model) {
                    $model = ee('Model')->make('Plugin');
                }
                $model->set([
                    'plugin_name' => $addon->getName(),
                    'plugin_package' => $addon->getPrefix(),
                    'plugin_version' => $addon->getVersion(),
                    'is_typography_related' => $addon->get('plugin.typography') ? 'y' : 'n'
                ]);
                $model->save();
            }

            $addon->updateDashboardWidgets();
            $addon->updateProlets();
        } catch (\Exception $e) {
            $this->info(lang('addons_not_installed'));
            $this->error(addslashes($e->getMessage()));
        }

        ee()->load->remove_package_path($addon->getPath());

        $this->info(sprintf(lang('command_addons_update_complete'), $addon->getName()));
    }

    /**
     * Migrate an installed extension while preserving its disabled state.
     *
     * @param \ExpressionEngine\Service\Addon\Addon $addon
     * @param string $version
     * @return void
     * @throws \Exception
     */
    private function updateExtension($addon, $version)
    {
        $extensionClass = ucfirst($addon->getPrefix()) . '_ext';
        $query = ee('Model')->get('Extension')->filter('class', $extensionClass);
        $installedHooks = $query->all();
        $installedExtension = $installedHooks->first();

        if (! $installedExtension) {
            return;
        }

        $wasDisabled = ! in_array(true, $installedHooks->pluck('enabled'), true);

        try {
            $class = $addon->getExtensionClass();
            $updater = new $class();
            $updater->update_extension($installedExtension->version);
        } finally {
            if ($wasDisabled) {
                ee('db')->where('class', $extensionClass)->update('extensions', ['enabled' => 'n']);
            }
        }

        ee()->extensions->version_numbers[$extensionClass] = $version;

        $updatedHooks = $query->all();
        $updatedHooks->version = $version;
        $updatedHooks->save();
    }
}
