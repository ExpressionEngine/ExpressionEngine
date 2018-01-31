<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Controller\Publish\QuickEdit;

use CP_Controller;

/**
 * Abstract Publish Controller
 */
abstract class AbstractQuickEdit extends CP_Controller {

	public function __construct()
	{
		parent::__construct();

		ee()->lang->loadfile('content');

		$this->assigned_channel_ids = array_keys(ee()->session->userdata('assigned_channels'));
	}

	/**
	 * Given a Collection of entries to edit, returns the FieldFacades for the
	 * fields enabled to quick-edit
	 *
	 * @return Array
	 */
	protected function getFieldsForEntries($entries)
	{
		$entry = ee('Model')->make('ChannelEntry');
		$entry->Channel = $this->getIntersectedChannel($entries->Channel);

		// Append common category groups to default field set
		$fields = $this->standard_default_fields;
		foreach ($entry->Channel->CategoryGroups->getIds() as $cat_group)
		{
			$fields[] = 'categories[cat_group_id_'.$cat_group.']';
		}

		$field_facades = [];
		foreach ($fields as $field)
		{
			$field_facades[$field] = $entry->getCustomField($field);
		}

		return $field_facades;
	}

	/**
	 * Given a Collection of channels, returns a channel object with traits each
	 * channel has in common, currently category groups and statuses
	 *
	 * @return Channel
	 */
	protected function getIntersectedChannel($channels)
	{
		$channels = $channels->intersect();

		// All entries belong to the same channel, easy peasy!
		if ($channels->count() < 2)
		{
			return $channels->first();
		}

		$channel = ee('Model')->make('Channel');
		$channel->cat_group = implode(
			'|',
			$channels->CategoryGroups->intersect()->getIds()
		);
		$channel->Statuses = $channels->Statuses->intersect();

		return $channel;
	}

}

// EOF
