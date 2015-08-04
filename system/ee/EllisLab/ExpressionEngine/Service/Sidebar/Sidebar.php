<?php
namespace EllisLab\ExpressionEngine\Service\Sidebar;

use EllisLab\ExpressionEngine\Service\View\ViewFactory;

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
 * ExpressionEngine Sidebar Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Sidebar {

	protected $headers = array();
	protected $view;

	public function __construct(ViewFactory $view)
	{
		$this->view = $view;
	}

	/**
	 * Syntactic sugar ¯\_(ツ)_/¯
	 */
	public function make()
	{
		return $this;
	}

	public function render()
	{
		$output = '';

		foreach ($this->headers as $header)
		{
			$output .= $header->render($this->view);
		}

		return $this->view->make('_shared/sidebar/sidebar')
			     ->render(array('sidebar' => $output));
	}

	public function addHeader($text, $url = NULL)
	{
		$header = new Header($text, $url);
		$this->headers[] = $header;
		return $header;
	}

}
// EOF