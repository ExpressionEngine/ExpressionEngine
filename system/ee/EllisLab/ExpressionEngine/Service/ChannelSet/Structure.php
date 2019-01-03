<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\ChannelSet;

/**
 * Channel Set Service
 * Helper class for all the crazy mixing and matching we have to do.
 */
class Structure {

	public static $hierarchy = array(
		'ee:Channel' => array(),
		'ee:ChannelFieldGroup' => array('ChannelFields' => 'ee:ChannelField'),
		'ee:CategoryGroup' => array('Categories' => 'ee:Category'),
		'ee:UploadDestination' => array(),
	);

	/**
	 * Todo: use lang keys here
	 */
	public static $human_model_names = array(
		'ee:Channel' => 'Channel',
		'ee:ChannelFieldGroup' => 'Channel Field Group',
		'ee:ChannelField' => 'Channel Field',
		'ee:CategoryGroup' => 'Category Group',
		'ee:Category' => 'Category',
		'ee:StatusGroup' => 'Status Group',
		'ee:Status' => 'Status',
		'ee:UploadDestination' => 'Upload Destination'
	);

	public static $title_fields = array(
		'ee:Channel' => 'channel_title',
		'ee:ChannelFieldGroup' => 'group_name',
		'ee:CategoryGroup' => 'group_name',
		'ee:ChannelField' => 'field_label',
		'ee:UploadDestination' => 'name'
	);

	public static $identity_fields = array(
		'ee:Channel' => 'channel_name',
		'ee:ChannelFieldGroup' => 'group_name',
		'ee:CategoryGroup' => 'group_name',
		'ee:ChannelField' => 'field_name',
		'ee:UploadDestination' => 'name'
	);

	public static $short_names = array(
		'ee:Channel' => array('channel_name' => 'channel_title'),
		'ee:ChannelField' => array('field_name' => 'field_label')
	);

	/**
	 * Get the most human descriptive name for a model, e.g. "Channel"
	 *
	 * Used in the error headings.
	 *
	 * @param Model $model The model instance
	 * @return String The nice name
	 */
	public static function getHumanName($model)
	{
		return static::$human_model_names[$model->getName()];
	}

	/**
	 * Get the relationships we need to walk to validate everything we've created.
	 *
	 * Save does this automatically, but with validate you would end up with
	 * weird nested arrays that are hard to process so we do it manually.
	 *
	 * @param Model $model The model instance to start from
	 * @return array List of relationship names to process next
	 */
	public static function getValidateRelationships($model)
	{
		$name = $model->getName();

		if (array_key_exists($name, static::$hierarchy))
		{
			return array_keys(static::$hierarchy[$name]);
		}

		return array();
	}

	/**
	 * Get the main title field for a model.
	 *
	 * @param Model $model The model instance
	 * @return String Title field
	 */
	public static function getTitleFieldFor($model)
	{
		$name = $model->getName();

		if (array_key_exists($name, static::$title_fields))
		{
			return static::$title_fields[$name];
		}

		throw new ImportException('Cannot find title field for '.$name);
		return NULL;
	}

	/**
	 * Get the uniquely identifying field for a model. This will be the
	 * field that is listed in the channel_set.json file. Or, in the case
	 * of field(groups) it's the filename without extension.
	 *
	 * @param Model $model The model instance
	 * @return String Name of identifying field
	 */
	public static function getIdentityFieldFor($model)
	{
		$name = $model->getName();

		if (array_key_exists($name, static::$identity_fields))
		{
			return static::$identity_fields[$name];
		}

		throw new ImportException('Cannot find title field for '.$name);
		return NULL;
	}

	/**
	 * For a given short name, grab the field that it's related to.
	 *
	 * For example, for `(channel, url_title)` this would return `title`
	 *
	 * @param Model $model The model instance
	 * @param String $field The potential shortname
	 * @return String Title field
	 */
	public static function getLongFieldIfShortened($model, $field)
	{
		$name = $model->getName();

		if (array_key_exists($name, static::$short_names))
		{
			if (array_key_exists($field, static::$short_names[$name]))
			{
				return static::$short_names[$name][$field];
			}
		}

		return NULL;
	}
}
