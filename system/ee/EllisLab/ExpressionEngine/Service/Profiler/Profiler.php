<?php

namespace EllisLab\ExpressionEngine\Service\Profiler;

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
 * ExpressionEngine Profiler
 *
 * @package		ExpressionEngine
 * @subpackage	Profiler
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Profiler {

	/**
	 * @var the sections to include
	 */
	protected $sections = array();

	/**
	 * Constructor
	 */
	public function __construct(array $sections = array())
	{
		$this->setSections($sections);
	}

	/**
	 * Sets the sections to be output
	 *
	 * @param array		$sections	names of sections to include
	 * @return object	this
	 **/
	public function setSections($sections)
	{
		$this->sections = $sections;
		return $this;
	}

	/**
	 * Render the Profiler
	 *
	 * @return string	rendered Profiler view
	 **/
	public function render()
	{
		$rendered_sections = array();

		// send back alllll the data with a view file
		foreach ($this->sections as $section)
		{
			$object = $this->newProfilerSection($section);
			$object->setData();
			$rendered_sections[] = $object->render();
		}

		return ee('View')->make('profiler/container')->render(array('sections' => $rendered_sections));
	}

	/**
	 * Helper function to create a profiler section object
	 *
	 * @param String $section_name Profiler section
	 * @return Object ProfilerSection
	 */
	protected function newProfilerSection($section_name)
	{
		$section_class = implode('', array_map('ucfirst', explode('_', $section_name)));

		$class = __NAMESPACE__."\\Section\\{$section_class}";

		if (class_exists($class))
		{
			return new $class;
		}

		throw new \Exception("Profiler section does not exist: `{$section_name}`.");
	}
}
