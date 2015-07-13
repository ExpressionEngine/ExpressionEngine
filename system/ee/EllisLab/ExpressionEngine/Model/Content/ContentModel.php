<?php

namespace EllisLab\ExpressionEngine\Model\Content;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\VariableColumnModel;
use EllisLab\ExpressionEngine\Model\Content\Display\DefaultLayout;
use EllisLab\ExpressionEngine\Model\Content\Display\FieldDisplay;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutInterface;

/**
 * create: new ChannelEntry()->getForm();
 * existing: $entry->fill($data)->getForm();
 * set data: $entry->title = "Foo"; $entry->getForm();
 * mass set: $entry->set(array); $entry->getForm();
 */

abstract class ContentModel extends VariableColumnModel {

	protected static $_events = array(
		'beforeValidate',
		'afterSave'
	);

	protected $_field_facades;
	protected $_field_was_saved;

	/**
	 * Define a way to get the parent structure. For example,
	 * in a channel entry, this would return the parent channel.
	 */
	abstract public function getStructure();

	/**
	 * Get the prefix for custom fields. Typically custom fields are
	 * stored as 'field_id_#' where # is the field id.
	 *
	 * @return String Custom field column prefix
	 */
	public function getCustomFieldPrefix()
	{
		return 'field_id_';
	}

	/**
	 * Optionally return an array of default fields.
	 *
	 * @return Array of field definitions
	 */
	protected function getDefaultFields()
	{
		return array();
	}

	/**
	 * Check if a custom field of $name exists
	 */
	public function hasCustomField($name)
	{
		if (strpos($name, $this->getCustomFieldPrefix()) !== 0)
		{
			$default_fields = $this->getDefaultFields();

			if ( ! isset($default_fields[$name]))
			{
				return FALSE;
			}
		}

		$this->usesCustomFields();

		if ( ! isset($this->_field_facades))
		{
			return FALSE;
		}

		return array_key_exists($name, $this->_field_facades);
	}

	/**
	 * Get a custom field facade
	 */
	public function getCustomField($name)
	{
		return $this->_field_facades[$name];
	}

	/**
	 * Get a list of all custom field facades
	 */
	public function getCustomFields()
	{
		return $this->_field_facades;
	}

	/**
	* Get a list of all custom field names
	*/
	public function getCustomFieldNames()
	{
		return array_keys($this->_field_facades);
	}

	public function onAfterSave()
	{
		foreach ($this->_field_was_saved as $field)
		{
			$field->setContentId($this->getId());
			$field->postSave();
		}
	}

	/**
	 * Make sure that calls to fill() also apply to custom fields
	 */
	public function fill(array $data = array())
	{
		parent::fill($data);

		$this->fillCustomFields($data);

		return $this;
	}

	/**
	 * Small change to custom fields to make sure the save() method
	 * gets fired.
	 */
	public function save()
	{
		$this->_field_was_saved = array();

		foreach ($this->getCustomFields() as $name => $field)
		{
			if ($this->isDirty($name))
			{
				$this->setRawProperty($name, $field->save($this));
				$this->_field_was_saved[] = $field;
			}
		}

		return parent::save();
	}

	/**
	 * Make sure set() calls have access to custom fields
	 */
	public function set(array $data = array())
	{
		$this->usesCustomFields();
		return parent::set($data);
	}

	/**
	 * Custom fields count as a valid property
	 */
	public function hasProperty($name)
	{
		if ( ! parent::hasProperty($name))
		{
			return $this->hasCustomField($name) || (strpos($name, 'field_ft_') == 0);
		}

		return TRUE;
	}

	/**
	 * Getting a property needs to check custom fields
	 */
	public function getProperty($name)
	{
		if ( ! parent::hasProperty($name) && $this->hasCustomField($name))
		{
			return $this->getCustomField($name)->getData();
		}

		return parent::getProperty($name);
	}

