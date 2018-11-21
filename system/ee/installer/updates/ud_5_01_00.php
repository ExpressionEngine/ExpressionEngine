<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_5_1_0;

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
			[
				'installGridImages',
			]
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function installGridImages()
	{
		$installed = ee('Model')->get('Fieldtype')
			->filter('name', 'grid_images')
			->first();

		if ( ! $installed)
		{
			ee('Model')->make('Fieldtype', [
				'name'                => 'grid_images',
				'version'             => '1.0',
				'settings'            => [],
				'has_global_settings' => 'n',
			])->save();
		}
	}
}

// EOF
