<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Jumps;

use CP_Controller;

class Caches extends Jumps
{
    public function __construct()
    {
        parent::__construct();
        if (!ee('Permission')->has('can_access_data')) {
            $this->sendResponse([]);
        }
    }

    /**
     * Publish Jump Data
     */
    public function index()
    {
        // Should never be here without another segment.
        show_error(lang('unauthorized_access'), 403);
    }

    public function clear()
    {
        ee()->lang->load('utilities');
        $caches = [
            'all' => lang('all_caches'),
            'page' => lang('templates'),
            'tag' => lang('tags'),
            'db' => lang('database')
        ];
        $response = [];
        foreach ($caches as $key => $command) {
            $response['clearCaches' . $key] = array(
                'icon' => 'fa-database',
                'command' => $command,
                'command_title' => $command,
                'dynamic' => false,
                'addon' => false,
                'action' => true,
                'target' => 'utilities/cache',
                'data' => [
                    'cache_type' => $key
                ]
            );
        }

        $this->sendResponse($response);
    }
}
