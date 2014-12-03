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
 * This will provide the HTML for a filter that will list a set of "<<number>>
 * results" options, a custom <input> element to specify a custom perpage number,
 * and a "All <<total>> results" option.
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Perpage extends Filter {

	/**
	 * Initializes our Perpage filter
	 *
	 * @param  int $total The total number of items available
	 * @param  string $lang_key The optional lang key to use for the "All
	 *                          <<$total>> items" option
	 * @return void
	 */
	public function __construct($total, $all_lang_key = 'all_items')
	{
		$total = (int) $total;

		$this->name = 'perpage';
		$this->label = 'show';
		$this->placeholder = lang('custom_limit');
		$this->options = array(
			'25'  => '25 '.lang('results'),
			'50'  => '50 '.lang('results'),
			'75'  => '75 '.lang('results'),
			'100' => '100 '.lang('results'),
			'150' => '150 '.lang('results'),
			$total => sprintf(lang($all_lang_key), $total)
		);
		$this->default_value = 20;

		$this->display_value = $this->value();
	}

	/**
	 * @see Filter::render() for the logic/behavior
	 * Overriding the parent value to coerce the value into an int
	 * and if we did not get one we will fall back and use the default value.
	 *
	 * @return int The number of items per page
	 */
	public function value()
	{
		$value = parent::value();

		if ( ! (int) $value)
		{
			$value = $this->default_value;
		}

		return (int) $value;
	}

	/**
	 * Validation:
	 *   - if value is a number, then it is valid
	 *   - otherwise it is invalid
	 */
	public function isValid()
	{
		$value = $this->value();

		if (is_int($value) && $value > 0)
		{
			return TRUE;
		}

		return FALSE;
	}

}
// EOF