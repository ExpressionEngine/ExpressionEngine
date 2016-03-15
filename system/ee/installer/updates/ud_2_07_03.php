<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.7.3
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
		ee()->load->dbforge();

		$steps = new ProgressIterator(
			array(
				'_update_email_db_columns',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Change email columns to varchar(75)
	 * @return void
	 */
	private function _update_email_db_columns()
	{
		$changes = array(
			'members' => 'email',
			'email_cache' => 'from_email',
			'email_console_cache' => 'recipient',
		);

		foreach ($changes as $table => $column)
		{
			ee()->smartforge->modify_column(
				$table,
				array(
					$column => array(
						'name' 			=> $column,
						'type' 			=> 'VARCHAR',
						'constraint' 	=> 75,
						'null' 			=> FALSE
					)
				)
			);
		}
	}
}
/* END CLASS */

// EOF
