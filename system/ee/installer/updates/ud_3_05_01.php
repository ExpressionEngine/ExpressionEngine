<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

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
				'addFieldSettingsColumns'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function addFieldSettingsColumns()
	{
		ee()->smartforge->add_column(
			'category_fields',
			array(
				'field_settings' => array(
					'type' => 'text',
					'null' => TRUE
				)
			)
		);

		ee()->smartforge->add_column(
			'member_fields',
			array(
				'm_field_settings' => array(
					'type' => 'text',
					'null' => TRUE
				)
			)
		);
	}
}

// EOF
