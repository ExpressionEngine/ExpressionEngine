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
        $channel = !empty(ee()->input->get('channel')) ? ee()->input->get('channel') : null;
        $query = ee('Model')->get('LogManagerView')
            ->filter('member_id', ee()->session->userdata('member_id'))
            ->filter('channel', $channel);
        $view = $query->first();
        if (empty($view)) {
            $view = ee('Model')->make('LogManagerView');
            $view->setRawProperty('channel', $channel);
            $view->setRawProperty('member_id', ee()->session->userdata('member_id'));
        }

        $view->columns = json_encode(ee()->input->post('columns'));

        if ($view->save()) {
            ee()->output->send_ajax_response('success');
        } else {
            ee()->output->send_ajax_response(array('error' => 'could_not_save_view'));
        }
    }
}

// EOF
