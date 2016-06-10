<?php

namespace EllisLab\ExpressionEngine\Service\Profiler;

use EE_Lang;
use EE_URI;
use EllisLab\ExpressionEngine\Service\View\ViewFactory;

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
	 * @var the section objects to render
	 */
	protected $sections = array();

	/**
	 * @var ViewFactory $view_factory A ViewFactory object for making and rendering views
	 **/
	private $view_factory;

	/**
	 * @var EE_URI $uri The EE_URI object
	 */
	private $uri;

	/**
	 * Constructor
	 * Inject:
	 *   EE_Lang $lang for loadfile()
	 *   ViewFactory $view_factory A ViewFactory object for making and rendering views
	 */
	public function __construct(EE_Lang $lang, ViewFactory $view_factory, EE_URI $uri)
	{
		$lang->loadfile('profiler');
		$this->view_factory = $view_factory;
		$this->uri = $uri;
	}

	/**
	 * Adds the sections
	 *
	 * @param string   $section_name  names of section to add
	 * @param mixed	   variable       add'l args are passed to the Section class
	 * @return object  this
	 **/
	public function addSection($section_name)
	{
		$args = func_get_args();
		array_shift($args);

		$section_class = implode('', array_map('ucfirst', explode('_', $section_name)));
		$class = __NAMESPACE__."\\Section\\{$section_class}";

		if ( ! class_exists($class))
		{
			// Default Section can handle any variable meant to be displayed
			// But would not know what to do with multiple arguments
			if (count($args) == 1)
			{
				$class = __NAMESPACE__."\\Section\\DefaultSection";
			}
			else
			{
				throw new \Exception("No usable Profiler Section for: `{$section_name}`.");
			}
		}

		// create the section and set its data
		$section = new $class($section_name);
		call_user_func_array(array($section, 'setData'), $args);

		$this->sections[] = $section;

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
		foreach ($this->sections as $index => $section)
		{
			$view = $this->view_factory->make($section->getViewName());
			$rendered_sections[] = $section->render($view, $index);
		}

		$view = $this->view_factory->make('profiler/container');
		return $view->render(array(
			'uri'               => ($this->uri->uri_string)
				? '/'.$this->uri->uri_string
				: lang('site_homepage'),
			'sections'          => $this->sections,
			'rendered_sections' => $rendered_sections
		));
	}
}

// EOF
