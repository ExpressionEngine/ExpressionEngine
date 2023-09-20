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
                $generatorFolder = $parts[1];
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
        // if template engine selected, add suffix
        $origTemplatePath = $this->path;
        if (!empty(ee('TemplateGenerator')->templateEngine) && ee('TemplateGenerator')->templateEngine != 'native') {
            $this->path .= '.' . ee('TemplateGenerator')->templateEngine;
        }

        // do not allow any path traversal
        if (strpos($this->path, '..') !== false) {
            throw new \Exception('Invalid stub path: ' . htmlentities($this->path));
        }

        // set the stub path that are specific to this stub and shared ones
        $stubPaths = ee('TemplateGenerator')->getStubPaths($this->provider, $this->generatorFolder);

        foreach ($stubPaths as $path) {
            if (strpos($path, '..') !== false) {
                throw new \Exception('The stub path is not allowed');
            }
            if (file_exists($path . '/' . $this->path . '.php')) {
                return $path . '/' . $this->path . '.php';
            }
        }

        // if still not there, try fallback to no engine extension
        if ($origTemplatePath != $this->path) {
            foreach ($stubPaths as $path) {
                if (file_exists($path . '/' . $origTemplatePath . '.php')) {
                    return $path . '/' . $origTemplatePath . '.php';
                }
            }
        }

        throw new \Exception('Stub file not found: ' . htmlentities($this->path));
    }
}
// EOF
