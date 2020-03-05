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
 * Viewtype Filter
 *
 * This will provide the HTML for a filter that will display a set of buttons
 * to change the view mode of the current result set into either list, thumbnail,
 * or a hybrid mini-thumbnail / list format.
 */
class Viewtype extends Filter {

	protected $total_threshold = 1000;
	protected $confirm_show_all = FALSE;

	/**
	 * Initializes our Perpage filter
	 *
	 * @todo inject ee()->cp (for ee()->cp->add_js_script)
	 *
	 * @param  int $total The total number of items available
	 * @param  string $lang_key The optional lang key to use for the "All
	 *                          <<$total>> items" option
	 * @param  bool $is_modal Is this Perpage filter in/for a modal?
	 * @return void
	 */
	public function __construct(array $options = array())
	{
		$this->name = 'viewtype';
		$this->label = 'viewtype_filter';
		$this->placeholder = 'view type';
		$this->options = $options;

		$this->options = array(
			'table'  => lang('viewtype_list'),
			'thumb'  => lang('viewtype_thumb'),
		);

		$this->default_value = 'table';
	}

	/**
	 * @see Filter::render() for the logic/behavior
	 * Overriding the parent value to coerce the value into an int
	 * and if we did not get one we will fall back and use the default value.
	 *
	 * @return int The number of items per page
	 */
	public function value()
	{
		$value = parent::value();

		if ( empty($value))
		{
			$value = $this->default_value;
		}

		return $value;
	}

	/**
	 * Validation
	 */
	public function isValid()
	{
		$value = $this->value();

		if (in_array($value, ['table', 'thumb']))
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @see Filter::render
	 *
	 * Overriding the abstract class's render method in order to render a custom
	 * perpage view which includes a modal for show-all
	 */
	public function render(ViewFactory $view, URL $url)
	{
		$original_options = $this->options;
		$options = $this->prepareOptions($url);
		$new_options = [];

		foreach ($options as $url => $label) {
			$new_options[] = [
				'url' => $url,
				'label' => $label
			];
		}

		// Merge the url and label with the viewtype so that all three options can be accessed in the view
		$options = array_combine(array_keys($original_options), $new_options);

		$filter = [
			'name'        => $this->name,
			'value'       => str_replace('"', '&quot;', $this->value()),
			'placeholder' => $this->placeholder,
			'options'     => $options
		];

		return $view->make('_shared/filters/viewtype')->render($filter);
	}
}

// EOF
