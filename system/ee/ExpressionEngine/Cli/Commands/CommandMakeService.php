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
        'description,d:' => 'command_make_service_option_description',
        'author,au:'     => 'command_make_service_option_author',

        'type,t:'        => 'command_make_service_option_type',
        'namespace,n:'   => 'command_make_service_option_namespace',
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
        $this->data['addon_type'] = $this->getAddonType();
        $this->data['namespace'] = $this->getNamespace();
        $this->data['is_singleton'] = $this->getSingletonOption();
        $this->data['service_name'] = $this->getServiceName();

        // Get description
        $this->data['description'] = $this->getOptionOrAsk(
            "--description",
            "Service " . lang('command_make_service_description_question'),
            $this->data['service_name'] . ' service'
        );

        // Get author
        $this->data['author'] = $this->getOptionOrAsk(
            "--author",
            "Service " . lang('command_make_service_author_question'),
            ee('Config')->get('cli_default_addon_author'),
            true
        );



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
            $this->fail('command_make_service_addon_name_required');
        }

        return $addon_name;
    }

    private function getAddonType()
    {
        $addon_type = $this->getOptionOrAsk(
            "--type",
            "Addon type (first-party or third-party)",
            'third-party',
            true
        );

        // Validate addon type
        $valid_types = ['first-party', 'third-party', 'first', 'third'];
        if (!in_array(strtolower($addon_type), $valid_types)) {
            $this->fail('command_make_service_invalid_addon_type');
        }

        // Normalize type
        if (in_array(strtolower($addon_type), ['first', 'first-party'])) {
            return 'first-party';
        }

        return 'third-party';
    }

    private function getNamespace()
    {
        $namespace = $this->getOptionOrAsk(
            "--namespace",
            "Service namespace (e.g., MyAddon\\Service)",
            '',
            true
        );

        if (empty($namespace)) {
            // Generate namespace from addon name
            $namespace = $this->generateNamespace($this->data['addon_name']);
        }

        return $namespace;
    }

    private function generateNamespace($addon_name)
    {
        // Convert addon name to proper namespace format
        $namespace = str_replace(['-', '_'], ' ', $addon_name);
        $namespace = ucwords($namespace);
        $namespace = str_replace(' ', '', $namespace);
        
        return $namespace . '\\Service';
    }

    private function getServiceName()
    {
        $service_name = $this->getOptionOrAsk(
            "--service-name",
            "Service name (e.g., MyService)",
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
            "Should this service be a singleton? (y/n)",
            'n',
            true
        );

        return strtolower($is_singleton) === 'y' || strtolower($is_singleton) === 'yes';
    }


} 