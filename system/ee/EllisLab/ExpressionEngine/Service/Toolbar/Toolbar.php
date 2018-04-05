<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Toolbar;

use EllisLab\ExpressionEngine\Service\Toolbar\Tool;
use EllisLab\ExpressionEngine\Service\View\View;

/**
 * Toolbar Service
 */
class Toolbar {

	/**
	 * @var array $tools An associative array of tools by type
	 */
	private $tools = [];

	/**
	 * @var View $view A view object for rendering Tools
	 */
	private $view;

	/**
	 * Constructor
	 *
	 * @param View $view A view object (for rendering Tools)
	 * @return void
	 */
	public function __construct(View $view)
	{
		$this->view = $view;
	}

	/**
	 * Allow read-access to private $tools property
	 *
	 * @throws InvalidArgumentException If the named property does not exist
	 * @return array This toolbar's tools
	 */
	public function __get($name)
	{
		if ($name == 'tools')
		{
			return $this->tools;
		}

		throw new InvalidArgumentException("No available property: '{$name}' on ".get_called_class());
	}

	/**
	 * Makes a new Toolbar of the specified type.
	 *
	 * @param string $type The type of the Toolbar (list, sidebar, etc.)
	 * @return self
	 */
	public function make($type = '')
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * Set the Type
	 *
	 * @param  string $type The type of the Toolbar (list, sidebar, etc.)
	 * @return self
	 */
	public function asType($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * Add a Tool to the toolbar
	 *
	 * @param string $type The type of tool (sets the icon: edit, view, sync, remove, etc.)
	 * @param string $title The title / text version of the tool
	 * @param string $url The URL for the tool
	 * @return EllisLab\ExpressionEngine\Service\Toolbar\Tool The Tool
	 */
	public function addTool($type, $title, $url = '')
	{
		return  $this->tools[] = new Tool($type, $title, $url);
	}

	/**
	 * Renders the Toolbar to HTML.
	 *
	 * @return string The rendered HTML of the Toolbar
	 */
	public function render()
	{
		return $this->view->render(['toolbar' => $this]);
	}
}

// EOF
