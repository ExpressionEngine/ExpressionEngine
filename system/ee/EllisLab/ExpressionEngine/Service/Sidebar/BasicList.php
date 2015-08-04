<?php
namespace EllisLab\ExpressionEngine\Service\Sidebar;

use EllisLab\ExpressionEngine\Service\View\View;

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
 * ExpressionEngine BasicList Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class BasicList {

	protected $items = array();

	public function addItem($text, $url = NULL)
	{
		$item = new BasicItem($text, $url);
		$this->items[] = $item;

		return $item;
	}

	public function render(View $view)
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