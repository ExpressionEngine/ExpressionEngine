<?php

namespace EllisLab\ExpressionEngine\Service\Profiler\Section;

use EllisLab\ExpressionEngine\Service\Profiler\ProfilerSection;
use \EE_Benchmark;

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

	/**
	 * private Benchmark object
	 *
	 * @var EE_Benchmark $bench object
	 **/
	private $bench;

	/**
	 * Set the section's data
	 *
	 * @return void
	 **/
	public function setData(EE_Benchmark $bench)
	{
		$profile = array();
		foreach ($bench->marker as $key => $val)
		{
			// We match the "end" marker so that the list ends
			// up in the order that it was defined
			if (preg_match("/(.+?)_end/i", $key, $match))
			{
				if (isset($bench->marker[$match[1].'_end']) AND isset($bench->marker[$match[1].'_start']))
				{
					$data[ucwords(str_replace(array('_', '-'), ' ', $match[1]))] = $bench->elapsed_time($match[1].'_start', $key);
				}
			}
		}

		$this->data = array('profiler_benchmark' => $data);
	}
}
