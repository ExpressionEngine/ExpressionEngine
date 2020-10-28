<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Filter;

use ExpressionEngine\Library\CP\URL;
use ExpressionEngine\Service\View\ViewFactory;

/**
 * Entry Keyword Filter
 */
class EntryKeyword extends Filter {

	public function __construct()
	{
		$this->name = 'filter_by_entry_keyword';
		$this->placeholder = lang('keyword_filter');
		$this->list_class = 'filter-search-form';
	}

	/**
	 * @see Filter::render
	 */
	public function render(ViewFactory $view, URL $url)
	{
		$filter = [
			'name'        => $this->name,
			'value'       => str_replace('"', '&quot;', $this->value()),
			'placeholder' => $this->placeholder
		];

		return $view->make('_shared/filters/entrykeyword')->render($filter);
	}

}

// EOF
