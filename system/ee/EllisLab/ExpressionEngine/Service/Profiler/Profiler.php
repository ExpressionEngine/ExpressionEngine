<?php

namespace EllisLab\ExpressionEngine\Service\Profiler;

use \EE_Lang;
use EllisLab\ExpressionEngine\Service\View\View;

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
	 * @var the section objects to render
	 */
	protected $sections = array();

	/**
	 * @var View $view A View object for rendering this alert
	 **/
	private $view;

	/**
	 * Constructor
	 * Inject:
	 *   EE_Lang $lang for loadfile()
	 *   View $view to render the container
	 */
	public function __construct(EE_Lang $lang, View $view)
	{
		$lang->loadfile('profiler');
		$this->view = $view;
	}

	/**
	 * Adds the sections
	 *
	 * @param array		$sections	names of sections to include
	 * @param mixed		variable	add'l args are passed to the Section class
	 * @return object	this
	 **/
	public function addSection($section_name)
	{
		$args = func_get_args();
		array_shift($args);

		$section_class = implode('', array_map('ucfirst', explode('_', $section_name)));
		$class = __NAMESPACE__."\\Section\\{$section_class}";

		if ( ! class_exists($class))
		{
			throw new \Exception("Profiler section does not exist: `{$section_name}`.");
		}

		// this looks like overkill, but it enables the Section classes to receive
		// excplicit and type hinted parameters. We max out at 4 for sanity, most
		// Section classes have either 1 or 0 injectables
		switch (count($args))
		{
			case 4:
				$section = new $class($args[0], $args[1], $args[2], $args[3]);
				break;
			case 3:
				$section = new $class($args[0], $args[1], $args[2]);
				break;
			case 2:
				$section = new $class($args[0], $args[1]);
				break;
			case 1:
				$section = new $class($args[0]);
				break;
			default:
				$section = new $class;
				break;
		}

		$section->setData();
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
		foreach ($this->sections as $section)
		{
			$rendered_sections[] = $section->render();
		}

		return $this->view->render(array('sections' => $rendered_sections));
	}
}
