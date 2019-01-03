<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Filter;

/**
 * Custom Filter
 */
class Custom extends Filter {

	/**
	 * Constructor
	 *
	 * @see Filter\Filter::options For the format of the $options array
	 *
	 * @param string $name    The name="" attribute for this filter
	 * @param string $label   A language key to be used for the display label
	 * @param array  $options An associative array to use to build the option
	 *                        list.
	 * @return void
	 */
	public function __construct($name, $label, array $options)
	{
		$this->name = $name;
		$this->label = $label;
		$this->options = $options;
	}

	/**
	 * Sets the placeholder value for this filter
	 *
	 * @param string $placeholder The value to use for the placeholder
	 * @return self This returns a reference to itself
	 */
	public function setPlaceholder($placeholder)
	{
		$this->placeholder = $placeholder;
		return $this;
	}

	/**
	 * Sets the default value for this filter
	 *
	 * @param string $value The value to use for the default value
	 * @return self This returns a reference to itself
	 */
	public function setDefaultValue($value)
	{
		$this->default_value = $value;
		return $this;
	}

	/**
	 * Disables the custom value by setting has_custom_value to False.
	 *
	 * @see Filter::has_custom_value
	 * @return self This returns a reference to itself
	 */
	public function disableCustomValue()
	{
		$this->has_custom_value = FALSE;
		return $this;
	}

	/**
	 * Use a list filter for long lists. This cannot be used in conjunction
	 * with custom values, so it will disable them.
	 *
	 * @return self This returns a reference to itself
	 */
	public function useListFilter()
	{
		$this->has_list_filter = count($this->options) > 10;
		$this->has_custom_value = FALSE;
		return $this;
	}

	/**
	 * Checks if the selection is valid
	 *
	 * @see Filter::has_custom_value
	 * @return self This returns a reference to itself
	 */
	public function isValid()
	{
		if ($this->has_custom_value)
		{
			return TRUE;
		}

		return parent::isValid();
	}

}

// EOF
