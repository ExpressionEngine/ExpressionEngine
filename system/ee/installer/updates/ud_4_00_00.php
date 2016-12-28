<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 4.0
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
				'add_field_data_flag',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Adds a column to the exp_channel_fields table that indicates if the
	 * data is in the exp_channel_data table or its own table
	 */
	private function add_field_data_flag()
	{
		if ( ! ee()->db->field_exists('field_data_in_channel_data', 'channel_fields'))
		{
			ee()->smartforge->add_column(
				'channel_fields',
				array(
					'field_data_in_channel_data' => array(
						'type'    => 'CHAR(1)',
						'null'    => FALSE,
						'default' => 'n'
					)
				)
			);
		}
	}
}

// EOF
