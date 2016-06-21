<?php

namespace EllisLab\ExpressionEngine\Service\CustomMenu;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.4
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Custom Submenu
 *
 * @package		ExpressionEngine
 * @subpackage	CP\CustomMenu
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Submenu extends Menu {

	public $title;
	public $addlink;
	public $placeholder;

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
