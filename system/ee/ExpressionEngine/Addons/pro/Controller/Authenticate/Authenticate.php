<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Controller\Authenticate;

use ExpressionEngine\Controller\Pro\Pro as Pro;

/**
 * Prolet Controller
 */
class Authenticate extends Pro
{
    /**
     * Constructor
     */
    public function __construct()
    {
        ee()->lang->load('pro');
    }

    public function authenticate()
    {
        ee()->cp->add_js_script(array(
            'pro_file' => array(
                'iframe-listener'
            )
        ));

        return ee('View')->make('pro:prolet')->render([
            'pro_class'   => 'pro-frontend-modal',
            'hide_topbar' => true,
            'output'      => ee('View')->make('pro:account/idle-login')->render()
        ]);
    }
}

// EOF
