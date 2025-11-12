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
 * Command to list add-ons
 */
class CommandAddonsList extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'List add-ons';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'addons:list';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php addons:list <i|installed|u|uninstalled|a|update-available>';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->data['list'] = $this->getFirstUnnamedArgument();

        switch ($this->data['list']) {
            case 'i':
            case 'installed':
                $langOption = 'command_addons_option_installed';
                $addons = $this->getAddonList('installed');

                break;
            case 'u':
            case 'uninstalled':
                $langOption = 'command_addons_option_uninstalled';
                $addons = $this->getAddonList('uninstalled');

                break;
            case 'a':
            case 'update-available':
                $langOption = 'command_addons_option_update';
                $addons = $this->getAddonList('update');

                break;
            default:
                $langOption = 'command_addons_option_available';
                $addons = $this->getAddonList();

                break;
        }

        $this->info(sprintf(lang('command_addons_list'), lang($langOption)));

        if (empty($addons)) {
            $this->fail('cli_no_addons');
        }

        // Output a text-based table of add-ons and versions
        $this->table([
            lang('command_addons_list_table_header_name'),
            lang('command_addons_list_table_header_shortname'),
            lang('command_addons_list_table_header_version'),
            lang('command_addons_list_table_header_installed'),
        ], $addons);
    }
}
