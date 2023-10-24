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
class Templates extends Jumps
{
    public function __construct()
    {
        parent::__construct();
        if (!ee('Permission')->can('access_design')) {
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
        $groups = $this->loadTemplateGroups(ee()->input->post('searchString'));

        $response = array();

        foreach ($groups as $group) {
            $response['viewTemplateGroup' . $group->group_name] = array(
                'icon' => 'fa-eye',
                'command' => $group->group_name,
                'command_title' => $group->group_name,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('design/manager/' . $group->group_name)->compile()
            );
        }

        $this->sendResponse($response);
    }

    public function create()
    {
        $groups = $this->loadTemplateGroups(ee()->input->post('searchString'));

        $response = array();

        foreach ($groups as $group) {
            $response['createTemplateIn' . $group->group_name] = array(
                'icon' => 'fa-plus',
                'command' => $group->group_name,
                'command_title' => $group->group_name,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('design/template/create/' . $group->group_name)->compile()
            );
        }

        $this->sendResponse($response);
    }

    public function group()
    {
        $groups = $this->loadTemplateGroups(ee()->input->post('searchString'));

        $response = array();

        foreach ($groups as $group) {
            $response['editTemplateGroup' . $group->group_name] = array(
                'icon' => 'fa-pencil-alt',
                'command' => $group->group_name,
                'command_title' => $group->group_name,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('design/group/edit/' . $group->group_name)->compile()
            );
        }

        $this->sendResponse($response);
    }

    public function edit()
    {
        $templates = $this->loadTemplates(ee()->input->post('searchString'));

        $response = array();

        foreach ($templates as $template) {
            $id = $template->getId();

            $response['editTemplate' . $template->getId()] = array(
                'icon' => 'fa-pencil-alt',
                'command' => $template->template_name,
                'command_title' => $template->template_name,
                'command_context' => $template->getTemplateGroup()->group_name,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('design/template/edit/' . $template->getId())->compile()
            );
        }

        $this->sendResponse($response);
    }

    private function loadTemplateGroups($searchString = false)
    {
        $groups = ee('Model')->get('TemplateGroup');

        if (!empty($searchString)) {
            // Break the search string into individual keywords so we can partially match them.
            $keywords = explode(' ', $searchString);

            foreach ($keywords as $keyword) {
                $groups->filter('group_name', 'LIKE', '%' . ee()->db->escape_like_str($keyword) . '%');
            }
        }

        return $groups->order('group_name', 'ASC')->limit(11)->all();
    }

    private function loadTemplates($searchString = false)
    {
        $templates = ee('Model')->get('Template');

        if (!empty($searchString)) {
            // Break the search string into individual keywords so we can partially match them.
            $keywords = explode(' ', $searchString);

            foreach ($keywords as $keyword) {
                $templates->filter('template_name', 'LIKE', '%' . ee()->db->escape_like_str($keyword) . '%');
            }
        }

        return $templates->order('template_name', 'ASC')->limit(11)->all();
    }
}
