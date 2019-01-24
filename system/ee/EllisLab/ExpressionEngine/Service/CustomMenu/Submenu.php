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
 * Custom Submenu
 */
class Submenu extends Menu {

	public $title;
	public $addlink;
	public $placeholder;
	public $view_all_link;

	private $has_add = FALSE;
	private $has_filter = FALSE;

	/**
	 * Cannot nest submenus, disable the parent function
	 *
	 * @throws Exception
	 */
	public function addSubmenu($title)
	{
		throw new \Exception("Cannot nest submenus.");
	}

	/**
	 * Has a filter textbox?
	 *
	 * @return bool Has filter
	 */
	public function hasFilter()
	{
		return $this->has_filter;
	}

	/**
	 * Has a "create/add" link?
	 *
	 * @return bool Has add link
	 */
	public function hasAddLink()
	{
		return $this->has_add;
	}

	/**
	 * Add filter box
	 *
	 * @param String $placholder Search box placeholder text
	 * @return $this
	 */
	public function withFilter($placeholder)
	{
		$this->has_filter = TRUE;
		$this->placeholder = $placeholder;
		return $this;
	}

	/**
	 * Add filter box, with a view all link
	 *
	 * @param string $placeholder Search box placeholder text
	 * @param string $view_all_link URL to use as a "View All" link
	 * @return $this
	 */
	public function withFilterLink($placeholder, $view_all_link)
	{
		$this->has_filter = TRUE;
		$this->placeholder = $placeholder;
		$this->view_all_link = $view_all_link;
		return $this;
	}

	/**
	 * Create a "create" link
	 *
	 * @param String $title Text of the add link
	 * @param Mixed $url URL string or CP/URL object
	 */
	public function withAddLink($title, $url)
	{
		$this->has_add = TRUE;
		$this->addlink = new Link($title, $url);
		return $this;
	}

	/**
	 * Is this a submenu?
	 *
	 * @return bool False
	 */
	public function isSubmenu()
	{
		return TRUE;
	}

	/**
	 * Set the submenu title. Internal method.
	 *
	 * @param String $title Set the title
	 */
	public function setTitle($title)
	{
		$this->title = htmlspecialchars($title);
		return $this;
	}
}
