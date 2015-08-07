<?php

namespace EllisLab\ExpressionEngine\Service\Profiler\Section;

use EllisLab\ExpressionEngine\Service\Profiler\ProfilerSection;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine DefaultSection Profiler Section
 *
 * @package		ExpressionEngine
 * @subpackage	Profiler\Section
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class DefaultSection extends ProfilerSection {

	/**
	 * Set the section's data
	 *
	 * @return void
	 **/
	public function setData($data)
	{
		if ( ! is_array($data) && ! is_object($data))
		{
			$this->data = array('profiler_'.$this->section_name => var_export($data, TRUE));
			return;
		}

		$data = (array) $data;

		if (count($data) == 0)
		{
			$prepped_data = lang('profiler_no_data');
		}
		else
		{
			$prepped_data = array();
			foreach ($data as $key => $val)
			{
				$prepped_data[$key] = htmlspecialchars(stripslashes(print_r($val, TRUE)));
			}

		}

		$this->data = array($this->section_name => $prepped_data);
	}
}
