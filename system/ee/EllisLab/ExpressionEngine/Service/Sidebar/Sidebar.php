<?php
namespace EllisLab\ExpressionEngine\Service\Sidebar;

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
 * ExpressionEngine Sidebar Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Sidebar {

	/**
	 * @var array $headers The headers in this sidebar
	 */
	protected $headers = array();

	/**
	 * @var ViewFactory $view A ViewFactory object with which we will render the sidebar.
	 */
	protected $view;

	/**
	 * Constructor: sets the ViewFactory property
	 *
	 * @param ViewFactory $view A ViewFactory object to use with rendering
	 */
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

	/**
	 * Renders the sidebar
	 *
	 * @return string The rendered HTML of the sidebar
	 */
	public function render()
	{
		$output = '';

		foreach ($this->headers as $header)
		{
			$output .= $header->render($this->view);
		}

		if (empty($output))
		{
			return '';
		}

		return $this->view->make('_shared/sidebar/sidebar')
			     ->render(array('sidebar' => $output));
	}

	/**
	 * Adds a header to the sidebar
	 *
	 * @param string $text The text of the header
	 * @param URL|string $url An optional CP\URL object or string containing the
	 *   URL for the text.
	 * @return Header A new Header object.
	 */
	public function addHeader($text, $url = NULL)
	{
		$header = new Header($text, $url);
		$this->headers[] = $header;
		return $header;
	}

}

// EOF