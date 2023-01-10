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
 * Keyword Filter
 */
class Keyword extends Filter
{
    public function __construct()
    {
        $this->name = 'filter_by_keyword';
        $this->placeholder = lang('keyword_filter');
        $this->list_class = 'filter-search-form';
        $this->view = '_shared/filters/keyword';
    }

    /**
     * @see Filter::render
     */
    public function render(ViewFactory $view, URL $url)
    {
        $filter = [
            'name' => $this->name,
            'value' => str_replace('"', '&quot;', strval($this->value())),
            'placeholder' => $this->placeholder
        ];

        return $view->make($this->view)->render($filter);
    }
}

// EOF
