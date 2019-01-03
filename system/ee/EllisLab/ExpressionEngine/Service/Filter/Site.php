<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Filter;

use InvalidArgumentException;
use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Service\View\ViewFactory;

/**
 * Site Filter
 *
 * This will provide the HTML for a filter that will list a set of sites as well
 * as a custom <input> element for searching for a site.
 */
class Site extends Filter {

	protected $msm_enabled = FALSE;

	/**
	 * Constructor
	 *
	 * @see Filter::$options for the format of the options array
	 *
	 * @param array $options An associative array of options
	 */
	public function __construct(array $options = array())
	{
		$this->name = 'filter_by_site';
		$this->label = 'site_filter';
		$this->placeholder = lang('filter_by_site');
		$this->options = $options;
	}

	/**
	 * Sets the $msm_enabled boolean variable to TRUE
	 *
	 * @return void
	 */
	public function enableMSM()
	{
		$this->msm_enabled = TRUE;
	}

	/**
	 * Sets the $msm_enabled boolean variable to FALSE
	 *
	 * @return void
	 */
	public function disableMSM()
	{
		$this->msm_enabled = FALSE;
	}

	/**
	 * Validation: is the value in our list of options?
	 */
	public function isValid()
	{
		// This is "valid" if MSM is Disabled
		if ( ! $this->msm_enabled)
		{
			return TRUE;
		}

		if ( ! (int) $this->value())
		{
			return FALSE;
		}

		return (array_key_exists((int) $this->value(), $this->options));
	}

	/**
	 * @see Filter::value For the parent behavior
	 *
	 * Overriding the value method to account for someone searching for a
	 * site
	 *
	 * @return int|NULL The value of the filter (NULL if it has no value)
	 */
	public function value()
	{
		$value = parent::value();

		if ( ! is_numeric($value) && ! empty($value))
		{
			$needle = strtolower($value);

			$matches = array_filter($this->options, function ($haystack) use ($needle) {
			    return(strpos(strtolower($haystack), $needle));
			});

			if ( ! empty($matches))
			{
				$value = array_shift(array_keys($matches));
			}
		}

		return $value;
	}

	/**
	 * @see Filter::render for render behavior and arguments
	 *
	 * Overrides the abstract render behavior by returning an empty string
	 * if multtiple sites are not available.
	 */
	public function render(ViewFactory $view, URL $url)
	{
		if ( ! $this->msm_enabled)
		{
			return '';
		}

		return parent::render($view, $url);
	}

}

// EOF
