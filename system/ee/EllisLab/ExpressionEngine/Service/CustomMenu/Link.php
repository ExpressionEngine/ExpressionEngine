<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\CustomMenu;

/**
 * Custom Menu Link
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

		if (is_a($url, 'EllisLab\ExpressionEngine\Library\CP\URL'))
		{
			$url = $url->compile();
		}
		elseif (strpos($url, '://') === FALSE && strpos($url, $base) !== 0)
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
