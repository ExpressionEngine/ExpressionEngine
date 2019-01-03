<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Profiler\Section;

use EllisLab\ExpressionEngine\Service\Profiler\ProfilerSection;

/**
 * Performance Profiler Section
 */
class Performance extends ProfilerSection {

	/**
	 * @var  int  total execution time
	 **/
	protected $total_time;

	/**
	 * Get a brief text summary (used for tabs, labels, etc.)
	 *
	 * @return  string  the section summary
	 **/
	public function getSummary()
	{
		return "<b>{$this->total_time}s</b> ".lang('profiler_load');
	}

	/**
	 * Gets the view name needed to render the section
	 *
	 * @return string  the view/name
	 **/
	public function getViewName()
	{
		return 'profiler/section/list';
	}

	/**
	 * Set the section's data
	 *
	 * @return void
	 **/
	public function setData($benchmarks)
	{
		$this->total_time = end($benchmarks['benchmarks']);

		$data = array();
		if (function_exists('memory_get_usage') && ($usage = memory_get_usage()) != '')
		{
			$data[lang('profiler_memory')] = $this->fmt_factory->make('Number', $usage)->bytes().' of '.ini_get('memory_limit');
		}
		else
		{
			$data[lang('profiler_memory')] = lang('profiler_no_memory_usage');
		}

		$data[lang('profiler_query_time')] = $benchmarks['database'];

		$data = $data + $benchmarks['benchmarks'];
		$this->data = array('performance' => $data);
	}
}

// EOF
