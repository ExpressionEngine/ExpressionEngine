<?php
namespace EllisLab\ExpressionEngine\Service\Sidebar;

use EllisLab\ExpressionEngine\Service\View\View;
use EllisLab\ExpressionEngine\Library\CP\URL;

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
 * ExpressionEngine ListItem Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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

}

// EOF