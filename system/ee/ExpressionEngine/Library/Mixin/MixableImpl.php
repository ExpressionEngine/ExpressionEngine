<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Mixin;

/**
 * Mixin Mixable abstract
 */
abstract class MixableImpl implements Mixable
{
    protected $_mixin_manager;

    /**
     * @return array Array of classes to mixin
     */
    abstract protected function getMixinClasses();

    /**
     * Check if the class has a given mixin
     *
     * @param String $name Mixin name
     * @return bool Has mixin of name
     */
    public function hasMixin($name)
    {
        return $this->getMixinManager()->hasMixin($name);
    }

    /**
     * Get a mixin by name
     *
     * @param String $name Mixin name
     * @return Mixin Mixin instance
     */
    public function getMixin($name)
    {
        return $this->getMixinManager()->getMixin($name);
    }

    /**
     * Get the current mixin manager
     */
    public function getMixinManager()
    {
        if (! isset($this->_mixin_manager)) {
            $this->setMixinManager($this->newMixinManager());
        }

        return $this->_mixin_manager;
    }

    /**
     * Set a mixin manager
     */
    public function setMixinManager($manager)
    {
        $this->_mixin_manager = $manager;
        $manager->setMixins($this->getMixinClasses());
    }

    /**
     * Create a new mixin manager
     */
    protected function newMixinManager()
    {
        return new Manager($this);
    }
}
