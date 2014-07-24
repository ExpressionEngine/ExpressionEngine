<?php
namespace EllisLab\ExpressionEngine\Model\Site\Preferences;

class ConcretePreferences
{

	public function __construct($preferences)
	{
		$this->populateFromCompressed($prerences);

	}

	public function compress($preferences)
	{
		return base64_encode(serialize($preferences));
	}

	public function decompress($preferences)
	{
		return base64_decode(unserialize($preferences));
	}

	public function getCompressed()
	{
		return $this->compress($this->toArray());
	}

	public function populateFromCompressed($preferences)
	{
		$preferences = $this->decompress($preferences);
		foreach($preferences as $pref=>$value)
		{
			$this->$pref = $value;
		}
	}

	public function __get($name)
	{
		if ( ! property_exists($this))
		{
			throw new \Exception('Attempt to access non-existent preference, ' . $name);
		}

		return $this->$name;
	}

	public function __set($name, $value)
	{
		if ( ! property_exists($this))
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
