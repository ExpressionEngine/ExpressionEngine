<?php
namespace EllisLab\ExpressionEngine\Service\Filter;

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
 * ExpressionEngine Perpage Filter Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Custom extends Filter {

	public function __construct($name, $label, array $options)
	{
		$this->name = $name;
		$this->label = $label;
		$this->options = $options;
	}

	public function setPlaceholder($placeholder)
	{
		$this->placeholder = $placeholder;
	}

	public function disableCustomValue()
	{
		$this->has_custom_value = FALSE;
	}

}
// EOF