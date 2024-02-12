<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Logs;

/**
 * Logs\CP Controller
 */
class Cp extends Logs
{
    /**
     * View Control Panel Log Files
     *
     * Shows the control panel action log
     *
     * @access public
     * @return mixed
     */
    public function index()
    {
        ee()->functions->redirect(ee('CP/URL')->make('logs', ['channel' => 'cp']));
    }
}
// END CLASS

// EOF
