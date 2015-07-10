<?php

namespace EllisLab\ExpressionEngine\Service\View;

use EE_Loader;
use View as LegacyView;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine ViewFactory Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ViewFactory {

	/**
	 * @var str The basepath to where views lie
	 */
	protected $basepath;

	/**
	 * @var obj An instance of EE_Loader
	 */
	protected $loader;

	/**
	 * @var obj An instance of View
	 */
	protected $view;

	/**
	 * Constructor: sets depdencies
	 *
	 * @param str $basepath The basepath to where views lie
	 * @param obj $loader An instance of EE_Loader
	 * @param obj $view An instnace of View
	 * @return void
	 */
	public function __construct($basepath, EE_Loader $loader, LegacyView $view)
	{
		$this->basepath = $basepath;
		$this->loader = $loader;
		$this->view = $view;
	}

	/**
	 * This will make and return a Service\View object
	 *
	 * @param str $path The path to the view template file (ex: '_shared/form')
	 * @return obj A EllisLab\ExpressionEngine\Service\View\View object
	 */
	public function make($path)
	{
		return new View($this->basepath.'/'.$path, $this->loader, $this->view);
	}

}
// EOF