<?php

namespace EllisLab\ExpressionEngine\Model\Content;

use EllisLab\ExpressionEngine\Service\Model\Model;

abstract class FieldModel extends Model {

	protected $field_type;

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
	 *
	 */
	abstract public function getStructure();

	 * After inserting, add the columns to the data table
	 */
	public function onAfterInsert()
	{
		$ft = $this->getFieldtypeInstance();

		$data = $this->getValues();
		$data['ee_action'] = 'add';

		$columns = $ft->settings_modify_column($data);
		$columns = $this->ensureDefaultColumns($columns);

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
		$columns = $this->ensureDefaultColumns($columns);

		$this->dropColumns($columns);
	}

	/**
	 * If the update changes the field_type, we need to sync the columns
	 * on the data table
	 */
	public function onAfterUpdate($changed)
	{
		if (isset($changed['field_type']))
		{
			$old_ft = $this->getFieldtypeInstance($changed['field_type'], $changed);
			$old_columns = $this->callSettingsModify($old_ft, 'delete', $changed);

			$new_ft = $this->getFieldtypeInstance();
			$new_columns = $this->callSettingsModify($new_ft, 'get_info');

			$this->diffColumns($old_columns, $new_columns);
		}
	}

	protected function callSettingsModify($ft, $action, $changed = array())
	{
		$data = $this->getValues();
		$data = array_merge($data, $changed);

		if ( ! isset($data['field_settings']))
		{
			$data['field_settings'] = base64_encode(serialize(array()));
		}

		$data['ee_action'] = $action;

		return $ft->settings_modify_column($data);
	}

	/**
	 * Get the instance of the current fieldtype
	 */
	protected function getFieldtypeInstance($field_type = NULL, $changed = array())
	{
		$field_type = $field_type ?: $this->field_type;
		$values = array_merge($this->getValues(), $changed);

		$facade = new FieldFacade($this->getId(), $values);
		return $facade->getNativeField();
	}

	/**
	 *
	 */
	private function diffColumns($old, $new)
	{
		$old = $this->ensureDefaultColumns($old);
		$new = $this->ensureDefaultColumns($new);

		$drop = array();
		$change = array();

		foreach ($old as $name => $prefs)
		{
			if ( ! isset($new[$name]))
			{
				$drop[$name] = $old[$name];
			}
			elseif ($prefs != $new[$name])
			{
				$change[$name] = $new[$name];
				unset($new[$name]);
			}
			else
			{
				unset($new[$name]);
			}
		}

		$this->dropColumns($drop);
		$this->modifyColumns($change);
		$this->createColumns($new);
	}

	/**
	 * Create columns, add the defaults if they don't exist
	 *
	 * @param Array $columns List of [column name => column definition]
	 */
	private function createColumns($columns)
	{
		if (empty($columns))
		{
			return;
		}

		$data_table = $this->getDataTable();

		ee()->load->dbforge();
		ee()->dbforge->add_column($data_table, $columns);
	}

	/**
	 * Modify columns that were changed
	 *
	 * @param Array $columns List of [column name => column definition]
	 */
	private function modifyColumns($columns)
	{
		if (empty($columns))
		{
			return;
		}

		$data_table = $this->getDataTable();

		foreach ($columns as $name => &$column)
		{
			if ( ! isset($column['name']))
			{
				$column['name'] = $name;
			}
		}

		ee()->load->dbforge();
		ee()->dbforge->modify_column($data_table, $columns);
	}

	/**
	 * Drop columns, including the defaults
	 *
	 * @param Array $columns List of column definitions as in createColumns, but
	 *						 only the keys are actually used
	 */
	private function dropColumns($columns)
	{
		if (empty($columns))
		{
			return;
		}

		$columns = $this->ensureDefaultColumns($columns);
		$columns = array_keys($columns);

		$data_table = $this->getDataTable();

		ee()->load->dbforge();
		ee()->dbforge->drop_column($columns, $column);
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