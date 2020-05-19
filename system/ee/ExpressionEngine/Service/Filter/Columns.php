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
 * Columns Filter
 */
class Columns extends Filter {

	public function __construct($columns, $channel = FALSE, $view_id = FALSE)
	{
		$this->name = 'view';
		$this->label = lang('columns_filter');
		$this->available_columns = $columns;
		$this->view_id = $view_id;

		if (! empty($channel))
		{
			$this->channel_id = $channel->channel_id;
		}
	}

	/**
	 * @see Filter::render
	 */
	public function render(ViewFactory $view, URL $url)
	{
		$available_views_result = ee('Model')->get('EntryManagerView')->filter('channel_id', (! empty($this->channel_id) ? $this->channel_id : 0))->all();

		$available_views = [];

		foreach ($available_views_result as $available_view) {
			$item_url = clone $url;
			$item_url->setQueryStringVariable('view', $available_view->view_id);

			$available_views[] = [
				'view_id' => $available_view->view_id,
				'name' => htmlentities($available_view->name, ENT_QUOTES, 'UTF-8'),
				'url' => $item_url->compile(),
			];
		}

		$selected_view = $this->view_id ? ee('Model')->get('EntryManagerView', $this->view_id)->first() : NULL;
		$selected_columns = [];

		if (! empty($selected_view))
		{
			$selected_columns = $selected_view->Columns->pluck('identifier');
		}

		$filter = array(
			'label'			=> $this->label,
			'value'			=> '',
			'available_columns' => $this->available_columns,
			'selected_columns' => $selected_columns,
			'available_views' => $available_views,
			'selected_view' => $this->view_id
		);

		return $view->make('_shared/filters/columns')->render($filter);
	}

}

// EOF
