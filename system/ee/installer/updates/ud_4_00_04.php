<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Updater\Version_4_0_4;

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
				'removeOrhpanedLayouts'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function removeOrhpanedLayouts()
	{
		$channel_ids = ee('Model')->get('Channel')
			->fields('channel_id')
			->all()
			->getIds();

		if ( ! empty($channel_ids))
		{
			ee('Model')->get('ChannelLayout')
				->filter('channel_id', 'NOT IN', $channel_ids)
				->delete();
		}
	}
}
// END CLASS

// EOF
