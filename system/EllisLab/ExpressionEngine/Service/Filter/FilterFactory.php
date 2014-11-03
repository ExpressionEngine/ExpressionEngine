<?php
namespace EllisLab\ExpressionEngine\Service\Filter;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Service\Filter;
use EllisLab\ExpressionEngine\Service\View\ViewFactory;

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

	protected $filters = array();

	public function __construct(ViewFactory $view)
	{
		$this->view = $view;
	}

	public function add($name)
	{
		// @TODO use an AliasService
		switch($name)
		{
			case 'Date':
				$this->filters[] = new Filter\Date();
				break;

			case 'Perpage':
				if (func_num_args() > 2)
				{
					$this->filters[] = new Filter\Perpage(func_get_arg(1), func_get_arg(2));
				}
				else
				{
					$this->filters[] = new Filter\Perpage(func_get_arg(1));
				}
				break;

			case 'Site':
				$this->filters[] = new Filter\Site();
				break;

			case 'Username':
				$this->filters[] = new Filter\Username();
				break;

		}
		return $this;
	}

	public function render(URL $base_url)
	{
		$url = clone $base_url;
		$url->addQueryStringVariables($this->values());

		$filters = array();

		foreach ($this->filters as $filter)
		{
			$filters[] = $filter->render($this->view, $url);
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

}
// END CLASS

/* End of file Date.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/Filter/Date.php */