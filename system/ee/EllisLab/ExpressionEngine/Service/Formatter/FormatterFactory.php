<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Formatter;

use EE_Lang;
use EE_Session;
use EllisLab\ExpressionEngine\Core\Provider;

/**
 * Formatter Factory
 */
class FormatterFactory {

	/**
	 * @var array Any needed app config settings
	 */
	protected $config;

	/**
	 * @var object $lang EE_Lang
	 **/
	private $lang;

	/**
	 * @var int bitwise mask of options
	 */
	protected $options;

	/**
	 * @var object $session EE_Session
	 */
	protected $session;

	/**
	 * Constructor
	 *
	 * @param object EllisLab\ExpressionEngine\Core\Provider
	 * @param integer bitwise-defined options
	 * @param object EE_Lang
	 */
	public function __construct(EE_Lang $lang, EE_Session $session, $config, $options)
	{
		$this->lang = $lang;
		$this->session = $session;
		$this->config = $config;
		$this->options = $options;
	}

	/**
	 * Helper function to create a formatter object
	 *
	 * @param String $formatter_name Formatter
	 * @param mixed $content The content to be formatted
	 * @return Object Formatter
	 */
	public function make($formatter_name, $content)
	{
		$formatter_class = implode('', array_map('ucfirst', explode('_', $formatter_name)));

		$class = __NAMESPACE__."\\Formats\\{$formatter_class}";

		if (class_exists($class))
		{
			return new $class($content, $this->lang, $this->session, $this->config, $this->options);
		}

		throw new \Exception("Unknown formatter: `{$formatter_name}`.");
	}
}

// EOF
