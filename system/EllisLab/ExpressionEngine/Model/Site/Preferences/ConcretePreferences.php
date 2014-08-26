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
 * ExpressionEngine Concrete Preferences
 *
 * @package		ExpressionEngine
 * @subpackage	Site\Preferences
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ConcretePreferences
{
	public function __construct(array $preferences = NULL)
	{
		foreach($preferences as $preference => $value)
		{
			$this->{$preference} = $value;
		}
	}

	public function __get($name)
	{
		if ( ! property_exists($this, $name))
		{
			throw new \Exception('Attempt to access non-existent preference, ' . $name);
		}

		return $this->$name;
	}

	public function __set($name, $value)
	{
		if ( ! property_exists($this, $name))
		{
			throw new \Exception('Attempt to access non-existent preference, ' . $name);
		}

		$this->$name = $value;
	}

	public function toArray()
	{
		$export = array();
		foreach(get_object_vars($this) as $key => $value)
		{
			if ($key[0] != '_')
			{
				$export[$key] = $this->$key;
			}
		}
		return $export;
	}

}
