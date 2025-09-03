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
 * Command to display ExpressionEngine version information
 */
class CommandVersion extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Version';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'version';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php version';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'format,f:' => 'command_version_option_format',
        'field,e:' => 'command_version_option_field',
    ];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $format = $this->option('--format', 'simple');
        $field = $this->option('--field', '');

        // Get ExpressionEngine version information
        $version_info = [
            'version' => defined('APP_VER') ? APP_VER : ee()->config->item('app_version'),
            'build' => defined('APP_BUILD') ? APP_BUILD : 'Unknown',
            'php_version' => PHP_VERSION,
        ];

        // If a specific field is requested, output only that field
        if (!empty($field)) {
            $this->displayField($version_info, $field);
            return;
        }

        // Display version based on format
        switch ($format) {
            case 'json':
                $this->displayJson($version_info);
                break;
            case 'simple':
            default:
                $this->displaySimple($version_info);
                break;
        }
    }

    /**
     * Display version in simple format
     * @param array $version_info
     */
    private function displaySimple($version_info)
    {
        $this->write(lang('command_version_header'));
        $this->write('');
        $this->write(sprintf(lang('command_version_expressionengine'), $version_info['version']));
        $this->write(sprintf(lang('command_version_build'), $version_info['build']));
        $this->write(sprintf(lang('command_version_php'), $version_info['php_version']));
    }

    /**
     * Display version in JSON format
     * @param array $version_info
     */
    private function displayJson($version_info)
    {
        $this->write(json_encode($version_info, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS));
    }

    /**
     * Display a specific field value
     * @param array $version_info
     * @param string $field
     */
    private function displayField($version_info, $field)
    {
        if (!array_key_exists($field, $version_info)) {
            $this->write(sprintf(lang('command_version_invalid_field'), $field));
            return;
        }

        $this->write($version_info[$field]);
    }
}
