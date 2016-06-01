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
				'update_category_fields',
				'alter_is_locked'
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

	/**
	 * Update is_locked in exp_member_groups to default to unlocked
	 *
	 * @return void
	 */
	private function alter_is_locked()
	{
		// ALTER TABLE `exp_member_groups` CHANGE COLUMN `is_locked` `is_locked` char(1) NOT NULL DEFAULT 'n';
		ee()->smartforge->modify_column(
			'member_groups',
			array(
				'is_locked' => array(
					'name'			=> 'is_locked',
					'type'			=> 'char',
					'constraint'	=> 1,
					'default'		=> 'n',
					'null'			=> FALSE
				)
			)
		);
	}
}

// EOF
