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
