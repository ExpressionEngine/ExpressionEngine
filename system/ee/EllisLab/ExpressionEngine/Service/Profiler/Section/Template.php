<?php

namespace EllisLab\ExpressionEngine\Service\Profiler\Section;

use EllisLab\ExpressionEngine\Service\Profiler\ProfilerSection;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Template Profiler Section
 *
 * @package		ExpressionEngine
 * @subpackage	Profiler\Section
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
