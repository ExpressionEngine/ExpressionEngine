<?php

namespace EllisLab\ExpressionEngine\Model\Content;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;

abstract class FieldModel extends Model {

	protected static $_events = array(
		'afterInsert',
		'afterUpdate',
		'afterDelete'
	);


	protected $_field_facade;

	/**
	 * Return the storing table
	 */
	abstract public function getDataTable();

	/**
	 *
	 */
	abstract public function getStructure();

	/**
	 *
	 */
	public function getField($override = array())
	{
		$field_type = $this->getFieldType();

		if (empty($field_type))
		{
			throw new \Exception('Cannot get field of unknown type.');
		}

		if ( ! isset($this->_field_facade) ||
			$this->_field_facade->getType() != $this->getFieldType() ||
			$this->_field_facade->getId() != $this->getId())
		{
			$values = array_merge($this->getValues(), $override);

			$this->_field_facade = new FieldFacade($this->getId(), $values);
			$this->_field_facade->setContentType($this->getContentType());
		}

		if (isset($this->field_fmt))
		{
			$this->_field_facade->setFormat($this->field_fmt);
		}

		return $this->_field_facade;
	}

	public function getSettingsForm()
	{
		return $this->getField($this->getSettingsValues())->getSettingsForm();
	}

	public function getSettingsValues()
	{
		return $this->getValues();
	}

	protected function getContentType()
	{
		return $this->getStructure()->getContentType();
	}

	public function set(array $data = array())
	{
		// getField() requires that we have a field type, but we might be trying
		// to set it! So, if we are, we'll do that first.
		if (isset($data['field_type']))
		{
			$this->setProperty('field_type', $data['field_type']);
		}

		$field = $this->getField($this->getSettingsValues());
		$data = array_merge($field->saveSettingsForm($data), $data);

		return parent::set($data);
	}

	public function validate()
	{
		$result = parent::validate();

		$settings = $this->getSettingsValues();

		if (isset($settings['field_settings']))
		{
			$field = $this->getField($this->getSettingsValues());
			$settings_result = $field->validateSettingsForm($settings['field_settings']);

			if ($settings_result instanceOf ValidationResult && $settings_result->failed())
			{
				foreach ($settings_result->getFailed() as $name => $rules)
				{
					foreach ($rules as $rule)
					{
						$result->addFailed($name, $rule);
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Calling the Post Save Settings after every save. Grid (and others?)
	 * saves its settings in the post_save_settings call.
	 */
	public function save()
	{
		parent::save();
		$this->callPostSaveSettings();
	}

	/**
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
		$old_type = (isset($changed['field_type'])) ? $changed['field_type'] : $this->field_type;
		$old_action = (isset($changed['field_type'])) ? 'delete' : 'get_info';

		$old_ft = $this->getFieldtypeInstance($old_type, $changed);
		$old_columns = $this->callSettingsModify($old_ft, $old_action, $changed);

		$new_ft = $this->getFieldtypeInstance();
		$new_columns = $this->callSettingsModify($new_ft, 'get_info');

		if ( ! empty($old_columns) || ! empty($new_columns))
		{
			$this->diffColumns($old_columns, $new_columns);
		}
	}

	protected function callSettingsModify($ft, $action, $changed = array())
	{
		$data = $this->getValues();
		$data = array_merge($data, $changed);

		if ( ! isset($data['field_settings']))
		{
			$data['field_settings'] = array();
		}

		$data['ee_action'] = $action;

		return $ft->settings_modify_column($data);
	}

	/**
	 * Calls post_save_settings on the fieldtype
	 */
	protected function callPostSaveSettings()
	{
		$data = $this->getValues();
		$field = $this->getField($this->getSettingsValues());
		$field->postSaveSettings($data);
	}

	/**
	 * Get the instance of the current fieldtype
	 */
	protected function getFieldtypeInstance($field_type = NULL, $changed = array())
	{
		$field_type = $field_type ?: $this->getFieldType();
		$values = array_merge($this->getValues(), $changed);

		$facade = new FieldFacade($this->getId(), $values);
		$facade->setContentType($this->getContentType());
		return $facade->getNativeField();
	}

	/**
	 * Simple getter for field type, override if your field type property has a
	 * different name.
	 *
	 * @access protected
	 * @return string The field type.
	 */
	protected function getFieldType()
	{
		return $this->field_type;
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
		$id_field_name = $this->getColumnPrefix().'field_id_'.$this->getId();
		$ft_field_name = $this->getColumnPrefix().'field_ft_'.$this->getId();

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

	/**
	 * Set a prefix on the default columns we manage for fields
	 *
	 * @return	String	Prefix string to use
	 */
	public function getColumnPrefix()
	{
		return '';
	}
}

// EOF
