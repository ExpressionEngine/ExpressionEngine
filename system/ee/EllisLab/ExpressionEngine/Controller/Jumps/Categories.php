<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Jumps;

use CP_Controller;

/**
 * Member Create Controller
 */
class Categories extends Jumps
{

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

	public function create()
	{
		$categoryGroups = $this->loadCategoryGroups(ee()->input->post('searchString'));

		$this->sendResponse($categoryGroups);
	}

	public function edit()
	{
		$categories = $this->loadCategories(ee()->input->post('searchString'));

		$this->sendResponse($categories);
	}

	private function loadCategoryGroups($searchString = false)
	{
		$categoryGroups = ee('Model')->get('CategoryGroup');

		if (!empty($searchString)) {
			// Break the search string into individual keywords so we can partially match them.
			$keywords = explode(' ', $searchString);

			foreach ($keywords as $keyword) {
				$categoryGroups->filter('group_name', 'LIKE', '%' . $keyword . '%');
			}
		}

		$categoryGroups = $categoryGroups->all();

		$response = array();

		foreach ($categoryGroups as $categoryGroup) {
			$id = $categoryGroup->getId();
			$title = $categoryGroup->group_name;

			$response['createCategoryIn' . $categoryGroup->getId()] = array(
				'icon' => 'fa-plus',
				'command' => 'create category in ' . $categoryGroup->group_name,
				'command_title' => 'Create <b>Category</b> in <b>' . $categoryGroup->group_name . '</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => ee('CP/URL')->make('categories/create/' . $categoryGroup->getId())->compile()
			);
		}

		return $response;
	}

	private function loadCategories($searchString = false)
	{
		$categories = ee('Model')->get('Category')
				->with('CategoryGroup')
				->fields('cat_id', 'cat_name', 'CategoryGroup.group_name');

		if (!empty($searchString)) {
			// Break the search string into individual keywords so we can partially match them.
			$keywords = explode(' ', $searchString);

			foreach ($keywords as $keyword) {
				$categories->filter('cat_name', 'LIKE', '%' . $keyword . '%');
			}
		}

		$categories = $categories->order('cat_name', 'ASC')
				->limit(11)
				->all();

		$response = array();

		foreach ($categories as $category) {
			$id = $category->getId();
			$title = $category->cat_name;

			$response['editCategory' . $category->getId()] = array(
				'icon' => 'fa-pencil-alt',
				'command' => $category->getCategoryGroup()->group_name . ' ' . $category->cat_name,
				'command_title' => $category->getCategoryGroup()->group_name . ' &raquo; <b>' . $category->cat_name . '</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => ee('CP/URL')->make('categories/edit/' . $category->getCategoryGroup()->getId() . '/' . $category->getId())->compile()
			);
		}

		return $response;
	}
}
