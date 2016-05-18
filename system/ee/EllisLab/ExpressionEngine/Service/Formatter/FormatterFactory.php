<?php

namespace EllisLab\ExpressionEngine\Service\Formatter;

use EllisLab\ExpressionEngine\Core\Provider;

class FormatterFactory {

	private $provider;
	private $lang;

	/**
	 *
	 */
	public function __construct(Provider $provider, $lang)
	{
		$this->provider = $provider;
		$this->lang = $lang;
	}

	/**
	 * Helper function to create a formatter object
	 *
	 * @param String $formatter_name Formatter
	 * @return Object Formatter
	 */
	public function make($formatter_name)
	{
		$formatter_class = implode('', array_map('ucfirst', explode('_', $formatter_name)));

		$class = __NAMESPACE__."\\Formats\\{$formatter_class}";

		if (class_exists($class))
		{
			return new $class($this->provider, $this->lang);
		}

		throw new \Exception("Unknown formatter: `{$formatter_name}`.");
	}
}

// EOF
