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
	 * Constructor
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

	public function setDisplayValue($value)
	{
		$this->display_value = $value;
	}

	public function getDisplayValue()
	{
		if (is_null($this->display_value))
		{
			return $this->getValue();
		}

		return $this->display_value;
	}

	public function setValue($value)
	{
		$this->value = $value;
	}

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

	public function setOptions(array $options)
	{
		$this->options = $options;
	}

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