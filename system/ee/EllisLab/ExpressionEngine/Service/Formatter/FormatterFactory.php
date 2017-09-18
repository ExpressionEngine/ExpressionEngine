<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Formatter;

use EE_Lang;
use EllisLab\ExpressionEngine\Core\Provider;

/**
 * Formatter Factory
 */
class FormatterFactory {

	/**
	 * @var object $lang EE_Lang
	 **/
	private $lang;

	/**
	 * @var boolean $intl_loaded Whether or not the intl extension is loaded
	 */
	protected $intl_loaded = FALSE;

	/**
	 * @var binary (1) Bitwise options make for intl_loaded. Can't use const until PHP 5.6
	 */
	private $OPT_INTL_LOADED = 0b00000001;

	/**
	 * Constructor
	 *
	 * @param object EllisLab\ExpressionEngine\Core\Provider
	 * @param integer bitwise-defined options
	 * @param object EE_Lang
	 */
	public function __construct(EE_Lang $lang, $options)
	{
		$this->lang = $lang;

		if ($options & $this->OPT_INTL_LOADED)
		{
			$this->intl_loaded = TRUE;
		}
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
