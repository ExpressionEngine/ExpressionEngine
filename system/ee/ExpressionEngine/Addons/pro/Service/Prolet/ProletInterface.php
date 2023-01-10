<?php
/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Service\Prolet;

/**
 * Prolet Interface
 *
 * All prolets are required to implement this interface
 */
interface ProletInterface
{
    /**
     * Filename of icon to be used
     *
     * @return string
     */
    public function getIcon();

    /**
     * Prolet name
     *
     * @return string
     */
    public function getName();

    /**
     * The name of javascript method that will be invoked by prolet
     *
     * @return string
     */
    public function getMethod();

    /**
     * Buttons for the prolet popup
     *
     * @return Array
     */
    public function getButtons();

    /**
     * Controller action that generates data for prolet view
     *
     * @return mixed
     */
    public function getAction();

    /**
     * Popup window size
     *
     * @return string
     */
    public function getSize();

    /**
     * Check if current member allowed to this prolet
     *
     * @return bool
     */
    public function checkPermissions();
}
