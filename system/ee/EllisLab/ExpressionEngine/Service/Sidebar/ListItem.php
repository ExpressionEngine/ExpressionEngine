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

use EllisLab\ExpressionEngine\Service\View\View;
use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * Siebar List Item
 */
abstract class ListItem {

	/**
	 * @var string $text The text of the item
	 */
	protected $text;

	/**
	 * @var URL|string $url The URL to use as an href attribute
	 */
	protected $url;

	/**
	 * @var bool $url_is_external Flag for external URLs
	 */
	protected $url_is_external = FALSE;

	/**
	 * @var array $class The class of the item
	 */
	protected $class = array();

	/**
	 * Constructor: sets the text and url properties of the item
	 *
	 * @param string $text The text of the item
	 * @param URL|string $url An optional CP\URL object or string containing the
	 *   URL for the text.
	 */
	public function __construct($text, $url = NULL)
	{
		$this->text = $text;
		if ($url)
		{
			$this->withUrl($url);
		}
	}

	/**
	 * Sets the URL property of the item
	 *
	 * @param URL|string $url A CP\URL object or string containing the
	 *   URL for the item.
	 * @return self This returns a reference to itself
	 */
	public function withUrl($url)
	{
		$this->url = $url;
		if ($url instanceof URL && $url->isTheRequestedURI())
		{
			$this->isActive();
		}
		return $this;
	}

	/**
	 * Sets the $url_is_external property
	 *
	 * @param bool $external (optional) TRUE if it is external, FALSE if not
	 * @return self This returns a reference to itself
	 */
	public function urlIsExternal($external = TRUE)
	{
		$this->url_is_external = $external;
		return $this;
	}

	/**
	 * Adds a class to the class array
	 *
	 * @return self This returns a reference to itself
	 */
	protected function addClass($class)
	{
		$this->class[$class] = TRUE;
		return $this;
	}

	/**
	 * Removes a class to the class array
	 *
	 * @return self This returns a reference to itself
	 */
	protected function removeClass($class)
	{
		if (isset($this->class[$class]))
		{
			unset($this->class[$class]);
		}
		return $this;
	}

	/**
	 * Converts the class array into a space delimited string.
	 *
	 * @return string All the classes separated by spaces.
	 */
	protected function getClass()
	{
		return implode(' ', array_keys($this->class));
	}

	/**
	 * Marks the item as active
	 *
	 * @return self This returns a reference to itself
	 */
	public function isActive()
	{
		return $this->addClass('act');
	}

	/**
	 * Marks the item as inactive
	 *
	 * @return self This returns a reference to itself
	 */
	public function isInactive()
	{
		return $this->removeClass('act');
	}

	/**
	 * Marks the item as selected
	 *
	 * @return self This returns a reference to itself
	 */
	public function isSelected()
	{
		return $this->addClass('selected');
	}

	/**
	 * Marks the item as not selected
	 *
	 * @return self This returns a reference to itself
	 */
	public function isDeselected()
	{
		return $this->removeClass('selected');
	}
}

// EOF
