<?php
/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Service\Prolet;

use ExpressionEngine\Service\View\ViewFactory;

/**
 * Abstract Prolet
 */
abstract class AbstractProlet implements ProletInterface
{
    /**
     * @var string $icon Prolet icon
     */
    protected $icon = 'icon.svg';

    /**
     * @var string $name Prolet name
     */
    protected $name;

    /**
     * @var string $method JS method to be triggered
     * Available options: [ajax, redirect, popup]
     */
    protected $method;

    /**
     * @var string $size Popup window size
     * Available options: [footer, large, small]
     */
    protected $size;

    /**
     * @var string $url Redirect URL
     */
    protected $url;

    /**
     * @var array $footer Footer definition for the prolet popup
     */
    protected $buttons = [
        [
            'type'          => 'button',
            'text'          => 'save',
            'buttonStyle'   => 'primary',
            'callback'      => 'save',
        ],
    ];

    /**
     * @var array $action Name of controller function that generates prolet
     */
    protected $action;

    /**
     * Syntactic sugar ¯\_(ツ)_/¯
     */
    public function make()
    {
        return $this;
    }

    /**
     * Full URL for icon to be used
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Prolet name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * The name of javascript method that will be invoked by prolet
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Prolet window size
     *
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Buttons for the prolet popup
     *
     * @return Array
     */
    public function getButtons()
    {
        return $this->buttons;
    }

    /**
     * Controller action that generates data for prolet view
     * As of EE Pro 1.0 only single action is supported
     *
     * @return mixed string or array
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * URL for ajax request or redirect
     * Prolets are expected to write their own implementations of this function
     *
     * @return string or null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Check if current member allowed to this prolet
     *
     * @return bool TRUE if access allowed, FALSE otherwise
     */
    public function checkPermissions()
    {
        return true;
    }
}

// EOF
