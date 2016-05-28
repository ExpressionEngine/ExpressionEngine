<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.3.3
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
				'update_category_fields'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Update category fields so their formatting is properly set
	 *
	 * @return void
	 */
	private function update_category_fields()
	{
		$category_fields = ee('Model')->get('CategoryField')
			->all()
			->indexBy('field_id');

		foreach ($category_fields as $id => $field)
		{
			ee()->db->update(
				'category_field_data',
				array('field_ft_'.$id => $field->field_default_fmt),
				array('field_ft_'.$id => NULL)
			);
		}
	}
}

// EOF
