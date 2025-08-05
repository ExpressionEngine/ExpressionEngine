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
 * Command to generate service folder for addons
 */
class CommandMakeService extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Service Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:service';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:service "my_addon"';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'       => 'command_make_service_option_addon',
        'singleton,s'    => 'command_make_service_option_singleton',
        'service-name,sn:' => 'command_make_service_option_service_name',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_service_lets_build_service');

        $this->data['addon_name'] = $this->getAddonName();
        $this->data['is_singleton'] = $this->getSingletonOption();
        $this->data['service_name'] = $this->getServiceName();

        $this->info('command_make_service_lets_build');

        $this->build();

        $this->info('command_make_service_created_successfully');
    }

    private function build()
    {
        try {
            // Build the service using the ServiceGenerator
            $service = ee('ServiceGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }
    }

    private function getAddonName()
    {
        // This is the addon name passed to the CLI
        $addon_name = $this->getFirstUnnamedArgument();

        // If no addon name was passed, ask for a name
        if (is_null($addon_name)) {
            $addon_name = $this->ask('command_make_service_what_is_addon_name');
        }

        // Lets filter the name to only allow alphanumerics, "-", "_" and spaces
        $addon_name = preg_replace("/[^A-Za-z0-9 \-_]/", '', $addon_name);

        if (empty(trim($addon_name))) {
            $this->fail(lang('command_make_service_addon_name_required'));
        }

        return $addon_name;
    }



    private function getServiceName()
    {
        $service_name = $this->getOptionOrAsk(
            "--service-name",
            lang('command_make_service_name_question'),
            '',
            true
        );

        if (empty($service_name)) {
            // Generate service name from addon name
            $service_name = $this->generateServiceName($this->data['addon_name']);
        }

        // Validate service name format
        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $service_name)) {
            $this->fail('Service name must start with a letter and contain only letters and numbers');
        }

        return $service_name;
    }

    private function generateServiceName($addon_name)
    {
        // Convert addon name to proper service name format
        $service_name = str_replace(['-', '_'], ' ', $addon_name);
        $service_name = ucwords($service_name);
        $service_name = str_replace(' ', '', $service_name);
        
        return $service_name . 'Service';
    }

    private function getSingletonOption()
    {
        $is_singleton = $this->getOptionOrAsk(
            "--singleton",
            lang('command_make_service_singleton_question'),
            'n',
            true
        );

        return strtolower($is_singleton) === 'y' || strtolower($is_singleton) === 'yes';
    }


} 