<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Error;

use CP_Controller;

/**
 * Error\CPException Controller
 */
class CPException extends CP_Controller
{
    public function __construct()
    {
        ee()->remove('__legacy_controller');
        parent::__construct();
        ee()->output->enable_profiler(false);
    }

    public function index($message = 'unauthorized_access', $code = 403)
    {
        ee('Response')->setStatus($code);
        $vars = [
            'code' => $code,
            'message' => lang($message),
            'cp_page_title' => lang('http_code_' . $code),
        ];
        ee()->cp->render('errors/cp_exception', $vars);
    }
}

// EOF
