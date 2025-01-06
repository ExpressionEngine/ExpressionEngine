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
 * Member Create Controller
 */
class Files extends Jumps
{
    public function __construct()
    {
        parent::__construct();
        if (!ee('Permission')->can('access_files')) {
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

    public function view()
    {
        $directories = $this->loadDirectories(ee()->input->post('searchString'));

        $response = array();

        foreach ($directories as $directory) {
            $id = $directory->getId();

            $response['viewFilesIn' . $directory->getId()] = array(
                'icon' => 'fa-eye',
                'command' => $directory->name,
                'command_title' => $directory->name,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('files/directory/' . $directory->getId())->compile()
            );
        }

        $this->sendResponse($response);
    }

    public function directories()
    {
        $directories = $this->loadDirectories(ee()->input->post('searchString'));

        $response = array();

        foreach ($directories as $directory) {
            $id = $directory->getId();

            $response['editUpload' . $directory->getId()] = array(
                'icon' => 'fa-pencil-alt',
                'command' => $directory->name,
                'command_title' => $directory->name,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('files/uploads/edit/' . $directory->getId())->compile()
            );
        }

        $this->sendResponse($response);
    }

    public function sync()
    {
        $directories = $this->loadDirectories(ee()->input->post('searchString'));

        $response = array();

        foreach ($directories as $directory) {
            $id = $directory->getId();

            $response['syncUpload' . $directory->getId()] = array(
                'icon' => 'fa-sync-alt',
                'command' => $directory->name,
                'command_title' => $directory->name,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('files/uploads/sync/' . $directory->getId())->compile()
            );
        }

        $this->sendResponse($response);
    }

    private function loadDirectories($searchString = false)
    {
        $directories = ee('Model')->get('UploadDestination')->filter('site_id', 'IN', [0, ee()->config->item('site_id')]);

        if (!empty($searchString)) {
            // Break the search string into individual keywords so we can partially match them.
            $keywords = explode(' ', $searchString);

            foreach ($keywords as $keyword) {
                $directories->filter('name', 'LIKE', '%' . ee()->db->escape_like_str($keyword) . '%');
            }
        }

        return $directories->order('name', 'ASC')->limit(11)->all();
    }
}
