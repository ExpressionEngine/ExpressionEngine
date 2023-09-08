<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\TemplateGenerator;

/**
 * Registered generators are kept in array,
 * and we need to make sure each array element has certain set of properties
 *
 * We ensure that by making each registry entry instance of this class
 */
class RegisteredGenerator
{

    /**
     * Own class name for the generator
     *
     * @var string
     */
    protected $className;

    /**
     * Fully qualified class name of Generator
     *
     * Used to spin generator instance
     *
     * @var string
     */
    protected $fqcn;

    /**
     * File system path to generator
     *
     * @var string
     */
    protected $path;

    /**
     * Provider prefix, which is add-on short name
     *
     * @var string
     */
    protected $prefix;

    /**
     * Instance of the generator
     *
     * @var TemplateGeneratorInterface
     */
    protected $instance;

    /**
     * Stub paths specific to this generator
     *
     * @var array
     */
    protected $stubPaths = [];

    /**
     * Construct the registry entry we ensure all required properties are set
     *
     * @param string $prefix
     * @param string $className
     * @param string $fqcn
     */
    public function __construct($prefix, $className, $fqcn)
    {
        $this->prefix = $prefix;
        $this->className = $className;
        $this->fqcn = $fqcn;
    }

    /**
     * We keep the properties private, but allow accessing all of them
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Spin the instance of the generator and return it
     *
     * @return TemplateGeneratorInterface
     */
    public function getInstance()
    {
        if (is_null($this->instance)) {
            $interfaces = class_implements($this->fqcn);
            if (! isset($interfaces[TemplateGeneratorInterface::class])) {
                throw new \Exception('Template Generator is invalid');
            }
            $this->instance = new $this->fqcn();
        }
        return $this->instance;
    }

    /**
     * Get the stub path specific to this add-on
     *
     * @return array
     */
    public function getStubPaths()
    {
        if (empty($this->stubPaths)) {
            $this->stubPaths = [];
            $optionValues = ee('TemplateGenerator')->getOptionValues();
            $provider = ee('App')->get($this->prefix);
            if (isset($optionValues['theme']) && !empty($optionValues['theme']) && $optionValues['theme'] != 'none') {
                // if we use a theme, we need to check the path set by theme
                $themeInfo = explode(':', $optionValues['theme']);
                $themeProviderPrefix = $themeInfo[0];
                $themeName = $themeInfo[1];
                if ($themeProviderPrefix != $this->prefix) {
                    $themeProvider = ee('App')->get($themeProviderPrefix);
                } else {
                    $themeProvider = $provider;
                }
                // user folder first, then own folder of theme add-on
                $this->stubPaths[] = SYSPATH . 'user/stubs/' . $themeProviderPrefix . '/' . $themeName . '/' . $this->prefix . '/' . lcfirst($this->className);
                // e.g. system/user/stubs/mytheme/tailwind/channel/entries
                $this->stubPaths[] = $themeProvider->getPath() . '/stubs/' . $themeName . '/' . $this->prefix . '/' . lcfirst($this->className);
                // e.g. system/user/addons/mytheme/stubs/tailwind/channel/entries
            }

            //user-provided stubs for this generator
            $this->stubPaths[] = SYSPATH . 'user/stubs/' . $this->prefix . '/' . lcfirst($this->className);
            // e.g. system/user/stubs/channel/entries
            // stubs provided by the generator add-on
            $this->stubPaths[] = $provider->getPath() . '/stubs/' . lcfirst($this->className);
            // e.g. system/ee/ExpressionEngine/addons/channel/entries
            // or system/user/addons/channel/entries
        }
        return $this->stubPaths;
    }
}
