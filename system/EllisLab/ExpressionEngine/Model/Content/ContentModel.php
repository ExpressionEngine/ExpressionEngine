<?php

namespace EllisLab\ExpressionEngine\Model\Content;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Model\Content\Display\DefaultLayout;
use EllisLab\ExpressionEngine\Model\Content\Display\FieldDisplay;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutInterface;

/**
 * create: new ChannelEntry()->getForm();
 * existing: $entry->fill($data)->getForm();
 * set data: $entry->title = "Foo"; $entry->getForm();
 * mass set: $entry->set(array); $entry->getForm();
 */

abstract class ContentModel extends Model {

	protected static $_events = array(
		'afterSetCustomField'
	);

	protected $_field_facades;

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
	 * Optionally return an array of default fields. If you override
	 * this you may also want to override `populateDefaultFields()`.
	 *
	 * @return Array of field definitions
	 */
	protected function getDefaultFields()
	{
		return array();
	}

	/**
	 * Do any work needed to setup the default fields with data
	 */
	protected function populateDefaultFields()
	{
		return;
	}

	/**
	 * Check if a custom field of $name exists
	 */
	public function hasCustomField($name)
	{
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
	 * TODO This is messy. Some fields don't return their data on save()
	 * and some use it to prep the data. Date is one of the prep ones,
	 * which we'll do here, but this definitely will need work. /TODO
	 */
	public function onAfterSetCustomField($name, $value)
	{
		if ($this->hasCustomField($name))
		{
			$field = $this->getCustomField($name);

			if ($field->getType() == 'date')
			{
				$field->save();
			}
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
		foreach ($this->_field_facades as $name => $field)
		{
			if ($this->isDirty($name))
			{
				$field->save();
			}
		}

		return parent::save();
	}

	/**
	 * Slight tweak to validate to make the custom fields
	 * validate correctly.
	 */
	public function validate()
	{
		$validator = $this->getValidator();

		foreach ($this->_field_facades as $name => $facade)
		{
			$validator->defineRule("customField-{$name}", function($value) use ($facade)
			{
				return $facade->validate($value);
			});
		}

		return parent::validate();
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
	 * Custom fields coutn as a valid property
	 */
	public function hasProperty($name)
	{
		if ( ! parent::hasProperty($name))
		{
			return $this->hasCustomField($name);
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

		$facades = $this->_field_facades;

		foreach ($facades as $name => $facade)
		{
			$rules[$name] = '';

			if ($facade->isRequired())
			{
				$rules[$name] .= 'required|';
			}

			$rules[$name] .= "customField-{$name}";
		}

		return $rules;
	}

	/**
	 * Get a list of fields
	 *
	 * @return array field names
	 */
	public function getFields()
	{
		$fields = parent::getFields();

		foreach ($this->_field_facades as $field_facade)
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
			$this->_field_facades
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
			$this->populateDefaultFields();
		}
	}

	/**
	 * Populate the custom fields on fill()
	 */
	protected function fillCustomFields(array $data = array())
	{
		$this->usesCustomFields();

		foreach ($data as $name => $value)
		{
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
			$this->addFacade(
				$field->getId(),
				$field->toArray(),
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

		$this->_field_facades[$name] = $facade;
	}

}