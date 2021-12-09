<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Installer Language
 */
class Installer_Lang extends EE_Lang
{
    /**
     * Forces the current language to English
     * @return string The idiom to load
     */
    public function getIdiom()
    {
        return 'english';
    }
}

// EOF
