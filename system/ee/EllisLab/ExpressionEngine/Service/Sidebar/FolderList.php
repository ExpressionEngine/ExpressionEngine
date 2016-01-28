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
 * ExpressionEngine FolderList Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class FolderList {

	/**
	 * @var string $name The name of this folder list
	 */
	protected $name;

	/**
	 * @var array $items Items in the list
	 */
	protected $items = array();

	/**
	 * @var URL|string $remove_url The URL to use as an href attribute
	 */
	protected $remove_url = '';

	/**
	 * @var string $removal_key The data attribute to use when removing an item
	 */
	protected $removal_key = 'id';

	/**
	 * @var string $no_results The text to display when the list(s) are empty.
	 */
	protected $no_results = '';

	/**
	 * @var boolean $can_reorder Whether or not the folder list can be reordered
	 */
	protected $can_reorder = FALSE;

	/**
	 * Constructor: sets the name of the list
	 *
	 * @param string $text The text of the header
	 */
	public function __construct($name)
	{
		$this->name = $name;
	}

	/**
	 * Sets the URL to use when removing an item
	 *
	 * @param URL|string $url A CP\URL object or string containing the
	 *   URL to use when removing an item.
	 * @return self This returns a reference to itself
	 */
	public function withRemoveUrl($url)
	{
		$this->remove_url = $url;
		return $this;
	}

	/**
	 * Sets the name of variable passed with the removal action
	 *
	 * @param string $key The name of the variable with
	 * @return self This returns a reference to itself
	 */
	public function withRemovalKey($key)
	{
		$this->removal_key = $key;
		return $this;
	}

	/**
	 * Sets the no results text which will display if this header's list(s) are
	 * empty.
	 *
	 * @param string $msg The text to display when the list(s) are empty.
	 * @return self This returns a reference to itself
	 */
	public function withNoResultsText($msg)
	{
		$this->no_results = $msg;
		return $this;
	}

	/**
	 * Allows the folder list to be reordered
	 *
	 * @return self This returns a reference to itself
	 */
	public function canReorder()
	{
		$this->can_reorder = TRUE;
		return $this;
	}

	/**
	 * Adds an item to this list
	 *
	 * @param string $text The text of the item
	 * @param URL|string $url An optional CP\URL object or string containing the
	 *   URL for the item.
	 * @return BasicItem A new BasicItem object
	 */
	public function addItem($text, $url = NULL)
	{
		$item = new FolderItem($text, $url, $this->name, $this->removal_key);
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

		if (empty($items) && $this->no_results)
		{
			$items = '<li class="no-results">' . $this->no_results . '</li>';
		}

		return $view->make('_shared/sidebar/folder_list')
			     ->render(array(
					 'items' => $items,
					 'name'  => $this->name,
					 'remove_url' => $this->remove_url,
					 'removal_key' => $this->removal_key,
					 'can_reorder' => $this->can_reorder
				 ));
	}

}

// EOF
