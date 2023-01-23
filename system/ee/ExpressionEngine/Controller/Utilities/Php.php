<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities;

/**
 * PHP Info Controller
 */
class Php extends Utilities
{
    /**
     * PHP Info
     *
     * @access	public
     * @return	void
     */
    public function index()
    {
        if (! ee('Permission')->can('access_utilities')) {
            show_error(lang('unauthorized_access'), 403);
        }

        exit(phpinfo());
    }
}
// END CLASS

// EOF
