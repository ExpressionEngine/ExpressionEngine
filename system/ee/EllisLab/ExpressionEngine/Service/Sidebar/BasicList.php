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
 * Sidebar BasicList
 */
class BasicList {

	/**
	 * @var array $items Items in the list
	 */
	protected $items = array();

	/**
	 * Adds an item to this list
	 *
	 * @param string $text The text of the item
	 * @param URL|string $url A CP\URL object or string containing the
	 *   URL for the item.
	 * @return BasicItem A new BasicItem object
	 */
	public function addItem($text, $url = NULL)
	{
		$item = new BasicItem($text, $url);
		$this->items[] = $item;

		return $item;
	}

	/**
	 * Renders this list. This should not be called directly. Instead use
	 * the Sidebar's render method.
	 *
	 * @see Sidebar::render
	 * @param ViewFactory $view A ViewFactory object to use with rendering
	 * @return string The rendered HTML of the list and its items
	 */
	public function render(ViewFactory $view)
	{
		$items = '';

		foreach ($this->items as $item)
		{
			$items .= $item->render($view);
		}

		return $view->make('_shared/sidebar/basic_list')
			     ->render(array('items' => $items));
	}

}

// EOF
