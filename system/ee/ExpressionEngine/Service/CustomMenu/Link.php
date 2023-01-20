<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\CustomMenu;

/**
 * Custom Menu Link
 */
class Link
{
    public $title;
    public $url;

    /**
     * Create a new menu item
     *
     * @param String $title Text of the menu item
     * @param Mixed $url URL string or CP/URL object
     */
    public function __construct($title, $url)
    {
        $this->title = htmlspecialchars($title);

        $base = ee('CP/URL')->make('')->compile();

        if (is_a($url, 'ExpressionEngine\Library\CP\URL')) {
            $url = $url->compile();
        } elseif (strpos($url, '://') === false && strpos($url, $base) !== 0) {
            $url = ee('CP/URL')->make($url)->compile();
        }

        $this->url = $url;
    }

    /**
     * Is this a submenu?
     *
     * @return bool False
     */
    public function isSubmenu()
    {
        return false;
    }
}
