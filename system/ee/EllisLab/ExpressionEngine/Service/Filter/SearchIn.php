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

use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Service\View\ViewFactory;

/**
 * Keyword Filter
 */
class SearchIn extends Filter {

	public function __construct($options, $default)
	{
		$this->name = 'search_in';
		$this->label = lang('search_in_filter');
		$this->options = $options;
		$this->default_value = $default;
	}

	/**
	 * @see Filter::render
	 */
	public function render(ViewFactory $view, URL $url)
	{
		$options = $this->prepareOptions($url);

		if (empty($options))
		{
			return;
		}

		$value = $this->display_value;
		if (is_null($value))
		{
			$value = (array_key_exists($this->value(), $this->options)) ?
				$this->options[$this->value()] :
				$this->value();
		}

		$filter = array(
			'label'			=> $this->label,
			'value'			=> $value,
			'options'		=> $options,
		);
		return $view->make('_shared/filters/searchin')->render($filter);
	}

}

// EOF
