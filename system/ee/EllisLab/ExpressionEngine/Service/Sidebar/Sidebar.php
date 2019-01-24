<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Sidebar;

use EllisLab\ExpressionEngine\Service\View\ViewFactory;

/**
 * Sidebar Service
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
	 * @var FolderList $list Primary folder list for this sidebar
	 */
	protected $list;

	/**
	 * @var ActionBar $action_bar Primary action bar for this sidebar
	 */
	protected $action_bar;

	/**
	 * @var string $class Any extra classes to apply to the containing div
	 */
	protected $class;

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
	 * Creates a new Sidebar object for when the singleton won't do
	 */
	public function makeNew()
	{
		return new static($this->view);
	}

	/**
	 * Renders the sidebar
	 *
	 * @return string The rendered HTML of the sidebar
	 */
	public function render()
	{
		$output = '';

		if ( ! empty($this->list))
		{
			$output .= $this->list->render($this->view);
		}

		foreach ($this->headers as $header)
		{
			$output .= $header->render($this->view);
		}

		if ( ! empty($this->action_bar))
		{
			$output .= $this->action_bar->render($this->view);
		}

		if (empty($output))
		{
			return '';
		}

		return $this->view->make('_shared/sidebar/sidebar')
			     ->render([
					'class' => $this->class,
					'sidebar' => $output,
				]);
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

	/**
	 * Adds a folder list to the sidebar, without a header
	 *
	 * @param string $name The name of the folder list
	 * @return FolderList A new FolderList object
	 */
	public function addFolderList($name)
	{
		$this->list = new FolderList($name);
		return $this->list;
	}

	/**
	 * Adds a folder list under this header
	 *
	 * @param string $name The name of the folder list
	 * @return FolderList A new FolderList object
	 */
	public function addActionBar()
	{
		$this->action_bar = new ActionBar();
		return $this->action_bar;
	}

	/**
	 * Adds some bottom margin to this sidebar
	 *
	 * @return self
	 */
	public function addMarginBottom()
	{
		$this->class = ' mb';
		return $this;
	}
}

// EOF
