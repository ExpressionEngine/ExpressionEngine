<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Core;

/**
 * Core Installer
 */
class Installer extends Core
{
    /**
     *
     */
    public function boot()
    {
        define('APPPATH', SYSPATH . 'ee/installer/');
        define('EE_APPPATH', BASEPATH);

        define('PATH_PRO_ADDONS', SYSPATH . 'ee/ExpressionEngine/Addons/pro/levelups/');
        define('PATH_ADDONS', SYSPATH . 'ee/ExpressionEngine/Addons/');
        define('PATH_MOD', SYSPATH . 'ee/ExpressionEngine/Addons/');
        define('PATH_PI', SYSPATH . 'ee/ExpressionEngine/Addons/');
        define('PATH_EXT', SYSPATH . 'ee/ExpressionEngine/Addons/');
        define('PATH_FT', SYSPATH . 'ee/ExpressionEngine/Addons/');
        define('INSTALLER', true);

        get_config(array('subclass_prefix' => 'Installer_'));

        parent::boot();
    }
}

// EOF
