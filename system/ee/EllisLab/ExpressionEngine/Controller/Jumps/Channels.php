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
class Channels extends Jumps
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

	public function edit()
	{
		$channels = $this->loadChannels(ee()->input->post('searchString'));

		$this->sendResponse($channels);
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

		$channels = $channels->all();

		$response = array();

		foreach ($channels as $channel) {
			$id = $channel->getId();
			$title = $channel->channel_title;

			$response['editChannel' . $channel->getId()] = array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit channel titled ' . $channel->channel_title,
				'command_title' => 'Edit <b>Channel</b> titled <b>' . $channel->channel_title . '</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => ee('CP/URL')->make('channels/edit/' . $channel->getId())->compile()
			);
		}

		return $response;
	}
}
