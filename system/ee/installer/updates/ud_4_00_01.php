<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Updater\Version_4_0_1;

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
				'resyncLayouts',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function resyncLayouts()
	{
		$layotus = ee('Model')->get('ChannelLayout')
			->with('Channel')
			->all()
			->synchronize();
	}

}
// END CLASS

// EOF
