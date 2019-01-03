<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_3_5_5;

/**
 * ExpressionEngine Update Class
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
				'normalizeFieldLayoutData',
				'addSessionCryptKey'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Normalize fields array in layout. Re bug #22894, if a category group was
	 * unassigned from a channel, the resulting channel layout's tab's fields
	 * array may end up as an associative array because we were simply unsetting
	 * the category field's index and thus making the indicies inconsistent.
	 * This in turn would make json_encode() treat the array as an object and
	 * the supporting layout JS spew errors. We've fixed the source of the
	 * inconsistency, here we'll normalize the data.
	 */
	private function normalizeFieldLayoutData()
	{
		$layouts = ee('Model')->get('ChannelLayout')->all();

		foreach ($layouts as $layout)
		{
			$field_layout = $layout->field_layout;

			foreach ($field_layout as &$section)
			{
				if ( ! isset($section['fields']))
				{
					continue 2;
				}

				$section['fields'] = array_values($section['fields']);
			}

			$layout->field_layout = $field_layout;
			$layout->save();
		}
	}

	/**
	 * Adds `session_crypt_key` config item to define the key to be used for
	 * session-related encryption such as cookie integrity and hidden form
	 * inputs
	 */
	private function addSessionCryptKey()
	{
		$session_crypt_key = ee()->config->item('session_crypt_key');
		if (empty($session_crypt_key))
		{
			ee()->config->update_site_prefs(
				array('session_crypt_key' => ee('Encrypt')->generateKey()),
				'all'
			);
		}
	}
}

// EOF
