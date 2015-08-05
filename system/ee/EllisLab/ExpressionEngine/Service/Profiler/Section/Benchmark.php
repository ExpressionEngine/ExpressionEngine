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
 * ExpressionEngine Benchmark Profiler Section
 *
 * @package		ExpressionEngine
 * @subpackage	Profiler\Section
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Benchmark extends ProfilerSection {

	public function setData()
	{
		$profile = array();
		foreach (ee()->benchmark->marker as $key => $val)
		{
			// We match the "end" marker so that the list ends
			// up in the order that it was defined
			if (preg_match("/(.+?)_end/i", $key, $match))
			{
				if (isset(ee()->benchmark->marker[$match[1].'_end']) AND isset(ee()->benchmark->marker[$match[1].'_start']))
				{
					$data[ucwords(str_replace(array('_', '-'), ' ', $match[1]))] = ee()->benchmark->elapsed_time($match[1].'_start', $key);
				}
			}
		}

		$this->data = array(lang('profiler_benchmark') => $data);
	}
}
