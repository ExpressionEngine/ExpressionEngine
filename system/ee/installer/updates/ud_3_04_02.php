<?php

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
		$steps = new ProgressIterator(
			array(
				'add_enable_devlog_alerts',
				'fix_file_dimension_site_ids'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function add_enable_devlog_alerts()
	{
		ee()->config->update_site_prefs(
			array('enable_devlog_alerts' => 'n'),
			'all'
		);
	}

	/**
	 * File dimensions were previously being saved with a site ID of 1 regardless
	 * of actual site saved on, this corrects previously-saved file dimensions
	 */
	private function fix_file_dimension_site_ids()
	{
		$upload_destinations = ee('Model')->get('UploadDestination')->all();

		foreach ($upload_destinations as $upload)
		{
			foreach ($upload->FileDimensions as $size)
			{
				if ($size->site_id != $upload->site_id)
				{
					$size->site_id = $upload->site_id;
					$size->save();
				}
			}
		}
	}
}

// EOF
