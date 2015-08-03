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

	public function asDeleteAction()
	{
		$this->class .= 'remove ';
	}

	public function render(ViewFactory $view)
	{
		$class = trim($this->class);

		if ($class)
		{
			$class = ' class=" . $class . "';
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