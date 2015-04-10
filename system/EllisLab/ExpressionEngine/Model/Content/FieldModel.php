<?php

namespace EllisLab\ExpressionEngine\Model\Content;

use EllisLab\ExpressionEngine\Service\Model\Model;

abstract class FieldModel extends Model {

	protected static $_events = array(
		'afterInsert',
		'afterUpdate',
		'afterDelete'
	);

	/**
	 * Return the storing table
	 */
	abstract public function getDataTable();

	/**
	 * After inserting, add the columns to the data table
	 */
	public function onAfterInsert()
	{
		$ft = $this->getFieldtypeInstance();

		$data = $this->getValues();
		$data['ee_action'] = 'add';

		$columns = $ft->settings_modify_column($data);

		$this->createColumns($columns);
	}

	/**
	 * After deleting, drop the columns
	 */
	public function onAfterDelete()
	{
		$ft = $this->getFieldtypeInstance();

		$data = $this->getValues();
		$data['ee_action'] = 'delete';

		$columns = $ft->settings_modify_column($data);

		$this->dropColumns($columns);
	}

	/**
	 * After updating, sync the columns on the data table
	 */
	public function onAfterUpdate()
	{
		// diff columns
		// add any that don't exist
		// remove any that shouldn't exist
		// alter any that changed
	}

	/**
	 * Get the instance of the current fieldtype
	 */
	protected function getFieldtypeInstance()
	{
		ee()->legacy_api->instantiate('channel_fields');

		$api = ee()->api_channel_fields;
		$api->fetch_all_fieldtypes();

		return $api->setup_handler($this->field_type, TRUE);
	}

	/**
	 * Create columns, add the defaults if they don't exist
	 *
	 * @param Array $columns List of [column name => column definition]
	 */
	private function createColumns($columns)
	{
		$columns = $this->ensureDefaultColumns($columns);

		$data_table = $this->getDataTable();
		ee()->load->dbforge();

		foreach ($columns as $name => $prefs)
		{
			ee()->dbforge->add_column($data_table, array($name => $prefs));
		}
	}

	/**
	 * Drop columns, including the defaults
	 *
	 * @param Array $columns List of column definitions as in createColumns, but
	 *						 only the keys are actually used
	 */
	private function dropColumns($columns)
	{
		$columns = $this->ensureDefaultColumns($columns);
		$columns = array_keys($columns);

		$data_table = $this->getDataTable();

		ee()->load->dbforge();

		foreach ($columns as $column)
		{
			ee()->dbforge->drop_column($data_table, $column);
		}
	}

	/**
	 * Add the default columns if they don't exist
	 *
	 * @param Array $columns Column definitions
	 * @return Array Updated column definitions
	 */
	private function ensureDefaultColumns($columns)
	{
		$id_field_name = 'field_id_'.$this->getId();
		$ft_field_name = 'field_ft_'.$this->getId();

		if ( ! isset($columns[$id_field_name]))
		{
			$columns[$id_field_name] = array(
				'type' => 'text',
				'null' => TRUE
			);
		}

		if ( ! isset($columns[$ft_field_name]))
		{
			$columns[$ft_field_name] = array(
				'type' => 'tinytext',
				'null' => TRUE
			);
		}

		return $columns;
	}
}