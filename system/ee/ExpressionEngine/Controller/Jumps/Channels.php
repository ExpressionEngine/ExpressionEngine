<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Jumps;

use CP_Controller;

/**
 * Member Create Controller
 */
class Channels extends Jumps
{

	public function __construct()
	{
		parent::__construct();
		if (!ee('Permission')->can('admin_channels'))
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

	public function edit()
	{
		$channels = $this->loadChannels(ee()->input->post('searchString'));

		$response = array();

		foreach ($channels as $channel) {
			$id = $channel->getId();
			$title = $channel->channel_title;

			$response['editChannel' . $channel->getId()] = array(
				'icon' => 'fa-pencil-alt',
				'command' => $channel->channel_title,
				'command_title' => $channel->channel_title,
				'dynamic' => false,
				'addon' => false,
				'target' => ee('CP/URL')->make('channels/edit/' . $channel->getId())->compile()
			);
		}

		$this->sendResponse($response);
	}

	private function loadChannels($searchString = false)
	{
		$channels = ee('Model')->get('Channel');

		if (!empty($searchString)) {
			// Break the search string into individual keywords so we can partially match them.
			$keywords = explode(' ', $searchString);

			foreach ($keywords as $keyword) {
				$channels->filter('channel_title', 'LIKE', '%' . $keyword . '%');
			}
		}

		return $channels->order('channel_title', 'ASC')->limit(11)->all();
	}
}
