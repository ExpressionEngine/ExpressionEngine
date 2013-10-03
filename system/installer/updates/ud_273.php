<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
		ee()->smartforge->modify_column(
			'members',
			array(
				'email' => array(
					'name' 			=> 'email',
					'type' 			=> 'VARCHAR',
					'constraint' 	=> 75,
					'null' 			=> FALSE
				)
			)
		);

		ee()->smartforge->modify_column(
			'email_cache',
			array(
				'from_email' => array(
					'name' 			=> 'from_email',
					'type' 			=> 'VARCHAR',
					'constraint' 	=> 75,
					'null' 			=> FALSE
				)
			)
		);

		ee()->smartforge->modify_column(
			'email_console_cache',
			array(
				'recipient' => array(
					'name' 			=> 'recipient',
					'type' 			=> 'VARCHAR',
					'constraint' 	=> 75,
					'null' 			=> FALSE
				)
			)
		);
	}
}
/* END CLASS */

/* End of file ud_273.php */
/* Location: ./system/expressionengine/installer/updates/ud_273.php */