<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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
		$this->label = lang('search_in');
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
