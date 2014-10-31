<?php
namespace EllisLab\ExpressionEngine\Service\CP\Filter;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
class Perpage extends Filter {

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
			'all' => sprintf(lang($all_lang_key), $total)
		);
		$this->default_value = 20;

		if (strtolower($this->value()) == 'all')
		{
			$this->selected_value = $total;
		}

		$this->display_value = $this->value();
	}

	public function isValid()
	{
		$value = $this->value();

		if (is_numeric($value) && $value > 0)
		{
			return TRUE;
		}

		return FALSE;
	}

}
// END CLASS

/* End of file Perpage.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/CP/Filter/Perpage.php */