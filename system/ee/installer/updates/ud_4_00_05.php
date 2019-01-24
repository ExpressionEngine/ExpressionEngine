<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_4_0_5;

/**
 * Update
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new \ProgressIterator(
			array(
				'fixGridModifierParsing'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Change value of Grid fields to be a string with a single space again to
	 * keep modifier parsing working
	 */
	private function fixGridModifierParsing()
	{
		$grid_fields = ee('Model')->get('ChannelField')
			->filter('field_type', 'grid')
			->all();

		foreach ($grid_fields as $field)
		{
			$column = 'field_id_'.$field->getId();

			ee()->db
				->where($column, '')
				->or_where($column, NULL)
				->update(
					$field->getDataStorageTable(),
					[$column => ' ']
				);
		}
	}
}
// END CLASS

// EOF
