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

/**
 * Jump Controller
 */
class Jumps extends CP_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->checkRequestSegments();
    }

    public function index()
    {
        if (! AJAX_REQUEST) {
            $this->invalidRequest();
        }
        
        // Dummy method to make sure it doesn't come up as a 404.
        // Is validated by the `checkRequestSegments()` in the constructor.
    }

    public function js()
    {
        ee()->load->library('javascript_loader');

        $jumpMenuItems = ee('CP/JumpMenu')->getItems();

        $contents = "
        EE.cp.jumpMenuURL = '" . ee('CP/URL', 'JUMPTARGET')->compile() . "';
        EE.cp.JumpMenuCommands = " . json_encode($jumpMenuItems) .";";

        $finfo = ee()->cache->file->get_metadata('jumpmenu/' . md5(ee()->session->getMember()->getId()));
        ee()->javascript_loader->set_headers('jumpmenu', $finfo['mtime']); 
        ee()->output->set_header('Content-Length: ' . strlen($contents));
        ee()->output->set_output($contents);
    }

    public function addons()
    {
        if (! AJAX_REQUEST) {
            $this->invalidRequest();
        }
        
        if (!ee('Permission')->can('access_addons')) {
            $this->sendResponse([]);
        }

        if (empty(ee()->uri->segments[4]) || empty(ee()->uri->segments[5])) {
            $this->invalidRequest();
        }

        $name = ee()->uri->segments[4];
        $method = ee()->uri->segments[5];

        $info = ee('Addon')->get($name);

        $class = $info->getJumpClass();
        $jumpMenu = new $class();

        if (! method_exists($jumpMenu, $method)) {
            $this->invalidMethod();
        }

        $searchKeywords = explode(' ', ee()->input->post('searchString'));

        $items = $jumpMenu->{$method}($searchKeywords);

        foreach ($items as $key => $item) {
            $items[$key]['target'] = ee('CP/URL')->make('addons/settings/' . $name . '/' . $items[$key]['target'])->compile();
        }

        return $this->sendResponse($items);
    }

    /**
     * Make sure we're passing the proper segments for each request.
     */
    private function checkRequestSegments()
    {
        if (empty(ee()->uri->segments[3])) {
            $this->invalidRequest();
        }
    }

    /**
     * Send the invalid request error response, passing any error messages we accrued along the way.
     * @return void   ajax compatible error string
     */
    private function invalidRequest()
    {
        show_error(lang('machines_only_request'), 400);
    }

    /**
     * Send the invalid method error response, passing any error messages we accrued along the way.
     * @return void   ajax compatible error string
     */
    private function invalidMethod()
    {
        show_error(lang('addon_missing_jump_method'), 400);
    }

    protected function sendResponse($response)
    {
        die(json_encode(array(
            'status' => 'success',
            'data' => $response
        )));
    }
}

// EOF
