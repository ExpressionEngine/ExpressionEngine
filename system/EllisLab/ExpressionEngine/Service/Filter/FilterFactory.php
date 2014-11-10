<?php
namespace EllisLab\ExpressionEngine\Service\Filter;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Service\Filter;
use EllisLab\ExpressionEngine\Service\View\ViewFactory;
use EllisLab\ExpressionEngine\Service\DependencyInjectionContainer;

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
 * ExpressionEngine FilterFactory Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class FilterFactory {

	protected $container;
	protected $filters = array();

	public function __construct(ViewFactory $view)
	{
		$this->view = $view;
	}

	public function setDIContainer(DependencyInjectionContainer $container)
	{
		$this->container = $container;
		return $this;
	}

	public function make($name, $label, array $options)
	{
		return new Filter\Custom($name, $label, $options);
	}

	public function add($filter)
	{
		if ($filter instanceof Filter\Filter)
		{
			$this->filters[] = $filter;
			return $this;
		}

		$args = func_get_args();
		$name = array_shift($args);

		$default = "createDefault{$name}";

		if (method_exists($this, $default))
		{
			$this->filters[] = call_user_func_array(
				array($this, $default),
				$args
			);
		}
		elseif (isset($this->container))
		{
			$this->filters[] = $this->container->make($name, $args);
		}
		else
		{
			throw new \Exception('Unknown filter: ' . $name);
		}

		return $this;
	}

	/**
	 * Renames the last filter to be added
	 *
	 * @param string $name The new name="" attribute for the previous filter
	 * @return obj         Returns itself ($this)
	 */
	public function withName($name)
	{
		if (empty($this->filters))
		{
			throw new \Exception('No filters have been addded. Cannot rename a filter.');
		}

		$filter = end($this->filters);
		$filter->name = $name;
		return $this;
	}

	public function render(URL $base_url)
	{
		$url = clone $base_url;
		$url->addQueryStringVariables($this->values());

		$filters = array();

		foreach ($this->filters as $filter)
		{
			$html = $filter->render($this->view, $url);
			if ( ! empty($html))
			{
				$filters[] = $html;
			}
		}

		return $this->view->make('filters')->render(array('filters' => $filters));
	}

	public function values()
	{
		$values = array();

		foreach ($this->filters as $filter)
		{
			$values[$filter->name] = $filter->value();
		}

		return $values;
	}

	protected function createDefaultDate()
	{
		return new Filter\Date();
	}

	protected function createDefaultSite()
	{
		return new Filter\Site();
	}

	protected function createDefaultPerpage($total, $lang_key = NULL)
	{
		if ( ! isset($lang_key))
		{
			return new Filter\Perpage($total);
		}

		return new Filter\Perpage($total, $lang_key);
	}

	protected function createDefaultUsername($usernames = array())
	{
		$filter = new Filter\Username($usernames);

		if (isset($this->container))
		{
			$filter->setQuery($this->container->make('Model')->get('Member'));
		}

		return $filter;
	}

}
// END CLASS

/* End of file FilterFactory.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/Filter/FilterFactory.php */