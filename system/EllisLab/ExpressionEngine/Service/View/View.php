<?php
namespace EllisLab\ExpressionEngine\Service\View;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use \EE_Loader;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine View Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class View {

	protected $path;
	protected $loader;
	protected $view;

	private $blockStack;

	public function __construct($path, EE_Loader $loader, \View $view)
	{
		$this->path = $path;
		$this->loader = $loader;
		$this->view = $view;
	}

	public function parse($path, $vars)
	{
		extract($vars);
		include($path);
	}

	public function render(array $vars)
	{
		return $this->loader->view($this->path, $vars, TRUE);
	}

	public function ee_view($view, $vars = array(), $return = FALSE)
	{
		return $this->loader->ee_view($view, $vars, $return);
	}

	public function view($view, $vars = array(), $return = FALSE)
	{
		return $this->loader->view($view, $vars, $return);
	}

	/**
	 * Allows our Views to define blocks to be used in a template/layout context
	 *
	 * @param str $name The name of the block
	 */
	public function startBlock($name)
	{
		$this->blockStack[] = array($name, FALSE);
		ob_start();
	}

	public function startOrAppendBlock($name)
	{
		$this->blockStack[] = array($name, TRUE);
		ob_start();
	}

	/**
	 * Ends the block storing the buffer on the View::blocks array based on the
	 * most recently specified name via startBlock.
	 */
	public function endBlock()
	{
		list($name, $append) = array_pop($this->blockStack);

		if ($name === NULL)
		{
			return; // @TODO Throw an error?
		}

		$buffer = '';

		if ($append && isset(ee()->view->blocks[$name]))
		{
			$buffer .= ee()->view->blocks[$name];
		}

		$buffer .= ob_get_contents();
		ob_end_clean();

		$this->view->blocks[$name] = $buffer;
	}
}
// END CLASS

/* End of file View.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/View/View.php */