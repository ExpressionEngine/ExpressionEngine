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
        'addon,a:'        => 'command_addons_update_option_addon',
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

        // Gather all the mcp information
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_addons_update_ask_addon", 'first', true, 'update');

        $addon = ee('pro:Addon')->get($this->data['addon']);

        $this->info(sprintf(lang('command_addons_update_in_progress'), $addon->getName()));

        ee()->load->library('addons/addons_installer');
        ee()->router->set_class('cp'); // some add-ons might be using this in installer
        ee()->load->library('cp');
        ee()->load->add_package_path($addon->getPath());
        ee()->lang->loadfile($addon->getPrefix(), '', false);

        try {
            $version = $addon->getVersion();
            $addon->updateConsentRequests();
            if ($addon->hasModule() && $addon->hasInstaller()) {
                $class = $addon->getInstallerClass();
                $UPD = new $class();
                if ($UPD->update($version) !== false) {
                    $module = ee('Model')->get('Module')
                        ->filter('module_name', ucfirst($addon->getPrefix()))
                        ->first();
                    $module->module_version = $version;
                    $module->save();
                }
            }
            if ($addon->hasFieldtype()) {
                $fts = $addon->getFieldtypeNames();
                foreach ($fts as $shortName => $name) {
                    ee()->api_channel_fields->include_handler($shortName);
                    $FT = ee()->api_channel_fields->setup_handler($shortName, true);
                    $update_ft = false;
                    if (!method_exists($FT, 'update')) {
                        $update_ft = true;
                    } else {
                        if ($FT->update($version) !== false) {
                            if (ee()->api_channel_fields->apply('update', array($version)) !== false) {
                                $update_ft = true;
                            }
                        }
                    }
                    if ($update_ft) {
                        $model = ee('Model')->get('Fieldtype')
                            ->filter('name', $shortName)
                            ->first();
                        $model->version = $version;
                        $model->save();
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
            $this->fail(addslashes($e->getMessage()));
        }

        ee()->load->remove_package_path($addon->getPath());

        ee()->cache->file->delete('/addons-status');
        ee('CP/JumpMenu')->clearAllCaches();

        $this->info(sprintf(lang('command_addons_update_complete'), $addon->getName()));
    }
}
