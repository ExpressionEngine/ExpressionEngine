<?php

namespace EllisLab\ExpressionEngine\Service\Profiler;

use EllisLab\ExpressionEngine\Service\View\View;
use EllisLab\ExpressionEngine\Service\Formatter\FormatterFactory;

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
 * ExpressionEngine Profiler Section Interface
 *
 * Represents a Profiler Section that can be added to the profiler output.
 * Will be loaded from a profiler section string of the section's name
 * (first character lower case).
 *
 * @package		ExpressionEngine
 * @subpackage	Profiler
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
abstract class ProfilerSection {

	/**
	 * @var The profiler section data
	 * 	    typical format is: [section label] => [[key => val], ...]
	 * 	    but can differ if the section implements its own render()
	 */
	protected $data = array();

	/**
	 * @var object $fmt_factory EllisLab\ExpressionEngine\Service\Formatter\FormatterFactory
	 **/
	protected $fmt_factory;

	/**
	 * @var The section's name, should map to a localization key
	 **/
	protected $section_name;

	/**
	 * Get a brief text summary (used for tabs, labels, etc.)
	 *
	 * @return  string  the section summary
	 **/
	abstract function getSummary();

	/**
	 * Constructor
	 *
	 * @param string $section_name the section's name, should map to a localization key
	 * @param object $fmt_factory EllisLab\ExpressionEngine\Service\Formatter\FormatterFactory
	 **/
	public function __construct($section_name, FormatterFactory $fmt_factory)
	{
		$this->section_name = $section_name;
		$this->fmt_factory = $fmt_factory;
	}

	/**
	 * Gets the view name needed to render the section
	 *
	 * @return string  the view/name
	 **/
	public function getViewName()
	{
		return 'profiler/default_section';
	}

	/**
	 * Gets the section name
	 *
	 * @return string  the section name
	 **/
	public function getSectionName()
	{
		return $this->section_name;
	}

	/**
	 * Set the section's data
	 * (Implemented by extended classes)
	 *
	 * @param  array/object  key => val data to display
	 * @return void
	 **/
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * Render the section with a view
	 *
	 * @param  object  View $view object to render
	 * @return string
	 **/
	public function render(View $view, $index)
	{
		return $view->render(array('profiler_data' => $this->data, 'index' => $index));
	}
}
