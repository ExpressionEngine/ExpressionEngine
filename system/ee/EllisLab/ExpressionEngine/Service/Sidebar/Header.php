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
 * ExpressionEngine Header Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Header {

	/**
	 * @var string $text The text of the header
	 */
	protected $text;

	/**
	 * @var string $class The class of the header
	 */
	protected $class = '';

	/**
	 * @var URL|string $url The URL to use as an href attribute
	 */
	protected $url;

	/**
	 * @var array $button An array with a text and url key that defines a button
	 */
	protected $button;

	/**
	 * @var array $list Lists under this header
	 */
	protected $list = array();

	/**
	 * Constructor: sets the text and url properties of the header
	 *
	 * @param string $text The text of the header
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
	 * Sets the URL property of the header
	 *
	 * @param URL|string $url A CP\URL object or string containing the
	 *   URL for the header.
	 * @return self This returns a reference to itself
	 */
	public function withUrl($url)
	{
		$this->url = $url;
		return $this;
	}

	/**
	 * Marks the header as active
	 *
	 * @return self This returns a reference to itself
	 */
	public function isActive()
	{
		$this->class .= 'act ';
		return $this;
	}

	/**
	 * Sets the button property of the header
	 *
	 * @param string $text The text of the button
	 * @param URL|string $url A CP\URL object or string containing the
	 *   URL for the button.
	 * @return self This returns a reference to itself
	 */
	public function withButton($text, $url)
	{
		$this->button = array(
			'text' => $text,
			'url' => $url
		);
		return $this;
	}

	/**
	 * Adds a basic list under this header
	 *
	 * @return BasicList A new BasicList object
	 */
	public function addBasicList()
	{
		$this->list = new BasicList();
		return $this->list;
	}

	/**
	 * Adds a folder list under this header
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
	 * Renders this header. This should not be called directly. Instead use
	 * the Sidebar's render method.
	 *
	 * @see Sidebar::render
	 * @param ViewFactory $view A ViewFactory object to use with rendering
	 * @return string The rendered HTML of the header and its lists
	 */
	public function render(ViewFactory $view)
	{
		$vars = array(
			'text' => $this->text,
			'class' => $this->class,
			'url' => $this->url,
			'button' => $this->button
		);

		$output = $view->make('_shared/sidebar/header')->render($vars);

		if ( ! empty($this->list))
		{
			$output .= $this->list->render($view);
		}

		return $output;
	}

}
// EOF