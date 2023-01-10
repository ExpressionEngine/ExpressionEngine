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

class Sites extends Jumps
{
    private $sites = array();

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Publish Jump Data
     */
    public function index()
    {
        // Should never be here without another segment.
        show_error(lang('unauthorized_access'), 403);
    }

    public function switch()
    {
        $site_list = ee()->session->userdata('assigned_sites');
        if (count($site_list) > 1) {
            foreach ($site_list as $id => $name) {
                if ($id != ee()->config->item('site_id')) {
                    $this->sites[] = [
                        'icon' => 'fa-globe',
                        'id' => $id,
                        'name' => $name
                    ];
                }
            }
        }

        $searchString = ee()->input->post('searchString');

        $response = array();

        if (!empty($searchString)) {
            // Break the search string into individual keywords so we can partially match them.
            $keywords = explode(' ', $searchString);

            foreach ($keywords as $keyword) {
                foreach ($this->sites as $site) {
                    if (preg_match('/' . $keyword . '/', $site)) {
                        $response['switchSite' . $site['id']] = array(
                            'icon' => $site['icon'],
                            'command' => $site['name'],
                            'command_title' => $site['name'],
                            'dynamic' => false,
                            'addon' => false,
                            'target' => 'msm/switch_to/' . $site['id']
                        );
                    }
                }
            }
        } else {
            foreach ($this->sites as $site) {
                $response['switchSite' . $site['id']] = array(
                    'icon' => $site['icon'],
                    'command' => $site['name'],
                    'command_title' => $site['name'],
                    'dynamic' => false,
                    'addon' => false,
                    'target' => 'msm/switch_to/' . $site['id']
                );
            }
        }

        $this->sendResponse($response);
    }

    public function edit()
    {
        $site_list = ee('Model')->get('Site')->all();

        foreach ($site_list as $site) {
            $this->sites[] = [
                'icon' => 'fa-globe',
                'id' => $site->site_id,
                'name' => $site->site_label
            ];
        }

        $searchString = ee()->input->post('searchString');

        $response = array();

        if (!empty($searchString)) {
            // Break the search string into individual keywords so we can partially match them.
            $keywords = explode(' ', $searchString);

            foreach ($keywords as $keyword) {
                foreach ($this->sites as $site) {
                    if (preg_match('/' . $keyword . '/', $site)) {
                        $response['editSite' . $site['id']] = array(
                            'icon' => $site['icon'],
                            'command' => $site['name'],
                            'command_title' => $site['name'],
                            'dynamic' => false,
                            'addon' => false,
                            'target' => 'msm/edit/' . $site['id']
                        );
                    }
                }
            }
        } else {
            foreach ($this->sites as $site) {
                $response['editSite' . $site['id']] = array(
                    'icon' => $site['icon'],
                    'command' => $site['name'],
                    'command_title' => $site['name'],
                    'dynamic' => false,
                    'addon' => false,
                    'target' => 'msm/edit/' . $site['id']
                );
            }
        }

        $this->sendResponse($response);
    }
}
