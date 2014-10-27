<?php
namespace EllisLab\ExpressionEngine\Service\CP\Filter;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Filter Class
 *
 * @package		ExpressionEngine
 * @subpackage	Error
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Filter {
	public $label;
	public $name;
	public $has_custom_value = TRUE;
	public $custom_value;
	public $placeholder;
	public $attributes;
	public $default_value;

	private $display_value;
	private $raw_value;
	private $value;
	private $options;

	/**
	 * Constructor: creates a Filter object with a name and label. Defaults to
	 * having a custom value, no placeholder, no attributes, and no default
	 * values. At the time of construction it will calculate the "raw value"
	 * of the filter as it was submitted (POST then GET)
	 *
	 * @param str	$name	The name of the GET/POST variable
	 * @param str	$label	The optional lang() key to use as the label.
	 *                    	If NULL, the $name will be used as the lang() key.
	 * @param array	$options	An associative array of options for the filter
	 *                      	where they key is the variable name, i.e.
	 *                       		'10' => 'Show 10 Items'
	 */
	public function __construct($name, $label = NULL, array $options = array())
	{
		$this->name = $name;
		$this->label = (is_null($label)) ? strtolower($name) : $label;
		$this->setOptions($options);

		$this->attributes = array();
		$this->value = NULL;
		$this->default_value = NULL;

		$this->raw_value = (ee()->input->post($this->name)) ?: ee()->input->get($this->name);
	}

	/**
	 * This sets the value to display in the UI
	 *
	 * @param str	$value	The value to display
	 * @return void
	 */
	public function setDisplayValue($value)
	{
		$this->display_value = $value;
	}

	/**
	 * This determies what to display in the UI. If a display value was set
	 * we will use that. Otherwise we will use the submitted/default value.
	 *
	 * @return str	The value to use in the UI.
	 */
	public function getDisplayValue()
	{
		if (is_null($this->display_value))
		{
			return $this->getValue();
		}

		return $this->display_value;
	}

	/**
	 * Manually sets the value of the filter. This will override the GET/POST
	 * value.
	 *
	 * @param str	$value	The value of the filter to use
	 * @return void
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * This will determine any custom values that were POSTed, as well as
	 * determining the value of the filter. If we manually set the filter's
	 * value, use that. If not, use the value submitted, otherwise return the
	 * default value. If all else fails, it will return NULL
	 *
	 * @return str|NULL	The value of the filter or NULL.
	 */
	public function getValue()
	{
		if ($this->has_custom_value)
		{
			$this->custom_value = ee()->input->post($this->name);
		}

		if (is_null($this->value))
		{
			if ($this->raw_value === FALSE) {
				return $this->default_value;
			}

			return $this->raw_value;
		}

		return $this->value;
	}

	/**
	 * Assigns an associative array of options to the filter
	 *
	 * @param array	$options	An associative array of options for the filter
	 *                      	where they key is the variable name, i.e.
	 *                       		'10' => 'Show 10 Items'
	 * @return void
	 */
	public function setOptions(array $options)
	{
		$this->options = $options;
	}

	/**
	 * Compiles URLs for all the options
	 *
	 * @param obj	$base_url A CP/URL object that serves as the base of the URLs
	 * @return array	An associative array of the options where the key is a
	 *               	URL and the value is the label. i.e.
	 * 		'http://index/admin.php?cp/foo&filter_by_bar=2' => 'Baz'
	 */
	public function getOptions(URL $base_url)
	{
		$options = array();
		foreach ($this->options as $show => $label)
		{
			$url = clone $base_url;
			$url->setQueryStringVariable($this->name, $show);
			$options[$url->compile()] = $label;
		}
		return $options;
	}
}
// END CLASS

/* End of file Filter.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/CP/Filter/Filter.php */