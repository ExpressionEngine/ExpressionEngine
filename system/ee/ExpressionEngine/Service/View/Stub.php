<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\View;

use ExpressionEngine\Core\Provider;

/**
 * Stub
 */
class Stub extends View
{
    /**
     * Name of folder where stubs are located
     *
     * @var string
     */
    public $generatorFolder;

    /**
     * Create a new stub object. Because stub path format is different, we need to override this
     *
     * @param  string $view Subview name, potentially with prefix
     * @return Stub         The subview instance
     */
    protected function make($view)
    {
        $provider = $this->provider;
        $generatorFolder = $this->generatorFolder;

        if (strpos($view, ':')) {
            $parts = explode(':', $view, 3);
            $prefix = $parts[0];
            if (isset($parts[2])) {
                $generatorFolder = '/' . $parts[1]; //adding leading slash as that makes building full path easier
                $view = $parts[2];
            } else {
                $generatorFolder = '';
                $view = $parts[1];
            }
            $provider = $provider->make('App')->get($prefix);
        }

        $stub = new static($view, $provider);
        $stub->generatorFolder = $generatorFolder;

        return $stub;
    }

    /**
     * Get the full server path to the stub file
     *
     * @return string The full server path
     */
    protected function getPath()
    {
        // do not allow any path traversal
        if (strpos($this->path, '..') !== false) {
            throw new \Exception('Invalid stub path: ' . htmlentities($this->path));
        }

        // set the stub path that are specific to this embed
        // so if you have embed like $this->embed('member:profile:_field_metadata', $field); running from channel:entries generator
        // the stubPaths map will look like
        // system/user/stubs/themes/tailwind/member/profile
        // system/ee/ExpressionEngine/Addons/themes/stubs/tailwind/profile
        // system/user/stubs/member/profile
        // system/ee/ExpressionEngine/Addons/member/stubs/profile
        // system/user/stubs/themes/tailwind/channel/entries
        // system/ee/ExpressionEngine/Addons/themes/stubs/tailwind/channel/entries
        // system/user/stubs/channel/entries
        //system/ee/ExpressionEngine/Addons/themes/stubs/entries
        // ... and then into shared folder

        $stubPaths = [];
        //here we add path provided by theme
        $optionValues = ee('TemplateGenerator')->getOptionValues();
        if (isset($optionValues['theme']) && !empty($optionValues['theme']) && $optionValues['theme'] != 'none') {
            // if we use a theme, we need to check the path set by theme
            $themeProvider = explode(':', $optionValues['theme']);
            $provider = ee('App')->get($themeProvider[0]);
            // user folder first, then own folder of theme add-on
            $stubPaths[] = SYSPATH . 'user/stubs/' . $themeProvider[0] . '/' . $themeProvider[1] . '/' . $this->provider->getPrefix() . $this->generatorFolder;
            // e.g. system/user/stubs/mytheme/tailwind/channel/entries
            $stubPaths[] = $provider->getPath() . '/stubs/' . $themeProvider[1] . '/' . $this->provider->getPrefix() . $this->generatorFolder;
            // e.g. system/user/addons/mytheme/stubs/tailwind/channel/entries
        }
        // and then, the embed's defaults
        $stubPaths[] = SYSPATH . 'user/stubs/' . $this->provider->getPrefix() . $this->generatorFolder;
        $stubPaths[] = $this->provider->getPath() . '/stubs' . $this->generatorFolder;

        // get the shared stub paths
        $stubPaths = array_merge($stubPaths, ee('TemplateGenerator')->getSharedStubPaths());

        foreach ($stubPaths as $path) {
            if (strpos($path, '..') !== false) {
                throw new \Exception('The stub path is not allowed');
            }
            if (file_exists($path . '/' . $this->path . '.php')) {
                return $path . '/' . $this->path . '.php';
            }
        }

        throw new \Exception('Stub file not found: ' . htmlentities($this->path));
    }
}
// EOF
