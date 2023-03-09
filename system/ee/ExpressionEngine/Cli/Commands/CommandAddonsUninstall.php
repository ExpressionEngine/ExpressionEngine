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
class CommandAddonsUninstall extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Uninstalls an add-on';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'addons:uninstall';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php addons:uninstall -a <addon_name>';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'        => 'command_addons_uninstall_option_addon',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        ee()->lang->loadfile('addons');
        $this->info('command_addons_uninstall_begin');

        // Gather all the mcp information
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_addons_uninstall_ask_addon", '', true, 'installed');

        $addon = ee('pro:Addon')->get($this->data['addon']);

        $this->info(sprintf(lang('command_addons_uninstall_in_progress'), $addon->getName()));

        ee()->load->library('addons/addons_installer');
        ee()->router->set_class('cp'); // some add-ons might be using this in installer
        ee()->load->library('cp');
        ee()->load->add_package_path($addon->getPath());
        ee()->lang->loadfile($addon->getPrefix(), '', false);

        try {
            $addon->removeConsentRequests();
            if ($addon->hasModule() && $addon->hasInstaller()) {
                $class = $addon->getInstallerClass();
                $UPD = new $class();
                if (! $UPD->uninstall()) {
                    $this->fail(lang('addons_not_uninstalled'));
                }
            }
            if ($addon->hasFieldtype()) {
                $fts = $addon->getFieldtypeNames();
                foreach ($fts as $shortName => $name) {
                    ee()->addons_installer->uninstall_fieldtype($shortName);
                }
            }
            if ($addon->hasExtension()) {
                ee()->addons_installer->uninstall_extension($addon->getPrefix());
            }
            if ($addon->hasPlugin()) {
                ee('Model')->get('Plugin')->filter('plugin_package', $addon->getPrefix())->delete();
            }

            $addon->updateDashboardWidgets(true);
            $addon->updateProlets(true);
        } catch (\Exception $e) {
            $this->info(lang('addons_not_installed'));
            $this->fail(addslashes($e->getMessage()));
        }

        ee()->load->remove_package_path($addon->getPath());

        ee()->cache->file->delete('/addons-status');
        ee('CP/JumpMenu')->clearAllCaches();

        $this->info(sprintf(lang('command_addons_uninstall_complete'), $addon->getName()));
    }
}
