<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Channel;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Channel Field Group Model
 */
class ChannelFieldGroup extends Model {

	protected static $_primary_key 	= 'group_id';
	protected static $_table_name = 'field_groups';

	protected static $_hook_id = 'channel_field_group';

	protected static $_relationships = array(
		'Channels' => array(
			'weak' => TRUE,
			'type' => 'hasAndBelongsToMany',
			'model' => 'Channel',
			'pivot' => array(
				'table' => 'channels_channel_field_groups'
			),
		),
		'ChannelFields' => array(
			'weak' => TRUE,
			'type' => 'hasAndBelongsToMany',
			'model' => 'ChannelField',
			'pivot' => array(
				'table' => 'channel_field_groups_fields'
			)
		)
	);

	protected static $_validation_rules = array(
		'group_name' => 'required|unique|validateName'
	);

	protected static $_events = array(
		'afterUpdate',
	);

	protected $group_id;
	protected $site_id;
	protected $group_name;

	/**
	 * Convenience method to fix inflection
	 */
	public function createChannelField($data)
	{
		return $this->createChannelFields($data);
	}

	public function validateName($key, $value, $params, $rule)
	{
		if ( ! preg_match("#^[a-zA-Z0-9_\-/\s]+$#i", $value))
		{
			return 'illegal_characters';
		}

		return TRUE;
	}

	public function onAfterUpdate($previous)
	{
		foreach ($this->Channels as $channel)
		{
			foreach ($channel->ChannelLayouts as $layout)
			{
				$layout->synchronize();
			}
		}
	}

}

// EOF
