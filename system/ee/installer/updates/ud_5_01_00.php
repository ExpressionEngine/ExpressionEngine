<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_5_1_0;

/**
 * Update
 */
class Updater {

	var $version_suffix = '';

	public $affected_tables = ['fieldtypes'];

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new \ProgressIterator(
			[
				'installFileGrid',
			]
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function installFileGrid()
	{
		$installed = ee('Model')->get('Fieldtype')
			->filter('name', 'file_grid')
			->first();

		if ( ! $installed)
		{
			ee('Model')->make('Fieldtype', [
				'name'                => 'file_grid',
				'version'             => '1.0',
				'settings'            => [],
				'has_global_settings' => 'n',
			])->save();
		}
	}
}

// EOF
