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
 * Template Profiler Section
 */
class Template extends ProfilerSection {

	/**
	 * @var  string  template memory
	 **/
	protected $template_memory;

	/**
	 * @var float  threshold for warnings, in seconds
	 **/
	protected $time_threshold = 0.25;

	/**
	 * @var float  threshold for warnings, in megabytes
	 **/
	protected $memory_threshold = 1.0;

	/**
	 * Get a brief text summary (used for tabs, labels, etc.)
	 *
	 * @return  string  the section summary
	 **/
	public function getSummary()
	{
		return $this->fmt_factory->make('Number', $this->template_memory)->bytes().
			' '.
			lang('profiler_'.$this->section_name);
	}

	/**
	 * Gets the view name needed to render the section
	 *
	 * @return string  the view/name
	 **/
	public function getViewName()
	{
		return 'profiler/section/template';
	}

	/**
	 * Set the section's data
	 *
	 * @return void
	 **/
	public function setData($log)
	{
		$last = end($log);
		$this->template_memory = $last['memory'];

		foreach($log as &$entry)
		{
			// convert human friendly megabytes into bytes for maths
			$entry['memory_threshold'] = $this->memory_threshold * 1048576;
			$entry['time_threshold'] = $this->time_threshold;
		}

		$this->data = array('template' => $log);
	}
}

// EOF