	/**
	 * Setting a property needs to apply to custom fields
	 */
	public function setProperty($name, $new_value)
	{
		if ($this->hasCustomField($name))
		{
			$this->emit('beforeSetCustomField', $name, $new_value);

			$field = $this->getCustomField($name);
			$value = $field->getData(); // old value

			$this->backupIfChanging($name, $value, $new_value);

			$field->setData($new_value);

			$this->emit('afterSetCustomField', $name, $new_value);

			if ( ! parent::hasProperty($name))
			{
				return $this;
			}

			$new_value = $field->getData();
		}

		return parent::setProperty($name, $new_value);
	}

	/**
	 * Support method for the model validation mixin
	 */
	public function getValidationRules()
	{
		$this->usesCustomFields();

		$rules = parent::getValidationRules();

		$facades = $this->getCustomFields();

		foreach ($facades as $name => $facade)
		{
			$rules[$name] = '';

	//		if ($facade->isRequired())
	//		{
	//			$rules[$name] .= 'required|';
	//		}

			$rules[$name] .= "validateCustomField";
		}

		return $rules;
	}

	public function onBeforeValidate()
	{
		/*
		foreach ($this->getCustomFields() as $name => $field)
		{
			if ($this->isDirty($name))
			{
				$this->setRawProperty($name, $field->save($this));
			}
		}
		*/
	}

	/**
	 * Callback to validate custom fields
	 */
	public function validateCustomField($key, $value, $params, $rule)
	{
		$result = $this->getCustomField($key)->validate($value);
		//
		// if ($result === 'required')
		// {
		// 	$rule->stop();
		// }

		return $result;
	}


	/**
	 * Get a list of fields
	 *
	 * @return array field names
	 */
	public function getFields()
	{
		$fields = parent::getFields();

		foreach ($this->getCustomFields() as $field_facade)
		{
			$fields[] = $field_facade->getName();
		}

		return $fields;
	}

	/**
	 * Get the layout for this content.
	 */
	public function getDisplay(LayoutInterface $layout = NULL)
	{
		$this->usesCustomFields();

		$fields = array_map(
			function($field) { return new FieldDisplay($field); },
			$this->getCustomFields()
		);

		$layout = $layout ?: new DefaultLayout();

		return $layout->transform($fields);
	}

	/**
	 * Ensures that custom fields are setup and their data is in sync.
	 */
	protected function usesCustomFields()
	{
		if ( ! isset($this->_field_facades))
		{
			$this->initializeCustomFields();
		}
	}

	/**
	 * Populate the custom fields on fill()
	 */
	protected function fillCustomFields(array $data = array())
	{
		foreach ($data as $name => $value)
		{
			if (strpos($name, 'field_ft_') === 0)
			{
				$name = str_replace('field_ft_', 'field_id_', $name);

				if ($this->hasCustomField($name))
				{
					$this->getCustomField($name)->setFormat($value);
				}

				continue;
			}

			if ($this->hasCustomField($name))
			{
				$this->getCustomField($name)->setData($value);
			}
		}
	}

	/**
	 * Get the party started, grab the structure and setup everything.
	 */
	protected function initializeCustomFields()
	{
		$this->_field_facades = array();

		$default_fields = $this->getDefaultFields();

		foreach ($default_fields as $id => $field)
		{
			$this->addFacade($id, $field);
		}

		$native_fields = $this->getStructure()->getCustomFields();
		$native_prefix = $this->getCustomFieldPrefix();

		foreach ($native_fields as $field)
        {
            $settings = array_merge($field->getSettingsValues(), $field->toArray());

            $this->addFacade(
                $field->getId(),
                $settings,
                $native_prefix
            );
        }
	}

	/**
	 * Turn a field into a facade for more consistent access.
	 */
	protected function addFacade($id, $info, $name_prefix = '')
	{
		$name = $name_prefix.$id;

		$facade = new FieldFacade($id, $info);
		$facade->setName($name);
		$facade->setContentId($this->getId());
		$facade->setContentType($this->getStructure()->getContentType());

		$this->_field_facades[$name] = $facade;
	}

}
