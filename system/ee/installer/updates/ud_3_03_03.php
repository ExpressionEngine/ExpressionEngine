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
		$steps = new ProgressIterator(
			array(
				'addFieldSettingsColumns',
				'update_category_fields',
				'alter_is_locked',
				'update_status_highlight'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * This column isn't needed until 3.5.1, but needs adding before we access
	 * the CategoryField models below
	 *
	 * @return void
	 */
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
	}

	/**
	 * Update category fields so their formatting is properly set
	 *
	 * @return void
	 */
	private function update_category_fields()
	{
		$category_fields = ee()->db->select('field_id', 'field_default_fmt')
			->get('category_fields')
			->result_array();

		foreach ($category_fields as $row)
		{
			ee()->db->update(
				'category_field_data',
				array('field_ft_'.$row['field_id'] => $row['field_default_fmt']),
				array('field_ft_'.$row['field_id'] => NULL)
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

	/**
	 * Update status highlight field to have a default
	 *
	 * @return void
	 */
	private function update_status_highlight()
	{
		// ALTER TABLE `exp_statuses` CHANGE COLUMN `highlight` `highlight` varchar(30) NOT NULL DEFAULT '000000';
		ee()->smartforge->modify_column(
			'statuses',
			array(
				'highlight' => array(
					'name'			=> 'highlight',
					'type'			=> 'varchar',
					'constraint'	=> 30,
					'default'		=> '000000',
					'null'			=> FALSE
				)
			)
		);

		// Update existing
		ee()->db->where('highlight', '')
			->update('statuses', array('highlight' => '000000'));
	}
}

// EOF
