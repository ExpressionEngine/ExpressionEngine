<?php

namespace EllisLab\ExpressionEngine\Service\CustomMenu;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * ExpressionEngine Custom Menu Link
 *
 * @package		ExpressionEngine
 * @subpackage	CP\CustomMenu
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Link {

	public $title;
	public $url;

	/**
	 * Create a new menu item
	 *
	 * @param String $title Text of the menu item
	 * @param Mixed $url URL string or CP/URL object
	 */
	public function __construct($title, $url)
	{
		$this->title = htmlspecialchars($title);

		$base = ee('CP/URL')->make('')->compile();

		if (strpos($url, '://') === FALSE && strpos($url, $base) !== 0)
		{
			$url = ee('CP/URL')->make($url)->compile();
		}

		$this->url = $url;
	}

	/**
	 * Is this a submenu?
	 *
	 * @return bool False
	 */
	public function isSubmenu()
	{
		return FALSE;
	}
}
