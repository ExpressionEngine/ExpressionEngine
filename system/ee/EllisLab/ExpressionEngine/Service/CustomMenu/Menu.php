<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\CustomMenu;

/**
 * Custom Menu
 */
class Menu {

	private $items = array();

	/**
	 * Add a new menu item
	 *
	 * @param String $title Text of the menu item
	 * @param Mixed $url URL string or CP/URL object
	 */
	public function addItem($title, $url)
	{
		$this->items[] = new Link($title, $url);
		return $this;
	}

	/**
	 * Create a new menu item
	 *
	 * @param String $title Text of the dropdown
	 * @return Submenu object
	 */
	public function addSubmenu($title)
	{
		$sub = new Submenu();
		$sub->setTitle($title);

		$this->items[] = $sub;

		return $sub;
	}

	/**
	 * Is the menu empty?
	 *
	 * @return bool Is empty?
	 */
	public function hasItems()
	{
		return ! empty($this->items);
	}


	/**
	 * Get all items in the menu
	 *
	 * @return array of Link|Submenu Objects
	 */
	public function getItems()
	{
		return $this->items;
	}
}
