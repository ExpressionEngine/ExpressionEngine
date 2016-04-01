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
		'afterSave',
		'beforeDelete'
	);

	protected $_field_facades;
	protected $_field_was_saved = array();
	protected $_custom_fields_loaded = FALSE;

	/**
	 * A link back to the owning Structure object.
	 *
	 * @return	Structure	A link back to the Structure object that defines
	 *						this Content's structure.
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
	 * Make sure we update our custom fields when save is triggered
	 */
	public function onAfterSave()
	{
		foreach ($this->_field_was_saved as $field)
		{
			$field->setContentId($this->getId());
			$field->postSave();
		}

		$this->_field_was_saved = array();
	}

	/**
	 * Cascade the delete to the fieldtypes
	 */
	 public function onBeforeDelete()
	 {
		 foreach ($this->getCustomFields() as $field)
		 {
			 $field->delete();
		 }
	 }

	/**
	 * Check if a custom field of $name exists
	 */
	public function hasCustomField($name)
	{
		if (strpos($name, $this->getCustomFieldPrefix()) !== 0)
		{
			$default_fields = $this->getDefaultFields();

			return array_key_exists($name, $default_fields);
		}

		$this->usesCustomFields();

		if ( ! isset($this->_field_facades))
		{
			return FALSE;
		}

		return array_key_exists($name, $this->_field_facades);
	}

	/**
	 * Get a list of all custom field facades
	 */
	public function getCustomFields()
	{
		$this->usesCustomFields();

		return $this->_field_facades ?: array();
	}

	/**
	 * Get a custom field facade
	 */
	public function getCustomField($name)
	{
		$this->usesCustomFields();

		return $this->_field_facades[$name];
	}

	/**
	* Get a list of all custom field names
	*/
	public function getCustomFieldNames()
	{
		$this->usesCustomFields();

		return array_keys($this->_field_facades);
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
	 * Batch update properties
	 *
	 * Safely updates any properties that might exist,
	 * passing them through the getters along the way.
	 *
	 * @param array $data Data to update
	 * @return $this
	 */
	public function set(array $data = array())
	{
		$this->usesCustomFields();
		return parent::set($data);
	}

	/**
	 * Make sure that calls to fill() also apply to custom fields
	 */
	public function fill(array $data = array())
	{
		parent::fill($data);

		if ($this->_custom_fields_loaded)
		{
			$this->fillCustomFields($data);
		}

		return $this;
	}

	/**
	 * Small change to custom fields to make sure the save() method
	 * gets fired.
	 */
	public function save()
	{
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
	 * Setting a property needs to apply to custom fields
	 */
	public function setProperty($name, $new_value)
	{
		if ($this->_custom_fields_loaded && $this->hasCustomField($name))
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
			if ( ! $this->isNew() && ! $this->isDirty($name))
			{
				continue;
			}

			if ( ! isset($rules[$name]))
			{
				$rules[$name] = '';
			}
			else
			{
				$rules[$name] .= '|';
			}

			if ($facade->isRequired())
			{
				$rules[$name] .= 'required|';
			}

			$rules[$name] .= "validateCustomField";
		}

		return $rules;
	}

	/**
	 * Callback to validate custom fields
	 */
	public function validateCustomField($key, $value, $params, $rule)
	{
		$field = $this->getCustomField($key);
		$result = $this->getCustomField($key)->validate($value);

		$this->setRawProperty($key, $field->getData());

		return $result;
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
		$this->setDataOnCustomFields($data);
	}

	protected function setDataOnCustomFields(array $data = array())
	{
		foreach ($data as $name => $value)
		{
			if (strpos($name, 'field_ft_') !== FALSE)
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

		$structure = $this->getStructure();

		// no structure yet? defer
		if ( ! $structure)
		{
			$this->_field_facades = NULL;
			return;
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

		$this->setDataOnCustomFields($this->getValues());

		foreach ($this->_field_facades as $facade)
		{
			$facade->setContentType($this->getStructure()->getContentType());
		}

		$this->_custom_fields_loaded = TRUE;
	}

	/**
	 * Turn a field into a facade for more consistent access.
	 */
	protected function addFacade($id, $info, $name_prefix = '')
	{
		$name = $name_prefix.$id;
		$format = NULL;

		if (array_key_exists('field_fmt', $info))
		{
			$format = $info['field_fmt'];
		}

		if ($this->hasProperty('field_ft_'.$id))
		{
			$format = $this->getProperty('field_ft_'.$id) ?: $format;
			$this->setProperty('field_ft_'.$id, $format);
		}

		$facade = new FieldFacade($id, $info);
		$facade->setName($name);
		$facade->setContentId($this->getId());

		if (isset($format))
		{
			$facade->setFormat($format);
		}

		$this->_field_facades[$name] = $facade;
	}

}

// EOF
