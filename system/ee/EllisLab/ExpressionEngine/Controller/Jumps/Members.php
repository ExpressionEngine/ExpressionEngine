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

class Members extends Jumps {

	public function __construct()
	{
		parent::__construct();
		if (!ee('Permission')->can('access_members'))
		{
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
		$groups = $this->loadMemberGroups(ee()->input->post('searchString'));

		$response = array();

		foreach ($groups as $group) {
			$response['viewMemberGroup' . $group->name] = array(
				'icon' => 'fa-eye',
				'command' => $group->name,
				'command_title' => $group->name,
				'dynamic' => false,
				'addon' => false,
				'target' => ee('CP/URL')->make('members', array('role_id' => $group->getId()))->compile()
			);
		}

		$this->sendResponse($response);
	}

	public function group()
	{
		$groups = $this->loadMemberGroups(ee()->input->post('searchString'));

		$response = array();

		foreach ($groups as $group) {
			$response['editMemberGroup' . $group->group_title] = array(
				'icon' => 'fa-pencil-alt',
				'command' => $group->group_title,
				'command_title' => $group->group_title,
				'dynamic' => false,
				'addon' => false,
				'target' => ee('CP/URL')->make('members/groups/edit/' . $group->getId())->compile()
			);
		}

		$this->sendResponse($response);
	}

	public function edit()
	{
		$members = $this->loadMembers(ee()->input->post('searchString'));

		$response = array();

		foreach ($members as $member) {
			$id = $member->getId();

			$response['editMember' . $member->getId()] = array(
				'icon' => 'fa-pencil-alt',
				'command' => $member->username,
				'command_title' => $member->username,
				'command_context' => $member->getMemberGroup()->group_title,
				'dynamic' => false,
				'addon' => false,
				'target' => ee('CP/URL')->make('members/profile/settings', array('id' => $member->getId()))->compile()
			);
		}

		$this->sendResponse($response);
	}

	private function loadMemberGroups($searchString = false)
	{
		$groups = ee('Model')->get('Role');

		if (!empty($searchString)) {
			// Break the search string into individual keywords so we can partially match them.
			$keywords = explode(' ', $searchString);

			foreach ($keywords as $keyword) {
				$groups->filter('name', 'LIKE', '%' . $keyword . '%');
			}
		}

		return $groups->order('name', 'ASC')->limit(11)->all();
	}

	private function loadMembers($searchString = false)
	{
		$members = ee('Model')->get('Member');

		if (!empty($searchString)) {
			// Break the search string into individual keywords so we can partially match them.
			$keywords = explode(' ', $searchString);

			foreach ($keywords as $keyword) {
				$members->filter('username', 'LIKE', '%' . $keyword . '%');
			}
		}

		return $members->order('username', 'ASC')->limit(11)->all();
	}
}
