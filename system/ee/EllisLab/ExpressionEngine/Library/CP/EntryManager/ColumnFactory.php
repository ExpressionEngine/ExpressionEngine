<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\CP\EntryManager;

/**
 * Entry Manager Column Factory
 */
class ColumnFactory
{
	private static $standard_columns = [
		'entry_id'        => Columns\EntryId::class,
		'title'           => Columns\Title::class,
		'url_title'       => Columns\UrlTitle::class,
		'author'          => Columns\Author::class,
		'status'          => Columns\Status::class,
		'entry_date'      => Columns\EntryDate::class,
		'expiration_date' => Columns\ExpirationDate::class,
		'channel_name'    => Columns\ChannelName::class,
		'checkbox'        => Columns\Checkbox::class
	];

	private static $instances = [];

	/**
	 * Returns an instance of a column given its identifier. This factory uses
	 * the Flyweight pattern to only keep a single instance of a column around
	 * as they don't really need to maintain state.
	 *
	 * @return Column
	 */
	public static function getColumn($identifier)
	{
		if (isset(self::$instances[$identifier]))
		{
			return self::$instances[$identifier];
		}

		if (isset(self::$standard_columns[$identifier]))
		{
			$class = self::$standard_columns[$identifier];
			self::$instances[$identifier] = new $class($identifier);
		}
		elseif (strpos($identifier, 'field_id_') === 0 && $field = self::getCompatibleField($identifier))
		{
			self::$instances[$identifier] = new Columns\CustomField($identifier, $field);
		}
		else
		{
			return NULL;
		}

		return self::$instances[$identifier];
	}

	/**
	 * Returns all available columns in the system, be it a system-standard
	 * column, a custom field, or a column provided by an extension
	 *
	 * @return array[Column]
	 */
	public static function getAvailableColumns()
	{
		return array_merge(
			self::getStandardColumns(),
			self::getChannelFieldColumns()
		);
	}

	/**
	 * Returns Column objects for all system-standard columns
	 *
	 * @return array[Column]
	 */
	private static function getStandardColumns()
	{
		return array_map(function($identifier, $column) {
			return self::getColumn($identifier);
		}, array_keys(self::$standard_columns), self::$standard_columns);
	}

	/**
	 * Returns Column objects for all custom field columns
	 *
	 * @return array[Column]
	 */
	private static function getChannelFieldColumns()
	{
		return ee('Model')->get('ChannelField')
			->all()
			->filter(function($field) {
				return in_array(
					$field->field_type,
					self::getCompatibleFieldtypes()
				);
			})
			->map(function($field) {
				return self::getColumn('field_id_'.$field->getId(), $field);
			});
	}

	/**
	 * Returns a ChannelField object given a field_id_x itenfifier
	 *
	 * @return ChannelField
	 */
	private static function getCompatibleField($identifier)
	{
		$field_id = str_replace('field_id_', '', $identifier);
		$field = ee('Model')->get('ChannelField', $field_id)->first();

		if ($field && in_array(
			$field->field_type,
			self::getCompatibleFieldtypes()
		)) {
			return $field;
		}

		return NULL;
	}

	/**
	 * Return list of fieldtypes that implement ColumnInterface
	 *
	 * @return array[string]
	 */
	private static function getCompatibleFieldtypes()
	{
		static $fieldtypes;

		if ($fieldtypes)
		{
			return $fieldtypes;
		}

		$fieldtypes = ee('Model')->get('Fieldtype')->all()->pluck('name');

		ee()->legacy_api->instantiate('channel_fields');

		$fieldtypes = array_filter($fieldtypes, function($fieldtype) {
			ee()->api_channel_fields->include_handler($fieldtype);
			return self::implementsInterace(self::getClassNameForFieldtype($fieldtype));
		});

		return $fieldtypes;
	}

	/**
	 * Returns whether or not a given class implements ColumnInterface
	 *
	 * @param string Full class name
	 * @return boolean
	 */
	private static function implementsInterace($class)
	{
		$interfaces = class_implements($class);

		return isset($interfaces[ColumnInterface::class]);
	}

	/**
	 * Returns class name for a given fieldtype
	 *
	 * @param string Fieldtype short name, i.e. checkboxes
	 * @return boolean
	 */
	private static function getClassNameForFieldtype($fieldtype)
	{
		return ucfirst($fieldtype) . '_ft';
	}
}
