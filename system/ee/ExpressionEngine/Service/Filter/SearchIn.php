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
class SearchIn extends Filter
{
    public function __construct($options, $default)
    {
        $this->name = 'search_in';
        $this->label = lang('search_titles_only');
        $this->options = $options;
        $this->default_value = $default;
    }

    /**
     * @see Filter::render
     */
    public function render(ViewFactory $view, URL $url)
    {
        $options = $this->prepareOptions($url);

        if (empty($options)) {
            return;
        }

        $value = $this->display_value;
        if (is_null($value)) {
            $value = (array_key_exists($this->value(), $this->options)) ?
                $this->options[$this->value()] :
                $this->value();
        }

        $filter = array(
            'label' => $this->label,
            'value' => $value,
            'options' => $options,
        );

        return $view->make('_shared/filters/searchin')->render($filter);
    }
}

// EOF
