<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Filter;

use ExpressionEngine\Library\CP\URL;
use ExpressionEngine\Service\View\ViewFactory;

/**
 * Views Filter
 */
class EntryManagerViews extends Filter
{
    public function __construct($view_id = null, $channel = null)
    {
        $this->name = 'views';
        $this->label = lang('views_filter');
        $this->view_id = $view_id;

        if (!empty($channel)) {
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

        $filter = array(
            'label' => $this->label,
            'value' => '',
            'available_views' => $available_views,
            'selected_view' => $this->view_id,
            'create_url' => ee('CP/URL')->make('publish/views/create')->compile(),
            'edit_url' => ee('CP/URL')->make('publish/views/edit/' . $this->view_id)->compile(),
        );

        return $view->make('_shared/filters/views')->render($filter);
    }
}

// EOF
