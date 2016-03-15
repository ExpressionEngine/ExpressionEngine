<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.5
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
 * @link		https://ellislab.com
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
				'_update_ip_address_length',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	private function _update_ip_address_length()
	{
		ee()->load->dbforge();

		$tables = array('sessions', 'throttle', 'online_users',
			'security_hashes', 'captcha', 'password_lockout',
			'email_console_cache', 'members', 'channel_titles',
			'channel_entries_autosave', 'cp_log', 'member_search',
			'remember_me');

		foreach ($tables as $table)
		{
			$column_settings = array(
				'ip_address' => array(
					'name' 			=> 'ip_address',
					'type' 			=> 'varchar',
					'constraint' 	=> 45,
					'default'		=> '0',
					'null'			=> FALSE
				)
			);

			if ($table == 'remember_me')
			{
				unset($column_settings['ip_address']['null']);
			}

			ee()->smartforge->modify_column($table, $column_settings);
		}
	}
}
/* END CLASS */

// EOF
