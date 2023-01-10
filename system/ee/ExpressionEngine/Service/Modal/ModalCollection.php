<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Modal;

use ExpressionEngine\Service\View\View;

/**
 * Modal Collection
 */
class ModalCollection
{
    /**
     * @var array $alerts An associative array of alerts by type
     */
    private $modals = array();

    /**
     * @var array An indexed array for storing the names of modals consumed via
     * startModal() and endModal()
     */
    private $modalStack = array();

    /**
     * Adds a named modal to the collection
     *
     * @param str $name The name of the modal
     * @param str $data The contents of the modal
     * @return self This returns a reference to itself
     */
    public function addModal($name, $data)
    {
        $this->modals[$name] = $data;

        return $this;
    }

    /**
     * This will start a new modal overwriting any previously defined modal of
     * the same name.
     *
     * @param str $name The name of the modal
     */
    public function startModal($name)
    {
        $this->modalStack[] = $name;
        ob_start();
    }

    /**
     * Ends the modal adding the modal to the collection based on the
     * most recently specified name via startModal.
     */
    public function endModal()
    {
        $name = array_pop($this->modalStack);

        if ($name === null) {
            throw new \Exception('View: Attempted to end modal without opening');
        }

        $buffer = '';

        $buffer .= ob_get_contents();
        ob_end_clean();

        $this->addModal($name, $buffer);
    }

    /**
     * Gets a named modal from the collection
     *
     * @param str $name The name of the modal
     * @return mixed The data stored for the named modal
     */
    public function getModal($name)
    {
        return $this->modals[$name];
    }

    /**
     * Gets all the modals stored in this collection
     *
     * @return array An array of stored modal data
     */
    public function getAllModals()
    {
        return $this->modals;
    }

    /**
     * Removed the previously set modal
     *
     * @param str $name The name of the modal
     * @return void
     */
    public function removeModal($name)
    {
        if (isset($this->modals[$name])) {
            unset($this->modals[$name]);
        }
    }

}

// EOF
