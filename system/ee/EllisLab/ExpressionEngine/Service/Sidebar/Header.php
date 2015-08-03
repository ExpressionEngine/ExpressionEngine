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

	protected $text;
	protected $url;
	protected $button;
	protected $list = array();

	public function __construct($text, $url = NULL)
	{
		$this->text = $text;
		if ($url)
		{
			$this->withUrl($url);
		}
		return $this;
	}

	public function withUrl($url)
	{
		$this->url = $url;
		return $this;
	}

	public function hasButton($text, $url)
	{
		$this->button = array(
			'text' => $text,
			'url' => $url
		);
		return $this;
	}

	public function addBasicList()
	{
		$this->list = new BasicList();
		return $this->list;
	}

	public function addFolderList($name)
	{
		$this->list = new FolderList($name);
		return $this->list;
	}

	public function render(ViewFactory $view)
	{
		$vars = array(
			'text' => $this->text,
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