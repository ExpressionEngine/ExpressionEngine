<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Controller\Publish;

use EllisLab\ExpressionEngine\Controller\Publish\AbstractPublish as AbstractPublishController;
use EllisLab\ExpressionEngine\Service\Model\Collection;

/**
 * Quick Bulk Edit Controller
 */
class QuickEdit extends AbstractPublishController {

	protected $standard_default_fields = [
		'status',
		'expiration_date',
		'comment_expiration_date',
		'sticky',
		'allow_comments',
		'author_id'
		// Plus common category groups added dynamically below
	];

	public function index()
	{
		$entries = ee('Model')->get('ChannelEntry', ee('Request')->get('entryIds'))->all();

		// TODO: Filter entries based on permissions, just in case
		$fields = $this->getFieldsForEntries($entries);
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
