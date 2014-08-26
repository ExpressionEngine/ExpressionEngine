<?php
namespace EllisLab\ExpressionEngine\Model\Site\Preferences;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Flexible Preferences
 *
 * @package		ExpressionEngine
 * @subpackage	Site\Preferences
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class FlexiblePreferences {
	protected $preferences = array();

	public function __construct($preferences = NULL)
	{
		$this->preferences = $preferences;
	}

	public function __get($name)
	{
		if ( isset ($this->preferences[$name]))
		{
			return $this->preferences[$name];
		}

		throw new \RuntimeException('Attempt to access unset preference ' . $name);
	}

	public function __set($name, $value)
	{
		$this->preferences[$name] = $value;
	}

	public function toArray()
	{
		return $this->preferences;
	}

}
