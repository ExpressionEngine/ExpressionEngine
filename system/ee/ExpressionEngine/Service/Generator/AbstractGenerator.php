<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Generator;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Library\String\Str;

abstract class AbstractGenerator
{
    public $name;
    public $addon;
    protected $filesystem;
    protected $str;
    protected $generatorPath;
    protected $addonPath;
    protected $stubPath;
    public $requireAddonExists = true;

    public $requiredComponentFiles = [
        // 'mod',
        // 'ext',
        // 'upd',
        // 'ft',
    ];

    /**
     * Check if the addon exists and return the addon object
     *
     * @return \ExpressionEngine\Service\Addon\Addon
     * @throws \Exception if addon does not exist
     */
    protected function checkAddonExists()
    {
        if (! $addon = ee('Addon')->get($this->addon)) {
            throw new \Exception(lang('cli_error_the_specified_addon_does_not_exist'), 1);
        }

        return $addon;
    }

    /**
     * Get the stub file path
     *
     * @param string $file
     * @return string
     */
    protected function stub($file)
    {
        return $this->stubPath . $file;
    }

    /**
     * Replace placeholders in stub content
     *
     * @param string $key
     * @param string $value
     * @param string $file
     * @return string
     */
    protected function write($key, $value, $file)
    {
        return str_replace('{{' . $key . '}}', $value, $file);
    }

    /**
     * Write a file to the addon directory
     *
     * @param string $name
     * @param string $contents
     * @param string|null $path
     */
    protected function putFile($name, $contents, $path = null)
    {
        if ($path) {
            $path = trim($path, '/') . '/';
        } else {
            $path = '';
        }

        if (!$this->filesystem->exists($this->addonPath . $path . $name)) {
            $this->filesystem->write($this->addonPath . $path . $name, $contents);
        }
    }

    /**
     * Initialize common properties
     *
     * @param string $addonName
     * @param bool $requireAddonExists Whether to check if the addon exists
     */
    protected function initCommon($addonName = null)
    {


        $this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Service/Generator';

        if ($addonName) {
            $this->addon = $addonName;
        }

        if ($this->requireAddonExists) {
            $addon = $this->checkAddonExists();
            $this->addonPath = $addon->getPath() . '/';
        }

        // Check that each file in the requiredComponentFiles array exists
        $this->checkAddonComponentsExists();
    }

    private function checkAddonComponentsExists()
    {
        if (! $this->requireAddonExists) {
            return false;
        }

        $addon = $this->checkAddonExists();
        $this->addonPath = $addon->getPath() . '/';

        foreach ($this->requiredComponentFiles as $file) {
            if (! file_exists($this->addonPath . $file . '.' . $this->addon . '.php')) {
                throw new \Exception(sprintf(lang('cli_error_the_specified_addon_component_does_not_exist'), $file), 1);
            }
        }
    }
}
