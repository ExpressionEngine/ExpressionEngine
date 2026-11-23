<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\License;

use CP_Controller;

/**
 * License controller
 */
class License extends CP_Controller
{
    /**
     * Early permissions checks
     */
    public function __construct()
    {
        parent::__construct();

        if (! AJAX_REQUEST or
            ee('Request')->method() != 'POST') {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    /**
     * Receives the license service's answer from the control panel and
     * stores it.
     *
     * @return string success or error
     */
    public function handleAccessResponse()
    {
        $response = ee()->input->post('licenseStatus');
        $status = ee('License/Status');
        $stored = is_array($response) && $status->store($response, ee()->input->post('license'));
        $challengeId = ee()->input->post('challenge_id');
        if (is_string($challengeId)) {
            $status->finishBrowserChallenge($challengeId, $stored);
        }

        return ee()->output->send_ajax_response(array(
            'messageType' => $stored ? 'success' : 'error',
            'message' => ''
        ));
    }

}
// EOF
