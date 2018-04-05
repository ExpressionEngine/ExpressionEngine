<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Toolbar;

use InvalidArgumentException;

/**
 * Toolbar Service Tool
 */
class Tool {

	protected $type;
	protected $attributes = [];
	protected $title;
	protected $url;
	protected $classes = [];


	/**
	 * Constructor: sets the tool, and injects the ToolCollection and View dependencies.
	 *
	 * @param string $type The type of tool (sets the icon: edit, view, sync, remove, etc.)
	 * @param string $title The title / text version of the tool
	 * @param string $url The URL for the tool
	 * @return self This returns a reference to itself
	 */
	public function __construct($type, $title, $url = '')
	{
		$this->type = $type;
		$this->title = $title;
		$this->url = $url;
		return $this;
	}

	/**
	 * Allows for read-only access to our protected and private properties
	 *
	 * @throws InvalidArgumentException If the named property does not exist
	 * @param string $name The name of the property
	 * @return mixed The value of the requested property
	 */
	public function __get($name)
	{
		if (property_exists($this, $name))
		{
			return $this->$name;
		}

		throw new InvalidArgumentException("No such property: '{$name}' on ".get_called_class());
	}

	public function asModal($name)
	{
		$this->classes[] = 'm-link';
		$this->classes[] = 'js-' . $this->type;
		$this->attributes['rel'] = 'modal-' . $name;
		return $this;
	}

	public function asExternal()
	{
		$this->attributes['rel'] = 'external';
		return $this;
	}

	public function asRemove()
	{
		$this->classes[] = 'remove';
		return $this;
	}

	public function addAttributes(Array $attr)
	{
		$this->attributes = array_merge($this->attributes, $attr);
		return $this;
	}

	public function withData($attr, $value)
	{
		$this->attributes['data-'.$attr] = $value;
		return $this;
	}
}
// END CLASS

// EOF
