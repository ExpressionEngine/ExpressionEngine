<?php
namespace EllisLab\ExpressionEngine\Model\Site\Preferences;

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
