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
                $class = $addon->getExtensionClass();
                $EXT = new $class();
                $EXT->update_extension($version);
                ee()->extensions->version_numbers[ucfirst($addon->getPrefix()) . '_ext'] = $version;

                $model = ee('Model')->get('Extension')
                    ->filter('class', ucfirst($addon->getPrefix()) . '_ext')
                    ->all();
                $model->version = $version;
                $model->save();
            }
            if ($addon->hasPlugin()) {
                $model = ee('Model')->get('Plugin')->filter('plugin_package', $addon->getPrefix())->first();
                $model->set([
                    'plugin_name' => $addon->getName(),
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
}
