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
 * ExpressionEngine BasicItem Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class BasicItem extends ListItem {

	/**
	 * Marks the item as a delete action
	 *
	 * @return self This returns a reference to itself
	 */
	public function asDeleteAction()
	{
		$this->class .= 'remove ';
	}

	/**
	 * Renders this item. This should not be called directly. Instead use
	 * the Sidebar's render method.
	 *
	 * @see Sidebar::render
	 * @param ViewFactory $view A ViewFactory object to use with rendering
	 * @return string The rendered HTML of the item
	 */
	public function render(ViewFactory $view)
	{
		$class = trim($this->class);

		if ($class)
		{
			$class = ' class="' . $class . '"';
		}

		$vars = array(
			'text' => $this->text,
			'url' => $this->url,
			'class' => $class
		);

		return $view->make('_shared/sidebar/basic_item')->render($vars);
	}

}
// EOF