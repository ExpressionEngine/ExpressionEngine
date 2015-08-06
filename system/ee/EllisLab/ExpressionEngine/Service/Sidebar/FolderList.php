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
 * ExpressionEngine FolderList Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class FolderList {

	protected $name;
	protected $items = array();
	protected $remove_url = '';
	protected $removal_key = 'id';

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function withRemoveUrl($url)
	{
		$this->remove_url = $url;
		return $this;
	}

	public function addItem($text, $url = NULL)
	{
		$item = new FolderItem($text, $url, $this->name, $this->removal_key);
		$this->items[] = $item;

		return $item;
	}

	public function render(ViewFactory $view)
	{
		$items = '';

		foreach ($this->items as $item)
		{
			$items .= $item->render($view);
		}

		return $view->make('_shared/sidebar/folder_list')
			     ->render(array(
					 'items' => $items,
					 'name'  => $this->name,
					 'remove_url' => $this->remove_url,
					 'removal_key' => $this->removal_key
				 ));
	}

}
// EOF