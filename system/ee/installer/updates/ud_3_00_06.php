<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_3_0_6;

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

		$steps = new \ProgressIterator(
			array(
				'_comment_formatting'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Increase the column for storing comment formatting
	 */
	private function _comment_formatting()
	{
		ee()->smartforge->modify_column(
			'channels',
			array(
				'comment_text_formatting' => array(
					'type'			=> 'char',
					'constraint'	=> 40,
					'null'			=> FALSE,
					'default'		=> 'xhtml',
				),
			)
		);

	}
}
/* END CLASS */

// EOF
