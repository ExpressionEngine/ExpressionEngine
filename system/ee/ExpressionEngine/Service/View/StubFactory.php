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
 * StubFactory
 */
class StubFactory
{
    /**
     * @var ExpressionEngine\Core\Provider
     */
    protected $provider;

    /**
     * Constructor
     *
     * @param Provider $provider The default provider for views
     */
    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * This will make and return a Stub object
     * Unlike Views, Stubs are passed as string with 3 parts separated by colons
     *
     * @param string $name The path to the stub file, prefixed with add-on name and generator folder
     * @return object A ExpressionEngine\Service\View\Stub object
     */
    public function make($path)
    {
        $provider = $this->provider;

        if (strpos($path, ':')) {
            $parts = explode(':', $path, 3);
            $prefix = $parts[0];
            if (isset($parts[2])) {
                $generatorFolder = $parts[1]; //adding leading slash as that makes building full path easier
                $path = $parts[2];
            } else {
                $generatorFolder = '';
                $path = $parts[1];
            }
            $provider = $provider->make('App')->get($prefix);
        }

        $stub = new Stub($path, $provider);
        $stub->generatorFolder = $generatorFolder;

        return $stub;
    }

    /**
     * Get the array of stub paths
     * This would include:
     * - user folder
     * - add-on folder
     * - theming add-on folder
     * - shared stubs folder
     *
     * @param Provider $provider
     * @param string $generatorFolder
     *
     * @return array
     */
    public function getGeneratorStubPaths(Provider $provider, string $generatorFolder, $theme = null)
    {
        $paths = [];
        $sharedPaths = []; // These are kept separate to append later so they are lower priority

        if (!empty($theme) && $theme != 'none') {
            // if we use a theme, we need to check the path set by theme
            if (!ee('TemplateGenerator')->hasTheme($theme)) {
                throw new \Exception('Theme not found');
            }

            $theme = ee('TemplateGenerator')->getTheme($theme);
            // user folder first, then own folder of theme add-on
            $paths[] = SYSPATH . 'user/stubs/' . $theme['prefix'] . '/' . $theme['folder'] . '/' . $provider->getPrefix() . '/' . $generatorFolder;
            // e.g. system/user/stubs/mytheme/tailwind/channel/entries
            $paths[] = ee('TemplateGenerator')->getThemeProvider($theme)->getPath() . '/stubs/' . $theme['folder'] . '/' . $provider->getPrefix() . '/' . $generatorFolder;
            // e.g. system/user/addons/mytheme/stubs/tailwind/channel/entries
            $sharedPaths[] = SYSPATH . 'user/stubs/' . $theme['prefix'] . '/' . $theme['folder'];
            $sharedPaths[] = ee('TemplateGenerator')->getThemeProvider($theme)->getPath() . '/stubs/' . $theme['folder'];
        }

        //user-provided stubs for this generator
        $paths[] = SYSPATH . 'user/stubs/' . $provider->getPrefix() . '/' . $generatorFolder;
        // e.g. system/user/stubs/channel/entries
        // stubs provided by the generator add-on
        $paths[] = $provider->getPath() . '/stubs/' . $generatorFolder;
        // e.g. system/ee/ExpressionEngine/addons/channel/entries
        // or system/user/addons/channel/entries

        //if specifics not found, fallback to shared stubs (user first)
        $sharedPaths[] = SYSPATH . 'user/stubs';

        // ee/templates/stubs is shared folder for native template engine
        $sharedPaths[] = SYSPATH . 'ee/templates/stubs';

        return array_merge($paths, $sharedPaths);
    }
}
// EOF
