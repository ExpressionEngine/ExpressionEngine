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
class Categories extends Jumps
{
    public function __construct()
    {
        parent::__construct();
        if (!ee('Permission')->hasAny(['create_categories', 'edit_categories'])) {
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

    public function create()
    {
        $categoryGroups = $this->loadCategoryGroups(ee()->input->post('searchString'));

        $response = array();

        foreach ($categoryGroups as $categoryGroup) {
            $id = $categoryGroup->getId();
            $title = $categoryGroup->group_name;

            $response['createCategoryIn' . $categoryGroup->getId()] = array(
                'icon' => 'fa-plus',
                'command' => $categoryGroup->group_name,
                'command_title' => $categoryGroup->group_name,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('categories/create/' . $categoryGroup->getId())->compile()
            );
        }

        $this->sendResponse($response);
    }

    public function edit()
    {
        $categories = $this->loadCategories(ee()->input->post('searchString'));

        $response = array();

        foreach ($categories as $category) {
            $id = $category->getId();
            $title = $category->cat_name;

            $response['editCategory' . $category->getId()] = array(
                'icon' => 'fa-pencil-alt',
                'command' => $category->cat_name,
                'command_title' => $category->cat_name,
                'command_context' => $category->getCategoryGroup()->group_name,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('categories/edit/' . $category->getCategoryGroup()->getId() . '/' . $category->getId())->compile()
            );
        }

        $this->sendResponse($response);
    }

    private function loadCategoryGroups($searchString = false)
    {
        $categoryGroups = ee('Model')->get('CategoryGroup')->filter('site_id', ee()->config->item('site_id'));

        if (!empty($searchString)) {
            // Break the search string into individual keywords so we can partially match them.
            $keywords = explode(' ', $searchString);

            foreach ($keywords as $keyword) {
                $categoryGroups->filter('group_name', 'LIKE', '%' . ee()->db->escape_like_str($keyword) . '%');
            }
        }

        return $categoryGroups->limit(11)->all();
    }

    private function loadCategories($searchString = false)
    {
        $categories = ee('Model')->get('Category')
            ->with('CategoryGroup')
            ->filter('CategoryGroup.site_id', ee()->config->item('site_id'))
            ->fields('cat_id', 'cat_name', 'CategoryGroup.group_name');

        if (!empty($searchString)) {
            // Break the search string into individual keywords so we can partially match them.
            $keywords = explode(' ', $searchString);

            foreach ($keywords as $keyword) {
                $categories->filter('cat_name', 'LIKE', '%' . ee()->db->escape_like_str($keyword) . '%');
            }
        }

        return $categories->order('cat_name', 'ASC')->limit(11)->all();
    }
}
