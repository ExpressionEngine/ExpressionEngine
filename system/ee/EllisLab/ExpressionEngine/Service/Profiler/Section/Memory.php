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
 * ExpressionEngine Memory Profiler Section
 *
 * @package		ExpressionEngine
 * @subpackage	Profiler\Section
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Memory extends ProfilerSection {

	/**
	 * Set the section's data
	 *
	 * @return void
	 **/
	public function setData()
	{
		if (function_exists('memory_get_usage') && ($usage = memory_get_usage()) != '')
		{
			$data = number_format($usage).' '.lang('bytes');
		}
		else
		{
			$data = lang('profiler_no_memory_usage');
		}

		$this->data = array(lang('profiler_memory') => $data);
	}
}
