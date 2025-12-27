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
 * Command to unpack addon and move to proper location
 */
class CommandAddonsUnpack extends Cli
{
    use UpdaterPaths;

    /**
     * name of command
     * @var string
     */
    public $name = 'Unpacks an add-on';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'addons:unpack';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php addons:unpack -a <addon_name>';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'        => 'command_addons_unpack_option_addon',
        'delete-zip,d'    => 'command_addons_unpack_option_delete_zip',
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
        $this->info('command_addons_unpack_begin');

        $unpacker = ee('Updater/Unpacker');
        $unpacker->setAddonArchivePath();

        // get the list of zip files
        $addons = $unpacker->getAddonsList();

        if (empty($addons)) {
            $this->fail('command_addons_unpack_no_zips_found');
        }

        // Gather all the addon information
        $addonShortName = $this->data['addon'] = $this->getOptionOrAsk('--addon', "command_addons_unpack_ask_addon", 'first', true, $addons);

        // unpack the addon
        $this->info(sprintf(lang('command_addons_unpack_in_progress'), $addonShortName));

        $unpacker->setAddonArchivePath($addonShortName);
        $unpacker->unzipPackage();

        $this->info(sprintf(lang('command_addons_unpack_moving_files'), $addonShortName));

        try {
            $resultMessage = $unpacker->unpackAndMoveAddon($addonShortName, $this->option('--delete-zip'));
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->info($resultMessage);
    }

    public function getOptionOrAsk($option, $askText, $default = '', $required = false, $addonList = [])
    {
        // Get option if it was passed
        if ($this->option($option)) {
            return $this->option($option);
        }

        // Get the answer by asking
        $answer = $this->askAddon(lang($askText), $addonList, $default);

        // If it was a required field and no answer was passed, fail
        if ($required && empty(trim($answer))) {
            $this->fail(lang('cli_error_is_required_field') . $option);
        }

        return $answer;
    }
}
