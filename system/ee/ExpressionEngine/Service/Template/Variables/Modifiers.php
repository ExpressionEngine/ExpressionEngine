<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Template\Variables;

use ExpressionEngine\Service\Template\Variables;
use ExpressionEngine\Core\Provider;

/**
 * Variable Modifiers Service
 */
class Modifiers
{
    /**
     * Array holding all modifiers
     *
     * @var array
     */
    protected $modifiers = [];
    
    public function __construct()
    {
        
    }

    /**
     * Add modifier to registry
     *
     * @param String $name
     * @param Provider $provider
     * @return array all registered modifiers
     */
    public function register(String $name, Provider $provider)
    {
        if (empty($name) || empty($provider)) {
            return $this->modifiers;
        }

        // get the modifier's FQCN and see if it exists
        $fqcn = trim($provider->getNamespace(), '\\') . '\\Modifiers\\' . ucfirst($name);
        if (! class_exists($fqcn)) {
            return $this->modifiers;
        }

        // does it implement interface?
        $interfaces = class_implements($fqcn);
        if (! isset($interfaces[ModifierInterface::class])) {
            return $this->modifiers;
        }

        // register it!
        $this->modifiers[$name] = $fqcn;

        // also register with add-on name
        $this->modifiers[$provider->getPrefix() . '_' . $name] = $fqcn;
        
        return $this->modifiers;
    }

    /**
     * Return all registered custom modifiers
     *
     * @return array
     */
    public function all()
    {
        return $this->modifiers;
    }

    /**
     * Check whether modifier is regisetered
     *
     * @param String $name
     * @return boolean
     */
    public function has($name)
    {
        return isset($this->modifiers[$name]);
    }
}
// END CLASS

// EOF
