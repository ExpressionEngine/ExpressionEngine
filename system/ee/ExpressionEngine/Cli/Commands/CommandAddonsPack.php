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
use ExpressionEngine\Service\Updater\Downloader\UpdaterPaths;
use FilesystemIterator;

/**
 * Command to pack addon to zip
 */
class CommandAddonsPack extends Cli
{
    use UpdaterPaths;

    /**
     * name of command
     * @var string
     */
    public $name = 'Packs an add-on';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'addons:pack';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php addons:pack -a <addon_name>';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'        => 'command_addons_pack_option_addon',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        defined('CLI_VERBOSE') || define('CLI_VERBOSE', $this->option('-v', false));
        ee()->lang->loadfile('addons');
        $this->info('command_addons_pack_begin');

        $packer = ee('Updater/Packer');

        $addonShortName = $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_addons_pack_ask_addon", 'first', true, 'all');

        $addon = ee('pro:Addon')->get($this->data['addon']);

        $packer->setAddonArchivePath($addonShortName);

        $this->info(sprintf(lang('command_addons_pack_in_progress'), $addon->getName()));

        try {
            $resultMessage = $packer->packAddon($addonShortName);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->info($resultMessage);
    }
}
