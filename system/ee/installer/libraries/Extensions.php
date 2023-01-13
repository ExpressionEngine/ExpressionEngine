<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
require_once(EE_APPPATH . '/libraries/Extensions.php');

/**
 * Installer Extensions
 */
class Installer_Extensions extends EE_Extensions
{
    /**
     * Installer doesn't allow any extensions to run, to
     * avoid running third-party code in this context
     **/
    public function call($which)
    {
        return;
    }
}
// END CLASS

// EOF
