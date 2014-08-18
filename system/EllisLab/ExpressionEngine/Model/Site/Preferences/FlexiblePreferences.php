<?php
namespace EllisLab\ExpressionEngine\Model\Site\Preferences;


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
