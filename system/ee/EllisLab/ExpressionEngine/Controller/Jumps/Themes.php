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

class Themes extends Jumps {

	private $themes = array('light' => 'fa-sun', 'dark' => 'fa-moon', 'coffee' => 'fa-coffee');

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
		$searchString = ee()->input->post('searchString');

		$response = array();

		if (!empty($searchString)) {
			// Break the search string into individual keywords so we can partially match them.
			$keywords = explode(' ', $searchString);

			foreach ($keywords as $keyword) {
				foreach ($this->themes as $theme => $icon) {
					if (preg_match('/' . $keyword . '/', $theme)) {
						$response['switchTheme' . $theme] = array(
							'icon' => $icon,
							'command' => $theme,
							'command_title' => $theme,
							'dynamic' => true,
							'addon' => false,
							'target' => 'theme/' . $theme
						);
					}
				}
			}

			if ($searchString === 'pink') {
				$response['switchThemePink'] = array(
					'icon' => 'fa-heart',
					'command' => 'pink',
					'command_title' => 'pink',
					'dynamic' => true,
					'addon' => false,
					'target' => 'theme/pink'
				);
			}
		} else {
			foreach ($this->themes as $theme => $icon) {
				$response['switchTheme' . $theme] = array(
					'icon' => $icon,
					'command' => $theme,
					'command_title' => $theme,
					'dynamic' => true,
					'addon' => false,
					'target' => 'theme/' . $theme
				);
			}
		}

		$this->sendResponse($response);
	}

	public function directories()
	{
		$directories = $this->loadDirectories(ee()->input->post('searchString'));

		$response = array();

		foreach ($directories as $directory) {
			$id = $directory->getId();

			$response['editEntry' . $directory->getId()] = array(
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

	private function loadDirectories($searchString = false)
	{
		$directories = ee('Model')->get('UploadDestination');

		if (!empty($searchString)) {
			// Break the search string into individual keywords so we can partially match them.
			$keywords = explode(' ', $searchString);

			foreach ($keywords as $keyword) {
				$directories->filter('name', 'LIKE', '%' . $keyword . '%');
			}
		}

		return $directories->order('name', 'ASC')->limit(11)->all();
	}
}
