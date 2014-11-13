<?php
namespace EllisLab\ExpressionEngine\Service\Filter;

use \InvalidArgumentException;

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
 * ExpressionEngine Site Filter Class
 *
 * This will provide the HTML for a filter that will list a set of sites as well
 * as a custom <input> element for searching for a site.
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Site extends Filter {

	protected $msm_enabled = FALSE;

	/**
	 * Constructor
	 *
	 * @see Filter::$options for the format of the options array
	 *
	 * @param bool  $msm_enabled Whether or not MSM is enabled
	 * @param array $options An associative array of options
	 */
	public function __construct($msm_enabled = NULL, array $options = array())
	{
		if ( ! is_null($msm_enabled))
		{
			$this->setMSMEnabled($msm_enabled);
		}

		$this->name = 'filter_by_site';
		$this->label = 'site';
		$this->placeholder = lang('filter_by_site');
		$this->options = $options;
	}

	/**
	 * Sets the $msm_enabled boolean variable
	 *
	 * @param bool $enabled Whether or not MSM is enabled
	 * @return void
	 */
	public function setMSMEnabled($enabled)
	{
		if ( ! is_bool($enabled))
		{
			throw new InvalidArgumentException('setmsm_enabled takes a boolean, a ' . gettype($enabled) . 'was passed.');
		}

		$this->msm_enabled = $enabled;
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

		return (array_key_exists($this->value(), $this->options));
	}

	/**
	 * @see Filter::render for render behavior.
	 *
	 * Overrides the abstract render behavior by returning an empty string
	 * if multtiple sites are not available.
	 */
	public function render()
	{
		if ( ! $this->msm_enabled)
		{
			return '';
		}

		parent::render();
	}

}
// EOF