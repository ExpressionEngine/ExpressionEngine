<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Sidebar;

use EllisLab\ExpressionEngine\Service\View\ViewFactory;

/**
 * Sidebar Header
 */
class ActionBar {

	/**
	 * @var array Each an array with a text and url key that defines a button
	 */
	protected $left_button;
	protected $right_button;

	/**
	 * Set left button
	 *
	 * @param string $text The text of the button
	 * @param URL|string $url A CP\URL object or string containing the
	 *   URL for the button.
	 * @param string $rel Optional value for the rel= attribute on the button
	 * @return self
	 */
	public function withLeftButton($text, $url, $rel = NULL)
	{
		$this->left_button = [
			'text' => $text,
			'url'  => $url,
			'rel'  => $rel
		];
		return $this;
	}

	/**
	 * Set right button
	 *
	 * @param string $text The text of the button
	 * @param URL|string $url A CP\URL object or string containing the
	 *   URL for the button.
	 * @param string $rel Optional value for the rel= attribute on the button
	 * @return self
	 */
	public function withRightButton($text, $url, $rel = NULL)
	{
		$this->right_button = [
			'text' => $text,
			'url'  => $url,
			'rel'  => $rel
		];
		return $this;
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
			'left_button' => $this->left_button,
			'right_button' => $this->right_button
		);

		return $view->make('_shared/sidebar/action_bar')->render($vars);
	}

}

// EOF
