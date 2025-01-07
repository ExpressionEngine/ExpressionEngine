<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Members;

use CP_Controller;

/**
 * Publish/Edit Controller
 */
class Views extends CP_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (! AJAX_REQUEST) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    public function saveDefault()
    {
        $query = ee('Model')->get('MemberManagerView')
            ->filter('member_id', ee()->session->userdata('member_id'))
            ->filter('role_id', (int) ee()->input->get('role_id'));
        $view = $query->first();
        if (empty($view)) {
            $view = ee('Model')->make('MemberManagerView');
        }

        $view->member_id = ee()->session->userdata('member_id');
        $view->role_id = (int) ee()->input->get('role_id');
        $view->columns = json_encode(ee()->input->post('columns'));

        if ($view->save()) {
            ee()->output->send_ajax_response('success');
        } else {
            ee()->output->send_ajax_response(array('error' => 'could_not_save_view'));
        }
    }
}

// EOF
