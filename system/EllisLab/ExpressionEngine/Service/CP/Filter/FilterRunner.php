<?php
namespace EllisLab\ExpressionEngine\Service\CP\Filter;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\URL;

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
 * ExpressionEngine FilterRunner Class
 *
 * @package		ExpressionEngine
 * @subpackage	Error
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class FilterRunner {
	public $filters;

	private $url;
	private $parameters;

	/**
	 * Constructor
	 */
	public function __construct(URL $url, array $filters = array())
	{
		$this->url = $url;
		$this->filters = $filters;

		$this->parameters = NULL;
	}

	public function getParameters()
	{
		if (is_null($this->parameters))
		{
			$this->getFilterValues();
		}

		return $this->parameters;
	}

	public function getUrl()
	{
		$this->getFilterValues();
		$url = clone $this->url;
		foreach ($this->parameters as $key => $value)
		{
			$url->setQueryStringVariable($key, $value);
		}
		return $url;
	}

	public function render()
	{
		if (empty($this->filters))
		{
			return '';
		}

		$url = $this->getUrl();
		$filters = array();

		foreach ($this->filters as $filter)
		{
			if (get_class($filter) != 'EllisLab\ExpressionEngine\Service\CP\Filter\Filter')
			{
				continue;
			}

			$filters[] = array(
				'label'			=> $filter->label,
				'name'			=> $filter->name,
				'value'			=> $filter->getDisplayValue(),
				'custom_value'	=> $filter->custom_value,
				'placeholder'	=> $filter->placeholder,
				'options'		=> $filter->getOptions($url),
				'attributes'	=> $filter->attributes
			);
		}

		return ee()->view->render('_shared/filters', array('filters' => $filters), TRUE);
	}

	private function getFilterValues()
	{
		$this->parameters = array();
		foreach ($this->filters as $filter)
		{
			if (get_class($filter) != 'EllisLab\ExpressionEngine\Service\CP\Filter\Filter')
			{
				continue;
			}

			$value = $filter->getValue();

			if ($value)
			{
				$this->parameters[$filter->name] = $value;
			}
		}
	}
}
// END CLASS

/* End of file FilterRunner.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/CP/Filter/FilterRunner.php */