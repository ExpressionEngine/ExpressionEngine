<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

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
		$steps = new ProgressIterator(
			array(
				'_update_session_table',
				'_fix_emoticon_config',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Update Session Table
	 *
	 * Drops site_id field from sessions table
	 *
	 * @return 	void
	 */
	private function _update_session_table()
	{
		// Drop site_id
		ee()->smartforge->drop_column('sessions', 'site_id');
    }

	/**
	 * Replaces emoticon_path in your config file with emoticon_url
	 *
	 * @return 	void
	 */
	private function _fix_emoticon_config()
	{
		if ($emoticon_url = ee()->config->item('emoticon_path'))
		{
			ee()->config->_update_config(array('emoticon_url' => $emoticon_url), array('emoticon_path' => ''));
		}
	}
}
/* END CLASS */

// EOF
