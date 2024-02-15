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

use ExpressionEngine\Service\CP\Filter\FilterFactory;
use ExpressionEngine\Service\CP\Filter\FilterRunner;

/**
 * Logs\Developer Controller
 */
class Developer extends Logs
{
    /**
     * Shows Developer Log page
     *
     * @access public
     * @return void
     */
    public function index()
    {
        ee()->functions->redirect(ee('CP/URL')->make('logs', ['channel' => 'developer']));
    }
}
// END CLASS

// EOF
