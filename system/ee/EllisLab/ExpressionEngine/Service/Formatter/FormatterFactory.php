<?php

namespace EllisLab\ExpressionEngine\Service\Formatter;

use EE_Lang;
use EllisLab\ExpressionEngine\Core\Provider;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine FormatterFactory Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class FormatterFactory {

	/**
	 * @var object $lang EE_Lang
	 **/
	private $lang;

	/**
	 * Constructor
	 *
	 * @param object EllisLab\ExpressionEngine\Core\Provider
	 * @param object EE_Lang
	 */
	public function __construct(EE_Lang $lang)
	{
		$this->lang = $lang;
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
			return new $class($content, $this->lang);
		}

		throw new \Exception("Unknown formatter: `{$formatter_name}`.");
	}
}

// EOF
