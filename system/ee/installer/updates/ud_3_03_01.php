<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
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
				'use_site_default_localization_settings',
				'set_encryption_key'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Redo to allow our localized date and time settings to have NULL values
	 * in the db.
	 *
	 * @return void
	 */
	private function use_site_default_localization_settings()
	{
		// Allow NULL and make that the default
		ee()->smartforge->modify_column(
			'members',
			array(
				'timezone' => array(
					'name'       => 'timezone',
					'type'       => 'varchar',
					'constraint' => 50,
					'null'       => TRUE,
					'default'    => NULL
				),
				'date_format' => array(
					'name'       => 'date_format',
					'type'       => 'varchar',
					'constraint' => 8,
					'null'       => TRUE,
					'default'    => NULL
				),
				'time_format' => array(
					'name'       => 'time_format',
					'type'       => 'char',
					'constraint' => 2,
					'null'       => TRUE,
					'default'    => NULL
				),
				'include_seconds' => array(
					'name'       => 'include_seconds',
					'type'       => 'char',
					'constraint' => 1,
					'null'       => TRUE,
					'default'    => NULL
				)
			)
		);
	}

	/**
	 * Create a valid Encryption Key
	 */
	private function set_encryption_key()
	{
		$encryption_key = ee()->config->item('encryption_key');
		if (empty($encryption_key))
		{
			ee()->config->update_site_prefs(
				array('encryption_key' => ee('Encrypt')->generateKey()),
				'all'
			);
		}
	}


}

// EOF
