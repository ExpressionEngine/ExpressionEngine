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
 * Command to clear selected caches
 */
class CommandMakeAddon extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Add-on Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:addon';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:addon "My Awesome Add-on"';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        // Add-on values
        'version,v:'      => 'command_make_addon_option_version',
        'description,d:'  => 'command_make_addon_option_description',
        'author,a:'       => 'command_make_addon_option_author',
        'author-url,u:'   => 'command_make_addon_option_author_url',
    ];

    protected $data = [];

    protected $type = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_addon_lets_build_addon');

        $this->data['name'] = $this->getName();

        // Get description
        $this->data['description'] = $this->getOptionOrAsk(
            "--description",
            "Add-on " . lang('command_make_addon_description_question'),
            $this->data['name'] . ' description'
        );

        // Get version
        $this->data['version'] = $this->getOptionOrAsk(
            "--version",
            "Add-on " . lang('command_make_addon_version_question'),
            '1.0.0',
            true
        );

        // Get author
        $this->data['author'] = $this->getOptionOrAsk(
            "--author",
            "Add-on " . lang('command_make_addon_author_question'),
            ee('Config')->get('cli_default_addon_author'),
            true
        );

        // Get author_url
        $this->data['author_url'] = $this->getOptionOrAsk(
            "--author-url",
            "Add-on " . lang('command_make_addon_author_url_question'),
            ee('Config')->get('cli_default_addon_author_url'),
            true
        );

        $this->info('command_make_addon_lets_build');

        $this->build();

        $this->info('command_make_addon_created_successfully');
    }

    private function build()
    {
        try {
            // Build the addon
            $service = ee('AddonGenerator', $this->data);

            return $service->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }
    }

    private function getName()
    {
        // This is the name passed to the CLI
        $name = $this->getFirstUnnamedArgument();

        // If no name was passed, ask for a name
        if (is_null($name)) {
            $name = $this->ask('command_make_addon_what_is_name');
        }

        // Lets filter the name to only allow alphanumerics, "-", "_" and spaces
        $name = preg_replace("/[^A-Za-z0-9 \-_]/", '', $name);

        if (empty(trim($name))) {
            $this->fail('command_make_addon_addon_name_required');
        }

        return $name;
    }
}
