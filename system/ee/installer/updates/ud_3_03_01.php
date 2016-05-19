<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.3.1
 * @filesource
 */

// ------------------------------------------------------------------------

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
				array('encryption_key' => sha1(uniqid(mt_rand(), TRUE))),
				'all'
			);
		}
	}


}

// EOF
